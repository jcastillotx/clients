<?php

namespace App\Http\Livewire\Admin\Settings;

use App\Services\BrandingService;
use App\Services\PlatformFeatureService;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithFileUploads;
use Stripe\Stripe;
use Stripe\Account as StripeAccount;

class SystemSettings extends Component
{
    use WithFileUploads;

    public string $tab = 'general';

    public array $general = [];

    public array $email = [];

    public array $payment = [];

    public array $storage = [];

    public array $notifications = [];

    public array $security = [];

    public array $branding = [];

    public array $integrations = [];

    public array $integrationStatus = [];

    public array $platformModules = [];

    public ?string $test_email_to = null;

    public $logo_upload;
    public $login_logo_upload;
    public $dashboard_logo_upload;
    public $login_background_upload;
    public $favicon_upload;
    public $document_logo_upload;

    public function mount(SettingsService $settings): void
    {
        abort_unless(Auth::user()?->can('manage settings'), 403);

        $g = $settings->getMany([
            'general.company_name' => config('app.name'),
            'general.address' => '',
            'general.phone' => '',
            'general.website' => '',
            'general.timezone' => config('app.timezone', 'UTC'),
            'general.business_hours' => 'Mon-Fri 9:00-17:00',
            'general.currency' => 'USD',
            'general.date_format' => 'M d, Y',
            'general.time_format' => 'h:i A',
            'general.language' => config('app.locale', 'en'),
        ]);
        $this->general = [
            'company_name' => $g['general.company_name'],
            'address' => $g['general.address'],
            'phone' => $g['general.phone'],
            'website' => $g['general.website'],
            'timezone' => $g['general.timezone'],
            'business_hours' => $g['general.business_hours'],
            'currency' => $g['general.currency'],
            'date_format' => $g['general.date_format'],
            'time_format' => $g['general.time_format'],
            'language' => $g['general.language'],
        ];

        $e = $settings->getMany([
            'email.smtp.host' => '',
            'email.smtp.port' => 587,
            'email.smtp.username' => '',
            'email.smtp.password' => '',
            'email.smtp.encryption' => 'tls',
            'email.from.address' => '',
            'email.from.name' => '',
            'email.signature' => '',
            'email.template.design' => null,
            'email.template.html' => '<div>{{ $content }}</div>',
            'email.events.invoice_paid' => true,
            'email.events.request_created' => true,
            'email.events.contract_signed' => true,
        ]);
        $this->email = [
            'smtp_host' => $e['email.smtp.host'],
            'smtp_port' => $e['email.smtp.port'],
            'smtp_username' => $e['email.smtp.username'],
            'smtp_password' => $e['email.smtp.password'],
            'smtp_encryption' => $e['email.smtp.encryption'],
            'from_address' => $e['email.from.address'],
            'from_name' => $e['email.from.name'],
            'signature' => $e['email.signature'],
            'template_design' => $e['email.template.design'],
            'template_html' => $e['email.template.html'],
            'events_invoice_paid' => (bool) $e['email.events.invoice_paid'],
            'events_request_created' => (bool) $e['email.events.request_created'],
            'events_contract_signed' => (bool) $e['email.events.contract_signed'],
        ];

        $p = $settings->getMany([
            'payment.stripe.mode' => 'test',
            'payment.stripe.test_public' => '',
            'payment.stripe.test_secret' => '',
            'payment.stripe.live_public' => '',
            'payment.stripe.live_secret' => '',
            'payment.paypal.client_id' => '',
            'payment.paypal.secret' => '',
            'payment.terms' => 'Net 30',
            'payment.late_fee.enabled' => false,
            'payment.late_fee.percent' => 0,
            'payment.tax_rate' => 0,
            'payment.accepted_methods' => ['card', 'ach'],
        ]);
        $this->payment = [
            'stripe_mode' => $p['payment.stripe.mode'],
            'stripe_test_public' => $p['payment.stripe.test_public'],
            'stripe_test_secret' => $p['payment.stripe.test_secret'],
            'stripe_live_public' => $p['payment.stripe.live_public'],
            'stripe_live_secret' => $p['payment.stripe.live_secret'],
            'paypal_client_id' => $p['payment.paypal.client_id'],
            'paypal_secret' => $p['payment.paypal.secret'],
            'payment_terms' => $p['payment.terms'],
            'late_fee_enabled' => (bool) $p['payment.late_fee.enabled'],
            'late_fee_percent' => $p['payment.late_fee.percent'],
            'tax_rate' => $p['payment.tax_rate'],
            'accepted_methods' => (array) $p['payment.accepted_methods'],
        ];

        $s = $settings->getMany([
            'storage.default_provider' => 'local',
            'storage.max_upload_mb' => 25,
            'storage.allowed_file_types' => 'pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
            'storage.quota.basic_gb' => 5,
            'storage.quota.standard_gb' => 25,
            'storage.quota.premium_gb' => 100,
            'storage.quota.enterprise_gb' => 500,
            'storage.retention_days' => 0,
            'storage.backup.enabled' => false,
            'storage.backup.provider' => 's3',
        ]);
        $this->storage = [
            'default_provider' => $s['storage.default_provider'],
            'max_upload_mb' => $s['storage.max_upload_mb'],
            'allowed_file_types' => $s['storage.allowed_file_types'],
            'quota_basic_gb' => $s['storage.quota.basic_gb'],
            'quota_standard_gb' => $s['storage.quota.standard_gb'],
            'quota_premium_gb' => $s['storage.quota.premium_gb'],
            'quota_enterprise_gb' => $s['storage.quota.enterprise_gb'],
            'retention_days' => $s['storage.retention_days'],
            'backup_enabled' => (bool) $s['storage.backup.enabled'],
            'backup_provider' => $s['storage.backup.provider'],
        ];

        $n = $settings->getMany([
            'notifications.admin.email' => true,
            'notifications.client.email_default' => true,
            'notifications.slack.webhook_url' => '',
            'notifications.teams.webhook_url' => '',
            'notifications.push.enabled' => false,
            'notifications.sms.enabled' => false,
            'notifications.sms.twilio_sid' => '',
            'notifications.sms.twilio_token' => '',
            'notifications.sms.twilio_from' => '',
        ]);
        $this->notifications = [
            'admin_email' => (bool) $n['notifications.admin.email'],
            'client_email_default' => (bool) $n['notifications.client.email_default'],
            'slack_webhook_url' => $n['notifications.slack.webhook_url'],
            'teams_webhook_url' => $n['notifications.teams.webhook_url'],
            'push_enabled' => (bool) $n['notifications.push.enabled'],
            'sms_enabled' => (bool) $n['notifications.sms.enabled'],
            'twilio_sid' => $n['notifications.sms.twilio_sid'],
            'twilio_token' => $n['notifications.sms.twilio_token'],
            'twilio_from' => $n['notifications.sms.twilio_from'],
        ];

        $sec = $settings->getMany([
            'security.2fa.enforce' => false,
            'security.password.min_length' => 10,
            'security.password.require_symbols' => false,
            'security.password.expiration_days' => 0,
            'security.session.timeout_minutes' => 120,
            'security.ip.whitelist' => '',
            'security.ip.blacklist' => '',
            'security.login.max_attempts' => 10,
            'security.api.rate_limit_per_minute' => 60,
        ]);
        $this->security = [
            'enforce_2fa' => (bool) $sec['security.2fa.enforce'],
            'password_min_length' => (int) $sec['security.password.min_length'],
            'password_require_symbols' => (bool) $sec['security.password.require_symbols'],
            'password_expiration_days' => (int) $sec['security.password.expiration_days'],
            'session_timeout_minutes' => (int) $sec['security.session.timeout_minutes'],
            'ip_whitelist' => (string) $sec['security.ip.whitelist'],
            'ip_blacklist' => (string) $sec['security.ip.blacklist'],
            'login_max_attempts' => (int) $sec['security.login.max_attempts'],
            'api_rate_limit_per_minute' => (int) $sec['security.api.rate_limit_per_minute'],
        ];

        // Get defaults for branding
        $brandDefaults = BrandingService::defaults();
        
        $b = $settings->getMany([
            'branding.logo_path' => '',
            'branding.login_logo_path' => '',
            'branding.dashboard_logo_path' => '',
            'branding.login_background_path' => '',
            'branding.favicon_path' => '',
            'branding.document_logo_path' => '',
            'branding.colors.primary' => $brandDefaults['color_primary'],
            'branding.colors.secondary' => $brandDefaults['color_secondary'],
            'branding.colors.accent' => $brandDefaults['color_accent'],
            'branding.buttons.primary' => $brandDefaults['button_primary'],
            'branding.buttons.primary_hover' => $brandDefaults['button_primary_hover'],
            'branding.buttons.secondary' => $brandDefaults['button_secondary'],
            'branding.buttons.secondary_hover' => $brandDefaults['button_secondary_hover'],
            'branding.sidebar.bg' => $brandDefaults['sidebar_bg'],
            'branding.sidebar.text' => $brandDefaults['sidebar_text'],
            'branding.sidebar.hover' => $brandDefaults['sidebar_hover'],
            'branding.sidebar.active' => $brandDefaults['sidebar_active'],
            'branding.navbar.bg' => $brandDefaults['navbar_bg'],
            'branding.navbar.text' => $brandDefaults['navbar_text'],
            'branding.content.bg' => $brandDefaults['content_bg'],
            'branding.invoice_template' => 'default',
            'branding.email.header_html' => '',
            'branding.email.footer_html' => '',
            'branding.site.header_html' => '',
            'branding.site.footer_html' => '',
            'branding.admin.header_html' => '',
            'branding.admin.footer_html' => '',
            'branding.custom_domain' => '',
            'branding.custom_css' => '',
            'branding.platform_name' => '',
            'branding.company_name' => '',
            'branding.tagline' => '',
        ]);
        // Get defaults from BrandingService
        $defaults = BrandingService::defaults();

        $this->branding = [
            'logo_path' => $b['branding.logo_path'] ?: '',
            'login_logo_path' => $b['branding.login_logo_path'] ?: '',
            'dashboard_logo_path' => $b['branding.dashboard_logo_path'] ?: '',
            'login_background_path' => $b['branding.login_background_path'] ?: '',
            'favicon_path' => $b['branding.favicon_path'] ?: '',
            'document_logo_path' => $b['branding.document_logo_path'] ?: '',
            'color_primary' => $b['branding.colors.primary'] ?: $defaults['color_primary'],
            'color_secondary' => $b['branding.colors.secondary'] ?: $defaults['color_secondary'],
            'color_accent' => $b['branding.colors.accent'] ?: $defaults['color_accent'],
            'button_primary' => $b['branding.buttons.primary'] ?: $defaults['button_primary'],
            'button_primary_hover' => $b['branding.buttons.primary_hover'] ?: $defaults['button_primary_hover'],
            'button_secondary' => $b['branding.buttons.secondary'] ?: $defaults['button_secondary'],
            'button_secondary_hover' => $b['branding.buttons.secondary_hover'] ?: $defaults['button_secondary_hover'],
            'sidebar_bg' => $b['branding.sidebar.bg'] ?: $defaults['sidebar_bg'],
            'sidebar_text' => $b['branding.sidebar.text'] ?: $defaults['sidebar_text'],
            'sidebar_hover' => $b['branding.sidebar.hover'] ?: $defaults['sidebar_hover'],
            'sidebar_active' => $b['branding.sidebar.active'] ?: $defaults['sidebar_active'],
            'navbar_bg' => $b['branding.navbar.bg'] ?: $defaults['navbar_bg'],
            'navbar_text' => $b['branding.navbar.text'] ?: $defaults['navbar_text'],
            'content_bg' => $b['branding.content.bg'] ?: $defaults['content_bg'],
            'invoice_template' => $b['branding.invoice_template'] ?: 'default',
            'email_header_html' => $b['branding.email.header_html'] ?: '',
            'email_footer_html' => $b['branding.email.footer_html'] ?: '',
            'site_header_html' => $b['branding.site.header_html'] ?: $b['branding.admin.header_html'] ?: '',
            'site_footer_html' => $b['branding.site.footer_html'] ?: $b['branding.admin.footer_html'] ?: '',
            'custom_domain' => $b['branding.custom_domain'] ?: '',
            'custom_css' => $b['branding.custom_css'] ?: '',
            'platform_name' => $b['branding.platform_name'] ?: '',
            'company_name' => $b['branding.company_name'] ?: '',
            'tagline' => $b['branding.tagline'] ?: '',
        ];
    }

