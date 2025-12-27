<?php

namespace App\Http\Livewire\Admin\Settings;

use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithFileUploads;

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

    public ?string $test_email_to = null;

    public $logo_upload;

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
            'storage.default_provider' => 's3',
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

        $b = $settings->getMany([
            'branding.logo_path' => '',
            'branding.colors.primary' => '#3c8dbc',
            'branding.colors.secondary' => '#6c757d',
            'branding.colors.accent' => '#00a65a',
            'branding.invoice_template' => 'default',
            'branding.email.header_html' => '',
            'branding.email.footer_html' => '',
            'branding.custom_domain' => '',
        ]);
        $this->branding = [
            'logo_path' => $b['branding.logo_path'],
            'color_primary' => $b['branding.colors.primary'],
            'color_secondary' => $b['branding.colors.secondary'],
            'color_accent' => $b['branding.colors.accent'],
            'invoice_template' => $b['branding.invoice_template'],
            'email_header_html' => $b['branding.email.header_html'],
            'email_footer_html' => $b['branding.email.footer_html'],
            'custom_domain' => $b['branding.custom_domain'],
        ];
    }

    public function setTab(string $tab): void
    {
        $allowed = ['general', 'email', 'payment', 'storage', 'notifications', 'security', 'branding'];
        if (in_array($tab, $allowed, true)) {
            $this->tab = $tab;
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
            'storage.default_provider' => $this->storage['default_provider'] ?? 's3',
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

    public function uploadLogo(SettingsService $settings): void
    {
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

        session()->flash('success', 'Logo uploaded.');
    }

    public function saveBranding(SettingsService $settings): void
    {
        $settings->setMany([
            'branding.logo_path' => $this->branding['logo_path'] ?? '',
            'branding.colors.primary' => $this->branding['color_primary'] ?? '#3c8dbc',
            'branding.colors.secondary' => $this->branding['color_secondary'] ?? '#6c757d',
            'branding.colors.accent' => $this->branding['color_accent'] ?? '#00a65a',
            'branding.invoice_template' => $this->branding['invoice_template'] ?? 'default',
            'branding.email.header_html' => $this->branding['email_header_html'] ?? '',
            'branding.email.footer_html' => $this->branding['email_footer_html'] ?? '',
            'branding.custom_domain' => $this->branding['custom_domain'] ?? '',
        ], 'branding');
        session()->flash('success', 'Branding settings saved.');
    }

    public function render()
    {
        return view('livewire.admin.settings.index');
    }
}
