<?php

namespace App\Providers;

use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class BrandingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->applyBrandingOverridesFromSettings();

        // Share branding configuration with all views
        View::composer('*', function ($view) {
            $view->with('branding', config('branding'));
        });

        // Generate CSS custom properties from branding config
        $this->generateBrandingCSS();
    }

    /**
     * Override config('branding') with values stored in SettingsService.
     *
     * This makes admin-configured branding apply immediately without requiring .env changes.
     */
    protected function applyBrandingOverridesFromSettings(): void
    {
        try {
            /** @var SettingsService $settings */
            $settings = app(SettingsService::class);

            // Core brand colors (also used as default button colors)
            foreach ([
                'branding.colors.primary' => 'branding.colors.primary',
                'branding.colors.secondary' => 'branding.colors.secondary',
                'branding.colors.accent' => 'branding.colors.accent',
            ] as $settingKey => $configKey) {
                $value = (string) $settings->get($settingKey, '');
                if ($value !== '') {
                    config()->set($configKey, $value);
                }
            }

            // Uploaded assets (stored on public disk, path like "branding/xyz.png")
            $logoPath = (string) $settings->get('branding.logo_path', '');
            if ($logoPath !== '') {
                config()->set('branding.logo.main', 'storage/' . ltrim($logoPath, '/'));
                // Keep email logo aligned unless specifically overridden elsewhere
                config()->set('branding.email.logo', 'storage/' . ltrim($logoPath, '/'));
            }

            $loginLogoPath = (string) $settings->get('branding.login_logo_path', '');
            if ($loginLogoPath !== '') {
                config()->set('branding.auth.login_logo', 'storage/' . ltrim($loginLogoPath, '/'));
            }

            $dashboardLogoPath = (string) $settings->get('branding.dashboard_logo_path', '');
            if ($dashboardLogoPath !== '') {
                config()->set('branding.admin.dashboard_logo', 'storage/' . ltrim($dashboardLogoPath, '/'));
            }

            $loginBackgroundPath = (string) $settings->get('branding.login_background_path', '');
            if ($loginBackgroundPath !== '') {
                config()->set('branding.auth.background_style', 'image');
                config()->set('branding.auth.background_image', 'storage/' . ltrim($loginBackgroundPath, '/'));
            }

            // Button colors (optional overrides)
            foreach ([
                'branding.buttons.primary' => 'branding.buttons.primary',
                'branding.buttons.primary_hover' => 'branding.buttons.primary_hover',
                'branding.buttons.secondary' => 'branding.buttons.secondary',
                'branding.buttons.secondary_hover' => 'branding.buttons.secondary_hover',
            ] as $settingKey => $configKey) {
                $value = (string) $settings->get($settingKey, '');
                if ($value !== '') {
                    config()->set($configKey, $value);
                }
            }

            // Sidebar colors (optional overrides)
            foreach ([
                'branding.colors.sidebar_bg' => 'branding.colors.sidebar_bg',
                'branding.colors.sidebar_text' => 'branding.colors.sidebar_text',
                'branding.colors.sidebar_hover' => 'branding.colors.sidebar_hover',
            ] as $settingKey => $configKey) {
                $value = (string) $settings->get($settingKey, '');
                if ($value !== '') {
                    config()->set($configKey, $value);
                }
            }

            // Admin header/footer HTML injection (optional)
            $siteHeaderHtml = (string) $settings->get('branding.site.header_html', '');
            $siteFooterHtml = (string) $settings->get('branding.site.footer_html', '');

            // Back-compat: if site-wide is empty, fall back to legacy admin-only settings.
            if ($siteHeaderHtml === '') {
                $siteHeaderHtml = (string) $settings->get('branding.admin.header_html', '');
            }
            if ($siteFooterHtml === '') {
                $siteFooterHtml = (string) $settings->get('branding.admin.footer_html', '');
            }

            if ($siteHeaderHtml !== '') {
                config()->set('branding.site.header_html', $siteHeaderHtml);
            }
            if ($siteFooterHtml !== '') {
                config()->set('branding.site.footer_html', $siteFooterHtml);
            }
        } catch (\Throwable $e) {
            // Don't break the app if settings storage is unavailable
            \Log::warning('Branding settings overrides not applied: ' . $e->getMessage());
        }
    }

    /**
     * Generate dynamic CSS file from branding configuration
     */
    protected function generateBrandingCSS(): void
    {
        $cssPath = public_path('css/brand.css');
        $config = config('branding');

        // Only generate in local/development environment
        // In production, run: php artisan branding:generate
        if (! app()->environment('local', 'development')) {
            return;
        }

        // Skip if file exists and is recent (less than 1 hour old)
        if (file_exists($cssPath) && (time() - filemtime($cssPath)) < 3600) {
            return;
        }

        try {
            // Ensure directory exists
            $dir = dirname($cssPath);
            if (! file_exists($dir)) {
                if (! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
                    \Log::warning("Cannot create directory: {$dir}");
                    return;
                }
            }

            $css = $this->buildCSSContent($config);

            // Write CSS file
            if (@file_put_contents($cssPath, $css) === false) {
                \Log::warning("Cannot write branding CSS to: {$cssPath}");
            }
        } catch (\Throwable $e) {
            // Log error but don't break the application
            \Log::error('Failed to generate branding CSS: ' . $e->getMessage());
        }
    }

    /**
     * Build CSS content from configuration
     */
    protected function buildCSSContent(array $config): string
    {
        $colors = $config['colors'] ?? [];
        $typography = $config['typography'] ?? [];
        $design = $config['design'] ?? [];
        $buttons = $config['buttons'] ?? [];
        $admin = $config['admin'] ?? [];

        $btnPrimary = $buttons['primary'] ?? ($colors['primary'] ?? '#2563eb');
        $btnPrimaryHover = $buttons['primary_hover'] ?? ($colors['primary_dark'] ?? '#1e40af');
        $btnSecondary = $buttons['secondary'] ?? ($colors['secondary'] ?? '#10b981');
        $btnSecondaryHover = $buttons['secondary_hover'] ?? ($colors['secondary'] ?? '#10b981');
        $adminPagePaddingComfy = $admin['page_padding'] ?? '1.5rem';

        // Sidebar colors (light theme defaults)
        $sidebarBg = $colors['sidebar_bg'] ?? '#f8fafc';
        $sidebarText = $colors['sidebar_text'] ?? '#334155';
        $sidebarHover = $colors['sidebar_hover'] ?? '#e2e8f0';

        $css = <<<CSS
/**
 * Kre8ivDesigns Marketing - Brand Styles
 * Auto-generated from config/branding.php
 * DO NOT EDIT THIS FILE DIRECTLY - Changes will be overwritten
 */

:root {
    /* Brand Colors */
    --brand-primary: {$colors['primary']};
    --brand-primary-dark: {$colors['primary_dark']};
    --brand-primary-light: {$colors['primary_light']};
    --brand-secondary: {$colors['secondary']};
    --brand-accent: {$colors['accent']};

    /* Text Colors */
    --brand-text-primary: {$colors['text_primary']};
    --brand-text-secondary: {$colors['text_secondary']};

    /* Background Colors */
    --brand-bg: {$colors['background']};
    --brand-bg-alt: {$colors['background_alt']};

    /* Status Colors */
    --brand-success: {$colors['success']};
    --brand-warning: {$colors['warning']};
    --brand-danger: {$colors['danger']};
    --brand-info: {$colors['info']};

    /* Typography */
    --brand-font-primary: {$typography['font_primary']};
    --brand-font-secondary: {$typography['font_secondary']};
    --brand-font-mono: {$typography['font_mono']};

    /* Design Elements */
    --brand-radius: {$design['border_radius']};
    --brand-radius-lg: {$design['border_radius_lg']};
    --brand-radius-sm: {$design['border_radius_sm']};
    --brand-shadow-sm: {$design['shadow_sm']};
    --brand-shadow: {$design['shadow']};
    --brand-shadow-lg: {$design['shadow_lg']};

    /* Button Colors (optional overrides) */
    --brand-btn-primary: {$btnPrimary};
    --brand-btn-primary-hover: {$btnPrimaryHover};
    --brand-btn-secondary: {$btnSecondary};
    --brand-btn-secondary-hover: {$btnSecondaryHover};

    /* Sidebar Colors (light theme) */
    --brand-sidebar-bg: {$sidebarBg};
    --brand-sidebar-text: {$sidebarText};
    --brand-sidebar-hover: {$sidebarHover};

    /* Admin Layout */
    --page-padding-comfy: {$adminPagePaddingComfy};
    --page-padding-compact: 1rem;
    --page-padding-extreme: 0.5rem;
    --page-padding: var(--page-padding-comfy);
    --admin-page-padding: var(--page-padding);
}

/* Density toggles */
html[data-density="comfy"] { --page-padding: var(--page-padding-comfy); }
html[data-density="compact"] { --page-padding: var(--page-padding-compact); }
html[data-density="extreme"] { --page-padding: var(--page-padding-extreme); }

/* Dark mode (base) - inverts light theme colors where appropriate */
html[data-theme="dark"] {
    --brand-bg: #0b1220;
    --brand-bg-alt: #111827;
    --brand-text-primary: #e5e7eb;
    --brand-text-secondary: #9ca3af;

    /* Sidebar (inverted for dark theme) */
    --brand-sidebar-bg: #0f172a;
    --brand-sidebar-text: #e2e8f0;
    --brand-sidebar-hover: #1e293b;
}

/* Global Overrides */
body {
    font-family: var(--brand-font-secondary);
    color: var(--brand-text-primary);
    background-color: var(--brand-bg);
}

/* Headings */
h1, h2, h3, h4, h5, h6 {
    font-family: var(--brand-font-primary);
    color: var(--brand-text-primary);
}

/* AdminLTE Theme Overrides */
.main-header .navbar {
    background-color: var(--brand-primary) !important;
    border-bottom: 3px solid var(--brand-primary-dark);
}

/* Sidebar styling - uses theme-aware variables */
.main-sidebar {
    background-color: var(--brand-sidebar-bg) !important;
}

.main-sidebar .sidebar {
    background-color: var(--brand-sidebar-bg);
}

.main-sidebar .nav-link {
    color: var(--brand-sidebar-text);
}

.main-sidebar .nav-link:hover {
    background-color: var(--brand-sidebar-hover);
}

.main-sidebar .nav-header {
    color: var(--brand-sidebar-text);
    opacity: 0.7;
}

.main-sidebar .user-panel .info a {
    color: var(--brand-sidebar-text);
}

.sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active,
.sidebar-light-primary .nav-sidebar > .nav-item > .nav-link.active {
    background-color: var(--brand-primary);
    color: white;
}

.brand-link {
    background-color: var(--brand-primary) !important;
    border-bottom: 1px solid var(--brand-primary-dark);
}

.brand-link .brand-text {
    color: white !important;
}

/* Buttons */
.btn-primary {
    background-color: var(--brand-btn-primary);
    border-color: var(--brand-btn-primary);
    border-radius: var(--brand-radius);
}

.btn-primary:hover {
    background-color: var(--brand-btn-primary-hover);
    border-color: var(--brand-btn-primary-hover);
}

.btn-secondary {
    background-color: var(--brand-btn-secondary);
    border-color: var(--brand-btn-secondary);
    border-radius: var(--brand-radius);
}

.btn-secondary:hover {
    background-color: var(--brand-btn-secondary-hover);
    border-color: var(--brand-btn-secondary-hover);
}

.btn-accent,
.btn-warning {
    background-color: var(--brand-accent);
    border-color: var(--brand-accent);
    border-radius: var(--brand-radius);
}

/* Cards */
.card {
    border-radius: var(--brand-radius);
    box-shadow: var(--brand-shadow);
    border: none;
}

html[data-theme="dark"] .card {
    background-color: var(--brand-bg-alt);
    color: var(--brand-text-primary);
}

.card-header {
    background-color: var(--brand-bg-alt);
    border-bottom: 2px solid var(--brand-primary-light);
    font-weight: 600;
}

.card-primary .card-header {
    background-color: var(--brand-primary);
    color: white;
}

/* Links */
a {
    color: var(--brand-primary);
}

a:hover {
    color: var(--brand-primary-dark);
}

/* Badges */
.badge-primary {
    background-color: var(--brand-primary);
}

.badge-success {
    background-color: var(--brand-success);
}

.badge-warning {
    background-color: var(--brand-warning);
}

.badge-danger {
    background-color: var(--brand-danger);
}

.badge-info {
    background-color: var(--brand-info);
}

/* Tailwind Component Overrides */
.bg-primary {
    background-color: var(--brand-primary) !important;
}

.text-primary {
    color: var(--brand-primary) !important;
}

.border-primary {
    border-color: var(--brand-primary) !important;
}

/* Login Page Styles */
.login-page {
    background: var(--brand-primary) !important;
}

.login-card-body {
    border-radius: var(--brand-radius-lg);
    box-shadow: var(--brand-shadow-lg);
}

.login-box-msg {
    color: var(--brand-text-secondary);
}

/* Custom Brand Styles */
.brand-gradient {
    background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-primary-dark) 100%);
}

