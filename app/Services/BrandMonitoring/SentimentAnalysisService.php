<?php

namespace App\Services\BrandMonitoring;

use App\Models\BrandMention;
use App\Services\AI\AIProviderManager;
use Illuminate\Support\Facades\Log;

/**
 * Batch Sentiment Analysis using existing AI providers
 *
 * Uses GPT-4o-mini by default ($0.00015/1K input, $0.0006/1K output)
 * Analyzes 50 mentions at once to save costs
 */
class SentimentAnalysisService
{
    public function __construct(
        private readonly AIProviderManager $ai
    ) {
    }

    /**
     * Analyze sentiment for all unanalyzed mentions in batch
     */
    public function analyzePendingSentiments(?int $limit = null): array
    {
        $batchSize = $limit ?? config('brand-monitoring.sentiment.batch_size', 50);

        // Get mentions without sentiment
        $mentions = BrandMention::whereNull('sentiment')
            ->orderBy('posted_at', 'desc')
            ->limit($batchSize)
            ->get();

        if ($mentions->isEmpty()) {
            return [
                'analyzed' => 0,
                'message' => 'No pending sentiments',
            ];
        }

        return $this->analyzeBatch($mentions->all());
    }

    /**
     * Analyze sentiment for a batch of mentions
     *
     * @param array<BrandMention> $mentions
     */
    public function analyzeBatch(array $mentions): array
    {
        if (empty($mentions)) {
            return ['analyzed' => 0];
        }

        try {
            // Build batch payload
            $items = [];
            foreach ($mentions as $i => $mention) {
                $items[] = [
                    'id' => $i,
                    'mention_id' => $mention->id,
                    'platform' => $mention->platform,
                    'text' => mb_substr($mention->mention_text, 0, 500), // Limit to save tokens
                    'author' => $mention->author,
                ];
            }

            $prompt = $this->buildBatchPrompt($items);

            $provider = config('brand-monitoring.sentiment.provider', 'openai');
            $model = config('brand-monitoring.sentiment.model', 'gpt-4o-mini');

            $result = $this->ai->withFallback($provider, function ($ai) use ($prompt, $model) {
                return $ai->chat([
                    ['role' => 'system', 'content' => 'You are a sentiment analysis expert. Analyze mentions and return strict JSON only.'],
                    ['role' => 'user', 'content' => $prompt],
                ], [
                    'model' => $model,
                    'max_tokens' => 2000,
                    'temperature' => 0.3,
                ]);
            }, taskType: 'sentiment_analysis');

            $response = $this->parseResponse($result['text'] ?? '');

            // Update mentions with sentiment
            $analyzed = 0;
            foreach ($response as $item) {
                $mentionId = $item['mention_id'] ?? null;
                $sentiment = $item['sentiment'] ?? 'neutral';

                if ($mentionId) {
                    BrandMention::where('id', $mentionId)->update([
                        'sentiment' => $this->normalizeSentiment($sentiment),
                    ]);
                    $analyzed++;
                }
            }

            return [
                'success' => true,
                'analyzed' => $analyzed,
                'total' => count($mentions),
                'cost_estimate_usd' => $this->estimateCost($result),
            ];

        } catch (\Throwable $e) {
            Log::error('Batch sentiment analysis failed', [
                'error' => $e->getMessage(),
                'batch_size' => count($mentions),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'analyzed' => 0,
            ];
        }
    }

    /**
     * Build batch sentiment analysis prompt
     */
    protected function buildBatchPrompt(array $items): string
    {
        $json = json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
Analyze the sentiment of these brand mentions. For each item, classify sentiment as:
- "positive" (praise, recommendation, satisfaction)
- "neutral" (factual, informational, mixed)
- "negative" (complaint, criticism, dissatisfaction)

Input:
{$json}

Output strict JSON array with format:
[
  {"mention_id": 123, "sentiment": "positive", "confidence": 0.95},
  ...
]

No explanations, only JSON array.
PROMPT;
    }

    /**
     * Parse AI response to extract sentiments
     */
    protected function parseResponse(string $text): array
    {
        // Remove markdown code blocks if present
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text) ?? $text;
        $text = preg_replace('/\s*```$/', '', $text) ?? $text;

        $decoded = json_decode($text, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Normalize sentiment to database enum
     */
    protected function normalizeSentiment(string $sentiment): string
    {
        $sentiment = strtolower(trim($sentiment));

        return match($sentiment) {
            'positive', 'pos' => 'positive',
            'negative', 'neg' => 'negative',
            default => 'neutral',
        };
    }

    /**
     * Estimate cost based on token usage
     */
    protected function estimateCost(array $result): float
    {
        $inputTokens = $result['tokens']['input'] ?? 0;
        $outputTokens = $result['tokens']['output'] ?? 0;

        // GPT-4o-mini pricing
        $costPer1kInput = 0.00015;
        $costPer1kOutput = 0.0006;

        return round(
            (($inputTokens / 1000) * $costPer1kInput) +
            (($outputTokens / 1000) * $costPer1kOutput),
            6
        );
    }

    /**
     * Analyze single mention (for real-time use)
     */
    public function analyzeSingle(BrandMention $mention): array
    {
        return $this->analyzeBatch([$mention]);
    }
}
