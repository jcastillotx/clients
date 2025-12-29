<?php

namespace App\Http\Livewire\Admin\BrandMonitoring;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class ApiStatus extends Component
{
    public $testingApi = null;

    public $testResults = [];

    public function mount()
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
    }

    public function testNewsApi()
    {
        $this->testingApi = 'newsapi';
        $apiKey = trim(config('brand-monitoring.news.newsapi.api_key'));

        if (empty($apiKey)) {
            $this->testResults['newsapi'] = [
                'status' => 'error',
                'message' => 'API key not configured',
            ];
            $this->testingApi = null;

            return;
        }

        try {
            // NewsAPI supports both X-Api-Key header and apiKey query param
            // Using header method as it's more reliable
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Api-Key' => $apiKey,
                ])
                ->get('https://newsapi.org/v2/top-headlines', [
                    'country' => 'us',
                    'pageSize' => 1,
                ]);

            if ($response->successful()) {
                $this->testResults['newsapi'] = [
                    'status' => 'success',
                    'message' => 'Connected successfully',
                    'limit' => '100 requests/day',
                ];
            } else {
                // Get detailed error from NewsAPI response
                $data = $response->json();
                $errorMessage = $data['message'] ?? 'Failed: HTTP '.$response->status();

                $this->testResults['newsapi'] = [
                    'status' => 'error',
                    'message' => 'Connection failed: '.$errorMessage,
                    'limit' => '100 requests/day',
                ];
            }
        } catch (\Throwable $e) {
            $this->testResults['newsapi'] = [
                'status' => 'error',
                'message' => 'Error: '.$e->getMessage(),
            ];
        }

        $this->testingApi = null;
    }

    public function testYelpApi()
    {
        $this->testingApi = 'yelp';
        $apiKey = config('brand-monitoring.reviews.yelp.api_key');

        if (empty($apiKey)) {
            $this->testResults['yelp'] = [
                'status' => 'error',
                'message' => 'API key not configured',
            ];
            $this->testingApi = null;

            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
            ])->timeout(10)->get('https://api.yelp.com/v3/businesses/search', [
                'term' => 'restaurant',
                'location' => 'New York',
                'limit' => 1,
            ]);

            $this->testResults['yelp'] = [
                'status' => $response->successful() ? 'success' : 'error',
                'message' => $response->successful()
                    ? 'Connected successfully'
                    : 'Failed: HTTP '.$response->status(),
                'limit' => '5,000 requests/day',
            ];
        } catch (\Throwable $e) {
            $this->testResults['yelp'] = [
                'status' => 'error',
                'message' => 'Error: '.$e->getMessage(),
            ];
        }

        $this->testingApi = null;
    }

    public function testGooglePlacesApi()
    {
        $this->testingApi = 'google_places';
        $apiKey = config('brand-monitoring.reviews.google_places.api_key');

        if (empty($apiKey)) {
            $this->testResults['google_places'] = [
                'status' => 'error',
                'message' => 'API key not configured',
            ];
            $this->testingApi = null;

            return;
        }

        try {
            $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/place/findplacefromtext/json', [
                'input' => 'Museum',
                'inputtype' => 'textquery',
                'fields' => 'place_id,name',
                'key' => $apiKey,
            ]);

            $this->testResults['google_places'] = [
                'status' => $response->successful() ? 'success' : 'error',
                'message' => $response->successful()
                    ? 'Connected successfully'
                    : 'Failed: HTTP '.$response->status(),
                'limit' => '$200 credit/month',
            ];
        } catch (\Throwable $e) {
            $this->testResults['google_places'] = [
                'status' => 'error',
                'message' => 'Error: '.$e->getMessage(),
            ];
        }

        $this->testingApi = null;
    }

    public function testRedditApi()
    {
        $this->testingApi = 'reddit';
        $clientId = config('brand-monitoring.social.reddit.client_id');
        $clientSecret = config('brand-monitoring.social.reddit.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            $this->testResults['reddit'] = [
                'status' => 'error',
                'message' => 'API credentials not configured',
            ];
            $this->testingApi = null;

            return;
        }

        try {
            $tokenResponse = Http::asForm()->post('https://www.reddit.com/api/v1/access_token', [
                'grant_type' => 'client_credentials',
            ], [
                'auth' => [$clientId, $clientSecret],
            ]);

            $this->testResults['reddit'] = [
                'status' => $tokenResponse->successful() ? 'success' : 'error',
                'message' => $tokenResponse->successful()
                    ? 'Connected successfully'
                    : 'Failed: HTTP '.$tokenResponse->status(),
                'limit' => '60 requests/minute',
            ];
        } catch (\Throwable $e) {
            $this->testResults['reddit'] = [
                'status' => 'error',
                'message' => 'Error: '.$e->getMessage(),
            ];
        }

        $this->testingApi = null;
    }

    public function testYoutubeApi()
    {
        $this->testingApi = 'youtube';
        $apiKey = config('brand-monitoring.social.youtube.api_key');

        if (empty($apiKey)) {
            $this->testResults['youtube'] = [
                'status' => 'error',
                'message' => 'API key not configured',
            ];
            $this->testingApi = null;

            return;
        }

        try {
            $response = Http::timeout(10)->get('https://www.googleapis.com/youtube/v3/search', [
                'part' => 'snippet',
                'q' => 'test',
                'type' => 'video',
                'maxResults' => 1,
                'key' => $apiKey,
            ]);

            $this->testResults['youtube'] = [
                'status' => $response->successful() ? 'success' : 'error',
                'message' => $response->successful()
                    ? 'Connected successfully'
                    : 'Failed: HTTP '.$response->status(),
                'limit' => '10,000 units/day',
            ];
        } catch (\Throwable $e) {
            $this->testResults['youtube'] = [
                'status' => 'error',
                'message' => 'Error: '.$e->getMessage(),
            ];
        }

        $this->testingApi = null;
    }

    public function testGoogleSearchApi()
    {
        $this->testingApi = 'google_search';
        $apiKey = config('brand-monitoring.web_mentions.google_search.api_key');
        $cx = config('brand-monitoring.web_mentions.google_search.search_engine_id');

        if (empty($apiKey) || empty($cx)) {
            $this->testResults['google_search'] = [
                'status' => 'error',
                'message' => 'API key or Search Engine ID not configured',
            ];
            $this->testingApi = null;

            return;
        }

        try {
            $response = Http::timeout(10)->get('https://www.googleapis.com/customsearch/v1', [
                'key' => $apiKey,
                'cx' => $cx,
                'q' => 'test',
                'num' => 1,
            ]);

            $this->testResults['google_search'] = [
                'status' => $response->successful() ? 'success' : 'error',
                'message' => $response->successful()
                    ? 'Connected successfully'
                    : 'Failed: HTTP '.$response->status(),
                'limit' => '100 searches/day',
            ];
        } catch (\Throwable $e) {
            $this->testResults['google_search'] = [
                'status' => 'error',
                'message' => 'Error: '.$e->getMessage(),
            ];
        }

        $this->testingApi = null;
    }

    public function testBingSearchApi()
    {
        $this->testingApi = 'bing_search';
        $apiKey = config('brand-monitoring.web_mentions.bing_search.api_key');

        if (empty($apiKey)) {
            $this->testResults['bing_search'] = [
                'status' => 'error',
                'message' => 'API key not configured',
            ];
            $this->testingApi = null;

            return;
        }

        try {
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $apiKey,
            ])->timeout(10)->get('https://api.bing.microsoft.com/v7.0/search', [
                'q' => 'test',
                'count' => 1,
            ]);

            $this->testResults['bing_search'] = [
                'status' => $response->successful() ? 'success' : 'error',
                'message' => $response->successful()
                    ? 'Connected successfully'
                    : 'Failed: HTTP '.$response->status(),
                'limit' => '1,000 searches/month',
            ];
        } catch (\Throwable $e) {
            $this->testResults['bing_search'] = [
                'status' => 'error',
                'message' => 'Error: '.$e->getMessage(),
            ];
        }

        $this->testingApi = null;
    }

    public function render()
    {
        $apis = [
            'newsapi' => [
                'name' => 'NewsAPI.org',
                'enabled' => config('brand-monitoring.news.newsapi.enabled'),
                'configured' => ! empty(config('brand-monitoring.news.newsapi.api_key')),
                'limit' => '100 requests/day',
                'category' => 'News',
            ],
            'google_news_rss' => [
                'name' => 'Google News RSS',
                'enabled' => config('brand-monitoring.news.google_news_rss.enabled'),
                'configured' => true, // No key needed
                'limit' => 'Unlimited',
                'category' => 'News',
            ],
            'yelp' => [
                'name' => 'Yelp Fusion API',
                'enabled' => config('brand-monitoring.reviews.yelp.enabled'),
                'configured' => ! empty(config('brand-monitoring.reviews.yelp.api_key')),
                'limit' => '5,000 requests/day',
                'category' => 'Reviews',
            ],
            'google_places' => [
                'name' => 'Google Places API',
                'enabled' => config('brand-monitoring.reviews.google_places.enabled'),
                'configured' => ! empty(config('brand-monitoring.reviews.google_places.api_key')),
                'limit' => '$200 credit/month',
                'category' => 'Reviews',
            ],
            'reddit' => [
                'name' => 'Reddit API',
                'enabled' => config('brand-monitoring.social.reddit.enabled'),
                'configured' => ! empty(config('brand-monitoring.social.reddit.client_id')),
                'limit' => '60 requests/minute',
                'category' => 'Social',
            ],
            'youtube' => [
                'name' => 'YouTube Data API',
                'enabled' => config('brand-monitoring.social.youtube.enabled'),
                'configured' => ! empty(config('brand-monitoring.social.youtube.api_key')),
                'limit' => '10,000 units/day',
                'category' => 'Social',
            ],
            'twitter_rss' => [
                'name' => 'Twitter RSS (Nitter)',
                'enabled' => config('brand-monitoring.social.twitter_rss.enabled'),
                'configured' => true, // No key needed
                'limit' => 'Unlimited',
                'category' => 'Social',
            ],
            'google_search' => [
                'name' => 'Google Custom Search',
                'enabled' => config('brand-monitoring.web_mentions.google_search.enabled'),
                'configured' => ! empty(config('brand-monitoring.web_mentions.google_search.api_key')),
                'limit' => '100 searches/day',
                'category' => 'Web',
            ],
            'bing_search' => [
                'name' => 'Bing Search API',
                'enabled' => config('brand-monitoring.web_mentions.bing_search.enabled'),
                'configured' => ! empty(config('brand-monitoring.web_mentions.bing_search.api_key')),
                'limit' => '1,000 searches/month',
                'category' => 'Web',
            ],
        ];

        return view('livewire.admin.brand-monitoring.api-status', [
            'apis' => $apis,
        ])->layout('layouts.admin');
    }
}