.brand-gradient-accent {
    background: linear-gradient(135deg, var(--brand-accent) 0%, var(--brand-secondary) 100%);
}

.text-brand {
    color: var(--brand-primary);
}

.bg-brand {
    background-color: var(--brand-primary);
}

.hover\:bg-brand-dark:hover {
    background-color: var(--brand-primary-dark);
}

/* Nav Links */
.nav-link {
    border-radius: var(--brand-radius-sm);
}

/* Forms */
.form-control:focus {
    border-color: var(--brand-primary);
    box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
}

/* Pagination */
.pagination .page-link {
    color: var(--brand-primary);
}

.pagination .page-item.active .page-link {
    background-color: var(--brand-primary);
    border-color: var(--brand-primary);
}

/* Tables */
.table thead th {
    background-color: var(--brand-bg-alt);
    color: var(--brand-text-primary);
    font-weight: 600;
}

/* Alerts */
.alert-primary {
    background-color: var(--brand-primary-light);
    border-color: var(--brand-primary);
    color: var(--brand-primary-dark);
}

/* Modals */
.modal-header {
    background-color: var(--brand-primary);
    color: white;
}

.modal-header .close {
    color: white;
}

/* Progress Bars */
.progress-bar {
    background-color: var(--brand-primary);
}

/* Tooltips */
.tooltip-inner {
    background-color: var(--brand-text-primary);
}

