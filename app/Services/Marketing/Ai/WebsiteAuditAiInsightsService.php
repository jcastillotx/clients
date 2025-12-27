<?php

namespace App\Services\Marketing\Ai;

use App\Services\AI\AIProviderManager;

class WebsiteAuditAiInsightsService
{
    public function __construct(private readonly AIProviderManager $ai)
    {
    }

    /**
     * @param array<string,mixed> $report
     * @param array{client_id?:?int, preferred_provider?:string, model?:?string} $options
     * @return array{summary:string, recommendations:array<int,array<string,mixed>>, roadmap:array<int,array<string,mixed>>, roi:array<string,mixed>, raw:?array<string,mixed>}
     */
    public function generate(array $report, array $options = []): array
    {
        $preferred = (string) ($options['preferred_provider'] ?? 'claude');
        $clientId = $options['client_id'] ?? null;
        $model = $options['model'] ?? null;

        $prompt = $this->buildPrompt($report);

        $res = $this->ai->withFallback($preferred, function ($provider) use ($prompt, $model, $clientId) {
            return $provider->chat([
                ['role' => 'system', 'content' => 'You are a senior website auditor and growth strategist. Be specific, practical, and concise.'],
                ['role' => 'user', 'content' => $prompt],
            ], [
                'task_type' => 'website_audit_insights',
                'client_id' => $clientId,
                'model' => $model,
                'max_tokens' => 1200,
                'timeout' => 90,
            ]);
        }, taskType: 'website_audit_insights');

        $text = (string) ($res['text'] ?? '');
        $json = $this->tryParseJson($text);

        if (is_array($json)) {
            return [
                'summary' => (string) ($json['summary'] ?? ''),
                'recommendations' => is_array($json['recommendations'] ?? null) ? (array) $json['recommendations'] : [],
                'roadmap' => is_array($json['roadmap'] ?? null) ? (array) $json['roadmap'] : [],
                'roi' => is_array($json['roi'] ?? null) ? (array) $json['roi'] : [],
                'raw' => $res,
            ];
        }

        return [
            'summary' => $text,
            'recommendations' => [],
            'roadmap' => [],
            'roi' => [],
            'raw' => $res,
        ];
    }

    /**
     * @param array<string,mixed> $report
     */
    protected function buildPrompt(array $report): string
    {
        $site = (string) ($report['meta']['website_url'] ?? '');
        $scores = $report['scores'] ?? [];
        $issues = $report['issues'] ?? [];
        $topIssues = array_slice(is_array($issues) ? $issues : [], 0, 30);

        $payload = json_encode([
            'website_url' => $site,
            'scores' => $scores,
            'top_issues' => $topIssues,
            'performance' => $report['performance'] ?? null,
            'seo' => $report['seo'] ?? null,
            'security' => $report['security'] ?? null,
            'mobile' => $report['mobile'] ?? null,
            'accessibility' => $report['accessibility'] ?? null,
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        if ($payload === false) {
            $payload = '{}';
        }

        return <<<PROMPT
Given this website audit snapshot (JSON), produce a client-ready action plan.

Requirements:
- Output STRICT JSON (no markdown) with keys:
  - summary (string)
  - recommendations (array of {priority:"critical|high|medium|low", title, why, how, effort:"S|M|L", expected_impact:{traffic,conversion,trust}, kpis:[...], affected_pages_hint})
  - roadmap (array of {week:int, focus, items:[...], dependencies:[...]})
  - roi ({assumptions, upside_ranges, notes})
- Be realistic about unknowns; don't invent metrics.
- Focus on highest leverage fixes first.

Audit JSON:
{$payload}
PROMPT;
    }

    protected function tryParseJson(string $text): ?array
    {
        $text = trim($text);
        if ($text === '') return null;

        // Common: model wraps JSON in fences; strip them.
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text) ?? $text;
        $text = preg_replace('/\s*```$/', '', $text) ?? $text;

        $decoded = json_decode($text, true);
        return is_array($decoded) ? $decoded : null;
    }
}