    public function setTab(string $tab): void
    {
        $allowed = ['general', 'email', 'payment', 'storage', 'notifications', 'security', 'branding', 'integrations', 'platform'];
        if (in_array($tab, $allowed, true)) {
            // Restrict branding and platform tabs to super admin only
            if (in_array($tab, ['branding', 'platform']) && !Auth::user()?->hasRole('super_admin')) {
                session()->flash('error', 'Only super admins can access this section.');
                return;
            }
            
            $this->tab = $tab;
            if ($tab === 'integrations') {
                $this->loadIntegrationStatus();
            }
            if ($tab === 'platform') {
                $this->loadPlatformModules();
            }
        }
    }

    public function loadPlatformModules(): void
    {
        /** @var PlatformFeatureService $platformService */
        $platformService = app(PlatformFeatureService::class);
        $this->platformModules = [];

        foreach (PlatformFeatureService::$modules as $key => $module) {
            $this->platformModules[$key] = $platformService->isEnabled($key);
        }
    }

    public function togglePlatformModule(string $module): void
    {
        // Only super_admin can toggle platform features
        if (! Auth::user()?->hasRole('super_admin')) {
            session()->flash('error', 'Only super admins can toggle platform features.');
            return;
        }

        /** @var PlatformFeatureService $platformService */
        $platformService = app(PlatformFeatureService::class);
        $newState = $platformService->toggle($module);
        $this->platformModules[$module] = $newState;

        $moduleName = PlatformFeatureService::$modules[$module]['name'] ?? $module;
        $status = $newState ? 'enabled' : 'disabled';
        session()->flash('success', "{$moduleName} has been {$status}.");
    }