/* Custom Utilities */
.shadow-brand {
    box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.1), 0 2px 4px -1px rgba(37, 99, 235, 0.06);
}

.border-brand {
    border-color: var(--brand-primary);
}

/* Responsive Logo */
.brand-logo {
    max-width: 100%;
    height: auto;
}

@media (max-width: 768px) {
    .brand-logo {
        max-width: 150px;
    }
}

/* Print Styles */
@media print {
    .main-header,
    .main-sidebar,
    .content-header {
        display: none !important;
    }

    .content-wrapper {
        margin-left: 0 !important;
        padding: 0 !important;
    }
}

/* Tabler Admin Panel - fixed padding */
.page-body > .container-fluid {
    padding-left: var(--page-padding) !important;
    padding-right: var(--page-padding) !important;
}

/* AdminLTE layout - fixed padding */
.content-wrapper .content > .container-fluid {
    padding-left: var(--page-padding) !important;
    padding-right: var(--page-padding) !important;
}

html[data-theme="dark"] .content-wrapper {
    background-color: var(--brand-bg);
}

html[data-theme="dark"] .main-header.navbar {
    background-color: var(--brand-bg-alt) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

/* ------------------------------------------------------------------ */
/* Compatibility utilities (Bootstrap 5 / Tabler classes used in views) */
/* ------------------------------------------------------------------ */

.text-end { text-align: right !important; }

.fw-semibold { font-weight: 600 !important; }

.subheader {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--brand-text-secondary);
}

.table-vcenter td,
.table-vcenter th {
    vertical-align: middle !important;
}

/* Minimal spacing utilities used by some admin views */
.me-1 { margin-right: 0.25rem !important; }
.me-2 { margin-right: 0.5rem !important; }
.me-3 { margin-right: 1rem !important; }
.ms-2 { margin-left: 0.5rem !important; }
.ps-2 { padding-left: 0.5rem !important; }
.pe-2 { padding-right: 0.5rem !important; }

.gap-1 { gap: 0.25rem !important; }
.gap-2 { gap: 0.5rem !important; }
.gap-3 { gap: 1rem !important; }

.row-cards > [class*="col-"] {
    margin-bottom: 1rem;
}

/* Equal-height cards for horizontal rows (site-wide norm where row-cards is used) */
.row.row-cards > [class*="col-"] {
    display: flex;
}

.row.row-cards > [class*="col-"] > .card {
    flex: 1 1 auto;
    width: 100%;
}

CSS;

        return $css;
    }
}
