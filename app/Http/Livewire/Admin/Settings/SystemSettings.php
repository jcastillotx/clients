<?php

namespace App\Http\Livewire\Admin\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class SystemSettings extends Component
{
    use WithFileUploads;

    public string $tab = 'general';

    /** @var array<string, mixed> */
    public array $state = [];

    // UI helpers
    public string $storageAllowedTypesCsv = '';

    // Branding upload
    public $logoUpload;

    // Test email
    public string $testEmailTo = '';

    // Simple template builder blocks (drag-and-drop ordering in UI)
    /** @var array<int, array{type:string, content:string}> */
    public array $emailTemplateBlocks = [];

    public function mount(): void
    {
        $this->tab = request()->query('tab', 'general');
        $this->loadState();
    }

    public function switchTab(string $tab): void
    {
        $allowed = ['general', 'email', 'payment', 'storage', 'notifications', 'security', 'branding'];
        $this->tab = in_array($tab, $allowed, true) ? $tab : 'general';
    }

    protected function loadState(): void
    {
        $this->state = [
            // General
            'company.name' => (string) Setting::getValue('company.name', config('app.name')),
            'company.address' => (string) Setting::getValue('company.address', ''),
            'company.phone' => (string) Setting::getValue('company.phone', ''),
            'company.website' => (string) Setting::getValue('company.website', ''),
            'business.timezone' => (string) Setting::getValue('business.timezone', config('app.timezone', 'UTC')),
            'business.hours' => (array) Setting::getValue('business.hours', [
                'mon' => ['09:00', '17:00'],
                'tue' => ['09:00', '17:00'],
                'wed' => ['09:00', '17:00'],
                'thu' => ['09:00', '17:00'],
                'fri' => ['09:00', '17:00'],
                'sat' => null,
                'sun' => null,
            ]),
            'locale.default_currency' => (string) Setting::getValue('locale.default_currency', 'USD'),
            'locale.date_format' => (string) Setting::getValue('locale.date_format', 'Y-m-d'),
            'locale.time_format' => (string) Setting::getValue('locale.time_format', 'H:i'),
            'locale.language' => (string) Setting::getValue('locale.language', config('app.locale', 'en')),

            // Email
            'mail.from_address' => (string) Setting::getValue('mail.from_address', config('mail.from.address')),
            'mail.from_name' => (string) Setting::getValue('mail.from_name', config('mail.from.name')),
            'mail.smtp.host' => (string) Setting::getValue('mail.smtp.host', config('mail.mailers.smtp.host')),
            'mail.smtp.port' => (int) Setting::getValue('mail.smtp.port', config('mail.mailers.smtp.port', 587)),
            'mail.smtp.username' => (string) Setting::getValue('mail.smtp.username', config('mail.mailers.smtp.username')),
            'mail.smtp.password' => (string) Setting::getValue('mail.smtp.password', ''),
            'mail.smtp.encryption' => (string) Setting::getValue('mail.smtp.encryption', config('mail.mailers.smtp.encryption', 'tls')),
            'mail.signature' => (string) Setting::getValue('mail.signature', ''),
            'mail.notify_events' => (array) Setting::getValue('mail.notify_events', [
                'request_created' => true,
                'request_updated' => true,
                'invoice_created' => true,
                'payment_received' => true,
            ]),
            'mail.template_blocks' => (array) Setting::getValue('mail.template_blocks', [
                ['type' => 'header', 'content' => '<h1>{{ $title ?? "Notification" }}</h1>'],
                ['type' => 'body', 'content' => '<p>{{ $slot ?? "" }}</p>'],
                ['type' => 'footer', 'content' => '<p style="color:#6b7280;font-size:12px;">{{ $company ?? "" }}</p>'],
            ]),

            // Payment
            'payments.mode' => (string) Setting::getValue('payments.mode', 'test'), // test|live
            'stripe.test_key' => (string) Setting::getValue('stripe.test_key', ''),
            'stripe.test_secret' => (string) Setting::getValue('stripe.test_secret', ''),
            'stripe.live_key' => (string) Setting::getValue('stripe.live_key', ''),
            'stripe.live_secret' => (string) Setting::getValue('stripe.live_secret', ''),
            'paypal.client_id' => (string) Setting::getValue('paypal.client_id', ''),
            'paypal.secret' => (string) Setting::getValue('paypal.secret', ''),
            'payments.default_terms' => (string) Setting::getValue('payments.default_terms', 'net_30'),
            'payments.late_fee.enabled' => (bool) Setting::getValue('payments.late_fee.enabled', false),
            'payments.late_fee.percent' => (float) Setting::getValue('payments.late_fee.percent', 0),
            'payments.tax_rate' => (float) Setting::getValue('payments.tax_rate', 0),
            'payments.methods' => (array) Setting::getValue('payments.methods', ['stripe']),

            // Storage
            'storage.default_provider' => (string) Setting::getValue('storage.default_provider', 's3'),
            'storage.max_upload_mb' => (int) Setting::getValue('storage.max_upload_mb', 50),
            'storage.allowed_types' => (array) Setting::getValue('storage.allowed_types', ['pdf', 'png', 'jpg', 'jpeg', 'doc', 'docx', 'xls', 'xlsx']),
            'storage.quota_by_tier_mb' => (array) Setting::getValue('storage.quota_by_tier_mb', [
                'basic' => 1024,
                'standard' => 5120,
                'premium' => 10240,
                'enterprise' => 51200,
            ]),
            'storage.retention_days' => (int) Setting::getValue('storage.retention_days', 0), // 0 = disabled
            'storage.backups.enabled' => (bool) Setting::getValue('storage.backups.enabled', false),
            'storage.backups.frequency' => (string) Setting::getValue('storage.backups.frequency', 'weekly'),

            // Notifications
            'notify.admin_events' => (array) Setting::getValue('notify.admin_events', [
                'new_request' => true,
                'payment_failed' => true,
                'storage_quota_80' => true,
            ]),
            'notify.client_defaults' => (array) Setting::getValue('notify.client_defaults', [
                'request_updates' => true,
                'invoice_updates' => true,
            ]),
            'notify.slack.webhook' => (string) Setting::getValue('notify.slack.webhook', ''),
            'notify.teams.webhook' => (string) Setting::getValue('notify.teams.webhook', ''),
            'notify.push.enabled' => (bool) Setting::getValue('notify.push.enabled', false),
            'notify.sms.enabled' => (bool) Setting::getValue('notify.sms.enabled', false),
            'notify.sms.twilio_sid' => (string) Setting::getValue('notify.sms.twilio_sid', ''),
            'notify.sms.twilio_token' => (string) Setting::getValue('notify.sms.twilio_token', ''),
            'notify.sms.from' => (string) Setting::getValue('notify.sms.from', ''),

            // Security
            'security.2fa_enforced' => (bool) Setting::getValue('security.2fa_enforced', false),
            'security.password.min_length' => (int) Setting::getValue('security.password.min_length', 10),
            'security.password.require_numbers' => (bool) Setting::getValue('security.password.require_numbers', true),
            'security.password.require_symbols' => (bool) Setting::getValue('security.password.require_symbols', false),
            'security.password.expire_days' => (int) Setting::getValue('security.password.expire_days', 0),
            'security.session_timeout_minutes' => (int) Setting::getValue('security.session_timeout_minutes', 120),
            'security.ip_allowlist' => (string) Setting::getValue('security.ip_allowlist', ''),
            'security.ip_blocklist' => (string) Setting::getValue('security.ip_blocklist', ''),
            'security.login_attempt_limit' => (int) Setting::getValue('security.login_attempt_limit', 10),
            'security.api_rate_limit_per_min' => (int) Setting::getValue('security.api_rate_limit_per_min', 60),

            // Branding
            'branding.logo_path' => (string) Setting::getValue('branding.logo_path', ''),
            'branding.primary' => (string) Setting::getValue('branding.primary', '#206bc4'),
            'branding.secondary' => (string) Setting::getValue('branding.secondary', '#1f2937'),
            'branding.accent' => (string) Setting::getValue('branding.accent', '#22c55e'),
            'branding.invoice_template' => (string) Setting::getValue('branding.invoice_template', 'default'),
            'branding.email_header_html' => (string) Setting::getValue('branding.email_header_html', ''),
            'branding.email_footer_html' => (string) Setting::getValue('branding.email_footer_html', ''),
            'branding.custom_domain' => (string) Setting::getValue('branding.custom_domain', ''),
        ];

        $this->emailTemplateBlocks = $this->state['mail.template_blocks'] ?: [];
        $this->storageAllowedTypesCsv = implode(',', (array) ($this->state['storage.allowed_types'] ?? []));
    }

