<?php

namespace App\Services\BrandMonitoring;

use App\Models\BrandMention;
use App\Models\Client;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * G2 Crowd Review Monitoring Service
 *
 * G2 API for B2B software reviews.
 */
class G2Service
{
    protected function getApiKey(): ?string
    {
        return Setting::getValue('api.brand.g2.api_key')
            ?: config('brand-monitoring.reviews.g2.api_key');
    }

    protected function isEnabled(): bool
    {
        return (bool) (Setting::getValue('api.brand.g2.enabled')
            ?? config('brand-monitoring.reviews.g2.enabled', false));
    }

    /**
     * Get reviews for a product on G2
     */
    public function getReviews(Client $client, ?string $productId = null): array
    {
        if (! $this->isEnabled()) {
            return ['skipped' => true, 'reason' => 'G2 API disabled'];
        }

        $apiKey = $this->getApiKey();
        if (empty($apiKey)) {
            return ['error' => 'G2 API key not configured'];
        }

        try {
            // Get product ID from client meta or search for it
            $productId = $productId ?? ($client->meta['g2_product_id'] ?? null);

            if (! $productId) {
                // Search for the product
                $searchResult = $this->searchProduct($client->company_name);
                if (isset($searchResult['error'])) {
                    return $searchResult;
                }
                $productId = $searchResult['product_id'] ?? null;

                if ($productId) {
                    $client->update([
                        'meta' => array_merge((array) $client->meta, [
                            'g2_product_id' => $productId,
                        ]),
                    ]);
                }
            }

            if (! $productId) {
                return ['error' => 'Product not found on G2'];
            }

            // Fetch reviews
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(15)->get("https://data.g2.com/api/v1/products/{$productId}/reviews", [
                'page[size]' => 50,
                'sort' => '-submitted_at',
            ]);

            if (! $response->successful()) {
                return ['error' => 'G2 reviews fetch failed: ' . $response->status()];
            }

            $data = $response->json();
            $reviews = $data['data'] ?? [];
            $mentions = [];

            foreach ($reviews as $review) {
                $attributes = $review['attributes'] ?? [];
                $rating = (int) ($attributes['star_rating'] ?? 0);
                $sentiment = $this->ratingSentiment($rating);

                $mention = $this->storeMention($client, [
                    'platform' => 'g2',
                    'mention_text' => ($attributes['title'] ?? '') . "\n\n" .
                        ($attributes['comment_answers']['love'] ?? '') . "\n" .
                        ($attributes['comment_answers']['hate'] ?? ''),
                    'author' => $attributes['user_name'] ?? 'Anonymous',
                    'url' => $attributes['url'] ?? null,
                    'posted_at' => isset($attributes['submitted_at'])
                        ? Carbon::parse($attributes['submitted_at'])
                        : now(),
                    'sentiment' => $sentiment,
                    'meta' => [
                        'rating' => $rating,
                        'review_id' => $review['id'] ?? null,
                        'verified' => $attributes['is_verified'] ?? false,
                        'api' => 'g2',
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
            Log::error('G2 API failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Search for a product on G2
     */
    protected function searchProduct(string $name): array
    {
        $apiKey = $this->getApiKey();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->timeout(10)->get('https://data.g2.com/api/v1/products', [
            'filter[name]' => $name,
            'page[size]' => 1,
        ]);

        if (! $response->successful()) {
            return ['error' => 'G2 search failed: ' . $response->status()];
        }

        $products = $response->json()['data'] ?? [];
        if (empty($products)) {
            return ['error' => 'Product not found'];
        }

        return [
            'product_id' => $products[0]['id'] ?? null,
            'name' => $products[0]['attributes']['name'] ?? null,
        ];
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
                ->where('platform', 'g2')
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
