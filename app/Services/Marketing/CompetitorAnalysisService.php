<?php

namespace App\Services\Marketing;

use App\Models\CompetitorAnalysis;
use App\Models\Client;
use App\Services\AI\AIProviderManager;
use Illuminate\Support\Facades\Log;

/**
 * Comprehensive Competitor Analysis Service
 *
 * Uses AI (preferably Perplexity for web-grounded research) to analyze
 * competitors and provide detailed insights including gaps, limitations,
 * SWOT analysis, and actionable recommendations.
 */
class CompetitorAnalysisService
{
    public function __construct(private readonly AIProviderManager $ai) {}

    /**
     * Perform a full competitor analysis.
     *
     * @param  Client  $client  The client requesting the analysis
     * @param  string  $competitorName  Name of the competitor to analyze
     * @param  string|null  $competitorUrl  Optional URL for the competitor
     * @param  string|null  $industry  Industry context for better analysis
     * @param  int|null  $userId  User ID who initiated the analysis
     * @return CompetitorAnalysis
     */
    public function analyze(
        Client $client,
        string $competitorName,
        ?string $competitorUrl = null,
        ?string $industry = null,
        ?int $userId = null
    ): CompetitorAnalysis {
        $startTime = microtime(true);

        // Create the analysis record
        $analysis = CompetitorAnalysis::create([
            'client_id' => $client->id,
            'created_by' => $userId,
            'competitor_name' => $competitorName,
            'competitor_url' => $this->normalizeUrl($competitorUrl),
            'competitor_industry' => $industry ?? $client->industry,
            'status' => CompetitorAnalysis::STATUS_PROCESSING,
        ]);

        try {
            // Gather comprehensive competitor intelligence
            $intelligence = $this->gatherCompetitorIntelligence($competitorName, $competitorUrl, $industry);

            // Perform SWOT analysis
            $swot = $this->performSwotAnalysis($competitorName, $intelligence);

            // Identify gaps and limitations
            $gapsLimitations = $this->identifyGapsAndLimitations($competitorName, $intelligence);

            // Generate strategic recommendations
            $recommendations = $this->generateRecommendations($client, $competitorName, $intelligence, $swot, $gapsLimitations);

            // Calculate processing time
            $processingTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            // Update the analysis record with all gathered data
            $analysis->update([
                'status' => CompetitorAnalysis::STATUS_COMPLETED,
                'company_overview' => $intelligence['company_overview'] ?? null,
                'products_services' => $intelligence['products_services'] ?? null,
                'market_position' => $intelligence['market_position'] ?? null,
                'strengths' => $swot['strengths'] ?? null,
                'weaknesses' => $swot['weaknesses'] ?? null,
                'opportunities' => $swot['opportunities'] ?? null,
                'threats' => $swot['threats'] ?? null,
                'pricing_strategy' => $intelligence['pricing_strategy'] ?? null,
                'marketing_channels' => $intelligence['marketing_channels'] ?? null,
                'target_audience' => $intelligence['target_audience'] ?? null,
                'technology_stack' => $intelligence['technology_stack'] ?? null,
                'online_presence' => $intelligence['online_presence'] ?? null,
                'content_strategy' => $intelligence['content_strategy'] ?? null,
                'customer_reviews' => $intelligence['customer_reviews'] ?? null,
                'gaps_limitations' => $gapsLimitations,
                'competitive_advantages' => $intelligence['competitive_advantages'] ?? null,
                'recommendations' => $recommendations,
                'sources' => $this->consolidateSources($intelligence, $swot, $gapsLimitations),
                'analysis_summary' => $this->generateSummary($competitorName, $swot, $gapsLimitations),
                'confidence_score' => $this->calculateConfidenceScore($intelligence),
                'processing_time_ms' => $processingTimeMs,
                'analyzed_at' => now(),
            ]);

            return $analysis->fresh();

        } catch (\Throwable $e) {
            Log::error('Competitor analysis failed', [
                'competitor' => $competitorName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $analysis->update([
                'status' => CompetitorAnalysis::STATUS_FAILED,
                'analysis_summary' => 'Analysis failed: ' . $e->getMessage(),
                'processing_time_ms' => (int) ((microtime(true) - $startTime) * 1000),
            ]);

            return $analysis->fresh();
        }
    }

    /**
     * Gather comprehensive competitor intelligence using AI.
     *
     * @return array<string, mixed>
     */
    protected function gatherCompetitorIntelligence(
        string $competitorName,
        ?string $competitorUrl,
        ?string $industry
    ): array {
        $prompt = $this->buildIntelligencePrompt($competitorName, $competitorUrl, $industry);

        $response = $this->ai->withFallback('perplexity', function ($provider) use ($prompt) {
            return $provider->chat([
                [
                    'role' => 'system',
                    'content' => $this->getIntelligenceSystemPrompt(),
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ], [
                'task_type' => 'competitor_analysis_intelligence',
                'timeout' => 120,
                'max_tokens' => 4000,
            ]);
        }, taskType: 'competitor_analysis_intelligence');

        $parsed = $this->tryParseJson((string) ($response['text'] ?? ''));

        return array_merge(
            is_array($parsed) ? $parsed : ['raw' => $response['text'] ?? ''],
            ['_sources' => $response['citations'] ?? $response['sources'] ?? null]
        );
    }

    /**
     * Perform SWOT analysis using AI.
     *
     * @param  array<string, mixed>  $intelligence
     * @return array<string, mixed>
     */
    protected function performSwotAnalysis(string $competitorName, array $intelligence): array
    {
        $context = json_encode([
            'competitor' => $competitorName,
            'overview' => $intelligence['company_overview'] ?? null,
            'products' => $intelligence['products_services'] ?? null,
            'market_position' => $intelligence['market_position'] ?? null,
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $response = $this->ai->withFallback('perplexity', function ($provider) use ($competitorName, $context) {
            return $provider->chat([
                [
                    'role' => 'system',
                    'content' => 'You are a strategic business analyst specializing in competitive intelligence. Provide detailed SWOT analysis based on current market research. Output strict JSON only.',
                ],
                [
                    'role' => 'user',
                    'content' => "Perform a comprehensive SWOT analysis for {$competitorName}.\n\nContext:\n{$context}\n\nReturn JSON with keys: strengths (array of detailed strength points), weaknesses (array of detailed weakness points), opportunities (array of market opportunities they could exploit), threats (array of threats to their business). Each item should be a string with specific, actionable insight.",
                ],
            ], [
                'task_type' => 'competitor_swot_analysis',
                'timeout' => 90,
                'max_tokens' => 2000,
            ]);
        }, taskType: 'competitor_swot_analysis');

        $parsed = $this->tryParseJson((string) ($response['text'] ?? ''));

        return array_merge(
            is_array($parsed) ? $parsed : [],
            ['_sources' => $response['citations'] ?? $response['sources'] ?? null]
        );
    }

    /**
     * Identify gaps and limitations in the competitor's offering.
     *
     * @param  array<string, mixed>  $intelligence
     * @return array<string, mixed>
     */
    protected function identifyGapsAndLimitations(string $competitorName, array $intelligence): array
    {
        $context = json_encode([
            'competitor' => $competitorName,
            'products' => $intelligence['products_services'] ?? null,
            'customer_reviews' => $intelligence['customer_reviews'] ?? null,
            'weaknesses' => $intelligence['weaknesses'] ?? null,
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $response = $this->ai->withFallback('perplexity', function ($provider) use ($competitorName, $context) {
            return $provider->chat([
                [
                    'role' => 'system',
                    'content' => 'You are a competitive intelligence specialist focused on identifying market gaps and business limitations. Provide web-grounded analysis with specific, actionable insights. Output strict JSON only.',
                ],
                [
                    'role' => 'user',
                    'content' => "Identify all significant gaps and limitations for {$competitorName}.\n\nContext:\n{$context}\n\nReturn JSON with:\n- product_gaps: Array of missing features or product limitations\n- service_gaps: Array of service delivery weaknesses\n- market_gaps: Array of underserved market segments\n- technology_gaps: Array of technical limitations or outdated systems\n- customer_experience_gaps: Array of CX issues from reviews/feedback\n- pricing_limitations: Array of pricing model weaknesses\n- geographic_limitations: Array of geographic coverage gaps\n- competitive_vulnerabilities: Array of areas where competitors could gain advantage\n- operational_limitations: Array of operational/scaling challenges\n\nEach item should be a detailed string explaining the specific gap/limitation and its business impact.",
                ],
            ], [
                'task_type' => 'competitor_gaps_analysis',
                'timeout' => 90,
                'max_tokens' => 2500,
            ]);
        }, taskType: 'competitor_gaps_analysis');

        $parsed = $this->tryParseJson((string) ($response['text'] ?? ''));

        return is_array($parsed) ? $parsed : ['raw' => $response['text'] ?? ''];
    }

    /**
     * Generate strategic recommendations based on the analysis.
     *
     * @param  array<string, mixed>  $intelligence
     * @param  array<string, mixed>  $swot
     * @param  array<string, mixed>  $gaps
     * @return array<string, mixed>
     */
    protected function generateRecommendations(
        Client $client,
        string $competitorName,
        array $intelligence,
        array $swot,
        array $gaps
    ): array {
        $context = json_encode([
            'our_company' => $client->company_name,
            'our_industry' => $client->industry,
            'competitor' => $competitorName,
            'competitor_weaknesses' => $swot['weaknesses'] ?? [],
            'competitor_gaps' => $gaps,
            'competitor_strengths' => $swot['strengths'] ?? [],
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $response = $this->ai->withFallback('claude', function ($provider) use ($context) {
            return $provider->chat([
                [
                    'role' => 'system',
                    'content' => 'You are a strategic marketing consultant. Generate actionable recommendations to help a company compete more effectively against a competitor. Output strict JSON only.',
                ],
                [
                    'role' => 'user',
                    'content' => "Based on the competitive analysis, generate strategic recommendations.\n\nContext:\n{$context}\n\nReturn JSON with:\n- immediate_actions: Array of actions that can be taken within 30 days\n- short_term_strategies: Array of strategies for 1-3 months\n- long_term_opportunities: Array of opportunities for 6+ months\n- differentiation_opportunities: Array of ways to differentiate from this competitor\n- market_positioning: Array of positioning recommendations\n- messaging_strategies: Array of marketing message recommendations\n- product_opportunities: Array of product/service opportunities based on competitor gaps\n- pricing_recommendations: Array of pricing strategy suggestions\n\nEach item should be specific and actionable.",
                ],
            ], [
                'task_type' => 'competitor_recommendations',
                'timeout' => 90,
                'max_tokens' => 2000,
            ]);
        }, taskType: 'competitor_recommendations');

        $parsed = $this->tryParseJson((string) ($response['text'] ?? ''));

        return is_array($parsed) ? $parsed : ['raw' => $response['text'] ?? ''];
    }

    /**
     * Build the intelligence gathering prompt.
     */
    protected function buildIntelligencePrompt(string $competitorName, ?string $url, ?string $industry): string
    {
        $urlContext = $url ? "Website: {$url}" : '';
        $industryContext = $industry ? "Industry: {$industry}" : '';

        return <<<PROMPT
Perform comprehensive competitive intelligence research on: {$competitorName}
{$urlContext}
{$industryContext}

Gather and return detailed information in JSON format with these keys:

1. company_overview: Object with:
   - description: Brief company description
   - founded: Year founded (if available)
   - headquarters: Location
   - employee_count: Estimated employees
   - revenue_estimate: Revenue range if available
   - funding: Funding information if startup
   - key_executives: Array of notable executives

2. products_services: Array of objects with:
   - name: Product/service name
   - description: What it does
   - pricing: Pricing info if available
   - key_features: Array of features

3. market_position: Object with:
   - market_share: Estimated market share
   - positioning: How they position themselves
   - key_differentiators: What makes them unique
   - main_competitors: Their main competitors

4. pricing_strategy: Object with:
   - model: Pricing model (subscription, one-time, freemium, etc.)
   - tiers: Array of pricing tiers if available
   - competitive_pricing: How prices compare to market

5. marketing_channels: Array of active marketing channels with effectiveness notes

6. target_audience: Object with:
   - primary_segments: Array of primary customer segments
   - company_sizes: Target company sizes (if B2B)
   - demographics: Key demographic info (if B2C)
   - use_cases: Common use cases

7. technology_stack: Array of known technologies they use

8. online_presence: Object with:
   - website_quality: Assessment of website
   - social_media: Active platforms and follower counts
   - content_quality: Assessment of their content
   - seo_visibility: SEO strength assessment

9. content_strategy: Object with:
   - blog_frequency: How often they publish
   - content_types: Types of content they create
   - topics: Main topics they cover
   - engagement: Content engagement level

10. customer_reviews: Object with:
    - overall_sentiment: positive/mixed/negative
    - common_praise: Array of commonly praised aspects
    - common_complaints: Array of common complaints
    - review_platforms: Where reviews are found

11. competitive_advantages: Array of their key competitive advantages

Ensure all data is current and web-grounded. Output strict JSON only.
PROMPT;
    }

    /**
     * Get the system prompt for intelligence gathering.
     */
    protected function getIntelligenceSystemPrompt(): string
    {
        return 'You are an expert competitive intelligence analyst with access to current web data. Your role is to gather comprehensive, accurate, and actionable intelligence about companies. Always cite sources when possible and indicate confidence levels. Prioritize recent information and factual data. Output strict JSON only, no markdown formatting.';
    }

    /**
     * Generate a summary of the analysis.
     *
     * @param  array<string, mixed>  $swot
     * @param  array<string, mixed>  $gaps
     */
    protected function generateSummary(string $competitorName, array $swot, array $gaps): string
    {
        $strengthCount = count($swot['strengths'] ?? []);
        $weaknessCount = count($swot['weaknesses'] ?? []);
        $gapCount = array_sum(array_map(fn ($v) => is_array($v) ? count($v) : 0, $gaps));

        return "Comprehensive analysis of {$competitorName} identified {$strengthCount} key strengths, {$weaknessCount} weaknesses, and {$gapCount} specific gaps/limitations. The analysis covers market position, SWOT factors, pricing strategy, marketing channels, and provides strategic recommendations for competitive positioning.";
    }

    /**
     * Calculate confidence score based on data completeness.
     *
     * @param  array<string, mixed>  $intelligence
     */
    protected function calculateConfidenceScore(array $intelligence): float
    {
        $fields = [
            'company_overview',
            'products_services',
            'market_position',
            'pricing_strategy',
            'marketing_channels',
            'target_audience',
            'online_presence',
            'customer_reviews',
        ];

        $score = 0;
        foreach ($fields as $field) {
            if (! empty($intelligence[$field])) {
                $score += 12.5;
            }
        }

        return min(100.0, max(0.0, $score));
    }

    /**
     * Consolidate sources from all analysis phases.
     *
     * @param  array<string, mixed>  $intelligence
     * @param  array<string, mixed>  $swot
     * @param  array<string, mixed>  $gaps
     * @return array<int, string>
     */
    protected function consolidateSources(array $intelligence, array $swot, array $gaps): array
    {
        $sources = [];

        foreach ([$intelligence, $swot, $gaps] as $data) {
            if (isset($data['_sources']) && is_array($data['_sources'])) {
                $sources = array_merge($sources, $data['_sources']);
            }
        }

        return array_values(array_unique($sources));
    }

    /**
     * Normalize URL format.
     */
    protected function normalizeUrl(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        $url = trim($url);
        if (! preg_match('/^https?:\/\//i', $url)) {
            $url = 'https://' . $url;
        }

        return $url;
    }

    /**
     * Try to parse JSON from AI response.
     *
     * @return array<string, mixed>|null
     */
    protected function tryParseJson(string $text): ?array
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        // Remove markdown code blocks if present
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text) ?? $text;
        $text = preg_replace('/\s*```$/', '', $text) ?? $text;

        $decoded = json_decode($text, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Quick analysis - performs a faster, lighter analysis.
     *
     * @return CompetitorAnalysis
     */
    public function quickAnalyze(
        Client $client,
        string $competitorName,
        ?string $competitorUrl = null,
        ?int $userId = null
    ): CompetitorAnalysis {
        $startTime = microtime(true);

        $analysis = CompetitorAnalysis::create([
            'client_id' => $client->id,
            'created_by' => $userId,
            'competitor_name' => $competitorName,
            'competitor_url' => $this->normalizeUrl($competitorUrl),
            'competitor_industry' => $client->industry,
            'status' => CompetitorAnalysis::STATUS_PROCESSING,
        ]);

        try {
            $response = $this->ai->withFallback('perplexity', function ($provider) use ($competitorName, $competitorUrl) {
                return $provider->chat([
                    [
                        'role' => 'system',
                        'content' => 'You are a competitive intelligence analyst. Provide a quick but comprehensive competitor overview with key strengths, weaknesses, and gaps. Output strict JSON only.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Quick competitive analysis for: {$competitorName}" . ($competitorUrl ? " ({$competitorUrl})" : '') . "\n\nReturn JSON with: company_overview (string), key_strengths (array), key_weaknesses (array), main_gaps (array), competitive_position (string), recommendation (string)",
                    ],
                ], [
                    'task_type' => 'competitor_quick_analysis',
                    'timeout' => 60,
                    'max_tokens' => 1500,
                ]);
            }, taskType: 'competitor_quick_analysis');

            $parsed = $this->tryParseJson((string) ($response['text'] ?? ''));

            $analysis->update([
                'status' => CompetitorAnalysis::STATUS_COMPLETED,
                'company_overview' => ['summary' => $parsed['company_overview'] ?? null],
                'strengths' => $parsed['key_strengths'] ?? null,
                'weaknesses' => $parsed['key_weaknesses'] ?? null,
                'gaps_limitations' => ['main_gaps' => $parsed['main_gaps'] ?? null],
                'market_position' => ['competitive_position' => $parsed['competitive_position'] ?? null],
                'recommendations' => ['quick_recommendation' => $parsed['recommendation'] ?? null],
                'sources' => $response['citations'] ?? $response['sources'] ?? null,
                'analysis_summary' => $parsed['company_overview'] ?? 'Quick analysis completed.',
                'confidence_score' => 60.0, // Lower confidence for quick analysis
                'processing_time_ms' => (int) ((microtime(true) - $startTime) * 1000),
                'analyzed_at' => now(),
            ]);

            return $analysis->fresh();

        } catch (\Throwable $e) {
            Log::error('Quick competitor analysis failed', [
                'competitor' => $competitorName,
                'error' => $e->getMessage(),
            ]);

            $analysis->update([
                'status' => CompetitorAnalysis::STATUS_FAILED,
                'analysis_summary' => 'Quick analysis failed: ' . $e->getMessage(),
                'processing_time_ms' => (int) ((microtime(true) - $startTime) * 1000),
            ]);

            return $analysis->fresh();
        }
    }

    /**
     * Compare multiple competitors.
     *
     * @param  array<int, string>  $competitorNames
     * @return array<string, mixed>
     */
    public function compareCompetitors(Client $client, array $competitorNames): array
    {
        $analyses = CompetitorAnalysis::where('client_id', $client->id)
            ->whereIn('competitor_name', $competitorNames)
            ->where('status', CompetitorAnalysis::STATUS_COMPLETED)
            ->get();

        if ($analyses->isEmpty()) {
            return ['error' => 'No completed analyses found for the specified competitors.'];
        }

        $comparison = [
            'competitors' => [],
            'comparison_matrix' => [],
        ];

        foreach ($analyses as $analysis) {
            $comparison['competitors'][$analysis->competitor_name] = [
                'strengths_count' => count($analysis->strengths ?? []),
                'weaknesses_count' => count($analysis->weaknesses ?? []),
                'confidence_score' => $analysis->confidence_score,
                'key_strengths' => array_slice($analysis->strengths ?? [], 0, 3),
                'key_weaknesses' => array_slice($analysis->weaknesses ?? [], 0, 3),
                'main_gaps' => $analysis->gaps_limitations['main_gaps'] ?? [],
            ];
        }

        return $comparison;
    }
}
