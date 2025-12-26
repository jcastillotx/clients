<?php

namespace App\Services\AdminReports;

use App\Models\Client;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Request as ServiceRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ReportDataService
{
    /**
     * Build report payload (charts + tables) for a given category.
     *
     * @return array{meta: array, charts: array, tables: array}
     */
    public function build(string $category, array $params): array
    {
        [$start, $end] = $this->dateRange($params);
        $granularity = Arr::get($params, 'granularity', 'month');

        return match ($category) {
            'financial' => $this->financial($start, $end, $granularity),
            'clients' => $this->clients($start, $end),
            'requests' => $this->requests($start, $end),
            'performance' => $this->performance($start, $end),
            'storage' => $this->storage($start, $end),
            default => [
                'meta' => ['category' => $category, 'start' => $start->toDateString(), 'end' => $end->toDateString()],
                'charts' => [],
                'tables' => [],
            ],
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function dateRange(array $params): array
    {
        $start = Arr::get($params, 'start_date');
        $end = Arr::get($params, 'end_date');

        if ($start && $end) {
            return [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()];
        }

        return [now()->subMonths(11)->startOfMonth(), now()->endOfMonth()];
    }

    protected function financial(Carbon $start, Carbon $end, string $granularity): array
    {
        // Revenue trend (paid invoices)
        [$periodExpr, $periodLabelExpr] = match ($granularity) {
            'year' => ["DATE_FORMAT(paid_at, '%Y')", "DATE_FORMAT(paid_at, '%Y')"],
            'quarter' => [
                "CONCAT(YEAR(paid_at), '-Q', QUARTER(paid_at))",
                "CONCAT(YEAR(paid_at), '-Q', QUARTER(paid_at))",
            ],
            default => ["DATE_FORMAT(paid_at, '%Y-%m')", "DATE_FORMAT(paid_at, '%b %Y')"],
        };

        $revenueTrend = Invoice::query()
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw("$periodExpr as period_key, $periodLabelExpr as period_label, SUM(amount) as revenue")
            ->groupBy('period_key', 'period_label')
            ->orderBy('period_key')
            ->get()
            ->map(fn ($r) => ['period' => $r->period_label, 'revenue' => (float) $r->revenue])
            ->all();

        $revenueByTier = Invoice::query()
            ->join('clients', 'clients.id', '=', 'invoices.client_id')
            ->where('invoices.status', 'paid')
            ->whereNotNull('invoices.paid_at')
            ->whereBetween('invoices.paid_at', [$start, $end])
            ->groupBy('clients.tier')
            ->orderBy('clients.tier')
            ->selectRaw('clients.tier as tier, SUM(invoices.amount) as revenue')
            ->get()
            ->map(fn ($r) => ['tier' => (string) $r->tier, 'revenue' => (float) $r->revenue])
            ->all();

        $revenueByServiceType = Invoice::query()
            ->leftJoin('requests', 'requests.id', '=', 'invoices.request_id')
            ->where('invoices.status', 'paid')
            ->whereNotNull('invoices.paid_at')
            ->whereBetween('invoices.paid_at', [$start, $end])
            ->groupBy(DB::raw("COALESCE(requests.type, 'unknown')"))
            ->orderBy(DB::raw("COALESCE(requests.type, 'unknown')"))
            ->selectRaw("COALESCE(requests.type, 'unknown') as service_type, SUM(invoices.amount) as revenue")
            ->get()
            ->map(fn ($r) => ['service_type' => (string) $r->service_type, 'revenue' => (float) $r->revenue])
            ->all();

        $paymentMethods = Payment::query()
            ->where('status', 'succeeded')
            ->whereBetween('processed_at', [$start, $end])
            ->groupBy('payment_method')
            ->orderBy('payment_method')
            ->selectRaw('payment_method, SUM(amount) as total')
            ->get()
            ->map(fn ($r) => ['payment_method' => (string) $r->payment_method, 'total' => (float) $r->total])
            ->all();

        // Outstanding receivables (unpaid invoices minus successful payments)
        $paymentsByInvoice = Payment::query()
            ->where('status', 'succeeded')
            ->groupBy('invoice_id')
            ->selectRaw('invoice_id, SUM(amount) as paid_total');

        $receivables = Invoice::query()
            ->leftJoinSub($paymentsByInvoice, 'p', fn ($join) => $join->on('p.invoice_id', '=', 'invoices.id'))
            ->whereIn('invoices.status', ['sent', 'overdue'])
            ->selectRaw('SUM(GREATEST(0, invoices.amount - COALESCE(p.paid_total, 0))) as outstanding')
            ->value('outstanding');

        $receivables = (float) ($receivables ?? 0);

        // Invoice aging buckets (unpaid)
        $unpaid = Invoice::query()
            ->with('client:id,company_name')
            ->whereIn('status', ['sent', 'overdue'])
            ->get()
            ->map(function (Invoice $i) {
                $daysPastDue = $i->due_date ? max(0, now()->startOfDay()->diffInDays($i->due_date->startOfDay(), false) * -1) : 0;
                $bucket = match (true) {
                    $daysPastDue <= 30 => '0-30',
                    $daysPastDue <= 60 => '31-60',
                    $daysPastDue <= 90 => '61-90',
                    default => '90+',
                };

                return [
                    'invoice_number' => $i->invoice_number,
                    'client' => $i->client?->company_name,
                    'due_date' => optional($i->due_date)->toDateString(),
                    'amount' => (float) $i->amount,
                    'status' => $i->status,
                    'bucket' => $bucket,
                ];
            });

        $agingBuckets = collect(['0-30', '31-60', '61-90', '90+'])->map(function (string $b) use ($unpaid) {
            return [
                'bucket' => $b,
                'count' => $unpaid->where('bucket', $b)->count(),
                'amount' => (float) $unpaid->where('bucket', $b)->sum('amount'),
            ];
        })->all();

        $agingByClient = $unpaid
            ->groupBy('client')
            ->map(function ($rows, $client) {
                return [
                    'client' => $client ?: '(unknown)',
                    'invoices' => $rows->count(),
                    'total_unpaid' => (float) $rows->sum('amount'),
                    '0_30' => (float) $rows->where('bucket', '0-30')->sum('amount'),
                    '31_60' => (float) $rows->where('bucket', '31-60')->sum('amount'),
                    '61_90' => (float) $rows->where('bucket', '61-90')->sum('amount'),
                    '90_plus' => (float) $rows->where('bucket', '90+')->sum('amount'),
                ];
            })
            ->sortByDesc('total_unpaid')
            ->take(15)
            ->values()
            ->all();

        // P&L (best-effort based on available fields)
        $totalRevenue = (float) Invoice::query()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount');

        $taxesCollected = (float) Invoice::query()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->sum('tax_amount');

        $discounts = (float) Invoice::query()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->sum('discount');

        $estimatedCosts = (float) ServiceRequest::query()
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$start, $end])
            ->sum('estimated_cost');

        $profit = $totalRevenue - $estimatedCosts;

        return [
            'meta' => [
                'category' => 'financial',
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'granularity' => $granularity,
            ],
            'charts' => [
                'revenueTrend' => $revenueTrend,
                'revenueByTier' => $revenueByTier,
                'revenueByServiceType' => $revenueByServiceType,
                'paymentMethods' => $paymentMethods,
                'invoiceAging' => $agingBuckets,
            ],
            'tables' => [
                'P&L Summary' => [[
                    'total_revenue' => $totalRevenue,
                    'estimated_costs' => $estimatedCosts,
                    'profit' => $profit,
                    'taxes_collected' => $taxesCollected,
                    'discounts' => $discounts,
                    'outstanding_receivables' => $receivables,
                ]],
                'Invoice Aging (by Client)' => $agingByClient,
                'Unpaid Invoices (sample)' => $unpaid->sortByDesc('amount')->take(25)->values()->all(),
            ],
        ];
    }

    protected function clients(Carbon $start, Carbon $end): array
    {
        $acquisition = Client::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as period_key, DATE_FORMAT(created_at, '%b %Y') as period_label, COUNT(*) as new_clients")
            ->groupBy('period_key', 'period_label')
            ->orderBy('period_key')
            ->get()
            ->map(fn ($r) => ['period' => $r->period_label, 'new_clients' => (int) $r->new_clients])
            ->all();

        $byTier = Client::query()
            ->selectRaw('tier, COUNT(*) as count')
            ->groupBy('tier')
            ->orderBy('tier')
            ->get()
            ->map(fn ($r) => ['tier' => (string) $r->tier, 'count' => (int) $r->count])
            ->all();

        $byStatus = Client::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(fn ($r) => ['status' => (string) $r->status, 'count' => (int) $r->count])
            ->all();

        // LTV (paid invoices all-time)
        $ltv = Invoice::query()
            ->join('clients', 'clients.id', '=', 'invoices.client_id')
            ->where('invoices.status', 'paid')
            ->selectRaw('clients.id, clients.company_name, clients.tier, SUM(invoices.amount) as lifetime_value')
            ->groupBy('clients.id', 'clients.company_name', 'clients.tier')
            ->orderByDesc('lifetime_value')
            ->limit(25)
            ->get()
            ->map(fn ($r) => [
                'client_id' => (int) $r->id,
                'company_name' => (string) $r->company_name,
                'tier' => (string) $r->tier,
                'lifetime_value' => (float) $r->lifetime_value,
            ])
            ->all();

        // Most active clients (requests + revenue) in range
        $mostRequests = ServiceRequest::query()
            ->join('clients', 'clients.id', '=', 'requests.client_id')
            ->whereBetween('requests.created_at', [$start, $end])
            ->selectRaw('clients.id, clients.company_name, COUNT(requests.id) as requests_count')
            ->groupBy('clients.id', 'clients.company_name')
            ->orderByDesc('requests_count')
            ->limit(15)
            ->get()
            ->map(fn ($r) => [
                'client_id' => (int) $r->id,
                'company_name' => (string) $r->company_name,
                'requests_count' => (int) $r->requests_count,
            ])
            ->all();

        $mostRevenue = Invoice::query()
            ->join('clients', 'clients.id', '=', 'invoices.client_id')
            ->where('invoices.status', 'paid')
            ->whereBetween('invoices.paid_at', [$start, $end])
            ->selectRaw('clients.id, clients.company_name, clients.tier, SUM(invoices.amount) as revenue')
            ->groupBy('clients.id', 'clients.company_name', 'clients.tier')
            ->orderByDesc('revenue')
            ->limit(15)
            ->get()
            ->map(fn ($r) => [
                'client_id' => (int) $r->id,
                'company_name' => (string) $r->company_name,
                'tier' => (string) $r->tier,
                'revenue' => (float) $r->revenue,
            ])
            ->all();

        // Retention (best-effort): activity in previous period vs current (requests created or invoices paid)
        $days = max(7, $start->diffInDays($end) + 1);
        $prevStart = (clone $start)->subDays($days);
        $prevEnd = (clone $start)->subDay()->endOfDay();

        $activePrev = $this->activeClientIds($prevStart, $prevEnd);
        $activeCurr = $this->activeClientIds($start, $end);

        $retained = array_values(array_intersect($activePrev, $activeCurr));
        $retentionRate = count($activePrev) > 0 ? (count($retained) / count($activePrev)) : null;

        // Churn risk: active clients with no activity in last 60 days
        $inactiveSince = now()->subDays(60);
        $activeRecent = $this->activeClientIds($inactiveSince, now());
        $churnRisk = Client::query()
            ->where('status', 'active')
            ->whereNotIn('id', $activeRecent)
            ->select(['id', 'company_name', 'tier', 'status', 'created_at'])
            ->orderBy('created_at')
            ->limit(25)
            ->get()
            ->map(fn (Client $c) => [
                'client_id' => $c->id,
                'company_name' => $c->company_name,
                'tier' => $c->tier,
                'status' => $c->status,
                'created_at' => $c->created_at?->toDateString(),
                'risk_reason' => 'No activity in last 60 days',
            ])
            ->all();

        return [
            'meta' => [
                'category' => 'clients',
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'charts' => [
                'acquisition' => $acquisition,
                'clientsByTier' => $byTier,
                'clientsByStatus' => $byStatus,
                'retention' => [[
                    'active_previous' => count($activePrev),
                    'active_current' => count($activeCurr),
                    'retained' => count($retained),
                    'retention_rate' => $retentionRate,
                ]],
            ],
            'tables' => [
                'Client Retention (best-effort)' => [[
                    'previous_period_start' => $prevStart->toDateString(),
                    'previous_period_end' => $prevEnd->toDateString(),
                    'current_period_start' => $start->toDateString(),
                    'current_period_end' => $end->toDateString(),
                    'active_previous' => count($activePrev),
                    'active_current' => count($activeCurr),
                    'retained' => count($retained),
                    'retention_rate' => $retentionRate,
                ]],
                'Top Clients by Lifetime Value' => $ltv,
                'Most Active Clients (by Requests)' => $mostRequests,
                'Top Clients (by Revenue)' => $mostRevenue,
                'Churn Risk (inactive clients)' => $churnRisk,
            ],
        ];
    }

    protected function requests(Carbon $start, Carbon $end): array
    {
        $byType = ServiceRequest::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => ['type' => (string) $r->type, 'count' => (int) $r->count])
            ->all();

        $byStatus = ServiceRequest::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => ['status' => (string) $r->status, 'count' => (int) $r->count])
            ->all();

        $byPriority = ServiceRequest::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => ['priority' => (string) $r->priority, 'count' => (int) $r->count])
            ->all();

        // Average completion time (hours) by type for completed requests
        $avgCompletion = ServiceRequest::query()
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$start, $end])
            ->selectRaw("type, AVG(TIMESTAMPDIFF(MINUTE, COALESCE(started_at, created_at), completed_at)) as avg_minutes, COUNT(*) as completed_count")
            ->groupBy('type')
            ->orderByDesc('completed_count')
            ->get()
            ->map(fn ($r) => [
                'type' => (string) $r->type,
                'completed_count' => (int) $r->completed_count,
                'avg_hours' => round(((float) $r->avg_minutes) / 60, 2),
            ])
            ->all();

        // Staff productivity (completed requests per staff)
        $staffProductivity = ServiceRequest::query()
            ->join('users', 'users.id', '=', 'requests.assigned_to')
            ->whereNull('users.client_id')
            ->whereNotNull('requests.completed_at')
            ->whereBetween('requests.completed_at', [$start, $end])
            ->selectRaw('users.id, users.name, COUNT(requests.id) as completed, AVG(TIMESTAMPDIFF(MINUTE, requests.created_at, requests.completed_at)) as avg_resolution_minutes')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('completed')
            ->limit(25)
            ->get()
            ->map(fn ($r) => [
                'user_id' => (int) $r->id,
                'name' => (string) $r->name,
                'completed' => (int) $r->completed,
                'avg_resolution_hours' => round(((float) $r->avg_resolution_minutes) / 60, 2),
            ])
            ->all();

        // Bottlenecks: open requests by status + average age
        $openByStatus = ServiceRequest::query()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->selectRaw('status, COUNT(*) as count, AVG(TIMESTAMPDIFF(HOUR, created_at, NOW())) as avg_age_hours')
            ->groupBy('status')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => [
                'status' => (string) $r->status,
                'count' => (int) $r->count,
                'avg_age_hours' => round((float) $r->avg_age_hours, 1),
            ])
            ->all();

        // SLA compliance: completed_by_due_date + overdue open
        $slaCompleted = ServiceRequest::query()
            ->whereNotNull('due_date')
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$start, $end])
            ->selectRaw("SUM(CASE WHEN completed_at <= CONCAT(due_date, ' 23:59:59') THEN 1 ELSE 0 END) as met,
                        SUM(CASE WHEN completed_at > CONCAT(due_date, ' 23:59:59') THEN 1 ELSE 0 END) as missed,
                        COUNT(*) as total")
            ->first();

        $slaOverdueOpen = ServiceRequest::query()
            ->whereNotNull('due_date')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where('due_date', '<', now()->toDateString())
            ->count();

        $slaTotal = (int) ($slaCompleted?->total ?? 0);
        $slaMet = (int) ($slaCompleted?->met ?? 0);
        $slaMissed = (int) ($slaCompleted?->missed ?? 0);
        $slaCompliance = $slaTotal > 0 ? ($slaMet / $slaTotal) : null;

        return [
            'meta' => [
                'category' => 'requests',
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'charts' => [
                'byType' => $byType,
                'byStatus' => $byStatus,
                'byPriority' => $byPriority,
                'avgCompletionByType' => $avgCompletion,
                'sla' => [[
                    'met' => $slaMet,
                    'missed' => $slaMissed,
                    'total' => $slaTotal,
                    'compliance' => $slaCompliance,
                    'overdue_open' => (int) $slaOverdueOpen,
                ]],
            ],
            'tables' => [
                'Avg Completion Time by Type (hours)' => $avgCompletion,
                'Staff Productivity' => $staffProductivity,
                'Request Bottlenecks (open)' => $openByStatus,
                'SLA Compliance Summary' => [[
                    'completed_with_due_date' => $slaTotal,
                    'met_sla' => $slaMet,
                    'missed_sla' => $slaMissed,
                    'sla_compliance' => $slaCompliance,
                    'overdue_open_requests' => (int) $slaOverdueOpen,
                ]],
            ],
        ];
    }

    protected function performance(Carbon $start, Carbon $end): array
    {
        // Response time: created_at -> started_at (best-effort)
        $response = ServiceRequest::query()
            ->whereNotNull('started_at')
            ->whereBetween('created_at', [$start, $end])
            ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, created_at, started_at)'));

        $resolution = ServiceRequest::query()
            ->whereNotNull('completed_at')
            ->whereBetween('created_at', [$start, $end])
            ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, created_at, completed_at)'));

        $workload = User::query()
            ->whereNull('client_id')
            ->leftJoin('requests', function ($join) {
                $join->on('requests.assigned_to', '=', 'users.id')
                    ->whereNotIn('requests.status', ['completed', 'cancelled']);
            })
            ->groupBy('users.id', 'users.name')
            ->selectRaw('users.id, users.name, COUNT(requests.id) as open_assigned')
            ->orderByDesc('open_assigned')
            ->limit(25)
            ->get()
            ->map(fn ($r) => ['user_id' => (int) $r->id, 'name' => (string) $r->name, 'open_assigned' => (int) $r->open_assigned])
            ->all();

        $monthly = ServiceRequest::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as period_key, DATE_FORMAT(created_at, '%b %Y') as period_label,
                        COUNT(*) as created_count,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count")
            ->groupBy('period_key', 'period_label')
            ->orderBy('period_key')
            ->get()
            ->map(fn ($r) => [
                'period' => $r->period_label,
                'created' => (int) $r->created_count,
                'completed' => (int) $r->completed_count,
            ])
            ->all();

        return [
            'meta' => [
                'category' => 'performance',
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'charts' => [
                'responseTime' => [[
                    'avg_response_hours' => $response !== null ? round(((float) $response) / 60, 2) : null,
                ]],
                'resolutionTime' => [[
                    'avg_resolution_hours' => $resolution !== null ? round(((float) $resolution) / 60, 2) : null,
                ]],
                'workload' => $workload,
                'monthly' => $monthly,
            ],
            'tables' => [
                'Performance Summary' => [[
                    'avg_response_hours' => $response !== null ? round(((float) $response) / 60, 2) : null,
                    'avg_resolution_hours' => $resolution !== null ? round(((float) $resolution) / 60, 2) : null,
                    'client_satisfaction' => 'Not tracked (no satisfaction fields yet)',
                ]],
                'Staff Workload Distribution (open assigned)' => $workload,
                'Monthly Trends' => $monthly,
            ],
        ];
    }

    protected function storage(Carbon $start, Carbon $end): array
    {
        $usageByClient = Document::query()
            ->join('clients', 'clients.id', '=', 'documents.client_id')
            ->selectRaw('clients.id, clients.company_name, SUM(documents.file_size) as bytes, COUNT(documents.id) as files')
            ->groupBy('clients.id', 'clients.company_name')
            ->orderByDesc('bytes')
            ->limit(25)
            ->get()
            ->map(fn ($r) => [
                'client_id' => (int) $r->id,
                'company_name' => (string) $r->company_name,
                'files' => (int) $r->files,
                'bytes' => (int) $r->bytes,
            ])
            ->all();

        $fileTypes = Document::query()
            ->selectRaw("LOWER(SUBSTRING_INDEX(original_filename, '.', -1)) as ext, COUNT(*) as files, SUM(file_size) as bytes")
            ->groupBy('ext')
            ->orderByDesc('bytes')
            ->limit(15)
            ->get()
            ->map(fn ($r) => [
                'ext' => (string) ($r->ext ?: 'unknown'),
                'files' => (int) $r->files,
                'bytes' => (int) $r->bytes,
            ])
            ->all();

        $largeFiles = Document::query()
            ->with('client:id,company_name')
            ->where('file_size', '>=', 50 * 1024 * 1024)
            ->orderByDesc('file_size')
            ->limit(25)
            ->get()
            ->map(fn (Document $d) => [
                'id' => $d->id,
                'client' => $d->client?->company_name,
                'original_filename' => $d->original_filename,
                'bytes' => (int) $d->file_size,
                'uploaded_at' => $d->created_at?->toDateTimeString(),
            ])
            ->all();

        // Provider usage isn't stored; present a placeholder summary.
        $provider = [[
            'provider' => config('filesystems.default', 'local'),
            'notes' => 'Provider tracking not stored on documents; showing default filesystem disk.',
        ]];

        return [
            'meta' => [
                'category' => 'storage',
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'charts' => [
                'usageByClient' => $usageByClient,
                'fileTypes' => $fileTypes,
            ],
            'tables' => [
                'Storage Usage by Client' => $usageByClient,
                'File Type Distribution' => $fileTypes,
                'Large File Alerts (>= 50MB)' => $largeFiles,
                'Storage Provider Summary' => $provider,
                'Sync Success Rate' => [[
                    'sync_success_rate' => 'Not available (no sync logs implemented)',
                ]],
            ],
        ];
    }

    /**
     * @return array<int>
     */
    protected function activeClientIds(Carbon $start, Carbon $end): array
    {
        $fromRequests = ServiceRequest::query()
            ->whereBetween('created_at', [$start, $end])
            ->distinct()
            ->pluck('client_id')
            ->all();

        $fromInvoices = Invoice::query()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->distinct()
            ->pluck('client_id')
            ->all();

        $fromDocuments = Document::query()
            ->whereBetween('created_at', [$start, $end])
            ->distinct()
            ->pluck('client_id')
            ->all();

        return array_values(array_unique(array_map('intval', array_merge($fromRequests, $fromInvoices, $fromDocuments))));
    }
}

