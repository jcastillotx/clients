<?php

namespace App\Services\AI;

use App\Services\AI\Prompts\TechnicalPrompts;

class TechnicalAdvisorService
{
    public function __construct(protected AIProviderManager $providers)
    {
    }

    /**
     * Review proposed architecture and return structured feedback.
     *
     * @return array<string,mixed>
     */
    public function architectureReview(array $technicalSpecs, array $options = []): array
    {
        $messages = [
            ['role' => 'system', 'content' => TechnicalPrompts::architectureReviewSystem()],
            ['role' => 'user', 'content' => "Technical specs JSON:\n" . json_encode($technicalSpecs, JSON_UNESCAPED_SLASHES) . "\nReturn JSON in the schema."],
        ];

        $preferred = (string) ($options['provider'] ?? 'claude');
        $res = $this->providers->withFallback($preferred, function ($provider) use ($messages, $options) {
            return $provider->chat($messages, [
                'task_type' => 'architecture_review',
                'timeout' => 240,
                'model' => $options['model'] ?? null,
            ]);
        }, 'architecture_review');

        return $this->parseJsonBestEffort((string) ($res['text'] ?? '')) ?: [
            'summary' => (string) ($res['text'] ?? ''),
            'assumptions' => [],
            'risks' => [],
            'security' => [],
            'scalability' => [],
            'reliability' => [],
            'cost_optimizations' => [],
            'alternative_approaches' => [],
            'next_steps' => [],
        ];
    }

    /**
     * Recommend a tech stack for given requirements.
     *
     * @return array<string,mixed>
     */
    public function technologyRecommendations(array $requirements, array $options = []): array
    {
        $messages = [
            ['role' => 'system', 'content' => TechnicalPrompts::techRecommendationsSystem()],
            ['role' => 'user', 'content' => "Requirements JSON:\n" . json_encode($requirements, JSON_UNESCAPED_SLASHES) . "\nReturn JSON in the schema."],
        ];

        $preferred = (string) ($options['provider'] ?? 'claude');
        $res = $this->providers->withFallback($preferred, function ($provider) use ($messages, $options) {
            return $provider->chat($messages, [
                'task_type' => 'tech_recommendations',
                'timeout' => 240,
                'model' => $options['model'] ?? null,
            ]);
        }, 'tech_recommendations');

        return $this->parseJsonBestEffort((string) ($res['text'] ?? '')) ?: [
            'requirements_summary' => (string) ($res['text'] ?? ''),
            'recommended_stack' => [
                'frontend' => '',
                'backend' => '',
                'database' => '',
                'infrastructure' => '',
                'analytics' => '',
                'notes' => '',
            ],
            'alternatives' => [],
            'decision_criteria' => [],
            'risks' => [],
            'next_steps' => [],
        ];
    }

    protected function parseJsonBestEffort(string $text): ?array
    {
        $t = trim($text);
        if ($t === '') return null;

        $decoded = json_decode($t, true);
        if (is_array($decoded)) return $decoded;

        $start = strpos($t, '{');
        $end = strrpos($t, '}');
        if ($start === false || $end === false || $end <= $start) return null;

        $slice = substr($t, $start, $end - $start + 1);
        $decoded = json_decode($slice, true);
        return is_array($decoded) ? $decoded : null;
    }
}