    protected function persist(array $kv, array $encryptedKeys = []): void
    {
        $uid = auth()->id();
        foreach ($kv as $key => $value) {
            $encrypt = in_array($key, $encryptedKeys, true);
            Setting::setValue($key, $value, $encrypt, $uid);
        }
    }

    public function saveGeneral(): void
    {
        $this->validate([
            'state.company.name' => ['required', 'string', 'max:255'],
            'state.company.address' => ['nullable', 'string', 'max:2000'],
            'state.company.phone' => ['nullable', 'string', 'max:255'],
            'state.company.website' => ['nullable', 'string', 'max:255'],
            'state.business.timezone' => ['required', 'string', 'max:255'],
            'state.locale.default_currency' => ['required', 'string', 'size:3'],
            'state.locale.date_format' => ['required', 'string', 'max:50'],
            'state.locale.time_format' => ['required', 'string', 'max:50'],
            'state.locale.language' => ['required', 'string', 'max:10'],
        ]);

        $this->persist([
            'company.name' => $this->state['company.name'],
            'company.address' => $this->state['company.address'],
            'company.phone' => $this->state['company.phone'],
            'company.website' => $this->state['company.website'],
            'business.timezone' => $this->state['business.timezone'],
            'business.hours' => $this->state['business.hours'],
            'locale.default_currency' => strtoupper($this->state['locale.default_currency']),
            'locale.date_format' => $this->state['locale.date_format'],
            'locale.time_format' => $this->state['locale.time_format'],
            'locale.language' => $this->state['locale.language'],
        ]);

        session()->flash('success', 'General settings saved.');
    }

