<?php

namespace App\Services\Social;

use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BlueskyService
{
    protected string $pdsHost = 'https://bsky.social';

    /**
     * Connect a Bluesky account using handle and app password
     */
    public function connect(int $clientId, string $handle, string $appPassword): SocialAccount
    {
        // Create session with Bluesky
        $response = Http::post("{$this->pdsHost}/xrpc/com.atproto.server.createSession", [
            'identifier' => $handle,
            'password' => $appPassword,
        ]);

        if ($response->failed()) {
            $error = $response->json('error') ?? 'Unknown error';
            throw new \Exception("Bluesky authentication failed: {$error}");
        }

        $data = $response->json();

        // Get profile info
        $profileResponse = Http::withToken($data['accessJwt'])
            ->get("{$this->pdsHost}/xrpc/app.bsky.actor.getProfile", [
                'actor' => $data['did'],
            ]);

        $profile = $profileResponse->json();

        // Create or update social account
        $account = SocialAccount::updateOrCreate(
            [
                'client_id' => $clientId,
                'platform' => 'bluesky',
            ],
            [
                'account_name' => $profile['displayName'] ?? $handle,
                'account_id' => $data['did'],
                'account_username' => $handle,
                'profile_picture_url' => $profile['avatar'] ?? null,
                'access_token' => $data['accessJwt'],
                'refresh_token' => $data['refreshJwt'],
                'is_connected' => true,
                'connected_at' => now(),
                'meta' => [
                    'did' => $data['did'],
                    'handle' => $data['handle'],
                    'app_password' => $appPassword, // Store for re-auth
                ],
            ]
        );

        Log::info('Bluesky account connected', [
            'client_id' => $clientId,
            'handle' => $handle,
        ]);

        return $account;
    }

    /**
     * Refresh the session tokens (alias for refreshToken)
     */
    public function refreshToken(SocialAccount $account): bool
    {
        return $this->refreshSession($account);
    }

    /**
     * Refresh the session tokens
     */
    public function refreshSession(SocialAccount $account): bool
    {
        try {
            $response = Http::post("{$this->pdsHost}/xrpc/com.atproto.server.refreshSession", [], [
                'Authorization' => "Bearer {$account->refresh_token}",
            ]);

            if ($response->failed()) {
                // Try re-authenticating with app password
                $meta = $account->meta ?? [];
                if (!empty($meta['app_password'])) {
                    $this->connect($account->client_id, $meta['handle'], $meta['app_password']);
                    return true;
                }
                return false;
            }

            $data = $response->json();

            $account->update([
                'access_token' => $data['accessJwt'],
                'refresh_token' => $data['refreshJwt'],
                'last_token_refresh' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Bluesky session refresh failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Create a post on Bluesky
     */
    public function createPost(SocialAccount $account, string $text, ?array $images = null): array
    {
        // Ensure we have a valid session
        $this->ensureValidSession($account);

        $record = [
            '$type' => 'app.bsky.feed.post',
            'text' => $text,
            'createdAt' => now()->toIso8601String(),
        ];

        // Handle images if provided
        if (!empty($images)) {
            $embedImages = [];
            foreach (array_slice($images, 0, 4) as $imageUrl) { // Max 4 images
                $blob = $this->uploadImage($account, $imageUrl);
                if ($blob) {
                    $embedImages[] = [
                        'alt' => '',
                        'image' => $blob,
                    ];
                }
            }

            if (!empty($embedImages)) {
                $record['embed'] = [
                    '$type' => 'app.bsky.embed.images',
                    'images' => $embedImages,
                ];
            }
        }

        // Parse facets (mentions, links, hashtags)
        $facets = $this->parseFacets($text);
        if (!empty($facets)) {
            $record['facets'] = $facets;
        }

        $response = Http::withToken($account->access_token)
            ->post("{$this->pdsHost}/xrpc/com.atproto.repo.createRecord", [
                'repo' => $account->meta['did'] ?? $account->account_id,
                'collection' => 'app.bsky.feed.post',
                'record' => $record,
            ]);

        if ($response->failed()) {
            $error = $response->json('message') ?? $response->body();
            throw new \Exception("Failed to post to Bluesky: {$error}");
        }

        $result = $response->json();

        return [
            'success' => true,
            'post_id' => $result['uri'] ?? null,
            'cid' => $result['cid'] ?? null,
            'url' => $this->buildPostUrl($account->account_username, $result['uri'] ?? ''),
        ];
    }

    /**
     * Upload an image to Bluesky
     */
    protected function uploadImage(SocialAccount $account, string $imageUrl): ?array
    {
        try {
            // Download the image
            $imageResponse = Http::get($imageUrl);
            if ($imageResponse->failed()) {
                return null;
            }

            $imageData = $imageResponse->body();
            $mimeType = $imageResponse->header('Content-Type') ?? 'image/jpeg';

            // Upload to Bluesky
            $response = Http::withToken($account->access_token)
                ->withHeaders(['Content-Type' => $mimeType])
                ->withBody($imageData, $mimeType)
                ->post("{$this->pdsHost}/xrpc/com.atproto.repo.uploadBlob");

            if ($response->failed()) {
                return null;
            }

            return $response->json('blob');
        } catch (\Exception $e) {
            Log::warning('Failed to upload image to Bluesky', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Parse text for mentions, links, and hashtags (facets)
     */
    protected function parseFacets(string $text): array
    {
        $facets = [];

        // Parse hashtags
        preg_match_all('/#(\w+)/u', $text, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $match) {
            $facets[] = [
                'index' => [
                    'byteStart' => $match[1],
                    'byteEnd' => $match[1] + strlen($match[0]),
                ],
                'features' => [
                    [
                        '$type' => 'app.bsky.richtext.facet#tag',
                        'tag' => ltrim($match[0], '#'),
                    ],
                ],
            ];
        }

        // Parse URLs
        preg_match_all('/https?:\/\/[^\s]+/u', $text, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $match) {
            $facets[] = [
                'index' => [
                    'byteStart' => $match[1],
                    'byteEnd' => $match[1] + strlen($match[0]),
                ],
                'features' => [
                    [
                        '$type' => 'app.bsky.richtext.facet#link',
                        'uri' => $match[0],
                    ],
                ],
            ];
        }

        return $facets;
    }

    /**
     * Build the URL for a post
     */
    protected function buildPostUrl(string $handle, string $uri): string
    {
        // URI format: at://did:plc:xxx/app.bsky.feed.post/rkey
        if (preg_match('/\/app\.bsky\.feed\.post\/([^\/]+)$/', $uri, $matches)) {
            return "https://bsky.app/profile/{$handle}/post/{$matches[1]}";
        }
        return "https://bsky.app/profile/{$handle}";
    }

    /**
     * Ensure we have a valid session
     */
    protected function ensureValidSession(SocialAccount $account): void
    {
        // Bluesky tokens expire quickly, always try to refresh
        if ($account->last_token_refresh === null || $account->last_token_refresh->diffInMinutes(now()) > 5) {
            $this->refreshSession($account);
            $account->refresh();
        }
    }

    /**
     * Disconnect the account
     */
    public function disconnect(SocialAccount $account): bool
    {
        try {
            // Delete the session
            Http::withToken($account->access_token)
                ->post("{$this->pdsHost}/xrpc/com.atproto.server.deleteSession");

            $account->update([
                'is_connected' => false,
                'access_token' => null,
                'refresh_token' => null,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Bluesky disconnect failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get user profile
     */
    public function getProfile(SocialAccount $account): array
    {
        $this->ensureValidSession($account);

        $response = Http::withToken($account->access_token)
            ->get("{$this->pdsHost}/xrpc/app.bsky.actor.getProfile", [
                'actor' => $account->meta['did'] ?? $account->account_id,
            ]);

        if ($response->failed()) {
            throw new \Exception('Failed to fetch Bluesky profile');
        }

        return $response->json();
    }
}
