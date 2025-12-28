<?php

namespace App\Services\BrandMonitoring;

use App\Models\BrandMention;
use App\Models\Client;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Capterra Review Monitoring Service
 *
 * Capterra/GetApp/Software Advice reviews via Gartner Digital Markets API.
 */
class CapterraService
{
    protected function getApiKey(): ?string
    {
        return Setting::getValue('api.brand.capterra.api_key')
            ?: config('brand-monitoring.reviews.capterra.api_key');
    }

    protected function isEnabled(): bool
    {
        return (bool) (Setting::getValue('api.brand.capterra.enabled')
            ?? config('brand-monitoring.reviews.capterra.enabled', false));
    }

    /**
     * Get reviews for a product on Capterra
     */
    public function getReviews(Client $client, ?string $productId = null): array
    {
        if (! $this->isEnabled()) {
            return ['skipped' => true, 'reason' => 'Capterra API disabled'];
        }

        $apiKey = $this->getApiKey();
        if (empty($apiKey)) {
            return ['error' => 'Capterra API key not configured'];
        }

        try {
            $productId = $productId ?? ($client->meta['capterra_product_id'] ?? null);

            if (! $productId) {
                // Capterra requires product ID from their listings
                return ['error' => 'Capterra product ID not configured. Set it in client settings.'];
            }

            // Fetch reviews via Gartner Digital Markets API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
            ])->timeout(15)->get("https://api.capterra.com/v1/products/{$productId}/reviews", [
                'limit' => 50,
                'sort_by' => 'created_at',
                'sort_order' => 'desc',
            ]);

            if (! $response->successful()) {
                return ['error' => 'Capterra reviews fetch failed: ' . $response->status()];
            }

            $data = $response->json();
            $reviews = $data['reviews'] ?? [];
            $mentions = [];

            foreach ($reviews as $review) {
                $rating = (int) ($review['overall_rating'] ?? 0);
                $sentiment = $this->ratingSentiment($rating);

                $mention = $this->storeMention($client, [
                    'platform' => 'capterra',
                    'mention_text' => ($review['title'] ?? '') . "\n\n" .
                        "Pros: " . ($review['pros'] ?? '') . "\n" .
                        "Cons: " . ($review['cons'] ?? ''),
                    'author' => $review['reviewer_name'] ?? 'Anonymous',
                    'url' => $review['review_url'] ?? null,
                    'posted_at' => isset($review['created_at'])
                        ? Carbon::parse($review['created_at'])
                        : now(),
                    'sentiment' => $sentiment,
                    'meta' => [
                        'rating' => $rating,
                        'review_id' => $review['id'] ?? null,
                        'source' => $review['source'] ?? 'capterra', // capterra, getapp, software_advice
                        'verified' => $review['verified_reviewer'] ?? false,
                        'api' => 'capterra',
                    ],
                ]);

                if ($mention) {
                    $mentions[] = $mention;
                }
            }

            return [
                'success' => true,
                'product_id' => $productId,
                'mentions_found' => count($mentions),
                'mentions' => $mentions,
            ];

        } catch (\Throwable $e) {
            Log::error('Capterra API failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);

            return ['error' => $e->getMessage()];
        }
    }

    protected function ratingSentiment(int $rating): string
    {
        return match (true) {
            $rating >= 4 => 'positive',
            $rating == 3 => 'neutral',
            default => 'negative',
        };
    }

    protected function storeMention(Client $client, array $data): ?BrandMention
    {
        $reviewId = $data['meta']['review_id'] ?? null;
        if ($reviewId) {
            $existing = BrandMention::where('client_id', $client->id)
                ->where('platform', 'capterra')
                ->whereJsonContains('meta->review_id', $reviewId)
                ->first();

            if ($existing) {
                return null;
            }
        }

        return BrandMention::create([
            'client_id' => $client->id,
            'platform' => $data['platform'],
            'mention_text' => $data['mention_text'],
            'author' => $data['author'] ?? null,
            'url' => $data['url'] ?? null,
            'posted_at' => $data['posted_at'] ?? now(),
            'sentiment' => $data['sentiment'] ?? null,
            'meta' => $data['meta'] ?? null,
        ]);
    }
}
