<?php

namespace App\Services\AI;

use App\Models\AiConversation;
use App\Models\AiInsightReport;
use App\Models\AiMessage;
use App\Models\Client;
use App\Models\ClientAiQuestion;
use App\Models\IndustryMonitor;
use App\Models\Request as ServiceRequest;
use App\Services\AI\Prompts\ResearchPrompts;

class ResearchAssistantService
{
    public function __construct(protected AIProviderManager $providers, protected AISafetyService $safety) {}

    /**
     * Web-grounded research report using Perplexity (with fallback).
     *
     * @return array<string,mixed>
     */
    public function conductResearch(string $topic, string $depth = 'standard', array $options = []): array
    {
        $depth = $this->normalizeDepth($depth);
        $region = $options['region'] ?? null;

        $messages = [
            ['role' => 'system', 'content' => ResearchPrompts::researchSystem()],
            ['role' => 'user', 'content' => ResearchPrompts::researchUser($topic, $depth, $region)],
        ];

        $res = $this->safety->safeChat($messages, [
            'provider' => 'perplexity',
            'task_type' => 'research',
            'timeout' => 180,
            'user_query' => $topic,
        ]);

        $json = $this->parseJsonBestEffort((string) ($res['text'] ?? ''));

        // Enrich sources if provider returned citations separately.
        if (is_array($json) && empty($json['sources']) && ! empty($res['sources'])) {
            $json['sources'] = $res['sources'];
        }

        if (is_array($json)) {
            $fc = $this->safety->factCheck($json);
            if (! $fc['passed']) {
                $json['_safety'] = ['fact_check' => $fc];
            }
        }

        return is_array($json) ? $json : [
            'topic' => $topic,
            'depth' => $depth,
            'executive_summary' => (string) ($res['text'] ?? ''),
            'key_findings' => [],
            'data_points' => [],
            'opportunities' => [],
            'risks' => [],
            'recommended_next_steps' => [],
            'sources' => $res['sources'] ?? $res['citations'] ?? [],
            'search_queries_used' => [],
        ];
    }

    /**
     * Competitive analysis: web-grounded research (Perplexity) + structured synthesis.
     */
    public function competitiveAnalysis(Client $client, array $competitors, array $options = []): AiInsightReport
    {
        $messages = [
            ['role' => 'system', 'content' => ResearchPrompts::competitiveSystem()],
            ['role' => 'user', 'content' => ResearchPrompts::competitiveUser($client, $competitors)],
        ];

        $res = $this->providers->withFallback('perplexity', function ($provider) use ($messages) {
            return $provider->chat($messages, [
                'task_type' => 'competitive_analysis',
                'timeout' => 240,
            ]);
        }, 'competitive_analysis');

        $payload = $this->parseJsonBestEffort((string) ($res['text'] ?? '')) ?: ['client_id' => $client->id, 'competitors' => $competitors];

        return AiInsightReport::create([
            'kind' => 'competitive_analysis',
            'payload' => $payload,
            'narrative' => null,
            'provider_used' => $res['provider'] ?? null,
            'model_used' => $res['model'] ?? null,
            'cost' => $res['estimated_cost'] ?? null,
        ]);
    }

    /**
     * Market analysis (industry + region) with web citations.
     */
    public function marketAnalysis(string $industry, ?string $region = null, array $options = []): AiInsightReport
    {
        $messages = [
            ['role' => 'system', 'content' => ResearchPrompts::marketSystem()],
            ['role' => 'user', 'content' => ResearchPrompts::marketUser($industry, $region)],
        ];

        $res = $this->providers->withFallback('perplexity', function ($provider) use ($messages) {
            return $provider->chat($messages, [
                'task_type' => 'market_analysis',
                'timeout' => 240,
            ]);
        }, 'market_analysis');

        $payload = $this->parseJsonBestEffort((string) ($res['text'] ?? '')) ?: ['industry' => $industry, 'region' => $region];

        return AiInsightReport::create([
            'kind' => 'market_analysis',
            'payload' => $payload,
            'narrative' => null,
            'provider_used' => $res['provider'] ?? null,
            'model_used' => $res['model'] ?? null,
            'cost' => $res['estimated_cost'] ?? null,
        ]);
    }