    public function saveEmail(): void
    {
        $this->state['mail.template_blocks'] = $this->emailTemplateBlocks;

        $this->validate([
            'state.mail.from_address' => ['required', 'email', 'max:255'],
            'state.mail.from_name' => ['required', 'string', 'max:255'],
            'state.mail.smtp.host' => ['required', 'string', 'max:255'],
            'state.mail.smtp.port' => ['required', 'integer', 'min:1', 'max:65535'],
            'state.mail.smtp.username' => ['nullable', 'string', 'max:255'],
            'state.mail.smtp.password' => ['nullable', 'string', 'max:255'],
            'state.mail.smtp.encryption' => ['nullable', 'string', 'max:10'],
            'state.mail.signature' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->persist([
            'mail.from_address' => $this->state['mail.from_address'],
            'mail.from_name' => $this->state['mail.from_name'],
            'mail.smtp.host' => $this->state['mail.smtp.host'],
            'mail.smtp.port' => (int) $this->state['mail.smtp.port'],
            'mail.smtp.username' => $this->state['mail.smtp.username'],
            'mail.smtp.password' => $this->state['mail.smtp.password'],
            'mail.smtp.encryption' => $this->state['mail.smtp.encryption'],
            'mail.signature' => $this->state['mail.signature'],
            'mail.notify_events' => $this->state['mail.notify_events'],
            'mail.template_blocks' => $this->state['mail.template_blocks'],
        ], encryptedKeys: [
            'mail.smtp.password',
        ]);

        session()->flash('success', 'Email settings saved.');
    }

    public function sendTestEmail(): void
    {
        $this->validate([
            'testEmailTo' => ['required', 'email', 'max:255'],
        ]);

        // Apply configured SMTP settings just for this send.
        config([
            'mail.from.address' => $this->state['mail.from_address'] ?? config('mail.from.address'),
            'mail.from.name' => $this->state['mail.from_name'] ?? config('mail.from.name'),
            'mail.mailers.smtp.host' => $this->state['mail.smtp.host'] ?? config('mail.mailers.smtp.host'),
            'mail.mailers.smtp.port' => (int) ($this->state['mail.smtp.port'] ?? config('mail.mailers.smtp.port')),
            'mail.mailers.smtp.username' => $this->state['mail.smtp.username'] ?? config('mail.mailers.smtp.username'),
            'mail.mailers.smtp.password' => $this->state['mail.smtp.password'] ?? config('mail.mailers.smtp.password'),
            'mail.mailers.smtp.encryption' => $this->state['mail.smtp.encryption'] ?? config('mail.mailers.smtp.encryption'),
        ]);

        try {
            Mail::mailer('smtp')->raw("Test email from " . ($this->state['company.name'] ?? config('app.name')) . ".", function ($m) {
                $m->to($this->testEmailTo)->subject('Test email');
            });
            session()->flash('success', 'Test email sent.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Failed to send test email: ' . $e->getMessage());
        }
    }

    public function savePayment(): void
    {
        $this->validate([
            'state.payments.mode' => ['required', 'in:test,live'],
            'state.payments.default_terms' => ['required', 'string', 'max:50'],
            'state.payments.late_fee.enabled' => ['boolean'],
            'state.payments.late_fee.percent' => ['numeric', 'min:0', 'max:100'],
            'state.payments.tax_rate' => ['numeric', 'min:0', 'max:100'],
        ]);

        $this->persist([
            'payments.mode' => $this->state['payments.mode'],
            'stripe.test_key' => $this->state['stripe.test_key'],
            'stripe.test_secret' => $this->state['stripe.test_secret'],
            'stripe.live_key' => $this->state['stripe.live_key'],
            'stripe.live_secret' => $this->state['stripe.live_secret'],
            'paypal.client_id' => $this->state['paypal.client_id'],
            'paypal.secret' => $this->state['paypal.secret'],
            'payments.default_terms' => $this->state['payments.default_terms'],
            'payments.late_fee.enabled' => (bool) $this->state['payments.late_fee.enabled'],
            'payments.late_fee.percent' => (float) $this->state['payments.late_fee.percent'],
            'payments.tax_rate' => (float) $this->state['payments.tax_rate'],
            'payments.methods' => array_values(array_unique((array) ($this->state['payments.methods'] ?? []))),
        ], encryptedKeys: [
            'stripe.test_secret',
            'stripe.live_secret',
            'paypal.secret',
        ]);

        session()->flash('success', 'Payment settings saved.');
    }

    public function saveStorage(): void
    {
        $this->validate([
            'state.storage.default_provider' => ['required', 'string', 'max:50'],
            'state.storage.max_upload_mb' => ['required', 'integer', 'min:1', 'max:10240'],
            'state.storage.retention_days' => ['required', 'integer', 'min:0', 'max:36500'],
            'state.storage.backups.enabled' => ['boolean'],
            'state.storage.backups.frequency' => ['required', 'string', 'max:50'],
        ]);

        $csv = $this->storageAllowedTypesCsv;
        $allowed = array_values(array_unique(array_filter(array_map(
            fn ($x) => strtolower(trim((string) $x)),
            explode(',', (string) $csv)
        ))));

        $this->persist([
            'storage.default_provider' => $this->state['storage.default_provider'],
            'storage.max_upload_mb' => (int) $this->state['storage.max_upload_mb'],
            'storage.allowed_types' => $allowed,
            'storage.quota_by_tier_mb' => $this->state['storage.quota_by_tier_mb'],
            'storage.retention_days' => (int) $this->state['storage.retention_days'],
            'storage.backups.enabled' => (bool) $this->state['storage.backups.enabled'],
            'storage.backups.frequency' => $this->state['storage.backups.frequency'],
        ]);

        session()->flash('success', 'Storage settings saved.');
    }

    public function saveNotifications(): void
    {
        $this->persist([
            'notify.admin_events' => $this->state['notify.admin_events'],
            'notify.client_defaults' => $this->state['notify.client_defaults'],
            'notify.slack.webhook' => $this->state['notify.slack.webhook'],
            'notify.teams.webhook' => $this->state['notify.teams.webhook'],
            'notify.push.enabled' => (bool) $this->state['notify.push.enabled'],
            'notify.sms.enabled' => (bool) $this->state['notify.sms.enabled'],
            'notify.sms.twilio_sid' => $this->state['notify.sms.twilio_sid'],
            'notify.sms.twilio_token' => $this->state['notify.sms.twilio_token'],
            'notify.sms.from' => $this->state['notify.sms.from'],
        ], encryptedKeys: [
            'notify.sms.twilio_token',
        ]);

        session()->flash('success', 'Notification settings saved.');
    }

    public function saveSecurity(): void
    {
        $this->validate([
            'state.security.password.min_length' => ['required', 'integer', 'min:6', 'max:128'],
            'state.security.password.expire_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'state.security.session_timeout_minutes' => ['required', 'integer', 'min:1', 'max:10080'],
            'state.security.login_attempt_limit' => ['required', 'integer', 'min:1', 'max:1000'],
            'state.security.api_rate_limit_per_min' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        $this->persist([
            'security.2fa_enforced' => (bool) $this->state['security.2fa_enforced'],
            'security.password.min_length' => (int) $this->state['security.password.min_length'],
            'security.password.require_numbers' => (bool) $this->state['security.password.require_numbers'],
            'security.password.require_symbols' => (bool) $this->state['security.password.require_symbols'],
            'security.password.expire_days' => (int) $this->state['security.password.expire_days'],
            'security.session_timeout_minutes' => (int) $this->state['security.session_timeout_minutes'],
            'security.ip_allowlist' => $this->state['security.ip_allowlist'],
            'security.ip_blocklist' => $this->state['security.ip_blocklist'],
            'security.login_attempt_limit' => (int) $this->state['security.login_attempt_limit'],
            'security.api_rate_limit_per_min' => (int) $this->state['security.api_rate_limit_per_min'],
        ]);

        session()->flash('success', 'Security settings saved.');
    }

    public function saveBranding(): void
    {
        if ($this->logoUpload) {
            $this->validate([
                'logoUpload' => ['image', 'max:2048'], // 2MB
            ]);

            $path = $this->logoUpload->storePublicly('branding', 'public');
            $this->state['branding.logo_path'] = $path;
            $this->logoUpload = null;
        }

        $this->validate([
            'state.branding.primary' => ['required', 'string', 'max:20'],
            'state.branding.secondary' => ['required', 'string', 'max:20'],
            'state.branding.accent' => ['required', 'string', 'max:20'],
            'state.branding.invoice_template' => ['required', 'string', 'max:50'],
            'state.branding.custom_domain' => ['nullable', 'string', 'max:255'],
        ]);

        $this->persist([
            'branding.logo_path' => $this->state['branding.logo_path'],
            'branding.primary' => $this->state['branding.primary'],
            'branding.secondary' => $this->state['branding.secondary'],
            'branding.accent' => $this->state['branding.accent'],
            'branding.invoice_template' => $this->state['branding.invoice_template'],
            'branding.email_header_html' => $this->state['branding.email_header_html'],
            'branding.email_footer_html' => $this->state['branding.email_footer_html'],
            'branding.custom_domain' => $this->state['branding.custom_domain'],
        ]);

        session()->flash('success', 'Branding saved.');
    }

    public function addEmailBlock(string $type): void
    {
        $type = in_array($type, ['header', 'body', 'footer'], true) ? $type : 'body';
        $this->emailTemplateBlocks[] = ['type' => $type, 'content' => ''];
    }

    public function removeEmailBlock(int $index): void
    {
        if (!isset($this->emailTemplateBlocks[$index])) return;
        array_splice($this->emailTemplateBlocks, $index, 1);
    }

    public function reorderEmailBlocks(array $orderedIndexes): void
    {
        $new = [];
        foreach ($orderedIndexes as $i) {
            if (isset($this->emailTemplateBlocks[$i])) {
                $new[] = $this->emailTemplateBlocks[$i];
            }
        }
        $this->emailTemplateBlocks = $new;
    }

    public function render()
    {
        return view('livewire.admin.settings.index')->layout('layouts.admin', ['title' => 'System Settings']);
    }
}

