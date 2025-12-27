<?php

namespace App\Services\Marketing;

use App\Models\Client;
use App\Models\MarketingMetric;
use App\Services\AI\AIProviderManager;
use Illuminate\Support\Arr;

class MarketingAnalyticsService
{
    public function __construct(private readonly AIProviderManager $ai) {}

    /**
     * @param  array{from:string,to:string}  $dateRange
     * @return array<string,mixed>
     */
    public function aggregateAllMetrics(Client $client, array $dateRange): array
    {
        $from = (string) ($dateRange['from'] ?? now()->subDays(30)->toDateString());
        $to = (string) ($dateRange['to'] ?? now()->toDateString());

        $rows = MarketingMetric::query()
            ->where('client_id', $client->id)
            ->whereDate('metric_date', '>=', $from)
            ->whereDate('metric_date', '<=', $to)
            ->orderBy('metric_date')
            ->get();

        $bySource = $rows->groupBy('source')->map(function ($group) {
            return $group->groupBy('metric_name')->map(fn ($r) => $r->map(fn ($x) => [
                'date' => (string) $x->metric_date,
                'value' => $x->metric_value !== null ? (float) $x->metric_value : $x->metric_value_text,
            ])->all())->all();
        })->all();

        return [
            'meta' => [
                'client_id' => $client->id,
                'from' => $from,
                'to' => $to,
                'rows' => $rows->count(),
            ],
            'metrics' => $bySource,
        ];
    }

    /**
     * @param  array<string,mixed>  $metrics
     * @return array<string,mixed>
     */
    public function generateInsights(Client $client, array $metrics): array
    {
        $payload = [
            'client' => [
                'company_name' => $client->company_name,
                'industry' => $client->industry,
                'website' => $client->website,
            ],
            'metrics' => Arr::except($metrics, ['provider_raw']),
            'task' => 'Generate insights, trends, anomalies, recommendations, and a short forecast. Output strict JSON with keys: summary, trends[], anomalies[], recommendations[], forecast.',
        ];

        $res = $this->ai->withFallback('claude', function ($provider) use ($payload) {
            return $provider->chat([
                ['role' => 'system', 'content' => 'You are a marketing analyst. Output strict JSON only.'],
                ['role' => 'user', 'content' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '{}'],
            ], [
                'task_type' => 'marketing_analytics_insights',
                'timeout' => 90,
                'max_tokens' => 1200,
            ]);
        }, taskType: 'marketing_analytics_insights');

        return $this->tryParseJson((string) ($res['text'] ?? '')) ?? ['raw' => (string) ($res['text'] ?? '')];
    }

    protected function tryParseJson(string $text): ?array
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text) ?? $text;
        $text = preg_replace('/\s*```$/', '', $text) ?? $text;
        $decoded = json_decode($text, true);

        return is_array($decoded) ? $decoded : null;
    }
}
