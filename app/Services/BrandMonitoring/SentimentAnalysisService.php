<?php

namespace App\Services\BrandMonitoring;

use App\Models\BrandMention;
use App\Models\User;
use App\Notifications\NegativeBrandMentionAlert;
use App\Services\AI\AIProviderManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

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
    ) {}

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
     * @param  array<BrandMention>  $mentions
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

            // Update mentions with sentiment and send alerts for negative ones
            $analyzed = 0;
            $negativeMentions = [];
            
            foreach ($response as $item) {
                $mentionId = $item['mention_id'] ?? null;
                $sentiment = $item['sentiment'] ?? 'neutral';
                $normalizedSentiment = $this->normalizeSentiment($sentiment);

                if ($mentionId) {
                    BrandMention::where('id', $mentionId)->update([
                        'sentiment' => $normalizedSentiment,
                    ]);
                    $analyzed++;
                    
                    // Track negative mentions for alerts
                    if ($normalizedSentiment === 'negative') {
                        $negativeMentions[] = $mentionId;
                    }
                }
            }
            
            // Send alerts for negative mentions
            $alertsSent = 0;
            if (!empty($negativeMentions) && config('brand-monitoring.alerts.negative_mentions.enabled', true)) {
                $alertsSent = $this->sendNegativeMentionAlerts($negativeMentions);
            }

            return [
                'success' => true,
                'analyzed' => $analyzed,
                'total' => count($mentions),
                'negative_found' => count($negativeMentions),
                'alerts_sent' => $alertsSent,
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

        return match ($sentiment) {
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

    /**
     * Send alerts for negative brand mentions
     *
     * @param array<int> $mentionIds
     * @return int Number of alerts sent
     */
    protected function sendNegativeMentionAlerts(array $mentionIds): int
    {
        $alertsSent = 0;
        
        $mentions = BrandMention::with('client')
            ->whereIn('id', $mentionIds)
            ->get();
        
        foreach ($mentions as $mention) {
            if (!$mention->client) {
                continue;
            }
            
            $clientName = $mention->client->company_name ?? 'Unknown Client';
            
            // Get users to notify: admins + staff assigned to this client
            $usersToNotify = User::query()
                ->where('is_active', true)
                ->where(function ($q) use ($mention) {
                    $q->whereHas('roles', function ($r) {
                        $r->whereIn('name', ['super_admin', 'admin']);
                    })
                    ->orWhereHas('assignedClients', function ($c) use ($mention) {
                        $c->where('clients.id', $mention->client_id);
                    });
                })
                ->get();
            
            if ($usersToNotify->isEmpty()) {
                continue;
            }
            
            try {
                Notification::send($usersToNotify, new NegativeBrandMentionAlert($mention, $clientName));
                $alertsSent += $usersToNotify->count();
                
                Log::info('Negative brand mention alert sent', [
                    'mention_id' => $mention->id,
                    'client_id' => $mention->client_id,
                    'platform' => $mention->platform,
                    'users_notified' => $usersToNotify->count(),
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to send negative mention alert', [
                    'mention_id' => $mention->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $alertsSent;
    }
}