    public function enableAllPlatformModules(): void
    {
        if (! Auth::user()?->hasRole('super_admin')) {
            session()->flash('error', 'Only super admins can toggle platform features.');
            return;
        }

        /** @var PlatformFeatureService $platformService */
        $platformService = app(PlatformFeatureService::class);

        foreach (array_keys(PlatformFeatureService::$modules) as $module) {
            $platformService->enable($module);
            $this->platformModules[$module] = true;
        }

        session()->flash('success', 'All platform modules have been enabled.');
    }

    public function disableAllPlatformModules(): void
    {
        if (! Auth::user()?->hasRole('super_admin')) {
            session()->flash('error', 'Only super admins can toggle platform features.');
            return;
        }

        /** @var PlatformFeatureService $platformService */
        $platformService = app(PlatformFeatureService::class);

        foreach (array_keys(PlatformFeatureService::$modules) as $module) {
            $platformService->disable($module);
            $this->platformModules[$module] = false;
        }

        session()->flash('success', 'All platform modules have been disabled.');
    }

    public function loadIntegrationStatus(): void
    {
        $this->integrationStatus = [];

        // Stripe
        $stripeKey = config('services.stripe.secret') ?: ($this->payment['stripe_mode'] === 'live'
            ? $this->payment['stripe_live_secret']
            : $this->payment['stripe_test_secret']);
        $this->integrationStatus['stripe'] = [
            'configured' => !empty($stripeKey),
            'connected' => false,
            'message' => null,
            'can_connect' => false,
        ];

        // PayPal
        $paypalClientId = $this->payment['paypal_client_id'] ?? '';
        $paypalSecret = $this->payment['paypal_secret'] ?? '';
        $this->integrationStatus['paypal'] = [
            'configured' => !empty($paypalClientId) && !empty($paypalSecret),
            'connected' => false,
            'message' => null,
            'can_connect' => false,
        ];

        // OpenAI
        $this->integrationStatus['openai'] = [
            'configured' => !empty(config('ai-providers.providers.openai.api_key')),
            'connected' => false,
            'message' => null,
            'can_connect' => false,
        ];

        // Dropbox (OAuth)
        $dropboxConfigured = !empty(config('services.dropbox.app_key')) && !empty(config('services.dropbox.app_secret'));
        $this->integrationStatus['dropbox'] = [
            'configured' => $dropboxConfigured,
            'connected' => false,
            'message' => null,
            'can_connect' => $dropboxConfigured,
            'oauth_url' => $dropboxConfigured ? route('storage.dropbox.authorize') : null,
        ];

        // Google Drive (OAuth)
        $googleConfigured = !empty(config('storage-providers.google_drive.client_id')) && !empty(config('storage-providers.google_drive.client_secret'));
        $this->integrationStatus['google_drive'] = [
            'configured' => $googleConfigured,
            'connected' => false,
            'message' => null,
            'can_connect' => $googleConfigured,
            'oauth_url' => $googleConfigured ? route('storage.google-drive.authorize') : null,
        ];

        // NewsAPI
        $this->integrationStatus['newsapi'] = [
            'configured' => !empty(config('brand-monitoring.news.newsapi.api_key')),
            'connected' => false,
            'message' => null,
            'can_connect' => false,
        ];

        // YouTube
        $this->integrationStatus['youtube'] = [
            'configured' => !empty(config('brand-monitoring.social.youtube.api_key')),
            'connected' => false,
            'message' => null,
            'can_connect' => false,
        ];

        // Yelp
        $this->integrationStatus['yelp'] = [
            'configured' => !empty(config('brand-monitoring.reviews.yelp.api_key')),
            'connected' => false,
            'message' => null,
            'can_connect' => false,
        ];

        // Google Places
        $this->integrationStatus['google_places'] = [
            'configured' => !empty(config('brand-monitoring.reviews.google_places.api_key')),
            'connected' => false,
            'message' => null,
            'can_connect' => false,
        ];

        // Twilio
        $this->integrationStatus['twilio'] = [
            'configured' => !empty($this->notifications['twilio_sid']) && !empty($this->notifications['twilio_token']),
            'connected' => false,
            'message' => null,
            'can_connect' => false,
        ];

        // Slack
        $this->integrationStatus['slack'] = [
            'configured' => !empty($this->notifications['slack_webhook_url']),
            'connected' => false,
            'message' => null,
            'can_connect' => false,
        ];

        // Facebook (OAuth)
        $facebookConfigured = !empty(config('services.facebook.client_id')) && !empty(config('services.facebook.client_secret'));
        $this->integrationStatus['facebook'] = [
            'configured' => $facebookConfigured,
            'connected' => false,
            'message' => null,
            'can_connect' => $facebookConfigured,
            'oauth_url' => $facebookConfigured ? route('oauth.facebook.redirect') : null,
        ];

        // LinkedIn (OAuth)
        $linkedinConfigured = !empty(config('services.linkedin.client_id')) && !empty(config('services.linkedin.client_secret'));
        $this->integrationStatus['linkedin'] = [
            'configured' => $linkedinConfigured,
            'connected' => false,
            'message' => null,
            'can_connect' => $linkedinConfigured,
            'oauth_url' => $linkedinConfigured ? route('oauth.linkedin.redirect') : null,
        ];
    }

