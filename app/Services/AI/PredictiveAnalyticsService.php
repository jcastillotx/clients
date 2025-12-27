<?php

namespace App\Services\AI;

use App\Models\AiInsightReport;
use App\Models\AnomalyAlert;
use App\Models\Client;
use App\Models\ClientHealthSnapshot;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Request as ServiceRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PredictiveAnalyticsService
{
    public function __construct(protected AIProviderManager $providers)
    {
    }

    /**
     * Forecast revenue for next N months.
     *
     * @param  int|string  $timeframe  e.g. 3,6,12 or '3m','6m','12m'
     * @return array<string,mixed>
     */
    public function forecastRevenue(int|string $timeframe = 6, array $options = []): array
    {
        $months = $this->normalizeMonths($timeframe, 6);
        $historyMonths = (int) ($options['history_months'] ?? 24);

        $series = $this->monthlyRevenueSeries($historyMonths);
        $forecast = $this->seasonalForecast($series, $months);

        $pipeline = $this->pipelineSignal($months);
        $forecastWithPipeline = $this->applyPipelineBoost($forecast, $pipeline);

        return [
            'timeframe_months' => $months,
            'history_months' => $historyMonths,
            'series' => $series,
            'pipeline' => $pipeline,
            'forecast' => $forecastWithPipeline,
        ];
    }

    /**
     * Predict client churn probability and retention actions.
     *
     * @return array<string,mixed>
     */
    public function predictClientChurn(Client $client, array $options = []): array
    {
        $client->loadMissing('user');

        $now = now();

        $lastRequestAt = ServiceRequest::query()
            ->where('client_id', $client->id)
            ->max('created_at');

        $lastPaymentAt = Payment::query()
            ->where('client_id', $client->id)
            ->where('status', 'succeeded')
            ->max('processed_at');

        $openOverdueInvoices = Invoice::query()
            ->where('client_id', $client->id)
            ->whereIn('status', ['sent', 'overdue'])
            ->whereDate('due_date', '<', $now->toDateString())
            ->count();

        $requests90 = ServiceRequest::query()
            ->where('client_id', $client->id)
            ->where('created_at', '>=', $now->copy()->subDays(90))
            ->count();

        $requestsPrev90 = ServiceRequest::query()
            ->where('client_id', $client->id)
            ->whereBetween('created_at', [$now->copy()->subDays(180), $now->copy()->subDays(90)])
            ->count();

        $daysSinceRequest = $lastRequestAt ? Carbon::parse($lastRequestAt)->diffInDays($now) : 365;
        $daysSincePayment = $lastPaymentAt ? Carbon::parse($lastPaymentAt)->diffInDays($now) : 365;

        // Heuristic risk score (0..1)
        $risk = 0.0;
        $risk += min(0.35, $daysSinceRequest / 365 * 0.35);
        $risk += min(0.25, $daysSincePayment / 365 * 0.25);
        $risk += min(0.25, $openOverdueInvoices * 0.08);

        // Trend drop in request frequency
        if ($requestsPrev90 > 0) {
            $drop = max(0.0, 1.0 - ($requests90 / $requestsPrev90));
            $risk += min(0.20, $drop * 0.20);
        } elseif ($requests90 === 0) {
            $risk += 0.10;
        }

        $risk = max(0.0, min(1.0, $risk));
        $level = $risk >= 0.66 ? 'high' : ($risk >= 0.33 ? 'medium' : 'low');

        $actions = $this->retentionActions($client, $level, [
            'days_since_request' => $daysSinceRequest,
            'overdue_invoices' => $openOverdueInvoices,
            'requests_90' => $requests90,
            'requests_prev_90' => $requestsPrev90,
        ]);

        return [
            'client_id' => $client->id,
            'churn_probability' => $risk,
            'risk_level' => $level,
            'features' => [
                'days_since_last_request' => $daysSinceRequest,
                'days_since_last_payment' => $daysSincePayment,
                'open_overdue_invoices' => $openOverdueInvoices,
                'requests_last_90_days' => $requests90,
                'requests_prev_90_days' => $requestsPrev90,
            ],
            'suggested_actions' => $actions,
        ];
    }

    /**
     * Generate a 0-100 client health score + persist snapshot + alert on significant drop.
     *
     * @return array<string,mixed>
     */
    public function generateClientHealthScore(Client $client, array $options = []): array
    {
        $churn = $this->predictClientChurn($client);
        $risk = (float) ($churn['churn_probability'] ?? 0);

        $paidLast90 = Payment::query()
            ->where('client_id', $client->id)
            ->where('status', 'succeeded')
            ->where('processed_at', '>=', now()->subDays(90))
            ->sum('amount');

        $openAmount = Invoice::query()
            ->where('client_id', $client->id)
            ->whereIn('status', ['sent', 'overdue'])
            ->sum('amount');

        $score = 100;
        $score -= (int) round($risk * 50);
        $score -= min(25, (int) round($openAmount > 0 ? 10 : 0));
        if (strtolower((string) $client->tier) === 'premium') $score += 5;
        if (strtolower((string) $client->tier) === 'enterprise') $score += 8;
        if ($paidLast90 > 0) $score += 5;

        $score = max(0, min(100, (int) $score));
        $level = $score < 45 ? 'high' : ($score < 70 ? 'medium' : 'low');

        $breakdown = [
            'churn_probability' => $risk,
            'open_invoice_amount' => (float) $openAmount,
            'paid_last_90_days' => (float) $paidLast90,
            'tier' => $client->tier,
        ];

        $snapshot = ClientHealthSnapshot::create([
            'client_id' => $client->id,
            'score' => $score,
            'churn_probability' => $risk,
            'risk_level' => $level,
            'breakdown' => $breakdown,
            'computed_at' => now(),
        ]);

        $prev = ClientHealthSnapshot::query()
            ->where('client_id', $client->id)
            ->where('id', '<', $snapshot->id)
            ->orderByDesc('id')
            ->first();

        if ($prev && ((int) $prev->score - $score) >= 15) {
            AnomalyAlert::create([
                'type' => 'health_drop',
                'severity' => 'warning',
                'client_id' => $client->id,
                'title' => 'Client health score dropped',
                'message' => "Health score dropped from {$prev->score} to {$score}.",
                'data' => ['previous' => (int) $prev->score, 'current' => $score],
            ]);
        }

        return [
            'client_id' => $client->id,
            'score' => $score,
            'risk_level' => $level,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Forecast request volume per month for next N months.
     *
     * @return array<string,mixed>
     */
    public function forecastRequestVolume(int|string $timeframe = 6, array $options = []): array
    {
        $months = $this->normalizeMonths($timeframe, 6);
        $historyMonths = (int) ($options['history_months'] ?? 24);

        $series = $this->monthlyRequestCountSeries($historyMonths);
        $forecast = $this->seasonalForecast($series, $months);

        return [
            'timeframe_months' => $months,
            'history_months' => $historyMonths,
            'series' => $series,
            'forecast' => $forecast,
        ];
    }

    /**
     * Identify workload bottlenecks and suggest staffing actions.
     *
     * @return array<string,mixed>
     */
    public function optimizeResourceAllocation(array $options = []): array
    {
        $open = ServiceRequest::query()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->get(['id', 'assigned_to', 'estimated_hours', 'priority', 'due_date', 'status']);

        $byUser = [];
        $unassigned = 0;
        foreach ($open as $r) {
            $hours = (float) ($r->estimated_hours ?? 0);
            if (!$r->assigned_to) {
                $unassigned++;
                continue;
            }
            $uid = (int) $r->assigned_to;
            $byUser[$uid]['count'] = ($byUser[$uid]['count'] ?? 0) + 1;
            $byUser[$uid]['hours'] = ($byUser[$uid]['hours'] ?? 0) + $hours;
        }

        $staff = User::query()->role(['super_admin', 'admin', 'staff'])->get(['id', 'name']);
        $rows = [];
        foreach ($staff as $u) {
            $rows[] = [
                'user_id' => (int) $u->id,
                'name' => (string) $u->name,
                'open_requests' => (int) ($byUser[$u->id]['count'] ?? 0),
                'estimated_hours' => (float) ($byUser[$u->id]['hours'] ?? 0),
            ];
        }

        usort($rows, fn ($a, $b) => ($b['estimated_hours'] <=> $a['estimated_hours']));
        $bottlenecks = array_values(array_filter($rows, fn ($r) => $r['estimated_hours'] >= 40));

        $suggestions = [];
        if ($unassigned > 0) {
            $suggestions[] = "There are {$unassigned} unassigned open requests. Assign or triage them to prevent delays.";
        }
        if (!empty($bottlenecks)) {
            $suggestions[] = 'Bottleneck detected: at least one staff member has >= 40 estimated hours of open work.';
        }
        if ($unassigned > 10 || count($bottlenecks) >= 2) {
            $suggestions[] = 'Consider temporary capacity (contractor) or hiring if this workload persists for 2–4 weeks.';
        }

        return [
            'unassigned_open_requests' => $unassigned,
            'staff_workload' => $rows,
            'bottlenecks' => $bottlenecks,
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Generate a natural language report (best-effort AI) from payload.
     */
    public function generateNarrative(string $prompt, array $payload, array $options = []): string
    {
        $preferred = (string) ($options['provider'] ?? 'openai');
        $model = $options['model'] ?? null;

        $ctx = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $messages = [
            ['role' => 'system', 'content' => 'You are an analytics assistant. Write a concise executive summary with insights and recommendations.'],
            ['role' => 'user', 'content' => $prompt . "\n\nData JSON:\n" . $ctx],
        ];

        try {
            $res = $this->providers->withFallback($preferred, function ($provider) use ($messages, $model) {
                return $provider->chat($messages, [
                    'task_type' => 'analytics_report',
                    'timeout' => 120,
                    'model' => $model,
                ]);
            }, 'analytics_report');

            return trim((string) ($res['text'] ?? ''));
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Competitive intelligence via Perplexity (research-backed).
     */
    public function competitiveIntelligence(string $topic, array $options = []): AiInsightReport
    {
        $preferred = (string) ($options['provider'] ?? 'perplexity');
        $messages = [
            ['role' => 'system', 'content' => 'You are a research analyst. Provide a market analysis with citations/sources if available.'],
            ['role' => 'user', 'content' => "Research topic:\n{$topic}\n\nReturn a concise report with bullet insights and pricing/service observations."],
        ];

        $payload = [];
        $narrative = '';
        $providerUsed = null;
        $modelUsed = null;
        $cost = null;

        try {
            $res = $this->providers->withFallback($preferred, function ($provider) use ($messages) {
                return $provider->chat($messages, [
                    'task_type' => 'market_analysis',
                    'timeout' => 180,
                ]);
            }, 'market_analysis');

            $narrative = (string) ($res['text'] ?? '');
            $payload = [
                'topic' => $topic,
                'sources' => $res['sources'] ?? $res['citations'] ?? null,
            ];
            $providerUsed = $res['provider'] ?? null;
            $modelUsed = $res['model'] ?? null;
            $cost = $res['estimated_cost'] ?? null;
        } catch (\Throwable $e) {
            $narrative = 'Market analysis failed: ' . $e->getMessage();
            $payload = ['topic' => $topic];
        }

        return AiInsightReport::create([
            'kind' => 'market_analysis',
            'payload' => $payload,
            'narrative' => $narrative,
            'provider_used' => $providerUsed,
            'model_used' => $modelUsed,
            'cost' => $cost,
        ]);
    }

    /**
     * Run anomaly detection and store alerts (best-effort heuristics).
     *
     * @return array<int, AnomalyAlert>
     */
    public function detectAnomalies(array $options = []): array
    {
        $alerts = [];
        $now = now();

        // Unusually high request volume from one client in last 7 days.
        $spiky = ServiceRequest::query()
            ->select('client_id', DB::raw('count(*) as cnt'))
            ->whereNotNull('client_id')
            ->where('created_at', '>=', $now->copy()->subDays(7))
            ->groupBy('client_id')
            ->having('cnt', '>=', 8)
            ->get();

        foreach ($spiky as $row) {
            $alerts[] = AnomalyAlert::create([
                'type' => 'volume_spike',
                'severity' => 'warning',
                'client_id' => (int) $row->client_id,
                'title' => 'High request volume',
                'message' => 'Client created ' . (int) $row->cnt . ' requests in the last 7 days.',
                'data' => ['count_7d' => (int) $row->cnt],
            ]);
        }

        // Payment delays: normally prompt clients now have overdue invoices.
        $late = Invoice::query()
            ->whereIn('status', ['sent', 'overdue'])
            ->whereDate('due_date', '<', $now->toDateString())
            ->limit(50)
            ->get(['id', 'client_id', 'invoice_number', 'due_date', 'amount']);

        foreach ($late as $inv) {
            $alerts[] = AnomalyAlert::create([
                'type' => 'payment_delay',
                'severity' => 'warning',
                'client_id' => (int) $inv->client_id,
                'title' => 'Invoice payment delay',
                'message' => "Invoice {$inv->invoice_number} is overdue.",
                'data' => ['invoice_id' => (int) $inv->id, 'amount' => (float) $inv->amount, 'due_date' => $inv->due_date?->toDateString()],
            ]);
        }

        return $alerts;
    }

    // -----------------------------
    // Internal helpers
    // -----------------------------

    protected function normalizeMonths(int|string $timeframe, int $default): int
    {
        if (is_int($timeframe)) return max(1, min(24, $timeframe));
        $t = strtolower(trim((string) $timeframe));
        $t = rtrim($t, 'm');
        if (ctype_digit($t)) return max(1, min(24, (int) $t));
        return $default;
    }

    /**
     * @return array<int, array{month:string, value:float}>
     */
    protected function monthlyRevenueSeries(int $monthsBack): array
    {
        $start = now()->startOfMonth()->subMonths($monthsBack - 1);

        $dateExpr = $this->coalesceDateExpr('processed_at', 'created_at');
        $monthExpr = $this->monthKeyExpr($dateExpr);

        $rows = Payment::query()
            ->where('status', 'succeeded')
            ->whereRaw("{$dateExpr} >= ?", [$start])
            ->selectRaw("{$monthExpr} as m, SUM(amount) as v")
            ->groupBy('m')
            ->orderBy('m')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[(string) $r->m] = (float) $r->v;
        }

        $out = [];
        $cur = $start->copy();
        for ($i = 0; $i < $monthsBack; $i++) {
            $key = $cur->format('Y-m-01');
            $out[] = ['month' => $key, 'value' => (float) ($map[$key] ?? 0.0)];
            $cur->addMonth();
        }
        return $out;
    }

    /**
     * @return array<int, array{month:string, value:float}>
     */
    protected function monthlyRequestCountSeries(int $monthsBack): array
    {
        $start = now()->startOfMonth()->subMonths($monthsBack - 1);

        $dateExpr = $this->coalesceDateExpr('created_at', 'created_at');
        $monthExpr = $this->monthKeyExpr($dateExpr);

        $rows = ServiceRequest::query()
            ->whereRaw("{$dateExpr} >= ?", [$start])
            ->selectRaw("{$monthExpr} as m, COUNT(*) as v")
            ->groupBy('m')
            ->orderBy('m')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[(string) $r->m] = (float) $r->v;
        }

        $out = [];
        $cur = $start->copy();
        for ($i = 0; $i < $monthsBack; $i++) {
            $key = $cur->format('Y-m-01');
            $out[] = ['month' => $key, 'value' => (float) ($map[$key] ?? 0.0)];
            $cur->addMonth();
        }
        return $out;
    }

    /**
     * Simple seasonal forecast:
     * - seasonality index by month-of-year from history
     * - trend from last 6 months slope
     *
     * @param array<int, array{month:string,value:float}> $series
     * @return array<int, array{month:string, predicted:float, ci80_low:float, ci80_high:float}>
     */
    protected function seasonalForecast(array $series, int $monthsAhead): array
    {
        $vals = array_map(fn ($r) => (float) $r['value'], $series);
        $overallAvg = count($vals) ? array_sum($vals) / count($vals) : 0.0;

        $byMonth = array_fill(1, 12, []);
        foreach ($series as $r) {
            $m = (int) substr($r['month'], 5, 2);
            $byMonth[$m][] = (float) $r['value'];
        }
        $seasonIdx = [];
        for ($m = 1; $m <= 12; $m++) {
            $avg = count($byMonth[$m]) ? array_sum($byMonth[$m]) / count($byMonth[$m]) : $overallAvg;
            $seasonIdx[$m] = $overallAvg > 0 ? ($avg / $overallAvg) : 1.0;
        }

        // Trend slope from last 6 points
        $n = min(6, count($series));
        $slope = 0.0;
        if ($n >= 2) {
            $last = array_slice($series, -$n);
            $xs = range(0, $n - 1);
            $ys = array_map(fn ($r) => (float) $r['value'], $last);
            $xbar = array_sum($xs) / $n;
            $ybar = array_sum($ys) / $n;
            $num = 0.0;
            $den = 0.0;
            for ($i = 0; $i < $n; $i++) {
                $num += ($xs[$i] - $xbar) * ($ys[$i] - $ybar);
                $den += ($xs[$i] - $xbar) * ($xs[$i] - $xbar);
            }
            $slope = $den > 0 ? ($num / $den) : 0.0;
        }

        $lastVal = count($series) ? (float) $series[count($series) - 1]['value'] : 0.0;

        // Residual-based CI (80%)
        $residuals = [];
        foreach ($series as $r) {
            $m = (int) substr($r['month'], 5, 2);
            $base = $overallAvg * ($seasonIdx[$m] ?? 1.0);
            $residuals[] = (float) $r['value'] - $base;
        }
        $std = $this->stddev($residuals);
        $ci = 1.28 * $std; // approx 80%

        $out = [];
        $cur = Carbon::createFromFormat('Y-m-d', (string) $series[count($series) - 1]['month'])->startOfMonth()->addMonth();
        for ($i = 0; $i < $monthsAhead; $i++) {
            $m = (int) $cur->format('m');
            $pred = max(0.0, ($lastVal + $slope * ($i + 1)) * ($seasonIdx[$m] ?? 1.0));
            $out[] = [
                'month' => $cur->format('Y-m-01'),
                'predicted' => $pred,
                'ci80_low' => max(0.0, $pred - $ci),
                'ci80_high' => max(0.0, $pred + $ci),
            ];
            $cur->addMonth();
        }
        return $out;
    }

    protected function stddev(array $values): float
    {
        $n = count($values);
        if ($n < 2) return 0.0;
        $mean = array_sum($values) / $n;
        $v = 0.0;
        foreach ($values as $x) {
            $v += ($x - $mean) * ($x - $mean);
        }
        return sqrt($v / ($n - 1));
    }

    /**
     * @return array<string,mixed>
     */
    protected function pipelineSignal(int $monthsAhead): array
    {
        $end = now()->copy()->addMonths($monthsAhead)->endOfMonth()->toDateString();
        $openInvoices = Invoice::query()
            ->whereIn('status', ['sent', 'overdue'])
            ->whereDate('due_date', '<=', $end)
            ->sum('amount');

        $openRequests = ServiceRequest::query()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->sum(DB::raw('COALESCE(estimated_cost, 0)'));

        return [
            'open_invoices_due_within_window' => (float) $openInvoices,
            'open_requests_estimated_cost' => (float) $openRequests,
        ];
    }

    /**
     * @param array<int, array{month:string,predicted:float,ci80_low:float,ci80_high:float}> $forecast
     */
    protected function applyPipelineBoost(array $forecast, array $pipeline): array
    {
        $openInvoices = (float) ($pipeline['open_invoices_due_within_window'] ?? 0);
        if ($openInvoices <= 0) return $forecast;

        // Spread a conservative portion across the window.
        $boost = ($openInvoices * 0.35) / max(1, count($forecast));
        foreach ($forecast as &$f) {
            $f['predicted'] += $boost;
            $f['ci80_low'] += $boost * 0.6;
            $f['ci80_high'] += $boost * 1.0;
        }
        unset($f);
        return $forecast;
    }

    /**
     * @return array<int,string>
     */
    protected function retentionActions(Client $client, string $level, array $signals): array
    {
        $actions = [];
        if ($level === 'high') {
            $actions[] = 'Schedule a check-in call with the client within 48 hours.';
            $actions[] = 'Review outstanding invoices and offer a clear payment plan if needed.';
            $actions[] = 'Send a short progress/status update with next steps and timeline.';
        } elseif ($level === 'medium') {
            $actions[] = 'Send a proactive “how’s everything going?” message and propose a small next win.';
            $actions[] = 'Offer a quarterly planning call to align priorities.';
        } else {
            $actions[] = 'Keep engagement steady: share monthly outcomes and upcoming opportunities.';
        }

        if (!empty($signals['overdue_invoices'])) {
            $actions[] = 'Address overdue billing promptly and confirm any blockers on their side.';
        }

        if (($signals['days_since_request'] ?? 0) > 90) {
            $actions[] = 'Suggest a lightweight maintenance/optimization package to restart momentum.';
        }

        return array_values(array_unique($actions));
    }

    protected function dbDriver(): string
    {
        try {
            return (string) DB::connection()->getDriverName();
        } catch (\Throwable) {
            return 'unknown';
        }
    }

    protected function coalesceDateExpr(string $primaryColumn, string $fallbackColumn): string
    {
        $driver = $this->dbDriver();
        $p = $primaryColumn;
        $f = $fallbackColumn;

        return match ($driver) {
            'pgsql' => "COALESCE({$p}, {$f})",
            default => "COALESCE({$p}, {$f})",
        };
    }

    protected function monthKeyExpr(string $dateExpr): string
    {
        $driver = $this->dbDriver();

        return match ($driver) {
            'sqlite' => "strftime('%Y-%m-01', {$dateExpr})",
            'pgsql' => "to_char(date_trunc('month', {$dateExpr}), 'YYYY-MM-01')",
            default => "DATE_FORMAT({$dateExpr}, '%Y-%m-01')", // mysql/mariadb
        };
    }
}

