<?php

namespace App\Services\Marketing;

use App\Models\BrandAudit;
use App\Models\BrandInconsistency;
use App\Models\Client;
use App\Services\AI\AIProviderManager;
use Illuminate\Support\Facades\Log;

class BrandAuditorService
{
    public function __construct(
        private readonly AIProviderManager $ai,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function performBrandAudit(Client $client, array $options = []): array
    {
        $audit = BrandAudit::create([
            'client_id' => $client->id,
            'audit_date' => now()->toDateString(),
            'status' => 'running',
        ]);

        try {
            $visual = $this->visualIdentityAudit($client);
            $messaging = $this->messagingAudit($client);
            $competitors = is_array($options['competitors'] ?? null) ? (array) $options['competitors'] : [];
            $competitive = $this->competitiveBrandAnalysis($client, $competitors);

            $scores = $this->score($visual, $messaging, $competitive);
            $report = [
                'meta' => [
                    'client_id' => $client->id,
                    'client_name' => $client->company_name,
                    'audit_id' => $audit->id,
                    'audited_at' => now()->toIso8601String(),
                ],
                'scores' => $scores,
                'visual' => $visual,
                'messaging' => $messaging,
                'competitive' => $competitive,
            ];

            $audit->update([
                'status' => 'completed',
                'overall_score' => (int) ($scores['overall'] ?? null),
                'visual_score' => (int) ($scores['visual'] ?? null),
                'messaging_score' => (int) ($scores['messaging'] ?? null),
                'consistency_score' => (int) ($scores['consistency'] ?? null),
                'perception_score' => (int) ($scores['perception'] ?? null),
                'report' => $report,
                'failure_reason' => null,
            ]);

            // Persist inconsistencies (best-effort)
            foreach ((array) ($report['visual']['inconsistencies'] ?? []) as $row) {
                if (! is_array($row)) {
                    continue;
                }
                BrandInconsistency::create([
                    'brand_audit_id' => $audit->id,
                    'category' => 'visual',
                    'severity' => (string) ($row['severity'] ?? 'warning'),
                    'location' => $row['location'] ?? null,
                    'description' => (string) ($row['description'] ?? ''),
                    'recommendation' => $row['recommendation'] ?? null,
                    'status' => 'open',
                    'meta' => $row['meta'] ?? null,
                ]);
            }

            return $report;
        } catch (\Throwable $e) {
            Log::warning('Brand audit failed', [
                'client_id' => $client->id,
                'audit_id' => $audit->id,
                'error' => $e->getMessage(),
            ]);

            $audit->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
            ]);

            return [
                'meta' => [
                    'client_id' => $client->id,
                    'audit_id' => $audit->id,
                    'status' => 'failed',
                ],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function visualIdentityAudit(Client $client): array
    {
        // Production-ready implementation would scan website + social + uploaded assets.
        // Here we provide an AI-guided analysis scaffold using known client fields.
        $payload = [
            'client' => [
                'company_name' => $client->company_name,
                'website' => $client->website,
                'industry' => $client->industry,
            ],
            'task' => 'Create a visual identity audit checklist and flag likely inconsistencies. Output strict JSON with keys: findings, asset_inventory, inconsistencies.',
        ];

        $res = $this->ai->withFallback('claude', function ($provider) use ($payload) {
            return $provider->chat([
                ['role' => 'system', 'content' => 'You are a brand designer auditing a brand system. Output strict JSON only.'],
                ['role' => 'user', 'content' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '{}'],
            ], [
                'task_type' => 'brand_visual_audit',
                'timeout' => 90,
                'max_tokens' => 1200,
            ]);
        }, taskType: 'brand_visual_audit');

        return $this->tryParseJson((string) ($res['text'] ?? '')) ?? ['raw' => (string) ($res['text'] ?? '')];
    }

    /**
     * @return array<string,mixed>
     */
    public function messagingAudit(Client $client): array
    {
        $payload = [
            'client' => [
                'company_name' => $client->company_name,
                'website' => $client->website,
                'industry' => $client->industry,
            ],
            'task' => 'Audit messaging consistency (voice/tone/value prop). Output strict JSON with keys: voice_attributes, themes, inconsistencies, recommendations.',
        ];

        $res = $this->ai->withFallback('claude', function ($provider) use ($payload) {
            return $provider->chat([
                ['role' => 'system', 'content' => 'You are a brand strategist. Output strict JSON only.'],
                ['role' => 'user', 'content' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '{}'],
            ], [
                'task_type' => 'brand_messaging_audit',
                'timeout' => 90,
                'max_tokens' => 1200,
            ]);
        }, taskType: 'brand_messaging_audit');

        return $this->tryParseJson((string) ($res['text'] ?? '')) ?? ['raw' => (string) ($res['text'] ?? '')];
    }

    /**
     * @param  array<int, array<string,mixed>|string>  $competitors
     * @return array<string,mixed>
     */
    public function competitiveBrandAnalysis(Client $client, array $competitors): array
    {
        $payload = [
            'client' => [
                'company_name' => $client->company_name,
                'website' => $client->website,
                'industry' => $client->industry,
            ],
            'competitors' => $competitors,
            'task' => 'Compare positioning and identify whitespace opportunities. Output strict JSON with keys: positioning_map, differentiation, opportunities, risks.',
        ];

        $res = $this->ai->withFallback('perplexity', function ($provider) use ($payload) {
            return $provider->chat([
                ['role' => 'system', 'content' => 'You are a competitive brand analyst. Output strict JSON only.'],
                ['role' => 'user', 'content' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '{}'],
            ], [
                'task_type' => 'brand_competitive_analysis',
                'timeout' => 90,
                'max_tokens' => 1200,
            ]);
        }, taskType: 'brand_competitive_analysis');

        return $this->tryParseJson((string) ($res['text'] ?? '')) ?? ['raw' => (string) ($res['text'] ?? '')];
    }

    /**
     * @return array{overall:int, visual:int, messaging:int, consistency:int, perception:int}
     */
    protected function score(array $visual, array $messaging, array $competitive): array
    {
        // Placeholder scoring. Production would compute from signals + sentiment.
        $visualScore = (int) ($visual['score'] ?? 80);
        $messagingScore = (int) ($messaging['score'] ?? 80);
        $consistency = (int) ($visual['consistency_score'] ?? $messaging['consistency_score'] ?? 75);
        $perception = (int) ($competitive['perception_score'] ?? 75);

        $overall = (int) round(($visualScore + $messagingScore + $consistency + $perception) / 4);

        return [
            'overall' => max(0, min(100, $overall)),
            'visual' => max(0, min(100, $visualScore)),
            'messaging' => max(0, min(100, $messagingScore)),
            'consistency' => max(0, min(100, $consistency)),
            'perception' => max(0, min(100, $perception)),
        ];
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