    public function testIntegration(string $service): void
    {
        $this->loadIntegrationStatus();

        try {
            switch ($service) {
                case 'stripe':
                    $stripeKey = config('services.stripe.secret') ?: ($this->payment['stripe_mode'] === 'live'
                        ? $this->payment['stripe_live_secret']
                        : $this->payment['stripe_test_secret']);
                    if (empty($stripeKey)) {
                        $this->integrationStatus['stripe']['message'] = 'API key not configured';
                        return;
                    }
                    Stripe::setApiKey($stripeKey);
                    $account = StripeAccount::retrieve();
                    $this->integrationStatus['stripe']['connected'] = true;
                    $this->integrationStatus['stripe']['message'] = 'Connected: ' . ($account->business_profile?->name ?? $account->id);
                    break;

                case 'paypal':
                    $clientId = $this->payment['paypal_client_id'] ?? '';
                    $secret = $this->payment['paypal_secret'] ?? '';
                    if (empty($clientId) || empty($secret)) {
                        $this->integrationStatus['paypal']['message'] = 'Credentials not configured';
                        return;
                    }
                    // Test by getting an access token from PayPal sandbox
                    $response = Http::asForm()
                        ->withBasicAuth($clientId, $secret)
                        ->timeout(10)
                        ->post('https://api-m.sandbox.paypal.com/v1/oauth2/token', [
                            'grant_type' => 'client_credentials',
                        ]);
                    if ($response->successful() && $response->json('access_token')) {
                        $this->integrationStatus['paypal']['connected'] = true;
                        $this->integrationStatus['paypal']['message'] = 'Connected successfully (Sandbox)';
                    } else {
                        $this->integrationStatus['paypal']['message'] = 'Connection failed: ' . ($response->json('error_description') ?? $response->status());
                    }
                    break;

                case 'openai':
                    $apiKey = config('ai-providers.providers.openai.api_key');
                    if (empty($apiKey)) {
                        $this->integrationStatus['openai']['message'] = 'API key not configured';
                        return;
                    }
                    $response = Http::withToken($apiKey)
                        ->timeout(10)
                        ->get('https://api.openai.com/v1/models');
                    if ($response->successful()) {
                        $this->integrationStatus['openai']['connected'] = true;
                        $this->integrationStatus['openai']['message'] = 'Connected successfully';
                    } else {
                        $this->integrationStatus['openai']['message'] = 'Connection failed: ' . $response->status();
                    }
                    break;

                case 'newsapi':
                    $apiKey = config('brand-monitoring.news.newsapi.api_key');
                    if (empty($apiKey)) {
                        $this->integrationStatus['newsapi']['message'] = 'API key not configured';
                        return;
                    }
                    $response = Http::timeout(10)
                        ->get('https://newsapi.org/v2/top-headlines', [
                            'apiKey' => $apiKey,
                            'country' => 'us',
                            'pageSize' => 1,
                        ]);
                    if ($response->successful()) {
                        $this->integrationStatus['newsapi']['connected'] = true;
                        $this->integrationStatus['newsapi']['message'] = 'Connected successfully';
                    } else {
                        $this->integrationStatus['newsapi']['message'] = 'Connection failed: ' . ($response->json('message') ?? $response->status());
                    }
                    break;

                case 'youtube':
                    $apiKey = config('brand-monitoring.social.youtube.api_key');
                    if (empty($apiKey)) {
                        $this->integrationStatus['youtube']['message'] = 'API key not configured';
                        return;
                    }
                    $response = Http::timeout(10)
                        ->get('https://www.googleapis.com/youtube/v3/search', [
                            'part' => 'snippet',
                            'q' => 'test',
                            'maxResults' => 1,
                            'key' => $apiKey,
                        ]);
                    if ($response->successful()) {
                        $this->integrationStatus['youtube']['connected'] = true;
                        $this->integrationStatus['youtube']['message'] = 'Connected successfully';
                    } else {
                        $this->integrationStatus['youtube']['message'] = 'Connection failed: ' . ($response->json('error.message') ?? $response->status());
                    }
                    break;

                case 'yelp':
                    $apiKey = config('brand-monitoring.reviews.yelp.api_key');
                    if (empty($apiKey)) {
                        $this->integrationStatus['yelp']['message'] = 'API key not configured';
                        return;
                    }
                    $response = Http::withToken($apiKey)
                        ->timeout(10)
                        ->get('https://api.yelp.com/v3/businesses/search', [
                            'term' => 'coffee',
                            'location' => 'NYC',
                            'limit' => 1,
                        ]);
                    if ($response->successful()) {
                        $this->integrationStatus['yelp']['connected'] = true;
                        $this->integrationStatus['yelp']['message'] = 'Connected successfully';
                    } else {
                        $this->integrationStatus['yelp']['message'] = 'Connection failed: ' . ($response->json('error.description') ?? $response->status());
                    }
                    break;

                case 'google_places':
                    $apiKey = config('brand-monitoring.reviews.google_places.api_key');
                    if (empty($apiKey)) {
                        $this->integrationStatus['google_places']['message'] = 'API key not configured';
                        return;
                    }
                    $response = Http::timeout(10)
                        ->get('https://maps.googleapis.com/maps/api/place/findplacefromtext/json', [
                            'input' => 'Google',
                            'inputtype' => 'textquery',
                            'fields' => 'place_id',
                            'key' => $apiKey,
                        ]);
                    if ($response->successful() && ($response->json('status') === 'OK' || $response->json('status') === 'ZERO_RESULTS')) {
                        $this->integrationStatus['google_places']['connected'] = true;
                        $this->integrationStatus['google_places']['message'] = 'Connected successfully';
                    } else {
                        $this->integrationStatus['google_places']['message'] = 'Connection failed: ' . ($response->json('error_message') ?? $response->json('status') ?? $response->status());
                    }
                    break;

                case 'slack':
                    $webhookUrl = $this->notifications['slack_webhook_url'] ?? '';
                    if (empty($webhookUrl)) {
                        $this->integrationStatus['slack']['message'] = 'Webhook URL not configured';
                        return;
                    }
                    // Just validate URL format - don't actually send
                    if (filter_var($webhookUrl, FILTER_VALIDATE_URL) && str_contains($webhookUrl, 'hooks.slack.com')) {
                        $this->integrationStatus['slack']['connected'] = true;
                        $this->integrationStatus['slack']['message'] = 'Webhook URL configured';
                    } else {
                        $this->integrationStatus['slack']['message'] = 'Invalid webhook URL format';
                    }
                    break;

                case 'twilio':
                    $sid = $this->notifications['twilio_sid'] ?? '';
                    $token = $this->notifications['twilio_token'] ?? '';
                    if (empty($sid) || empty($token)) {
                        $this->integrationStatus['twilio']['message'] = 'Credentials not configured';
                        return;
                    }
                    $response = Http::withBasicAuth($sid, $token)
                        ->timeout(10)
                        ->get("https://api.twilio.com/2010-04-01/Accounts/{$sid}.json");
                    if ($response->successful()) {
                        $this->integrationStatus['twilio']['connected'] = true;
                        $this->integrationStatus['twilio']['message'] = 'Connected: ' . ($response->json('friendly_name') ?? $sid);
                    } else {
                        $this->integrationStatus['twilio']['message'] = 'Connection failed: ' . $response->status();
                    }
                    break;

                default:
                    $this->integrationStatus[$service]['message'] = 'Test not available for this service';
            }
        } catch (\Throwable $e) {
            $this->integrationStatus[$service]['message'] = 'Error: ' . $e->getMessage();
            $this->integrationStatus[$service]['connected'] = false;
        }
    }

