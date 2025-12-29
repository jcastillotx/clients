<?php

namespace App\Http\Livewire\Admin\Settings;

use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

/**
 * Integration Settings - API keys for Brand Monitoring and Social Media.
 *
 * Note: AI provider configuration has been moved to the dedicated AI Providers
 * management page (Admin > AI > Providers) which provides cost tracking,
 * priority ordering, multiple model configs, and status management.
 */
class ApiSettings extends Component
{
    public string $tab = 'brand_monitoring';

    // Brand Monitoring API Keys
    public array $brandMonitoring = [];

    // Social Media API Keys
    public array $social = [];

    // Test results
    public array $testResults = [];

    public function mount(SettingsService $settings): void
    {
        abort_unless(Auth::user()?->can('manage settings'), 403);

        // Load Brand Monitoring settings
        $bmDefaults = [
            'api.brand.newsapi.api_key' => '',
            'api.brand.newsapi.enabled' => true,
            'api.brand.youtube.api_key' => '',
            'api.brand.youtube.enabled' => true,
            'api.brand.reddit.client_id' => '',
            'api.brand.reddit.client_secret' => '',
            'api.brand.reddit.enabled' => true,
            'api.brand.yelp.api_key' => '',
            'api.brand.yelp.enabled' => true,
            'api.brand.google_places.api_key' => '',
            'api.brand.google_places.enabled' => true,
            'api.brand.google_search.api_key' => '',
            'api.brand.google_search.engine_id' => '',
            'api.brand.google_search.enabled' => true,
            'api.brand.bing_search.api_key' => '',
            'api.brand.bing_search.enabled' => true,
            'api.brand.trustpilot.api_key' => '',
            'api.brand.trustpilot.api_secret' => '',
            'api.brand.trustpilot.enabled' => false,
            'api.brand.g2.api_key' => '',
            'api.brand.g2.enabled' => false,
            'api.brand.capterra.api_key' => '',
            'api.brand.capterra.enabled' => false,
            'api.brand.facebook.access_token' => '',
            'api.brand.facebook.enabled' => false,
        ];
        $bmValues = $settings->getMany($bmDefaults);

        $this->brandMonitoring = [
            'newsapi_api_key' => $bmValues['api.brand.newsapi.api_key'],
            'newsapi_enabled' => (bool) $bmValues['api.brand.newsapi.enabled'],
            'youtube_api_key' => $bmValues['api.brand.youtube.api_key'],
            'youtube_enabled' => (bool) $bmValues['api.brand.youtube.enabled'],
            'reddit_client_id' => $bmValues['api.brand.reddit.client_id'],
            'reddit_client_secret' => $bmValues['api.brand.reddit.client_secret'],
            'reddit_enabled' => (bool) $bmValues['api.brand.reddit.enabled'],
            'yelp_api_key' => $bmValues['api.brand.yelp.api_key'],
            'yelp_enabled' => (bool) $bmValues['api.brand.yelp.enabled'],
            'google_places_api_key' => $bmValues['api.brand.google_places.api_key'],
            'google_places_enabled' => (bool) $bmValues['api.brand.google_places.enabled'],
            'google_search_api_key' => $bmValues['api.brand.google_search.api_key'],
            'google_search_engine_id' => $bmValues['api.brand.google_search.engine_id'],
            'google_search_enabled' => (bool) $bmValues['api.brand.google_search.enabled'],
            'bing_search_api_key' => $bmValues['api.brand.bing_search.api_key'],
            'bing_search_enabled' => (bool) $bmValues['api.brand.bing_search.enabled'],
            'trustpilot_api_key' => $bmValues['api.brand.trustpilot.api_key'],
            'trustpilot_api_secret' => $bmValues['api.brand.trustpilot.api_secret'],
            'trustpilot_enabled' => (bool) $bmValues['api.brand.trustpilot.enabled'],
            'g2_api_key' => $bmValues['api.brand.g2.api_key'],
            'g2_enabled' => (bool) $bmValues['api.brand.g2.enabled'],
            'capterra_api_key' => $bmValues['api.brand.capterra.api_key'],
            'capterra_enabled' => (bool) $bmValues['api.brand.capterra.enabled'],
            'facebook_access_token' => $bmValues['api.brand.facebook.access_token'],
            'facebook_enabled' => (bool) $bmValues['api.brand.facebook.enabled'],
        ];

        // Load Social Media settings
        $socialDefaults = [
            'api.social.facebook.client_id' => '',
            'api.social.facebook.client_secret' => '',
            'api.social.linkedin.client_id' => '',
            'api.social.linkedin.client_secret' => '',
            'api.social.twitter.api_key' => '',
            'api.social.twitter.api_secret' => '',
            'api.social.twitter.access_token' => '',
            'api.social.twitter.access_secret' => '',
            'api.social.instagram.client_id' => '',
            'api.social.instagram.client_secret' => '',
            'api.social.tiktok.client_key' => '',
            'api.social.tiktok.client_secret' => '',
            'api.social.pinterest.app_id' => '',
            'api.social.pinterest.app_secret' => '',
            'api.social.bluesky.identifier' => '',
            'api.social.bluesky.password' => '',
            'api.social.mastodon.instance' => '',
            'api.social.mastodon.access_token' => '',
            'api.social.threads.client_id' => '',
            'api.social.threads.client_secret' => '',
        ];
        $socialValues = $settings->getMany($socialDefaults);

        $this->social = [
            'facebook_client_id' => $socialValues['api.social.facebook.client_id'],
            'facebook_client_secret' => $socialValues['api.social.facebook.client_secret'],
            'linkedin_client_id' => $socialValues['api.social.linkedin.client_id'],
            'linkedin_client_secret' => $socialValues['api.social.linkedin.client_secret'],
            'twitter_api_key' => $socialValues['api.social.twitter.api_key'],
            'twitter_api_secret' => $socialValues['api.social.twitter.api_secret'],
            'twitter_access_token' => $socialValues['api.social.twitter.access_token'],
            'twitter_access_secret' => $socialValues['api.social.twitter.access_secret'],
            'instagram_client_id' => $socialValues['api.social.instagram.client_id'],
            'instagram_client_secret' => $socialValues['api.social.instagram.client_secret'],
            'tiktok_client_key' => $socialValues['api.social.tiktok.client_key'],
            'tiktok_client_secret' => $socialValues['api.social.tiktok.client_secret'],
            'pinterest_app_id' => $socialValues['api.social.pinterest.app_id'],
            'pinterest_app_secret' => $socialValues['api.social.pinterest.app_secret'],
            'bluesky_identifier' => $socialValues['api.social.bluesky.identifier'],
            'bluesky_password' => $socialValues['api.social.bluesky.password'],
            'mastodon_instance' => $socialValues['api.social.mastodon.instance'],
            'mastodon_access_token' => $socialValues['api.social.mastodon.access_token'],
            'threads_client_id' => $socialValues['api.social.threads.client_id'],
            'threads_client_secret' => $socialValues['api.social.threads.client_secret'],
        ];
    }

