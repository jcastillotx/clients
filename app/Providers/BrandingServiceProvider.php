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
     * Build CSS content from configuration (Admindek theme)
     */
    protected function buildCSSContent(array $config): string
    {
        $colors = $config['colors'] ?? [];
        $typography = $config['typography'] ?? [];
        $design = $config['design'] ?? [];
        $admin = $config['admin'] ?? [];

        // Colors with defaults (Admindek palette)
        $primary = $colors['primary'] ?? '#04a9f5';
        $primaryDark = $colors['primary_dark'] ?? '#0288d1';
        $primaryLight = $colors['primary_light'] ?? '#e1f5fe';
        $secondary = $colors['secondary'] ?? '#1de9b6';
        $accent = $colors['accent'] ?? '#a389d4';
        $textPrimary = $colors['text_primary'] ?? '#373a3c';
        $textSecondary = $colors['text_secondary'] ?? '#919aa3';
        $background = $colors['background'] ?? '#f4f7fa';
        $backgroundAlt = $colors['background_alt'] ?? '#ffffff';
        $success = $colors['success'] ?? '#1de9b6';
        $warning = $colors['warning'] ?? '#f4c22b';
        $danger = $colors['danger'] ?? '#f44236';
        $info = $colors['info'] ?? '#04a9f5';

        // Sidebar colors (Admindek dark sidebar)
        $sidebarBg = $colors['sidebar_bg'] ?? '#3f4d67';
        $sidebarText = $colors['sidebar_text'] ?? '#b5bdca';
        $sidebarHover = $colors['sidebar_hover'] ?? '#4a5a7a';
        $sidebarActive = $colors['sidebar_active'] ?? $primary;
        $sidebarHeader = '#374058';

        // Header gradient
        $headerStart = $colors['header_start'] ?? $primary;
        $headerEnd = $colors['header_end'] ?? $secondary;

        // Typography
        $fontPrimary = $typography['font_primary'] ?? "'Open Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
        $fontSecondary = $typography['font_secondary'] ?? $fontPrimary;
        $fontMono = $typography['font_mono'] ?? "'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace";

        // Design elements
        $radius = $design['border_radius'] ?? '5px';
        $radiusLg = $design['border_radius_lg'] ?? '10px';
        $radiusSm = $design['border_radius_sm'] ?? '3px';
        $shadowSm = $design['shadow_sm'] ?? '0 1px 3px 0 rgba(0, 0, 0, 0.1)';
        $shadow = $design['shadow'] ?? '0 2px 6px 0 rgba(0, 0, 0, 0.1)';
        $shadowLg = $design['shadow_lg'] ?? '0 5px 20px 0 rgba(0, 0, 0, 0.1)';

        $pagePadding = $admin['page_padding'] ?? '25px';

        $css = <<<CSS
/**
 * Admindek Theme - Brand Styles
 * Based on Admindek HTML Dashboard from AdminLTE
 * Auto-generated from config/branding.php
 */

:root {
    /* Primary Brand Colors (Admindek palette) */
    --brand-primary: {$primary};
    --brand-primary-dark: {$primaryDark};
    --brand-primary-light: {$primaryLight};
    --brand-secondary: {$secondary};
    --brand-accent: {$accent};

    /* Text Colors */
    --brand-text-primary: {$textPrimary};
    --brand-text-secondary: {$textSecondary};
    --brand-text-muted: #b5bdca;

    /* Background Colors */
    --brand-bg: {$background};
    --brand-bg-alt: {$backgroundAlt};

    /* Sidebar Colors */
    --brand-sidebar-bg: {$sidebarBg};
    --brand-sidebar-text: {$sidebarText};
    --brand-sidebar-hover: {$sidebarHover};
    --brand-sidebar-active: {$sidebarActive};
    --brand-sidebar-header: {$sidebarHeader};

    /* Header Gradient */
    --brand-header-start: {$headerStart};
    --brand-header-end: {$headerEnd};

    /* Status Colors */
    --brand-success: {$success};
    --brand-warning: {$warning};
    --brand-danger: {$danger};
    --brand-info: {$info};

    /* Typography */
    --brand-font-primary: {$fontPrimary};
    --brand-font-secondary: {$fontSecondary};
    --brand-font-mono: {$fontMono};

    /* Design Elements */
    --brand-radius: {$radius};
    --brand-radius-lg: {$radiusLg};
    --brand-radius-sm: {$radiusSm};
    --brand-shadow-sm: {$shadowSm};
    --brand-shadow: {$shadow};
    --brand-shadow-lg: {$shadowLg};

    /* Layout */
    --page-padding-comfy: {$pagePadding};
    --page-padding-compact: 15px;
    --page-padding-extreme: 10px;
    --page-padding: var(--page-padding-comfy);
    --sidebar-width: 264px;
}

/* Density toggles */
html[data-density="comfy"] { --page-padding: var(--page-padding-comfy); }
html[data-density="compact"] { --page-padding: var(--page-padding-compact); }
html[data-density="extreme"] { --page-padding: var(--page-padding-extreme); }

/* Dark mode */
html[data-theme="dark"] {
    --brand-bg: #1a1f2b;
    --brand-bg-alt: #242a38;
    --brand-text-primary: #e4e6eb;
    --brand-text-secondary: #a8b0bf;
    --brand-sidebar-bg: #141820;
    --brand-sidebar-header: #0f1318;
}

/* Global Styles */
body {
    font-family: var(--brand-font-secondary);
    color: var(--brand-text-primary);
    background-color: var(--brand-bg);
    font-size: 14px;
    line-height: 1.5;
}

h1, h2, h3, h4, h5, h6 {
    font-family: var(--brand-font-primary);
    color: var(--brand-text-primary);
    font-weight: 600;
}

a { color: var(--brand-primary); text-decoration: none; }
a:hover { color: var(--brand-primary-dark); }

/* Header / Navbar */
.main-header.navbar {
    background: linear-gradient(to right, var(--brand-header-start), var(--brand-header-end)) !important;
    border: none !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
    min-height: 60px;
}

.main-header .navbar-nav .nav-link { color: rgba(255, 255, 255, 0.9) !important; }
.main-header .navbar-nav .nav-link:hover { color: #ffffff !important; }
.main-header [data-widget="pushmenu"] { color: rgba(255, 255, 255, 0.9) !important; }

html[data-theme="dark"] .main-header.navbar {
    background: linear-gradient(to right, #1e3a5f, #2d4a6f) !important;
}

/* Sidebar */
.main-sidebar {
    background-color: var(--brand-sidebar-bg) !important;
    box-shadow: 2px 0 6px rgba(0, 0, 0, 0.15);
}

.main-sidebar .sidebar { background-color: var(--brand-sidebar-bg); }

.brand-link {
    background-color: var(--brand-sidebar-header) !important;
    border-bottom: none !important;
    padding: 15px 20px !important;
    min-height: 60px;
}

.brand-link .brand-text { color: #ffffff !important; font-weight: 600; }

.main-sidebar .nav-link {
    color: var(--brand-sidebar-text) !important;
    padding: 12px 20px !important;
    border-left: 3px solid transparent;
    transition: all 0.2s ease;
}

.main-sidebar .nav-link:hover {
    background-color: var(--brand-sidebar-hover) !important;
    color: #ffffff !important;
}

.main-sidebar .nav-link.active {
    background-color: var(--brand-sidebar-hover) !important;
    color: #ffffff !important;
    border-left-color: var(--brand-sidebar-active) !important;
}

.main-sidebar .nav-icon { color: var(--brand-sidebar-text); width: 24px; margin-right: 10px; }
.main-sidebar .nav-link:hover .nav-icon,
.main-sidebar .nav-link.active .nav-icon { color: var(--brand-sidebar-active); }

.main-sidebar .nav-header {
    color: var(--brand-sidebar-text) !important;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 20px 20px 8px !important;
    opacity: 0.6;
}

.nav-treeview { background-color: rgba(0, 0, 0, 0.15); }
.nav-treeview > .nav-item > .nav-link { padding-left: 45px !important; font-size: 13px; }

.main-sidebar .user-panel { border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding: 15px 20px; }
.main-sidebar .user-panel .info a { color: #ffffff !important; font-weight: 500; }

/* Content */
.content-wrapper { background-color: var(--brand-bg); }
.content-wrapper .content { padding: var(--page-padding); }
.content-wrapper .content > .container-fluid { padding-left: 0 !important; padding-right: 0 !important; }

html[data-theme="dark"] .content-wrapper { background-color: var(--brand-bg); }

/* Cards */
.card {
    background-color: var(--brand-bg-alt);
    border: none;
    border-radius: var(--brand-radius);
    box-shadow: var(--brand-shadow);
    margin-bottom: 25px;
}

.card:hover { box-shadow: var(--brand-shadow-lg); }

.card-header {
    background-color: transparent;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    padding: 20px 25px;
    font-weight: 600;
}

.card-body { padding: 25px; }

.card-primary .card-header {
    background: linear-gradient(to right, var(--brand-header-start), var(--brand-header-end));
    color: #ffffff;
    border-radius: var(--brand-radius) var(--brand-radius) 0 0;
}

html[data-theme="dark"] .card { background-color: var(--brand-bg-alt); }

/* Buttons */
.btn {
    border-radius: var(--brand-radius);
    font-weight: 500;
    padding: 8px 20px;
    border: none;
    transition: all 0.2s ease;
}

.btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15); }

.btn-primary {
    background: linear-gradient(to right, var(--brand-primary), #29b6f6);
    color: #ffffff;
}

.btn-success { background: linear-gradient(to right, var(--brand-success), #4aedc4); color: #ffffff; }
.btn-danger { background: linear-gradient(to right, var(--brand-danger), #ef5350); color: #ffffff; }
.btn-warning { background: linear-gradient(to right, var(--brand-warning), #ffca28); color: #ffffff; }
.btn-info { background: linear-gradient(to right, var(--brand-info), #29b6f6); color: #ffffff; }
.btn-secondary { background-color: #919aa3; color: #ffffff; }

/* Badges */
.badge { font-weight: 500; padding: 5px 10px; border-radius: var(--brand-radius-sm); }
.badge-primary, .bg-primary { background-color: var(--brand-primary) !important; }
.badge-success, .bg-success { background-color: var(--brand-success) !important; }
.badge-warning, .bg-warning { background-color: var(--brand-warning) !important; }
.badge-danger, .bg-danger { background-color: var(--brand-danger) !important; }
.badge-info, .bg-info { background-color: var(--brand-info) !important; }

/* Forms */
.form-control {
    border: 1px solid #e3e7ed;
    border-radius: var(--brand-radius);
    padding: 10px 15px;
}

.form-control:focus {
    border-color: var(--brand-primary);
    box-shadow: 0 0 0 3px rgba(4, 169, 245, 0.15);
}

/* Tables */
.table thead th {
    background-color: transparent;
    border-bottom: 2px solid #e3e7ed;
    color: var(--brand-text-secondary);
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
}

.table tbody td { padding: 15px; border-bottom: 1px solid #f0f3f6; }

/* Modals */
.modal-content { border: none; border-radius: var(--brand-radius-lg); }
.modal-header {
    background: linear-gradient(to right, var(--brand-header-start), var(--brand-header-end));
    border-bottom: none;
    border-radius: var(--brand-radius-lg) var(--brand-radius-lg) 0 0;
    color: #ffffff;
}

/* Login */
.login-page { background: linear-gradient(135deg, var(--brand-sidebar-bg) 0%, #2d3a4f 100%) !important; }
.login-card-body { border-radius: var(--brand-radius-lg); box-shadow: var(--brand-shadow-lg); }

/* Utilities */
.text-primary { color: var(--brand-primary) !important; }
.text-success { color: var(--brand-success) !important; }
.text-warning { color: var(--brand-warning) !important; }
.text-danger { color: var(--brand-danger) !important; }
.border-primary { border-color: var(--brand-primary) !important; }
.brand-gradient { background: linear-gradient(to right, var(--brand-header-start), var(--brand-header-end)); }

/* Compatibility */
.text-end { text-align: right !important; }
.fw-semibold { font-weight: 600 !important; }
.me-1 { margin-right: 0.25rem !important; }
.me-2 { margin-right: 0.5rem !important; }
.me-3 { margin-right: 1rem !important; }
.ms-2 { margin-left: 0.5rem !important; }
.gap-1 { gap: 0.25rem !important; }
.gap-2 { gap: 0.5rem !important; }
.gap-3 { gap: 1rem !important; }

.row.row-cards > [class*="col-"] { display: flex; margin-bottom: 25px; }
.row.row-cards > [class*="col-"] > .card { flex: 1 1 auto; width: 100%; }

/* Print */
@media print {
    .main-header, .main-sidebar, .main-footer { display: none !important; }
    .content-wrapper { margin-left: 0 !important; }
}

/* Validation icons */
.text-rose-600 > svg, div[class*="text-rose"] > svg:first-child {
    width: 0.875rem !important; height: 0.875rem !important;
    max-width: 0.875rem !important; max-height: 0.875rem !important;
    flex-shrink: 0 !important;
}

CSS;

        return $css;
    }
}
