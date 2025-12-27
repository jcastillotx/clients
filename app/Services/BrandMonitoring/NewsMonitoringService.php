<?php

namespace App\Services\BrandMonitoring;

use App\Models\BrandMention;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * News & Press Monitoring using free APIs
 *
 * - NewsAPI.org: FREE 100 requests/day, 1 month history
 * - Google News RSS: FREE unlimited
 */
class NewsMonitoringService
{
    /**
     * Search for brand mentions in news using NewsAPI.org
     * FREE tier: 100 requests/day
     */
    public function searchNewsAPI(Client $client, array $keywords = []): array
    {
        if (! config('brand-monitoring.news.newsapi.enabled')) {
            return ['skipped' => true, 'reason' => 'NewsAPI disabled'];
        }

        $apiKey = config('brand-monitoring.news.newsapi.api_key');
        if (empty($apiKey)) {
            return ['error' => 'NewsAPI key not configured'];
        }

        $keywords = ! empty($keywords) ? $keywords : [$client->company_name];
        $mentions = [];

        foreach ($keywords as $keyword) {
            try {
                // Everything endpoint - searches title and body
                $response = Http::timeout(15)
                    ->get('https://newsapi.org/v2/everything', [
                        'q' => $keyword,
                        'apiKey' => $apiKey,
                        'language' => 'en',
                        'sortBy' => 'publishedAt',
                        'pageSize' => 20, // Max 100
                        'from' => now()->subDays(7)->toIso8601String(),
                    ]);

                if (! $response->successful()) {
                    Log::warning('NewsAPI request failed', [
                        'status' => $response->status(),
                        'keyword' => $keyword,
                    ]);

                    continue;
                }

                $data = $response->json();
                $articles = $data['articles'] ?? [];

                foreach ($articles as $article) {
                    $mention = $this->storeMention($client, [
                        'platform' => 'news',
                        'mention_text' => ($article['title'] ?? '')."\n\n".($article['description'] ?? ''),
                        'author' => $article['author'] ?? $article['source']['name'] ?? 'Unknown',
                        'url' => $article['url'] ?? null,
                        'posted_at' => isset($article['publishedAt'])
                            ? Carbon::parse($article['publishedAt'])
                            : now(),
                        'meta' => [
                            'source' => $article['source']['name'] ?? null,
                            'image' => $article['urlToImage'] ?? null,
                            'keyword' => $keyword,
                            'api' => 'newsapi',
                        ],
                    ]);

                    if ($mention) {
                        $mentions[] = $mention;
                    }
                }

            } catch (\Throwable $e) {
                Log::error('NewsAPI search failed', [
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
     * Monitor Google News RSS feeds (FREE, unlimited)
     */
    public function searchGoogleNewsRSS(Client $client, array $keywords = []): array
    {
        if (! config('brand-monitoring.news.google_news_rss.enabled')) {
            return ['skipped' => true, 'reason' => 'Google News RSS disabled'];
        }

        $keywords = ! empty($keywords) ? $keywords : [$client->company_name];
        $mentions = [];

        foreach ($keywords as $keyword) {
            try {
                $url = 'https://news.google.com/rss/search?q='.urlencode($keyword).'&hl=en-US&gl=US&ceid=US:en';

                $response = Http::timeout(15)->get($url);

                if (! $response->successful()) {
                    continue;
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

                    $mention = $this->storeMention($client, [
                        'platform' => 'google_news',
                        'mention_text' => (string) $item->title,
                        'author' => isset($item->source) ? (string) $item->source : 'Google News',
                        'url' => (string) $item->link,
                        'posted_at' => $pubDate,
                        'meta' => [
                            'keyword' => $keyword,
                            'api' => 'google_news_rss',
                        ],
                    ]);

                    if ($mention) {
                        $mentions[] = $mention;
                    }
                }

            } catch (\Throwable $e) {
                Log::error('Google News RSS failed', [
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
                return null; // Already exists
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