    public function setTab(string $tab): void
    {
        $allowed = ['brand_monitoring', 'social'];
        if (in_array($tab, $allowed, true)) {
            $this->tab = $tab;
        }
    }

    public function saveBrandMonitoringSettings(SettingsService $settings): void
    {
        $encryptedKeys = [
            'api.brand.newsapi.api_key',
            'api.brand.youtube.api_key',
            'api.brand.reddit.client_secret',
            'api.brand.yelp.api_key',
            'api.brand.google_places.api_key',
            'api.brand.google_search.api_key',
            'api.brand.bing_search.api_key',
            'api.brand.trustpilot.api_key',
            'api.brand.trustpilot.api_secret',
            'api.brand.g2.api_key',
            'api.brand.capterra.api_key',
            'api.brand.facebook.access_token',
        ];

        $settings->setMany([
            'api.brand.newsapi.api_key' => $this->brandMonitoring['newsapi_api_key'] ?? '',
            'api.brand.newsapi.enabled' => (bool) ($this->brandMonitoring['newsapi_enabled'] ?? true),
            'api.brand.youtube.api_key' => $this->brandMonitoring['youtube_api_key'] ?? '',
            'api.brand.youtube.enabled' => (bool) ($this->brandMonitoring['youtube_enabled'] ?? true),
            'api.brand.reddit.client_id' => $this->brandMonitoring['reddit_client_id'] ?? '',
            'api.brand.reddit.client_secret' => $this->brandMonitoring['reddit_client_secret'] ?? '',
            'api.brand.reddit.enabled' => (bool) ($this->brandMonitoring['reddit_enabled'] ?? true),
            'api.brand.yelp.api_key' => $this->brandMonitoring['yelp_api_key'] ?? '',
            'api.brand.yelp.enabled' => (bool) ($this->brandMonitoring['yelp_enabled'] ?? true),
            'api.brand.google_places.api_key' => $this->brandMonitoring['google_places_api_key'] ?? '',
            'api.brand.google_places.enabled' => (bool) ($this->brandMonitoring['google_places_enabled'] ?? true),
            'api.brand.google_search.api_key' => $this->brandMonitoring['google_search_api_key'] ?? '',
            'api.brand.google_search.engine_id' => $this->brandMonitoring['google_search_engine_id'] ?? '',
            'api.brand.google_search.enabled' => (bool) ($this->brandMonitoring['google_search_enabled'] ?? true),
            'api.brand.bing_search.api_key' => $this->brandMonitoring['bing_search_api_key'] ?? '',
            'api.brand.bing_search.enabled' => (bool) ($this->brandMonitoring['bing_search_enabled'] ?? true),
            'api.brand.trustpilot.api_key' => $this->brandMonitoring['trustpilot_api_key'] ?? '',
            'api.brand.trustpilot.api_secret' => $this->brandMonitoring['trustpilot_api_secret'] ?? '',
            'api.brand.trustpilot.enabled' => (bool) ($this->brandMonitoring['trustpilot_enabled'] ?? false),
            'api.brand.g2.api_key' => $this->brandMonitoring['g2_api_key'] ?? '',
            'api.brand.g2.enabled' => (bool) ($this->brandMonitoring['g2_enabled'] ?? false),
            'api.brand.capterra.api_key' => $this->brandMonitoring['capterra_api_key'] ?? '',
            'api.brand.capterra.enabled' => (bool) ($this->brandMonitoring['capterra_enabled'] ?? false),
            'api.brand.facebook.access_token' => $this->brandMonitoring['facebook_access_token'] ?? '',
            'api.brand.facebook.enabled' => (bool) ($this->brandMonitoring['facebook_enabled'] ?? false),
        ], 'api.brand', $encryptedKeys);

        session()->flash('success', 'Brand monitoring settings saved successfully.');
    }

