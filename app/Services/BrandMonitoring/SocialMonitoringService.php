<?php

namespace App\Services\BrandMonitoring;

use App\Models\BrandMention;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Social Media Monitoring using free APIs
 *
 * - Reddit API: FREE, 60 requests/minute
 * - YouTube Data API: FREE 10,000 quota units/day
 * - Twitter RSS (via nitter.net): FREE unlimited
 */
class SocialMonitoringService
{
    /**
     * Search Reddit for brand mentions
     * FREE tier: 60 requests/minute
     */
    public function searchReddit(Client $client, array $keywords = []): array
    {
        if (! config('brand-monitoring.social.reddit.enabled')) {
            return ['skipped' => true, 'reason' => 'Reddit API disabled'];
        }

        $clientId = config('brand-monitoring.social.reddit.client_id');
        $clientSecret = config('brand-monitoring.social.reddit.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            return ['error' => 'Reddit API credentials not configured'];
        }

        try {
            // Get OAuth token
            $tokenResponse = Http::asForm()->post('https://www.reddit.com/api/v1/access_token', [
                'grant_type' => 'client_credentials',
            ], [
                'auth' => [$clientId, $clientSecret],
            ]);

            if (! $tokenResponse->successful()) {
                return ['error' => 'Reddit OAuth failed'];
            }

            $accessToken = $tokenResponse->json()['access_token'] ?? null;
            if (! $accessToken) {
                return ['error' => 'No access token received'];
            }

            $keywords = ! empty($keywords) ? $keywords : [$client->company_name];
            $mentions = [];

            foreach ($keywords as $keyword) {
                // Search across all of Reddit
                $searchResponse = Http::withHeaders([
                    'Authorization' => 'Bearer '.$accessToken,
                    'User-Agent' => config('brand-monitoring.social.reddit.user_agent'),
                ])->get('https://oauth.reddit.com/search', [
                    'q' => $keyword,
                    'sort' => 'new',
                    'limit' => 100, // Max 100
                    't' => 'week', // Last week
                    'type' => 'link,comment',
                ]);

                if (! $searchResponse->successful()) {
                    Log::warning('Reddit search failed', ['keyword' => $keyword]);

                    continue;
                }

                $data = $searchResponse->json();
                $posts = $data['data']['children'] ?? [];

                foreach ($posts as $post) {
                    $postData = $post['data'] ?? [];

                    $mention = $this->storeMention($client, [
                        'platform' => 'reddit',
                        'mention_text' => ($postData['title'] ?? '')."\n\n".($postData['selftext'] ?? $postData['body'] ?? ''),
                        'author' => $postData['author'] ?? 'deleted',
                        'url' => 'https://reddit.com'.($postData['permalink'] ?? ''),
                        'posted_at' => isset($postData['created_utc'])
                            ? Carbon::createFromTimestamp($postData['created_utc'])
                            : now(),
                        'meta' => [
                            'subreddit' => $postData['subreddit'] ?? null,
                            'score' => $postData['score'] ?? 0,
                            'num_comments' => $postData['num_comments'] ?? 0,
                            'keyword' => $keyword,
                            'api' => 'reddit',
                        ],
                    ]);

                    if ($mention) {
                        $mentions[] = $mention;
                    }
                }

                // Rate limiting: max 60 requests/minute
                usleep(1000000); // 1 second delay between searches
            }

            return [
                'success' => true,
                'mentions_found' => count($mentions),
                'mentions' => $mentions,
            ];

        } catch (\Throwable $e) {
            Log::error('Reddit API failed', [
                'error' => $e->getMessage(),
            ]);

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Search YouTube for brand mentions
     * FREE tier: 10,000 quota units/day (100 searches = 100 units)
     */
    public function searchYouTube(Client $client, array $keywords = []): array
    {
        if (! config('brand-monitoring.social.youtube.enabled')) {
            return ['skipped' => true, 'reason' => 'YouTube API disabled'];
        }

        $apiKey = config('brand-monitoring.social.youtube.api_key');
        if (empty($apiKey)) {
            return ['error' => 'YouTube API key not configured'];
        }

        $keywords = ! empty($keywords) ? $keywords : [$client->company_name];
        $mentions = [];

        foreach ($keywords as $keyword) {
            try {
                // Search for videos mentioning the brand
                $searchResponse = Http::get('https://www.googleapis.com/youtube/v3/search', [
                    'part' => 'snippet',
                    'q' => $keyword,
                    'type' => 'video',
                    'order' => 'date',
                    'maxResults' => 25, // Max 50
                    'publishedAfter' => now()->subDays(7)->toIso8601String(),
                    'key' => $apiKey,
                ]);

                if (! $searchResponse->successful()) {
                    Log::warning('YouTube search failed', ['keyword' => $keyword]);

                    continue;
                }

                $data = $searchResponse->json();
                $items = $data['items'] ?? [];

                foreach ($items as $item) {
                    $snippet = $item['snippet'] ?? [];
                    $videoId = $item['id']['videoId'] ?? null;

                    if (! $videoId) {
                        continue;
                    }

                    $mention = $this->storeMention($client, [
                        'platform' => 'youtube',
                        'mention_text' => ($snippet['title'] ?? '')."\n\n".($snippet['description'] ?? ''),
                        'author' => $snippet['channelTitle'] ?? 'Unknown',
                        'url' => $videoId ? "https://www.youtube.com/watch?v={$videoId}" : null,
                        'posted_at' => isset($snippet['publishedAt'])
                            ? Carbon::parse($snippet['publishedAt'])
                            : now(),
                        'meta' => [
                            'video_id' => $videoId,
                            'channel_id' => $snippet['channelId'] ?? null,
                            'thumbnail' => $snippet['thumbnails']['high']['url'] ?? null,
                            'keyword' => $keyword,
                            'api' => 'youtube',
                        ],
                    ]);

                    if ($mention) {
                        $mentions[] = $mention;
                    }
                }

            } catch (\Throwable $e) {
                Log::error('YouTube API failed', [
                    'keyword' => $keyword,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'success' => true,
            'mentions_found' => count($mentions),
            'mentions' => $mentions,
        ];
    }

    /**
     * Monitor Twitter via RSS (Nitter instances)
     * FREE unlimited (no API key needed)
     */
    public function searchTwitterRSS(Client $client, array $keywords = []): array
    {
        if (! config('brand-monitoring.social.twitter_rss.enabled')) {
            return ['skipped' => true, 'reason' => 'Twitter RSS disabled'];
        }

        $keywords = ! empty($keywords) ? $keywords : [$client->company_name];
        $mentions = [];
        $nitterInstances = config('brand-monitoring.social.twitter_rss.nitter_instances', []);

        foreach ($keywords as $keyword) {
            foreach ($nitterInstances as $instance) {
                try {
                    $url = $instance.'/search/rss?f=tweets&q='.urlencode($keyword);

                    $response = Http::timeout(10)->get($url);

                    if (! $response->successful()) {
                        continue; // Try next instance
                    }

                    $xml = simplexml_load_string($response->body());
                    if ($xml === false) {
                        continue;
                    }

                    foreach ($xml->channel->item as $item) {
                        $pubDate = isset($item->pubDate) ? Carbon::parse((string) $item->pubDate) : now();

                        // Skip if older than 7 days
                        if ($pubDate->lt(now()->subDays(7))) {
                            continue;
                        }

                        // Extract username from link
                        $link = (string) $item->link;
                        preg_match('/\/([^\/]+)\/status/', $link, $matches);
                        $author = $matches[1] ?? 'Unknown';

                        $mention = $this->storeMention($client, [
                            'platform' => 'x',
                            'mention_text' => strip_tags((string) $item->description),
                            'author' => '@'.$author,
                            'url' => str_replace($instance, 'https://twitter.com', $link),
                            'posted_at' => $pubDate,
                            'meta' => [
                                'keyword' => $keyword,
                                'api' => 'twitter_rss',
                            ],
                        ]);

                        if ($mention) {
                            $mentions[] = $mention;
                        }
                    }

                    break; // Successfully got data, don't try other instances

                } catch (\Throwable $e) {
                    Log::debug('Nitter instance failed', [
                        'instance' => $instance,
                        'keyword' => $keyword,
                    ]);

                    continue; // Try next instance
                }
            }
        }

        return [
            'success' => true,
            'mentions_found' => count($mentions),
            'mentions' => $mentions,
        ];
    }

    /**
     * Store mention (deduplicate by URL)
     */
    protected function storeMention(Client $client, array $data): ?BrandMention
    {
        // Deduplicate by URL
        if (! empty($data['url'])) {
            $existing = BrandMention::where('client_id', $client->id)
                ->where('url', $data['url'])
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
            'sentiment' => null, // Will be analyzed in batch later
            'meta' => $data['meta'] ?? null,
        ]);
    }
}
