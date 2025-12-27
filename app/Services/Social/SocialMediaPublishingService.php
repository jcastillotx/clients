<?php

namespace App\Services\Social;

use App\Models\ContentCalendarItem;
use App\Models\SocialAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocialMediaPublishingService
{
    /**
     * Publish a scheduled post to its platform
     */
    public function publishPost(ContentCalendarItem $post): bool
    {
        // Verify post is ready to publish
        if (!$this->canPublish($post)) {
            Log::warning('Post cannot be published', [
                'post_id' => $post->id,
                'status' => $post->status,
                'scheduled_for' => $post->scheduled_for,
            ]);
            return false;
        }

        // Get the connected social account
        $account = SocialAccount::where('client_id', $post->client_id)
            ->where('platform', $post->platform)
            ->where('is_connected', true)
            ->first();

        if (!$account) {
            $post->markAsFailed('No connected account found for platform: ' . $post->platform);
            return false;
        }

        // Refresh token if needed
        if ($account->needsTokenRefresh()) {
            $this->refreshAccountToken($account);
            $account->refresh(); // Reload from database
        }

        // Verify token is still valid
        if ($account->isTokenExpired()) {
            $post->markAsFailed('Account token is expired and could not be refreshed');
            return false;
        }

        try {
            // Publish to the platform
            $result = match($post->platform) {
                'facebook' => $this->publishToFacebook($post, $account),
                'linkedin' => $this->publishToLinkedIn($post, $account),
                default => throw new \Exception('Unsupported platform: ' . $post->platform),
            };

            if ($result['success']) {
                $post->markAsPublished();
                $account->update(['last_post_at' => now()]);

                // Store platform post ID in meta
                $meta = $post->meta ?? [];
                $meta['platform_post_id'] = $result['post_id'] ?? null;
                $meta['published_url'] = $result['url'] ?? null;
                $post->update(['meta' => $meta]);

                Log::info('Post published successfully', [
                    'post_id' => $post->id,
                    'platform' => $post->platform,
                    'platform_post_id' => $result['post_id'] ?? null,
                ]);

                return true;
            } else {
                $post->markAsFailed($result['error'] ?? 'Unknown error');
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Post publishing failed', [
                'post_id' => $post->id,
                'platform' => $post->platform,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $post->markAsFailed('Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a post can be published
     */
    protected function canPublish(ContentCalendarItem $post): bool
    {
        // Must be scheduled or approved status
        if (!in_array($post->status, ['scheduled', 'approved'])) {
            return false;
        }

        // Must have a scheduled time that has passed
        if (!$post->scheduled_for || $post->scheduled_for->isFuture()) {
            return false;
        }

        // Must have content
        if (empty($post->content_text)) {
            return false;
        }

        return true;
    }

    /**
     * Refresh account token if needed
     */
    protected function refreshAccountToken(SocialAccount $account): bool
    {
        try {
            $service = match($account->platform) {
                'facebook' => new FacebookOAuthService(),
                'linkedin' => new LinkedInOAuthService(),
                default => null,
            };

            if ($service && $service->refreshToken($account)) {
                Log::info('Account token refreshed during publishing', [
                    'account_id' => $account->id,
                    'platform' => $account->platform,
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to refresh account token', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Publish post to Facebook
     */
    protected function publishToFacebook(ContentCalendarItem $post, SocialAccount $account): array
    {
        // First, get the user's pages
        $pagesResponse = Http::get("https://graph.facebook.com/v18.0/me/accounts", [
            'access_token' => $account->access_token,
        ]);

        if ($pagesResponse->failed()) {
            return [
                'success' => false,
                'error' => 'Failed to fetch Facebook pages: ' . $pagesResponse->body(),
            ];
        }

        $pages = $pagesResponse->json()['data'] ?? [];

        if (empty($pages)) {
            return [
                'success' => false,
                'error' => 'No Facebook pages found. Please connect a page.',
            ];
        }

        // Use the first page (in production, you'd let users select which page)
        $page = $pages[0];
        $pageId = $page['id'];
        $pageAccessToken = $page['access_token'];

        // Prepare post content
        $content = $post->content_text;
        if ($post->hashtags) {
            $content .= "\n\n" . $post->hashtags;
        }

        // Publish the post
        $postData = [
            'message' => $content,
            'access_token' => $pageAccessToken,
        ];

        // Add media if present
        if (!empty($post->media_urls) && is_array($post->media_urls)) {
            // For simplicity, just add the first image URL
            $firstMedia = $post->media_urls[0] ?? null;
            if ($firstMedia) {
                $postData['link'] = $firstMedia;
            }
        }

        $publishResponse = Http::post("https://graph.facebook.com/v18.0/{$pageId}/feed", $postData);

        if ($publishResponse->failed()) {
            return [
                'success' => false,
                'error' => 'Failed to publish to Facebook: ' . $publishResponse->body(),
            ];
        }

        $result = $publishResponse->json();

        return [
            'success' => true,
            'post_id' => $result['id'] ?? null,
            'url' => "https://www.facebook.com/{$result['id']}",
        ];
    }

    /**
     * Publish post to LinkedIn
     */
    protected function publishToLinkedIn(ContentCalendarItem $post, SocialAccount $account): array
    {
        // Get the user's LinkedIn profile URN
        $profileResponse = Http::withToken($account->access_token)
            ->get('https://api.linkedin.com/v2/userinfo');

        if ($profileResponse->failed()) {
            return [
                'success' => false,
                'error' => 'Failed to fetch LinkedIn profile: ' . $profileResponse->body(),
            ];
        }

        $profile = $profileResponse->json();
        $authorUrn = 'urn:li:person:' . $profile['sub'];

        // Prepare post content
        $content = $post->content_text;
        if ($post->hashtags) {
            $content .= "\n\n" . $post->hashtags;
        }

        // Create the post (UGC Post API)
        $postData = [
            'author' => $authorUrn,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => [
                        'text' => $content,
                    ],
                    'shareMediaCategory' => 'NONE',
                ],
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ];

        // Add media if present
        if (!empty($post->media_urls) && is_array($post->media_urls)) {
            $firstMedia = $post->media_urls[0] ?? null;
            if ($firstMedia) {
                $postData['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'] = 'ARTICLE';
                $postData['specificContent']['com.linkedin.ugc.ShareContent']['media'] = [
                    [
                        'status' => 'READY',
                        'originalUrl' => $firstMedia,
                    ],
                ];
            }
        }

        $publishResponse = Http::withToken($account->access_token)
            ->withHeaders([
                'X-Restli-Protocol-Version' => '2.0.0',
                'LinkedIn-Version' => '202401',
            ])
            ->post('https://api.linkedin.com/v2/ugcPosts', $postData);

        if ($publishResponse->failed()) {
            return [
                'success' => false,
                'error' => 'Failed to publish to LinkedIn: ' . $publishResponse->body(),
            ];
        }

        $result = $publishResponse->json();
        $postId = $result['id'] ?? null;

        return [
            'success' => true,
            'post_id' => $postId,
            'url' => $postId ? "https://www.linkedin.com/feed/update/{$postId}" : null,
        ];
    }

    /**
     * Publish all scheduled posts that are ready
     */
    public function publishScheduledPosts(): array
    {
        $posts = ContentCalendarItem::where('content_type', 'social')
            ->whereIn('status', ['scheduled', 'approved'])
            ->where('scheduled_for', '<=', Carbon::now())
            ->whereNotNull('scheduled_for')
            ->with(['client'])
            ->get();

        $results = [
            'total' => $posts->count(),
            'published' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        foreach ($posts as $post) {
            $success = $this->publishPost($post);

            if ($success) {
                $results['published']++;
            } else {
                if ($post->status === 'failed') {
                    $results['failed']++;
                } else {
                    $results['skipped']++;
                }
            }
        }

        Log::info('Scheduled post publishing completed', $results);

        return $results;
    }
}
