<?php

namespace App\Services\BrandMonitoring;

use App\Models\BrandMention;
use App\Models\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Review Monitoring using free API tiers
 *
 * - Yelp Fusion API: FREE 5000 requests/day
 * - Google Places API: FREE $200 credit/month (~40k searches)
 */
class ReviewMonitoringService
{
    /**
     * Search for business on Yelp and get reviews
     * FREE tier: 5000 requests/day
     */
    public function getYelpReviews(Client $client, ?string $businessId = null): array
    {
        if (!config('brand-monitoring.reviews.yelp.enabled')) {
            return ['skipped' => true, 'reason' => 'Yelp API disabled'];
        }

        $apiKey = config('brand-monitoring.reviews.yelp.api_key');
        if (empty($apiKey)) {
            return ['error' => 'Yelp API key not configured'];
        }

        try {
            // Step 1: Search for business if no ID provided
            if (!$businessId) {
                $searchResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                ])->get('https://api.yelp.com/v3/businesses/search', [
                    'term' => $client->company_name,
                    'location' => $client->city . ', ' . $client->state,
                    'limit' => 1,
                ]);

                if (!$searchResponse->successful()) {
                    return ['error' => 'Yelp search failed: ' . $searchResponse->status()];
                }

                $businesses = $searchResponse->json()['businesses'] ?? [];
                if (empty($businesses)) {
                    return ['error' => 'Business not found on Yelp'];
                }

                $businessId = $businesses[0]['id'];

                // Store business ID in client meta for future use
                $client->update([
                    'meta' => array_merge((array) $client->meta, [
                        'yelp_business_id' => $businessId,
                    ]),
                ]);
            }

            // Step 2: Get reviews for the business
            $reviewsResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get("https://api.yelp.com/v3/businesses/{$businessId}/reviews", [
                'limit' => 50, // Max is 50
                'sort_by' => 'newest',
            ]);

            if (!$reviewsResponse->successful()) {
                return ['error' => 'Yelp reviews fetch failed: ' . $reviewsResponse->status()];
            }

            $data = $reviewsResponse->json();
            $reviews = $data['reviews'] ?? [];
            $mentions = [];

            foreach ($reviews as $review) {
                $rating = (int) ($review['rating'] ?? 0);

                // Convert rating to sentiment
                $sentiment = $this->ratingSentiment($rating);

                $mention = $this->storeMention($client, [
                    'platform' => 'yelp',
                    'mention_text' => $review['text'] ?? '',
                    'author' => $review['user']['name'] ?? 'Anonymous',
                    'url' => $review['url'] ?? null,
                    'posted_at' => isset($review['time_created'])
                        ? Carbon::parse($review['time_created'])
                        : now(),
                    'sentiment' => $sentiment,
                    'meta' => [
                        'rating' => $rating,
                        'user_id' => $review['user']['id'] ?? null,
                        'user_image' => $review['user']['image_url'] ?? null,
                        'api' => 'yelp',
                    ],
                ]);

                if ($mention) {
                    $mentions[] = $mention;
                }
            }

            return [
                'success' => true,
                'business_id' => $businessId,
                'total_reviews' => $data['total'] ?? 0,
                'mentions_found' => count($mentions),
                'mentions' => $mentions,
            ];

        } catch (\Throwable $e) {
            Log::error('Yelp API failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get Google Places reviews
     * FREE: $200/month credit (~40k place searches or ~800k place details)
     */
    public function getGooglePlacesReviews(Client $client, ?string $placeId = null): array
    {
        if (!config('brand-monitoring.reviews.google_places.enabled')) {
            return ['skipped' => true, 'reason' => 'Google Places disabled'];
        }

        $apiKey = config('brand-monitoring.reviews.google_places.api_key');
        if (empty($apiKey)) {
            return ['error' => 'Google Places API key not configured'];
        }

        try {
            // Step 1: Find Place if no ID provided
            if (!$placeId) {
                $searchResponse = Http::get('https://maps.googleapis.com/maps/api/place/findplacefromtext/json', [
                    'input' => $client->company_name,
                    'inputtype' => 'textquery',
                    'fields' => 'place_id,name',
                    'key' => $apiKey,
                ]);

                if (!$searchResponse->successful()) {
                    return ['error' => 'Google Places search failed: ' . $searchResponse->status()];
                }

                $candidates = $searchResponse->json()['candidates'] ?? [];
                if (empty($candidates)) {
                    return ['error' => 'Business not found on Google Places'];
                }

                $placeId = $candidates[0]['place_id'];

                // Store place ID in client meta
                $client->update([
                    'meta' => array_merge((array) $client->meta, [
                        'google_place_id' => $placeId,
                    ]),
                ]);
            }

            // Step 2: Get Place Details including reviews
            $detailsResponse = Http::get('https://maps.googleapis.com/maps/api/place/details/json', [
                'place_id' => $placeId,
                'fields' => 'name,rating,reviews',
                'key' => $apiKey,
            ]);

            if (!$detailsResponse->successful()) {
                return ['error' => 'Google Places details failed: ' . $detailsResponse->status()];
            }

            $result = $detailsResponse->json()['result'] ?? [];
            $reviews = $result['reviews'] ?? [];
            $mentions = [];

            foreach ($reviews as $review) {
                $rating = (int) ($review['rating'] ?? 0);
                $sentiment = $this->ratingSentiment($rating);

                $mention = $this->storeMention($client, [
                    'platform' => 'google',
                    'mention_text' => $review['text'] ?? '',
                    'author' => $review['author_name'] ?? 'Anonymous',
                    'url' => $review['author_url'] ?? null,
                    'posted_at' => isset($review['time'])
                        ? Carbon::createFromTimestamp($review['time'])
                        : now(),
                    'sentiment' => $sentiment,
                    'meta' => [
                        'rating' => $rating,
                        'profile_photo' => $review['profile_photo_url'] ?? null,
                        'api' => 'google_places',
                    ],
                ]);

                if ($mention) {
                    $mentions[] = $mention;
                }
            }

            return [
                'success' => true,
                'place_id' => $placeId,
                'overall_rating' => $result['rating'] ?? null,
                'mentions_found' => count($mentions),
                'mentions' => $mentions,
            ];

        } catch (\Throwable $e) {
            Log::error('Google Places API failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Convert star rating to sentiment
     */
    protected function ratingSentiment(int $rating): string
    {
        return match(true) {
            $rating >= 4 => 'positive',
            $rating == 3 => 'neutral',
            default => 'negative',
        };
    }

    /**
     * Store mention (deduplicate by URL or text hash)
     */
    protected function storeMention(Client $client, array $data): ?BrandMention
    {
        // Deduplicate by URL or text hash
        if (!empty($data['url'])) {
            $existing = BrandMention::where('client_id', $client->id)
                ->where('url', $data['url'])
                ->first();

            if ($existing) {
                return null;
            }
        } else {
            // Use text hash for deduplication if no URL
            $textHash = md5($data['mention_text']);
            $existing = BrandMention::where('client_id', $client->id)
                ->where('platform', $data['platform'])
                ->whereRaw("MD5(mention_text) = ?", [$textHash])
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