    public function saveSocialSettings(SettingsService $settings): void
    {
        $encryptedKeys = [
            'api.social.facebook.client_secret',
            'api.social.linkedin.client_secret',
            'api.social.twitter.api_secret',
            'api.social.twitter.access_secret',
            'api.social.instagram.client_secret',
            'api.social.tiktok.client_secret',
            'api.social.pinterest.app_secret',
            'api.social.bluesky.password',
            'api.social.mastodon.access_token',
            'api.social.threads.client_secret',
        ];

        $settings->setMany([
            'api.social.facebook.client_id' => $this->social['facebook_client_id'] ?? '',
            'api.social.facebook.client_secret' => $this->social['facebook_client_secret'] ?? '',
            'api.social.linkedin.client_id' => $this->social['linkedin_client_id'] ?? '',
            'api.social.linkedin.client_secret' => $this->social['linkedin_client_secret'] ?? '',
            'api.social.twitter.api_key' => $this->social['twitter_api_key'] ?? '',
            'api.social.twitter.api_secret' => $this->social['twitter_api_secret'] ?? '',
            'api.social.twitter.access_token' => $this->social['twitter_access_token'] ?? '',
            'api.social.twitter.access_secret' => $this->social['twitter_access_secret'] ?? '',
            'api.social.instagram.client_id' => $this->social['instagram_client_id'] ?? '',
            'api.social.instagram.client_secret' => $this->social['instagram_client_secret'] ?? '',
            'api.social.tiktok.client_key' => $this->social['tiktok_client_key'] ?? '',
            'api.social.tiktok.client_secret' => $this->social['tiktok_client_secret'] ?? '',
            'api.social.pinterest.app_id' => $this->social['pinterest_app_id'] ?? '',
            'api.social.pinterest.app_secret' => $this->social['pinterest_app_secret'] ?? '',
            'api.social.bluesky.identifier' => $this->social['bluesky_identifier'] ?? '',
            'api.social.bluesky.password' => $this->social['bluesky_password'] ?? '',
            'api.social.mastodon.instance' => $this->social['mastodon_instance'] ?? '',
            'api.social.mastodon.access_token' => $this->social['mastodon_access_token'] ?? '',
            'api.social.threads.client_id' => $this->social['threads_client_id'] ?? '',
            'api.social.threads.client_secret' => $this->social['threads_client_secret'] ?? '',
        ], 'api.social', $encryptedKeys);

        session()->flash('success', 'Social media settings saved successfully.');
    }

