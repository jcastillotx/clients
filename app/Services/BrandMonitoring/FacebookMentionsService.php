<?php

namespace App\Services\BrandMonitoring;

use App\Models\BrandMention;
use App\Models\Client;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Facebook Brand Mentions Service
 *
 * Uses Facebook Graph API to monitor page mentions and tags.
 * Requires Page Access Token with pages_read_engagement permission.
 */
class FacebookMentionsService
{
    protected function getAccessToken(): ?string
    {
        return Setting::getValue('api.brand.facebook.access_token')
            ?: config('brand-monitoring.social.facebook.access_token');
    }

    protected function isEnabled(): bool
    {
        return (bool) (Setting::getValue('api.brand.facebook.enabled')
            ?? config('brand-monitoring.social.facebook.enabled', false));
    }

    /**
     * Get mentions and tags for a Facebook page
     */
    public function getMentions(Client $client, ?string $pageId = null): array
    {
        if (! $this->isEnabled()) {
            return ['skipped' => true, 'reason' => 'Facebook mentions disabled'];
        }

        $accessToken = $this->getAccessToken();
        if (empty($accessToken)) {
            return ['error' => 'Facebook access token not configured'];
        }

        try {
            $pageId = $pageId ?? ($client->meta['facebook_page_id'] ?? null);

            if (! $pageId) {
                // Try to get page ID from the access token
                $meResponse = Http::get('https://graph.facebook.com/v18.0/me/accounts', [
                    'access_token' => $accessToken,
                ]);

                if ($meResponse->successful()) {
                    $pages = $meResponse->json()['data'] ?? [];
                    if (! empty($pages)) {
                        $pageId = $pages[0]['id'];
                        $client->update([
                            'meta' => array_merge((array) $client->meta, [
                                'facebook_page_id' => $pageId,
                            ]),
                        ]);
                    }
                }
            }

            if (! $pageId) {
                return ['error' => 'Facebook page ID not found'];
            }

            $mentions = [];

            // Get tagged posts (posts where the page is tagged)
            $taggedResponse = Http::get("https://graph.facebook.com/v18.0/{$pageId}/tagged", [
                'access_token' => $accessToken,
                'fields' => 'id,message,from,created_time,permalink_url',
                'limit' => 50,
            ]);

            if ($taggedResponse->successful()) {
                $posts = $taggedResponse->json()['data'] ?? [];

                foreach ($posts as $post) {
                    $postedAt = isset($post['created_time'])
                        ? Carbon::parse($post['created_time'])
                        : now();

                    // Skip if older than 7 days
                    if ($postedAt->lt(now()->subDays(7))) {
                        continue;
                    }

                    $mention = $this->storeMention($client, [
                        'platform' => 'facebook',
                        'mention_text' => $post['message'] ?? '[No text content]',
                        'author' => $post['from']['name'] ?? 'Unknown',
                        'url' => $post['permalink_url'] ?? null,
                        'posted_at' => $postedAt,
                        'meta' => [
                            'post_id' => $post['id'] ?? null,
                            'from_id' => $post['from']['id'] ?? null,
                            'type' => 'tag',
                            'api' => 'facebook_graph',
                        ],
                    ]);

                    if ($mention) {
                        $mentions[] = $mention;
                    }
                }
            }

            // Get page reviews/recommendations
            $reviewsResponse = Http::get("https://graph.facebook.com/v18.0/{$pageId}/ratings", [
                'access_token' => $accessToken,
                'fields' => 'created_time,recommendation_type,review_text,reviewer',
                'limit' => 50,
            ]);

            if ($reviewsResponse->successful()) {
                $reviews = $reviewsResponse->json()['data'] ?? [];

                foreach ($reviews as $review) {
                    $postedAt = isset($review['created_time'])
                        ? Carbon::parse($review['created_time'])
                        : now();

                    $sentiment = ($review['recommendation_type'] ?? '') === 'positive' ? 'positive' : 'negative';

                    $mention = $this->storeMention($client, [
                        'platform' => 'facebook',
                        'mention_text' => $review['review_text'] ?? ($sentiment === 'positive' ? 'Recommends this business' : 'Does not recommend'),
                        'author' => $review['reviewer']['name'] ?? 'Anonymous',
                        'url' => null,
                        'posted_at' => $postedAt,
                        'sentiment' => $sentiment,
                        'meta' => [
                            'type' => 'review',
                            'recommendation' => $review['recommendation_type'] ?? null,
                            'api' => 'facebook_graph',
                        ],
                    ]);

                    if ($mention) {
                        $mentions[] = $mention;
                    }
                }
            }

            return [
                'success' => true,
                'page_id' => $pageId,
                'mentions_found' => count($mentions),
                'mentions' => $mentions,
            ];

        } catch (\Throwable $e) {
            Log::error('Facebook mentions API failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);

            return ['error' => $e->getMessage()];
        }
    }

    protected function storeMention(Client $client, array $data): ?BrandMention
    {
        // Deduplicate by post_id
        $postId = $data['meta']['post_id'] ?? null;
        if ($postId) {
            $existing = BrandMention::where('client_id', $client->id)
                ->where('platform', 'facebook')
                ->whereJsonContains('meta->post_id', $postId)
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