    public function saveGeneral(SettingsService $settings): void
    {
        $settings->setMany([
            'general.company_name' => $this->general['company_name'] ?? '',
            'general.address' => $this->general['address'] ?? '',
            'general.phone' => $this->general['phone'] ?? '',
            'general.website' => $this->general['website'] ?? '',
            'general.timezone' => $this->general['timezone'] ?? 'UTC',
            'general.business_hours' => $this->general['business_hours'] ?? '',
            'general.currency' => $this->general['currency'] ?? 'USD',
            'general.date_format' => $this->general['date_format'] ?? 'M d, Y',
            'general.time_format' => $this->general['time_format'] ?? 'h:i A',
            'general.language' => $this->general['language'] ?? 'en',
        ], 'general');
        session()->flash('success', 'General settings saved.');
    }

    public function saveEmail(SettingsService $settings): void
    {
        $encrypted = [
            'email.smtp.password',
        ];
        $settings->setMany([
            'email.smtp.host' => $this->email['smtp_host'] ?? '',
            'email.smtp.port' => (int) ($this->email['smtp_port'] ?? 587),
            'email.smtp.username' => $this->email['smtp_username'] ?? '',
            'email.smtp.password' => $this->email['smtp_password'] ?? '',
            'email.smtp.encryption' => $this->email['smtp_encryption'] ?? 'tls',
            'email.from.address' => $this->email['from_address'] ?? '',
            'email.from.name' => $this->email['from_name'] ?? '',
            'email.signature' => $this->email['signature'] ?? '',
            'email.template.design' => $this->email['template_design'] ?? null,
            'email.template.html' => $this->email['template_html'] ?? '',
            'email.events.invoice_paid' => (bool) ($this->email['events_invoice_paid'] ?? true),
            'email.events.request_created' => (bool) ($this->email['events_request_created'] ?? true),
            'email.events.contract_signed' => (bool) ($this->email['events_contract_signed'] ?? true),
        ], 'email', $encrypted);
        session()->flash('success', 'Email settings saved.');
    }

