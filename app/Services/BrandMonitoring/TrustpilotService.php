<?php

namespace App\Services\BrandMonitoring;

use App\Models\BrandMention;
use App\Models\Client;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Trustpilot Review Monitoring Service
 *
 * Uses Trustpilot Business Unit API for review monitoring.
 * Requires API key and Business Unit ID.
 */
class TrustpilotService
{
    protected function getApiKey(): ?string
    {
        return Setting::getValue('api.brand.trustpilot.api_key')
            ?: config('brand-monitoring.reviews.trustpilot.api_key');
    }

    protected function getApiSecret(): ?string
    {
        return Setting::getValue('api.brand.trustpilot.api_secret')
            ?: config('brand-monitoring.reviews.trustpilot.api_secret');
    }

    protected function isEnabled(): bool
    {
        return (bool) (Setting::getValue('api.brand.trustpilot.enabled')
            ?? config('brand-monitoring.reviews.trustpilot.enabled', false));
    }

    /**
     * Get reviews for a business on Trustpilot
     */
    public function getReviews(Client $client, ?string $businessUnitId = null): array
    {
        if (! $this->isEnabled()) {
            return ['skipped' => true, 'reason' => 'Trustpilot API disabled'];
        }

        $apiKey = $this->getApiKey();
        if (empty($apiKey)) {
            return ['error' => 'Trustpilot API key not configured'];
        }

        try {
            // Get business unit ID from client meta or search for it
            $businessUnitId = $businessUnitId
                ?? ($client->meta['trustpilot_business_unit_id'] ?? null);

            if (! $businessUnitId) {
                // Search for the business
                $searchResult = $this->searchBusiness($client->company_name);
                if (isset($searchResult['error'])) {
                    return $searchResult;
                }
                $businessUnitId = $searchResult['business_unit_id'] ?? null;

                if ($businessUnitId) {
                    // Store for future use
                    $client->update([
                        'meta' => array_merge((array) $client->meta, [
                            'trustpilot_business_unit_id' => $businessUnitId,
                        ]),
                    ]);
                }
            }

            if (! $businessUnitId) {
                return ['error' => 'Business not found on Trustpilot'];
            }

            // Fetch reviews
            $response = Http::withHeaders([
                'apikey' => $apiKey,
            ])->timeout(15)->get("https://api.trustpilot.com/v1/business-units/{$businessUnitId}/reviews", [
                'perPage' => 50,
                'orderBy' => 'createdat.desc',
            ]);

            if (! $response->successful()) {
                return ['error' => 'Trustpilot reviews fetch failed: ' . $response->status()];
            }

            $data = $response->json();
            $reviews = $data['reviews'] ?? [];
            $mentions = [];

            foreach ($reviews as $review) {
                $rating = (int) ($review['stars'] ?? 0);
                $sentiment = $this->ratingSentiment($rating);

                $mention = $this->storeMention($client, [
                    'platform' => 'trustpilot',
                    'mention_text' => ($review['title'] ?? '') . "\n\n" . ($review['text'] ?? ''),
                    'author' => $review['consumer']['displayName'] ?? 'Anonymous',
                    'url' => $review['links']['find']?->href ?? null,
                    'posted_at' => isset($review['createdAt'])
                        ? Carbon::parse($review['createdAt'])
                        : now(),
                    'sentiment' => $sentiment,
                    'meta' => [
                        'rating' => $rating,
                        'review_id' => $review['id'] ?? null,
                        'api' => 'trustpilot',
                    ],
                ]);

                if ($mention) {
                    $mentions[] = $mention;
                }
            }

            return [
                'success' => true,
                'business_unit_id' => $businessUnitId,
                'total_reviews' => $data['totalCount'] ?? count($reviews),
                'mentions_found' => count($mentions),
                'mentions' => $mentions,
            ];

        } catch (\Throwable $e) {
            Log::error('Trustpilot API failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Search for a business on Trustpilot
     */
    protected function searchBusiness(string $name): array
    {
        $apiKey = $this->getApiKey();

        $response = Http::withHeaders([
            'apikey' => $apiKey,
        ])->timeout(10)->get('https://api.trustpilot.com/v1/business-units/find', [
            'name' => $name,
        ]);

        if (! $response->successful()) {
            return ['error' => 'Trustpilot search failed: ' . $response->status()];
        }

        $units = $response->json()['units'] ?? [];
        if (empty($units)) {
            return ['error' => 'Business not found'];
        }

        return [
            'business_unit_id' => $units[0]['id'] ?? null,
            'name' => $units[0]['displayName'] ?? null,
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
        // Deduplicate by review_id or URL
        $reviewId = $data['meta']['review_id'] ?? null;
        if ($reviewId) {
            $existing = BrandMention::where('client_id', $client->id)
                ->where('platform', 'trustpilot')
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