    /**
     * Marketing content research: trends + keywords + outline + competitor content notes.
     *
     * @return array<string,mixed>
     */
    public function contentResearch(string $topic, string $audience, array $options = []): array
    {
        $region = $options['region'] ?? null;
        $messages = [
            ['role' => 'system', 'content' => ResearchPrompts::seoSystem()],
            ['role' => 'user', 'content' => ResearchPrompts::seoUser($topic, $audience, $region)],
        ];

        $res = $this->providers->withFallback('perplexity', function ($provider) use ($messages) {
            return $provider->chat($messages, [
                'task_type' => 'seo_research',
                'timeout' => 240,
            ]);
        }, 'seo_research');

        return $this->parseJsonBestEffort((string) ($res['text'] ?? '')) ?: [
            'topic' => $topic,
            'audience' => $audience,
            'search_intent' => 'informational',
            'keyword_clusters' => [],
            'content_ideas' => [],
            'outline' => ['h1' => $topic, 'sections' => []],
            'competitor_content_notes' => [],
            'sources' => $res['sources'] ?? $res['citations'] ?? [],
        ];
    }

    /**
     * Creative brainstorming: GPT-4/OpenAI (fallback to OpenRouter).
     *
     * @return array<string,mixed>
     */
    public function brainstorm(array $brief, array $options = []): array
    {
        $messages = [
            ['role' => 'system', 'content' => ResearchPrompts::creativeSystem()],
            ['role' => 'user', 'content' => "Creative brief JSON:\n".json_encode($brief, JSON_UNESCAPED_SLASHES)."\nReturn JSON in the schema."],
        ];

        $res = $this->providers->withFallback('openai', function ($provider) use ($messages) {
            return $provider->chat($messages, [
                'task_type' => 'creative_brainstorm',
                'timeout' => 120,
            ]);
        }, 'creative_brainstorm');

        return $this->parseJsonBestEffort((string) ($res['text'] ?? '')) ?: ['campaign_ideas' => [], 'brand_names' => [], 'taglines' => []];
    }

    /**
     * Q&A for clients (tracked for opportunities + can attach to request).
     */
    public function answerClientQuestion(
        string $question,
        array $context = [],
        array $options = []
    ): ClientAiQuestion {
        $clientId = $context['client_id'] ?? null;
        $askedBy = $context['asked_by'] ?? null;
        $requestId = $context['request_id'] ?? null;
        $topic = $context['topic'] ?? null;
        $category = $context['category'] ?? null;

        $conversation = AiConversation::create([
            'client_id' => $clientId,
            'user_id' => $askedBy,
            'context_type' => $requestId ? 'request' : 'general',
            'context_id' => $requestId ? (int) $requestId : null,
            'title' => $topic ?: 'Client Q&A',
        ]);

        AiMessage::create([
            'ai_conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $question,
            'provider_used' => null,
            'model_used' => null,
            'tokens_used' => null,
            'cost' => null,
            'response_time_ms' => null,
        ]);

        $messages = [
            ['role' => 'system', 'content' => ResearchPrompts::researchSystem()],
            ['role' => 'user', 'content' => "Answer this business question with citations where possible.\n\nQuestion: {$question}\n\nContext JSON:\n".json_encode($context, JSON_UNESCAPED_SLASHES)],
        ];

        $res = $this->providers->withFallback('perplexity', function ($provider) use ($messages) {
            return $provider->chat($messages, [
                'task_type' => 'client_qa',
                'timeout' => 180,
            ]);
        }, 'client_qa');

        $payload = $this->parseJsonBestEffort((string) ($res['text'] ?? ''));

        $answerText = is_array($payload)
            ? (($payload['executive_summary'] ?? '')."\n\n".$this->bullets($payload['recommended_next_steps'] ?? []))
            : (string) ($res['text'] ?? '');

        $tokens = (array) ($res['tokens'] ?? []);
        $tokensUsed = (int) ($tokens['total'] ?? ((int) ($tokens['input'] ?? 0) + (int) ($tokens['output'] ?? 0)));

        AiMessage::create([
            'ai_conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => (string) ($res['text'] ?? ''),
            'provider_used' => $res['provider'] ?? null,
            'model_used' => $res['model'] ?? null,
            'tokens_used' => $tokensUsed > 0 ? $tokensUsed : null,
            'cost' => $res['estimated_cost'] ?? null,
            'response_time_ms' => isset($res['response_time_ms']) ? (int) $res['response_time_ms'] : null,
        ]);

        $sources = is_array($payload) ? ($payload['sources'] ?? []) : ($res['sources'] ?? $res['citations'] ?? []);

        // Lightweight opportunity tagging heuristic.
        $isOpp = false;
        $oppType = null;
        $qLower = strtolower($question);
        if (str_contains($qLower, 'pricing') || str_contains($qLower, 'cost') || str_contains($qLower, 'package')) {
            $isOpp = true;
            $oppType = 'upsell';
        } elseif (str_contains($qLower, 'strategy') || str_contains($qLower, 'roadmap')) {
            $isOpp = true;
            $oppType = 'consulting';
        }

        return ClientAiQuestion::create([
            'client_id' => $clientId,
            'asked_by' => $askedBy,
            'ai_conversation_id' => $conversation->id,
            'category' => $category,
            'topic' => $topic,
            'question' => $question,
            'answer' => $answerText,
            'sources' => $sources,
            'tags' => is_array($payload) ? ($payload['search_queries_used'] ?? []) : null,
            'is_opportunity' => $isOpp,
            'opportunity_type' => $oppType,
            'request_id' => $requestId,
            'answered_at' => now(),
        ]);
    }