    public function openEmailBuilder(): void
    {
        $this->dispatch('open-email-builder', design: $this->email['template_design'] ?? null);
    }

    /**
     * Save builder output (design JSON + exported HTML).
     */
    public function saveEmailTemplate(mixed $design, string $html, SettingsService $settings): void
    {
        $this->email['template_design'] = $design;
        $this->email['template_html'] = $html;

        $settings->setMany([
            'email.template.design' => $design,
            'email.template.html' => $html,
        ], 'email');

        session()->flash('success', 'Email template saved from builder.');
    }

    public function sendTestEmail(): void
    {
        Validator::make(['to' => $this->test_email_to], [
            'to' => ['required', 'email'],
        ])->validate();

        Mail::raw('This is a test email from System Settings.', function ($m) {
            $m->to($this->test_email_to)->subject('Test Email');
        });

        session()->flash('success', 'Test email sent (check mail configuration).');
    }

    public function savePayment(SettingsService $settings): void
    {
        $encrypted = [
            'payment.stripe.test_secret',
            'payment.stripe.live_secret',
            'payment.paypal.secret',
        ];
        $settings->setMany([
            'payment.stripe.mode' => $this->payment['stripe_mode'] ?? 'test',
            'payment.stripe.test_public' => $this->payment['stripe_test_public'] ?? '',
            'payment.stripe.test_secret' => $this->payment['stripe_test_secret'] ?? '',
            'payment.stripe.live_public' => $this->payment['stripe_live_public'] ?? '',
            'payment.stripe.live_secret' => $this->payment['stripe_live_secret'] ?? '',
            'payment.paypal.client_id' => $this->payment['paypal_client_id'] ?? '',
            'payment.paypal.secret' => $this->payment['paypal_secret'] ?? '',
            'payment.terms' => $this->payment['payment_terms'] ?? 'Net 30',
            'payment.late_fee.enabled' => (bool) ($this->payment['late_fee_enabled'] ?? false),
            'payment.late_fee.percent' => (float) ($this->payment['late_fee_percent'] ?? 0),
            'payment.tax_rate' => (float) ($this->payment['tax_rate'] ?? 0),
            'payment.accepted_methods' => (array) ($this->payment['accepted_methods'] ?? []),
        ], 'payment', $encrypted);
        session()->flash('success', 'Payment settings saved.');
    }

    public function saveStorage(SettingsService $settings): void
    {
        $settings->setMany([
            'storage.default_provider' => $this->storage['default_provider'] ?? 'local',
            'storage.max_upload_mb' => (int) ($this->storage['max_upload_mb'] ?? 25),
            'storage.allowed_file_types' => $this->storage['allowed_file_types'] ?? '',
            'storage.quota.basic_gb' => (int) ($this->storage['quota_basic_gb'] ?? 5),
            'storage.quota.standard_gb' => (int) ($this->storage['quota_standard_gb'] ?? 25),
            'storage.quota.premium_gb' => (int) ($this->storage['quota_premium_gb'] ?? 100),
            'storage.quota.enterprise_gb' => (int) ($this->storage['quota_enterprise_gb'] ?? 500),
            'storage.retention_days' => (int) ($this->storage['retention_days'] ?? 0),
            'storage.backup.enabled' => (bool) ($this->storage['backup_enabled'] ?? false),
            'storage.backup.provider' => $this->storage['backup_provider'] ?? 's3',
        ], 'storage');
        session()->flash('success', 'Storage settings saved.');
    }

    public function saveNotifications(SettingsService $settings): void
    {
        $encrypted = [
            'notifications.slack.webhook_url',
            'notifications.teams.webhook_url',
            'notifications.sms.twilio_sid',
            'notifications.sms.twilio_token',
        ];
        $settings->setMany([
            'notifications.admin.email' => (bool) ($this->notifications['admin_email'] ?? true),
            'notifications.client.email_default' => (bool) ($this->notifications['client_email_default'] ?? true),
            'notifications.slack.webhook_url' => $this->notifications['slack_webhook_url'] ?? '',
            'notifications.teams.webhook_url' => $this->notifications['teams_webhook_url'] ?? '',
            'notifications.push.enabled' => (bool) ($this->notifications['push_enabled'] ?? false),
            'notifications.sms.enabled' => (bool) ($this->notifications['sms_enabled'] ?? false),
            'notifications.sms.twilio_sid' => $this->notifications['twilio_sid'] ?? '',
            'notifications.sms.twilio_token' => $this->notifications['twilio_token'] ?? '',
            'notifications.sms.twilio_from' => $this->notifications['twilio_from'] ?? '',
        ], 'notifications', $encrypted);
        session()->flash('success', 'Notification settings saved.');
    }

