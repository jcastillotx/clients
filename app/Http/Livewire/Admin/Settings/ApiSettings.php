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

    // SEO Integration API Keys
    public array $seo = [];

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
            // Free news APIs
            'mediastack_api_key' => $bmValues['api.brand.mediastack.api_key'] ?? '',
            'mediastack_enabled' => (bool) ($bmValues['api.brand.mediastack.enabled'] ?? false),
            'gnews_api_key' => $bmValues['api.brand.gnews.api_key'] ?? '',
            'gnews_enabled' => (bool) ($bmValues['api.brand.gnews.enabled'] ?? false),
            // Commercial APIs
            'brandwatch_api_key' => $bmValues['api.brand.brandwatch.api_key'] ?? '',
            'brandwatch_api_secret' => $bmValues['api.brand.brandwatch.api_secret'] ?? '',
            'brandwatch_project_id' => $bmValues['api.brand.brandwatch.project_id'] ?? '',
            'brandwatch_enabled' => (bool) ($bmValues['api.brand.brandwatch.enabled'] ?? false),
            'mention_api_key' => $bmValues['api.brand.mention.api_key'] ?? '',
            'mention_account_id' => $bmValues['api.brand.mention.account_id'] ?? '',
            'mention_enabled' => (bool) ($bmValues['api.brand.mention.enabled'] ?? false),
            'brand24_api_key' => $bmValues['api.brand.brand24.api_key'] ?? '',
            'brand24_project_id' => $bmValues['api.brand.brand24.project_id'] ?? '',
            'brand24_enabled' => (bool) ($bmValues['api.brand.brand24.enabled'] ?? false),
            'sprout_social_api_key' => $bmValues['api.brand.sprout_social.api_key'] ?? '',
            'sprout_social_api_secret' => $bmValues['api.brand.sprout_social.api_secret'] ?? '',
            'sprout_social_enabled' => (bool) ($bmValues['api.brand.sprout_social.enabled'] ?? false),
            'meltwater_api_key' => $bmValues['api.brand.meltwater.api_key'] ?? '',
            'meltwater_api_secret' => $bmValues['api.brand.meltwater.api_secret'] ?? '',
            'meltwater_enabled' => (bool) ($bmValues['api.brand.meltwater.enabled'] ?? false),
            'talkwalker_api_key' => $bmValues['api.brand.talkwalker.api_key'] ?? '',
            'talkwalker_project_id' => $bmValues['api.brand.talkwalker.project_id'] ?? '',
            'talkwalker_enabled' => (bool) ($bmValues['api.brand.talkwalker.enabled'] ?? false),
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

        // Load SEO Integration settings
        $seoDefaults = [
            // Free APIs
            'api.seo.google_search_console.enabled' => false,
            'api.seo.google_search_console.client_id' => '',
            'api.seo.google_search_console.client_secret' => '',
            'api.seo.google_search_console.refresh_token' => '',
            'api.seo.google_pagespeed.enabled' => true,
            'api.seo.google_pagespeed.api_key' => '',
            'api.seo.bing_webmaster.enabled' => false,
            'api.seo.bing_webmaster.api_key' => '',
            'api.seo.ubersuggest.enabled' => false,
            'api.seo.ubersuggest.api_key' => '',
            'api.seo.keywords_everywhere.enabled' => false,
            'api.seo.keywords_everywhere.api_key' => '',
            // Low-cost APIs
            'api.seo.dataforseo.enabled' => false,
            'api.seo.dataforseo.login' => '',
            'api.seo.dataforseo.password' => '',
            'api.seo.serpapi.enabled' => false,
            'api.seo.serpapi.api_key' => '',
            'api.seo.mangools.enabled' => false,
            'api.seo.mangools.api_key' => '',
            'api.seo.spyfu.enabled' => false,
            'api.seo.spyfu.api_key' => '',
            'api.seo.majestic.enabled' => false,
            'api.seo.majestic.api_key' => '',
            // Commercial APIs
            'api.seo.moz.enabled' => false,
            'api.seo.moz.access_id' => '',
            'api.seo.moz.secret_key' => '',
            'api.seo.ahrefs.enabled' => false,
            'api.seo.ahrefs.api_key' => '',
            'api.seo.semrush.enabled' => false,
            'api.seo.semrush.api_key' => '',
            'api.seo.screaming_frog.enabled' => false,
            'api.seo.screaming_frog.license_key' => '',
        ];
        $seoValues = $settings->getMany($seoDefaults);

        $this->seo = [
            // Free APIs
            'google_search_console_enabled' => (bool) $seoValues['api.seo.google_search_console.enabled'],
            'google_search_console_client_id' => $seoValues['api.seo.google_search_console.client_id'],
            'google_search_console_client_secret' => $seoValues['api.seo.google_search_console.client_secret'],
            'google_search_console_refresh_token' => $seoValues['api.seo.google_search_console.refresh_token'],
            'google_pagespeed_enabled' => (bool) $seoValues['api.seo.google_pagespeed.enabled'],
            'google_pagespeed_api_key' => $seoValues['api.seo.google_pagespeed.api_key'],
            'bing_webmaster_enabled' => (bool) $seoValues['api.seo.bing_webmaster.enabled'],
            'bing_webmaster_api_key' => $seoValues['api.seo.bing_webmaster.api_key'],
            'ubersuggest_enabled' => (bool) $seoValues['api.seo.ubersuggest.enabled'],
            'ubersuggest_api_key' => $seoValues['api.seo.ubersuggest.api_key'],
            'keywords_everywhere_enabled' => (bool) $seoValues['api.seo.keywords_everywhere.enabled'],
            'keywords_everywhere_api_key' => $seoValues['api.seo.keywords_everywhere.api_key'],
            // Low-cost APIs
            'dataforseo_enabled' => (bool) $seoValues['api.seo.dataforseo.enabled'],
            'dataforseo_login' => $seoValues['api.seo.dataforseo.login'],
            'dataforseo_password' => $seoValues['api.seo.dataforseo.password'],
            'serpapi_enabled' => (bool) $seoValues['api.seo.serpapi.enabled'],
            'serpapi_api_key' => $seoValues['api.seo.serpapi.api_key'],
            'mangools_enabled' => (bool) $seoValues['api.seo.mangools.enabled'],
            'mangools_api_key' => $seoValues['api.seo.mangools.api_key'],
            'spyfu_enabled' => (bool) $seoValues['api.seo.spyfu.enabled'],
            'spyfu_api_key' => $seoValues['api.seo.spyfu.api_key'],
            'majestic_enabled' => (bool) $seoValues['api.seo.majestic.enabled'],
            'majestic_api_key' => $seoValues['api.seo.majestic.api_key'],
            // Commercial APIs
            'moz_enabled' => (bool) $seoValues['api.seo.moz.enabled'],
            'moz_access_id' => $seoValues['api.seo.moz.access_id'],
            'moz_secret_key' => $seoValues['api.seo.moz.secret_key'],
            'ahrefs_enabled' => (bool) $seoValues['api.seo.ahrefs.enabled'],
            'ahrefs_api_key' => $seoValues['api.seo.ahrefs.api_key'],
            'semrush_enabled' => (bool) $seoValues['api.seo.semrush.enabled'],
            'semrush_api_key' => $seoValues['api.seo.semrush.api_key'],
            'screaming_frog_enabled' => (bool) $seoValues['api.seo.screaming_frog.enabled'],
            'screaming_frog_license_key' => $seoValues['api.seo.screaming_frog.license_key'],
        ];
    }

    public function setTab(string $tab): void
    {
        $allowed = ['ai', 'brand_monitoring', 'social', 'seo'];
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
            // Free news APIs
            'api.brand.mediastack.api_key',
            'api.brand.gnews.api_key',
            // Commercial APIs
            'api.brand.brandwatch.api_key',
            'api.brand.brandwatch.api_secret',
            'api.brand.mention.api_key',
            'api.brand.brand24.api_key',
            'api.brand.sprout_social.api_key',
            'api.brand.sprout_social.api_secret',
            'api.brand.meltwater.api_key',
            'api.brand.meltwater.api_secret',
            'api.brand.talkwalker.api_key',
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
            // Free news APIs
            'api.brand.mediastack.api_key' => $this->brandMonitoring['mediastack_api_key'] ?? '',
            'api.brand.mediastack.enabled' => (bool) ($this->brandMonitoring['mediastack_enabled'] ?? false),
            'api.brand.gnews.api_key' => $this->brandMonitoring['gnews_api_key'] ?? '',
            'api.brand.gnews.enabled' => (bool) ($this->brandMonitoring['gnews_enabled'] ?? false),
            // Commercial APIs
            'api.brand.brandwatch.api_key' => $this->brandMonitoring['brandwatch_api_key'] ?? '',
            'api.brand.brandwatch.api_secret' => $this->brandMonitoring['brandwatch_api_secret'] ?? '',
            'api.brand.brandwatch.project_id' => $this->brandMonitoring['brandwatch_project_id'] ?? '',
            'api.brand.brandwatch.enabled' => (bool) ($this->brandMonitoring['brandwatch_enabled'] ?? false),
            'api.brand.mention.api_key' => $this->brandMonitoring['mention_api_key'] ?? '',
            'api.brand.mention.account_id' => $this->brandMonitoring['mention_account_id'] ?? '',
            'api.brand.mention.enabled' => (bool) ($this->brandMonitoring['mention_enabled'] ?? false),
            'api.brand.brand24.api_key' => $this->brandMonitoring['brand24_api_key'] ?? '',
            'api.brand.brand24.project_id' => $this->brandMonitoring['brand24_project_id'] ?? '',
            'api.brand.brand24.enabled' => (bool) ($this->brandMonitoring['brand24_enabled'] ?? false),
            'api.brand.sprout_social.api_key' => $this->brandMonitoring['sprout_social_api_key'] ?? '',
            'api.brand.sprout_social.api_secret' => $this->brandMonitoring['sprout_social_api_secret'] ?? '',
            'api.brand.sprout_social.enabled' => (bool) ($this->brandMonitoring['sprout_social_enabled'] ?? false),
            'api.brand.meltwater.api_key' => $this->brandMonitoring['meltwater_api_key'] ?? '',
            'api.brand.meltwater.api_secret' => $this->brandMonitoring['meltwater_api_secret'] ?? '',
            'api.brand.meltwater.enabled' => (bool) ($this->brandMonitoring['meltwater_enabled'] ?? false),
            'api.brand.talkwalker.api_key' => $this->brandMonitoring['talkwalker_api_key'] ?? '',
            'api.brand.talkwalker.project_id' => $this->brandMonitoring['talkwalker_project_id'] ?? '',
            'api.brand.talkwalker.enabled' => (bool) ($this->brandMonitoring['talkwalker_enabled'] ?? false),
        ], 'api.brand', $encryptedKeys);

        session()->flash('success', 'Brand monitoring settings saved successfully.');
    }

    public function saveSeoSettings(SettingsService $settings): void
    {
        $encryptedKeys = [
            'api.seo.google_search_console.client_secret',
            'api.seo.google_search_console.refresh_token',
            'api.seo.google_pagespeed.api_key',
            'api.seo.bing_webmaster.api_key',
            'api.seo.ubersuggest.api_key',
            'api.seo.keywords_everywhere.api_key',
            'api.seo.dataforseo.password',
            'api.seo.serpapi.api_key',
            'api.seo.mangools.api_key',
            'api.seo.spyfu.api_key',
            'api.seo.majestic.api_key',
            'api.seo.moz.secret_key',
            'api.seo.ahrefs.api_key',
            'api.seo.semrush.api_key',
            'api.seo.screaming_frog.license_key',
        ];

        $settings->setMany([
            // Free APIs
            'api.seo.google_search_console.enabled' => (bool) ($this->seo['google_search_console_enabled'] ?? false),
            'api.seo.google_search_console.client_id' => $this->seo['google_search_console_client_id'] ?? '',
            'api.seo.google_search_console.client_secret' => $this->seo['google_search_console_client_secret'] ?? '',
            'api.seo.google_search_console.refresh_token' => $this->seo['google_search_console_refresh_token'] ?? '',
            'api.seo.google_pagespeed.enabled' => (bool) ($this->seo['google_pagespeed_enabled'] ?? true),
            'api.seo.google_pagespeed.api_key' => $this->seo['google_pagespeed_api_key'] ?? '',
            'api.seo.bing_webmaster.enabled' => (bool) ($this->seo['bing_webmaster_enabled'] ?? false),
            'api.seo.bing_webmaster.api_key' => $this->seo['bing_webmaster_api_key'] ?? '',
            'api.seo.ubersuggest.enabled' => (bool) ($this->seo['ubersuggest_enabled'] ?? false),
            'api.seo.ubersuggest.api_key' => $this->seo['ubersuggest_api_key'] ?? '',
            'api.seo.keywords_everywhere.enabled' => (bool) ($this->seo['keywords_everywhere_enabled'] ?? false),
            'api.seo.keywords_everywhere.api_key' => $this->seo['keywords_everywhere_api_key'] ?? '',
            // Low-cost APIs
            'api.seo.dataforseo.enabled' => (bool) ($this->seo['dataforseo_enabled'] ?? false),
            'api.seo.dataforseo.login' => $this->seo['dataforseo_login'] ?? '',
            'api.seo.dataforseo.password' => $this->seo['dataforseo_password'] ?? '',
            'api.seo.serpapi.enabled' => (bool) ($this->seo['serpapi_enabled'] ?? false),
            'api.seo.serpapi.api_key' => $this->seo['serpapi_api_key'] ?? '',
            'api.seo.mangools.enabled' => (bool) ($this->seo['mangools_enabled'] ?? false),
            'api.seo.mangools.api_key' => $this->seo['mangools_api_key'] ?? '',
            'api.seo.spyfu.enabled' => (bool) ($this->seo['spyfu_enabled'] ?? false),
            'api.seo.spyfu.api_key' => $this->seo['spyfu_api_key'] ?? '',
            'api.seo.majestic.enabled' => (bool) ($this->seo['majestic_enabled'] ?? false),
            'api.seo.majestic.api_key' => $this->seo['majestic_api_key'] ?? '',
            // Commercial APIs
            'api.seo.moz.enabled' => (bool) ($this->seo['moz_enabled'] ?? false),
            'api.seo.moz.access_id' => $this->seo['moz_access_id'] ?? '',
            'api.seo.moz.secret_key' => $this->seo['moz_secret_key'] ?? '',
            'api.seo.ahrefs.enabled' => (bool) ($this->seo['ahrefs_enabled'] ?? false),
            'api.seo.ahrefs.api_key' => $this->seo['ahrefs_api_key'] ?? '',
            'api.seo.semrush.enabled' => (bool) ($this->seo['semrush_enabled'] ?? false),
            'api.seo.semrush.api_key' => $this->seo['semrush_api_key'] ?? '',
            'api.seo.screaming_frog.enabled' => (bool) ($this->seo['screaming_frog_enabled'] ?? false),
            'api.seo.screaming_frog.license_key' => $this->seo['screaming_frog_license_key'] ?? '',
        ], 'api.seo', $encryptedKeys);

        session()->flash('success', 'SEO integration settings saved successfully.');
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
                // SEO APIs
                'google_pagespeed' => $this->testGooglePageSpeed(),
                'dataforseo' => $this->testDataForSEO(),
                'serpapi' => $this->testSerpApi(),
                'moz' => $this->testMoz(),
                'ahrefs' => $this->testAhrefs(),
                'semrush' => $this->testSEMrush(),
                // Commercial Brand Monitoring
                'mention' => $this->testMention(),
                'brand24' => $this->testBrand24(),
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

    protected function testGooglePageSpeed(): array
    {
        $apiKey = $this->seo['google_pagespeed_api_key'] ?? '';
        // PageSpeed API works without key (with limits), but we test with key if provided
        $params = ['url' => 'https://www.google.com', 'strategy' => 'desktop'];
        if (!empty($apiKey)) {
            $params['key'] = $apiKey;
        }

        $response = Http::timeout(30)
            ->get('https://www.googleapis.com/pagespeedonline/v5/runPagespeed', $params);

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Connected successfully'];
        }

        return ['success' => false, 'message' => 'Connection failed: ' . ($response->json('error.message') ?? $response->status())];
    }

    protected function testDataForSEO(): array
    {
        $login = $this->seo['dataforseo_login'] ?? '';
        $password = $this->seo['dataforseo_password'] ?? '';
        if (empty($login) || empty($password)) {
            return ['success' => false, 'message' => 'Login or password not configured'];
        }

        $response = Http::withBasicAuth($login, $password)
            ->timeout(10)
            ->get('https://api.dataforseo.com/v3/serp/google/organic/locations');

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Connected successfully'];
        }

        return ['success' => false, 'message' => 'Connection failed: ' . ($response->json('status_message') ?? $response->status())];
    }

    protected function testSerpApi(): array
    {
        $apiKey = $this->seo['serpapi_api_key'] ?? '';
        if (empty($apiKey)) {
            return ['success' => false, 'message' => 'API key not configured'];
        }

        $response = Http::timeout(10)
            ->get('https://serpapi.com/account', ['api_key' => $apiKey]);

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Connected successfully'];
        }

        return ['success' => false, 'message' => 'Connection failed: ' . ($response->json('error') ?? $response->status())];
    }

    protected function testMoz(): array
    {
        $accessId = $this->seo['moz_access_id'] ?? '';
        $secretKey = $this->seo['moz_secret_key'] ?? '';
        if (empty($accessId) || empty($secretKey)) {
            return ['success' => false, 'message' => 'Access ID or Secret Key not configured'];
        }

        $response = Http::withBasicAuth($accessId, $secretKey)
            ->timeout(15)
            ->post('https://lsapi.seomoz.com/v2/url_metrics', [
                'targets' => ['moz.com'],
            ]);

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Connected successfully'];
        }

        return ['success' => false, 'message' => 'Connection failed: ' . ($response->json('error_message') ?? $response->status())];
    }

    protected function testAhrefs(): array
    {
        $apiKey = $this->seo['ahrefs_api_key'] ?? '';
        if (empty($apiKey)) {
            return ['success' => false, 'message' => 'API key not configured'];
        }

        $response = Http::withToken($apiKey)
            ->timeout(10)
            ->get('https://api.ahrefs.com/v3/subscription-info');

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Connected successfully'];
        }

        return ['success' => false, 'message' => 'Connection failed: ' . ($response->json('error') ?? $response->status())];
    }

    protected function testSEMrush(): array
    {
        $apiKey = $this->seo['semrush_api_key'] ?? '';
        if (empty($apiKey)) {
            return ['success' => false, 'message' => 'API key not configured'];
        }

        $response = Http::timeout(10)
            ->get('https://api.semrush.com/', [
                'type' => 'domain_ranks',
                'key' => $apiKey,
                'domain' => 'semrush.com',
                'database' => 'us',
            ]);

        if ($response->successful() && !str_contains($response->body(), 'ERROR')) {
            return ['success' => true, 'message' => 'Connected successfully'];
        }

        return ['success' => false, 'message' => 'Connection failed: ' . substr($response->body(), 0, 100)];
    }

    protected function testMention(): array
    {
        $apiKey = $this->brandMonitoring['mention_api_key'] ?? '';
        if (empty($apiKey)) {
            return ['success' => false, 'message' => 'API key not configured'];
        }

        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
            ->timeout(10)
            ->get('https://api.mention.com/api/accounts/me');

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Connected successfully'];
        }

        return ['success' => false, 'message' => 'Connection failed: ' . ($response->json('message') ?? $response->status())];
    }

    protected function testBrand24(): array
    {
        $apiKey = $this->brandMonitoring['brand24_api_key'] ?? '';
        if (empty($apiKey)) {
            return ['success' => false, 'message' => 'API key not configured'];
        }

        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
            ->timeout(10)
            ->get('https://api.brand24.com/v3/account/projects');

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Connected successfully'];
        }

        return ['success' => false, 'message' => 'Connection failed: ' . ($response->json('message') ?? $response->status())];
    }

    public function render()
    {
        return view('livewire.admin.settings.api-settings')->layout('layouts.admin');
    }
}