    /**
     * Project-specific research dossier attached to a request.
     */
    public function createResearchDossierForRequest(ServiceRequest $request, string $topic, string $depth = 'standard', array $options = []): AiInsightReport
    {
        $result = $this->conductResearch($topic, $depth, $options);
        $result['request_id'] = $request->id;
        $result['client_id'] = $request->client_id;

        return AiInsightReport::create([
            'kind' => 'project_dossier',
            'period_start' => null,
            'period_end' => null,
            'payload' => $result,
            'narrative' => $result['executive_summary'] ?? null,
        ]);
    }

    /**
     * Run an industry monitor now and save an insight report.
     */
    public function runIndustryMonitor(IndustryMonitor $monitor, array $options = []): AiInsightReport
    {
        $topic = "{$monitor->industry} industry trends";
        if (! empty($monitor->region)) {
            $topic .= " in {$monitor->region}";
        }
        if (! empty($monitor->keywords)) {
            $topic .= ' (keywords: '.implode(', ', (array) $monitor->keywords).')';
        }

        $data = $this->conductResearch($topic, 'standard', ['region' => $monitor->region]);

        $report = AiInsightReport::create([
            'kind' => 'industry_trend_analysis',
            'payload' => [
                'monitor_id' => $monitor->id,
                'client_id' => $monitor->client_id,
                'industry' => $monitor->industry,
                'region' => $monitor->region,
                'keywords' => $monitor->keywords,
                'report' => $data,
            ],
            'narrative' => $data['executive_summary'] ?? null,
        ]);

        $monitor->update([
            'last_run_at' => now(),
            'last_report_id' => $report->id,
        ]);

        return $report;
    }

    // ------------------------
    // Helpers
    // ------------------------

    protected function normalizeDepth(string $depth): string
    {
        $d = strtolower(trim($depth));

        return in_array($d, ['quick', 'standard', 'deep'], true) ? $d : 'standard';
    }

    protected function parseJsonBestEffort(string $text): ?array
    {
        $t = trim($text);
        if ($t === '') {
            return null;
        }

        // Try direct decode
        $decoded = json_decode($t, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Try extracting the first JSON object.
        $start = strpos($t, '{');
        $end = strrpos($t, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $slice = substr($t, $start, $end - $start + 1);
        $decoded = json_decode($slice, true);

        return is_array($decoded) ? $decoded : null;
    }

    protected function bullets(array $items): string
    {
        $items = array_values(array_filter(array_map(fn ($x) => is_string($x) ? trim($x) : '', $items)));
        if (empty($items)) {
            return '';
        }

        return implode("\n", array_map(fn ($x) => "- {$x}", $items));
    }
}