    public function saveSecurity(SettingsService $settings): void
    {
        $settings->setMany([
            'security.2fa.enforce' => (bool) ($this->security['enforce_2fa'] ?? false),
            'security.password.min_length' => (int) ($this->security['password_min_length'] ?? 10),
            'security.password.require_symbols' => (bool) ($this->security['password_require_symbols'] ?? false),
            'security.password.expiration_days' => (int) ($this->security['password_expiration_days'] ?? 0),
            'security.session.timeout_minutes' => (int) ($this->security['session_timeout_minutes'] ?? 120),
            'security.ip.whitelist' => $this->security['ip_whitelist'] ?? '',
            'security.ip.blacklist' => $this->security['ip_blacklist'] ?? '',
            'security.login.max_attempts' => (int) ($this->security['login_max_attempts'] ?? 10),
            'security.api.rate_limit_per_minute' => (int) ($this->security['api_rate_limit_per_minute'] ?? 60),
        ], 'security');
        session()->flash('success', 'Security settings saved.');
    }

    public function uploadLogo(): void
    {
        /** @var SettingsService $settings */
        $settings = app(SettingsService::class);

        if (! $this->logo_upload) {
            return;
        }

        Validator::make(['logo' => $this->logo_upload], [
            'logo' => ['file', 'max:2048', 'mimes:png,jpg,jpeg,webp,svg'],
        ])->validate();

        $path = $this->logo_upload->store('branding', 'public');
        $this->branding['logo_path'] = $path;
        $settings->set('branding.logo_path', $path, 'branding');
        $this->logo_upload = null;

        app(BrandingService::class)->clearCache();
        session()->flash('success', 'Logo uploaded.');
    }

    public function uploadLoginLogo(): void
    {
        /** @var SettingsService $settings */
        $settings = app(SettingsService::class);

        if (! $this->login_logo_upload) {
            return;
        }

        Validator::make(['logo' => $this->login_logo_upload], [
            'logo' => ['file', 'max:2048', 'mimes:png,jpg,jpeg,webp,svg'],
        ])->validate();

        $path = $this->login_logo_upload->store('branding', 'public');
        $this->branding['login_logo_path'] = $path;
        $settings->set('branding.login_logo_path', $path, 'branding');
        $this->login_logo_upload = null;

        app(BrandingService::class)->clearCache();
        session()->flash('success', 'Login logo uploaded.');
    }

    public function uploadDashboardLogo(): void
    {
        /** @var SettingsService $settings */
        $settings = app(SettingsService::class);

        if (! $this->dashboard_logo_upload) {
            return;
        }

        Validator::make(['logo' => $this->dashboard_logo_upload], [
            'logo' => ['file', 'max:2048', 'mimes:png,jpg,jpeg,webp,svg'],
        ])->validate();

        $path = $this->dashboard_logo_upload->store('branding', 'public');
        $this->branding['dashboard_logo_path'] = $path;
        $settings->set('branding.dashboard_logo_path', $path, 'branding');
        $this->dashboard_logo_upload = null;

        app(BrandingService::class)->clearCache();
        session()->flash('success', 'Dashboard logo uploaded.');
    }

    public function uploadLoginBackground(): void
    {
        /** @var SettingsService $settings */
        $settings = app(SettingsService::class);

        if (! $this->login_background_upload) {
            return;
        }

        Validator::make(['bg' => $this->login_background_upload], [
            'bg' => ['file', 'max:5120', 'mimes:png,jpg,jpeg,webp'],
        ])->validate();

        $path = $this->login_background_upload->store('branding', 'public');
        $this->branding['login_background_path'] = $path;
        $settings->set('branding.login_background_path', $path, 'branding');
        $this->login_background_upload = null;

        app(BrandingService::class)->clearCache();
        session()->flash('success', 'Login background uploaded.');
    }

    public function uploadFavicon(): void
    {
        /** @var SettingsService $settings */
        $settings = app(SettingsService::class);

        if (! $this->favicon_upload) {
            return;
        }

        Validator::make(['favicon' => $this->favicon_upload], [
            'favicon' => ['file', 'max:512', 'mimes:png,ico,jpg,jpeg'],
        ])->validate();

        $path = $this->favicon_upload->store('branding', 'public');
        $this->branding['favicon_path'] = $path;
        $settings->set('branding.favicon_path', $path, 'branding');
        $this->favicon_upload = null;

        app(BrandingService::class)->clearCache();
        session()->flash('success', 'Favicon uploaded.');
    }

    public function uploadDocumentLogo(): void
    {
        /** @var SettingsService $settings */
        $settings = app(SettingsService::class);

        if (! $this->document_logo_upload) {
            return;
        }

        Validator::make(['document_logo' => $this->document_logo_upload], [
            'document_logo' => ['file', 'max:2048', 'mimes:png,jpg,jpeg,webp'],
        ])->validate();

        $path = $this->document_logo_upload->store('branding', 'public');
        $this->branding['document_logo_path'] = $path;
        $settings->set('branding.document_logo_path', $path, 'branding');
        $this->document_logo_upload = null;

        app(BrandingService::class)->clearCache();
        session()->flash('success', 'Document logo uploaded.');
    }

