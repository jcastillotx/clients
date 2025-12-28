<?php

namespace App\Services\Social;

use App\Models\Setting;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mastodon Publishing Service
 *
 * Supports any Mastodon-compatible instance (Mastodon, Pleroma, etc.).
 * Uses direct API access with access token.
 */
class MastodonService
{
    protected function getDefaultInstance(): string
    {
        return Setting::getValue('api.social.mastodon.instance')
            ?: config('services.mastodon.instance', 'https://mastodon.social');
    }

    protected function getAccessToken(): ?string
    {
        return Setting::getValue('api.social.mastodon.access_token')
            ?: config('services.mastodon.access_token');
    }

    /**
     * Connect a Mastodon account using instance URL and access token
     */
    public function connectAccount(int $clientId, string $instance, string $accessToken): ?SocialAccount
    {
        // Normalize instance URL
        $instance = rtrim($instance, '/');
        if (! str_starts_with($instance, 'http')) {
            $instance = 'https://' . $instance;
        }

        // Verify credentials
        $response = Http::withToken($accessToken)
            ->timeout(10)
            ->get("{$instance}/api/v1/accounts/verify_credentials");

        if (! $response->successful()) {
            Log::error('Mastodon account verification failed', [
                'instance' => $instance,
                'status' => $response->status(),
            ]);
            return null;
        }

        $profile = $response->json();

        return SocialAccount::updateOrCreate(
            [
                'client_id' => $clientId,
                'platform' => 'mastodon',
            ],
            [
                'platform_user_id' => $profile['id'] ?? null,
                'platform_username' => $profile['acct'] ?? $profile['username'] ?? null,
                'access_token' => $accessToken,
                'token_expires_at' => null, // Mastodon tokens don't expire
                'is_connected' => true,
                'meta' => [
                    'instance' => $instance,
                    'display_name' => $profile['display_name'] ?? null,
                    'avatar' => $profile['avatar'] ?? null,
                    'followers_count' => $profile['followers_count'] ?? 0,
                    'following_count' => $profile['following_count'] ?? 0,
                    'statuses_count' => $profile['statuses_count'] ?? 0,
                ],
            ]
        );
    }

    /**
     * Create a post (toot) on Mastodon
     */
    public function createPost(SocialAccount $account, string $content, ?array $mediaUrls = null): array
    {
        $instance = $account->meta['instance'] ?? $this->getDefaultInstance();
        $accessToken = $account->access_token;

        // Mastodon has a 500 character limit by default
        if (mb_strlen($content) > 500) {
            $content = mb_substr($content, 0, 497) . '...';
        }

        $postData = [
            'status' => $content,
            'visibility' => 'public',
        ];

        // Upload media if provided
        $mediaIds = [];
        if (! empty($mediaUrls) && is_array($mediaUrls)) {
            foreach (array_slice($mediaUrls, 0, 4) as $mediaUrl) {
                $mediaId = $this->uploadMedia($account, $mediaUrl);
                if ($mediaId) {
                    $mediaIds[] = $mediaId;
                }
            }
        }

        if (! empty($mediaIds)) {
            $postData['media_ids'] = $mediaIds;
        }

        $response = Http::withToken($accessToken)
            ->timeout(30)
            ->post("{$instance}/api/v1/statuses", $postData);

        if (! $response->successful()) {
            return [
                'success' => false,
                'error' => 'Failed to post: ' . $response->body(),
            ];
        }

        $result = $response->json();

        return [
            'success' => true,
            'post_id' => $result['id'] ?? null,
            'url' => $result['url'] ?? null,
        ];
    }

    /**
     * Upload media to Mastodon
     */
    protected function uploadMedia(SocialAccount $account, string $mediaUrl): ?string
    {
        $instance = $account->meta['instance'] ?? $this->getDefaultInstance();
        $accessToken = $account->access_token;

        try {
            // Download the media first
            $mediaContent = Http::timeout(30)->get($mediaUrl);
            if (! $mediaContent->successful()) {
                return null;
            }

            // Determine content type
            $contentType = $mediaContent->header('Content-Type') ?? 'image/jpeg';
            $extension = match ($contentType) {
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                'video/mp4' => 'mp4',
                default => 'jpg',
            };

            // Upload to Mastodon
            $response = Http::withToken($accessToken)
                ->timeout(60)
                ->attach('file', $mediaContent->body(), "media.{$extension}")
                ->post("{$instance}/api/v2/media");

            if ($response->successful()) {
                return $response->json()['id'] ?? null;
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning('Mastodon media upload failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get recent posts from the account
     */
    public function getRecentPosts(SocialAccount $account, int $limit = 20): array
    {
        $instance = $account->meta['instance'] ?? $this->getDefaultInstance();
        $accessToken = $account->access_token;
        $userId = $account->platform_user_id;

        $response = Http::withToken($accessToken)
            ->timeout(15)
            ->get("{$instance}/api/v1/accounts/{$userId}/statuses", [
                'limit' => $limit,
                'exclude_replies' => true,
                'exclude_reblogs' => true,
            ]);

        if (! $response->successful()) {
            return [];
        }

        return $response->json() ?? [];
    }
}