    public function testConnection(string $provider): void
    {
        $this->testResults[$provider] = ['testing' => true];

        try {
            $result = match ($provider) {
                'newsapi' => $this->testNewsAPI(),
                'youtube' => $this->testYouTube(),
                'yelp' => $this->testYelp(),
                'google_places' => $this->testGooglePlaces(),
                default => ['success' => false, 'message' => 'Unknown provider'],
            };

            $this->testResults[$provider] = $result;
        } catch (\Throwable $e) {
            $this->testResults[$provider] = [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    protected function testNewsAPI(): array
    {
        $apiKey = $this->brandMonitoring['newsapi_api_key'] ?? '';
        if (empty($apiKey)) {
            return ['success' => false, 'message' => 'API key not configured'];
        }

        $response = Http::timeout(10)
            ->get('https://newsapi.org/v2/top-headlines', [
                'apiKey' => $apiKey,
                'country' => 'us',
                'pageSize' => 1,
            ]);

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Connected successfully'];
        }

        return ['success' => false, 'message' => 'Connection failed: ' . ($response->json('message') ?? $response->status())];
    }

    protected function testYouTube(): array
    {
        $apiKey = $this->brandMonitoring['youtube_api_key'] ?? '';
        if (empty($apiKey)) {
            return ['success' => false, 'message' => 'API key not configured'];
        }

        $response = Http::timeout(10)
            ->get('https://www.googleapis.com/youtube/v3/search', [
                'part' => 'snippet',
                'q' => 'test',
                'maxResults' => 1,
                'key' => $apiKey,
            ]);

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Connected successfully'];
        }

        return ['success' => false, 'message' => 'Connection failed: ' . ($response->json('error.message') ?? $response->status())];
    }

    protected function testYelp(): array
    {
        $apiKey = $this->brandMonitoring['yelp_api_key'] ?? '';
        if (empty($apiKey)) {
            return ['success' => false, 'message' => 'API key not configured'];
        }

        $response = Http::withToken($apiKey)
            ->timeout(10)
            ->get('https://api.yelp.com/v3/businesses/search', [
                'term' => 'coffee',
                'location' => 'NYC',
                'limit' => 1,
            ]);

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Connected successfully'];
        }

        return ['success' => false, 'message' => 'Connection failed: ' . ($response->json('error.description') ?? $response->status())];
    }

    protected function testGooglePlaces(): array
    {
        $apiKey = $this->brandMonitoring['google_places_api_key'] ?? '';
        if (empty($apiKey)) {
            return ['success' => false, 'message' => 'API key not configured'];
        }

        $response = Http::timeout(10)
            ->get('https://maps.googleapis.com/maps/api/place/findplacefromtext/json', [
                'input' => 'Google',
                'inputtype' => 'textquery',
                'fields' => 'place_id',
                'key' => $apiKey,
            ]);

        if ($response->successful() && in_array($response->json('status'), ['OK', 'ZERO_RESULTS'])) {
            return ['success' => true, 'message' => 'Connected successfully'];
        }

        return ['success' => false, 'message' => 'Connection failed: ' . ($response->json('error_message') ?? $response->json('status') ?? $response->status())];
    }

    public function render()
    {
        return view('livewire.admin.settings.api-settings')->layout('layouts.admin');
    }
}