    public function applyColorPreset(string $preset): void
    {
        if (!Auth::user()?->hasRole('super_admin')) {
            session()->flash('error', 'Only super admins can modify branding.');
            return;
        }

        $presets = BrandingService::colorPresets();

        if (!isset($presets[$preset])) {
            session()->flash('error', 'Invalid color preset.');
            return;
        }

        $colors = $presets[$preset];

        // Apply all colors from the preset
        $this->branding['color_primary'] = $colors['color_primary'];
        $this->branding['color_secondary'] = $colors['color_secondary'] ?? $this->branding['color_secondary'];
        $this->branding['color_accent'] = $colors['color_accent'] ?? $this->branding['color_accent'];
        $this->branding['button_primary'] = $colors['color_primary'];
        $this->branding['button_primary_hover'] = $colors['color_primary_dark'];
        $this->branding['button_secondary'] = $colors['color_secondary'] ?? $this->branding['button_secondary'];
        
        // Apply sidebar colors
        if (isset($colors['sidebar_bg'])) {
            $this->branding['sidebar_bg'] = $colors['sidebar_bg'];
        }
        if (isset($colors['sidebar_text'])) {
            $this->branding['sidebar_text'] = $colors['sidebar_text'];
        }
        if (isset($colors['sidebar_hover'])) {
            $this->branding['sidebar_hover'] = $colors['sidebar_hover'];
        }
        $this->branding['sidebar_active'] = $colors['color_primary'];
        
        // Apply navbar colors
        if (isset($colors['navbar_bg'])) {
            $this->branding['navbar_bg'] = $colors['navbar_bg'];
        }
        if (isset($colors['navbar_text'])) {
            $this->branding['navbar_text'] = $colors['navbar_text'];
        }

        session()->flash('success', "Applied '{$colors['name']}' color preset. Click Save to apply changes.");
    }

    public function saveBranding(): void
    {
        // Only super admin can save branding
        if (!Auth::user()?->hasRole('super_admin')) {
            session()->flash('error', 'Only super admins can modify branding settings.');
            return;
        }

        /** @var SettingsService $settings */
        $settings = app(SettingsService::class);

        $settings->setMany([
            'branding.logo_path' => $this->branding['logo_path'] ?? '',
            'branding.login_logo_path' => $this->branding['login_logo_path'] ?? '',
            'branding.dashboard_logo_path' => $this->branding['dashboard_logo_path'] ?? '',
            'branding.login_background_path' => $this->branding['login_background_path'] ?? '',
            'branding.favicon_path' => $this->branding['favicon_path'] ?? '',
            'branding.document_logo_path' => $this->branding['document_logo_path'] ?? '',
            'branding.colors.primary' => $this->branding['color_primary'] ?? '#007bff',
            'branding.colors.secondary' => $this->branding['color_secondary'] ?? '#6c757d',
            'branding.colors.accent' => $this->branding['color_accent'] ?? '#28a745',
            'branding.buttons.primary' => $this->branding['button_primary'] ?? '',
            'branding.buttons.primary_hover' => $this->branding['button_primary_hover'] ?? '',
            'branding.buttons.secondary' => $this->branding['button_secondary'] ?? '',
            'branding.buttons.secondary_hover' => $this->branding['button_secondary_hover'] ?? '',
            'branding.sidebar.bg' => $this->branding['sidebar_bg'] ?? '#343a40',
            'branding.sidebar.text' => $this->branding['sidebar_text'] ?? '#c2c7d0',
            'branding.sidebar.hover' => $this->branding['sidebar_hover'] ?? '#495057',
            'branding.sidebar.active' => $this->branding['sidebar_active'] ?? '#007bff',
            'branding.navbar.bg' => $this->branding['navbar_bg'] ?? '#343a40',
            'branding.navbar.text' => $this->branding['navbar_text'] ?? '#ffffff',
            'branding.content.bg' => $this->branding['content_bg'] ?? '#f4f6f9',
            'branding.invoice_template' => $this->branding['invoice_template'] ?? 'default',
            'branding.email.header_html' => $this->branding['email_header_html'] ?? '',
            'branding.email.footer_html' => $this->branding['email_footer_html'] ?? '',
            'branding.site.header_html' => $this->branding['site_header_html'] ?? '',
            'branding.site.footer_html' => $this->branding['site_footer_html'] ?? '',
            'branding.admin.header_html' => $this->branding['site_header_html'] ?? '',
            'branding.admin.footer_html' => $this->branding['site_footer_html'] ?? '',
            'branding.custom_domain' => $this->branding['custom_domain'] ?? '',
            'branding.custom_css' => $this->branding['custom_css'] ?? '',
            'branding.platform_name' => $this->branding['platform_name'] ?? '',
            'branding.company_name' => $this->branding['company_name'] ?? '',
            'branding.tagline' => $this->branding['tagline'] ?? '',
        ], 'branding');

        // Clear branding cache
        app(BrandingService::class)->clearCache();

        session()->flash('success', 'Branding settings saved.');
    }

    public function render()
    {
        return view('livewire.admin.settings.index', [
            'platformModuleDefinitions' => PlatformFeatureService::$modules,
            'platformCategoryLabels' => PlatformFeatureService::categoryLabels(),
            'isSuperAdmin' => Auth::user()?->hasRole('super_admin') ?? false,
        ])->layout('layouts.admin');
    }
}
