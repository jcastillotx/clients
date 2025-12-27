<?php

namespace App\Services\BrandMonitoring;

use App\Models\BrandMention;
use App\Models\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Web Mention Monitoring using free search APIs
 *
 * - Google Custom Search: FREE 100 queries/day, $5 per 1000 additional
 * - Bing Search API: FREE 1000 queries/month
 */
class WebMentionService
{
    /**
     * Search Google for brand mentions
     * FREE tier: 100 searches/day
     */
    public function searchGoogle(Client $client, array $keywords = []): array
    {
        if (! config('brand-monitoring.web_mentions.google_search.enabled')) {
            return ['skipped' => true, 'reason' => 'Google Search disabled'];
        }

        $apiKey = config('brand-monitoring.web_mentions.google_search.api_key');
        $searchEngineId = config('brand-monitoring.web_mentions.google_search.search_engine_id');

        if (empty($apiKey) || empty($searchEngineId)) {
            return ['error' => 'Google Custom Search not configured'];
        }

        $keywords = ! empty($keywords) ? $keywords : [$client->company_name];
        $mentions = [];

        foreach ($keywords as $keyword) {
            try {
                // Search with date restriction (last week)
                $response = Http::get('https://www.googleapis.com/customsearch/v1', [
                    'key' => $apiKey,
                    'cx' => $searchEngineId,
                    'q' => $keyword,
                    'num' => 10, // Max 10 results per request
                    'dateRestrict' => 'w1', // Last week
                    'sort' => 'date', // Sort by date
                ]);

                if (! $response->successful()) {
                    Log::warning('Google Search failed', [
                        'status' => $response->status(),
                        'keyword' => $keyword,
                    ]);

                    continue;
                }

                $data = $response->json();
                $items = $data['items'] ?? [];

                foreach ($items as $item) {
                    $mention = $this->storeMention($client, [
                        'platform' => 'web',
                        'mention_text' => ($item['title'] ?? '')."\n\n".($item['snippet'] ?? ''),
                        'author' => $item['displayLink'] ?? parse_url($item['link'] ?? '', PHP_URL_HOST),
                        'url' => $item['link'] ?? null,
                        'posted_at' => now(), // Google CSE doesn't return publish dates easily
                        'meta' => [
                            'source' => $item['displayLink'] ?? null,
                            'keyword' => $keyword,
                            'api' => 'google_custom_search',
                        ],
                    ]);

                    if ($mention) {
                        $mentions[] = $mention;
                    }
                }

            } catch (\Throwable $e) {
                Log::error('Google Custom Search failed', [
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
     * Search Bing for brand mentions
     * FREE tier: 1000 searches/month
     */
    public function searchBing(Client $client, array $keywords = []): array
    {
        if (! config('brand-monitoring.web_mentions.bing_search.enabled')) {
            return ['skipped' => true, 'reason' => 'Bing Search disabled'];
        }

        $apiKey = config('brand-monitoring.web_mentions.bing_search.api_key');

        if (empty($apiKey)) {
            return ['error' => 'Bing Search API key not configured'];
        }

        $keywords = ! empty($keywords) ? $keywords : [$client->company_name];
        $mentions = [];

        foreach ($keywords as $keyword) {
            try {
                $response = Http::withHeaders([
                    'Ocp-Apim-Subscription-Key' => $apiKey,
                ])->get('https://api.bing.microsoft.com/v7.0/search', [
                    'q' => $keyword,
                    'count' => 50, // Max 50
                    'freshness' => 'Week', // Last week
                    'textDecorations' => false,
                    'textFormat' => 'Raw',
                ]);

                if (! $response->successful()) {
                    Log::warning('Bing Search failed', [
                        'status' => $response->status(),
                        'keyword' => $keyword,
                    ]);

                    continue;
                }

                $data = $response->json();
                $webPages = $data['webPages']['value'] ?? [];

                foreach ($webPages as $page) {
                    $mention = $this->storeMention($client, [
                        'platform' => 'web',
                        'mention_text' => ($page['name'] ?? '')."\n\n".($page['snippet'] ?? ''),
                        'author' => $page['displayUrl'] ?? parse_url($page['url'] ?? '', PHP_URL_HOST),
                        'url' => $page['url'] ?? null,
                        'posted_at' => isset($page['dateLastCrawled'])
                            ? \Carbon\Carbon::parse($page['dateLastCrawled'])
                            : now(),
                        'meta' => [
                            'source' => $page['displayUrl'] ?? null,
                            'keyword' => $keyword,
                            'api' => 'bing_search',
                        ],
                    ]);

                    if ($mention) {
                        $mentions[] = $mention;
                    }
                }

            } catch (\Throwable $e) {
                Log::error('Bing Search failed', [
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
