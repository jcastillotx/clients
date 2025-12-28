<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class BrandingService
{
    /**
     * Cache TTL in seconds.
     */
    protected const CACHE_TTL = 3600;

    /**
     * Cache key prefix.
     */
    protected const CACHE_KEY = 'branding_settings';

    /**
     * Default branding values - Professional gray/blue/white theme.
     */
    protected static array $defaults = [
        // Logos
        'logo_path' => '',
        'login_logo_path' => '',
        'dashboard_logo_path' => '',
        'favicon_path' => '',
        'login_background_path' => '',
        'document_logo_path' => '',

        // Brand Colors - Professional Blue/Gray/White Theme
        'color_primary' => '#3b82f6',       // Modern blue
        'color_primary_dark' => '#1d4ed8',  // Darker blue for hover
        'color_primary_light' => '#93c5fd', // Light blue for accents
        'color_secondary' => '#64748b',     // Slate gray
        'color_accent' => '#0ea5e9',        // Sky blue accent
        'color_success' => '#22c55e',       // Green
        'color_warning' => '#f59e0b',       // Amber
        'color_danger' => '#ef4444',        // Red
        'color_info' => '#06b6d4',          // Cyan

        // Sidebar Colors - Dark professional look
        'sidebar_bg' => '#1e293b',          // Slate 800
        'sidebar_text' => '#94a3b8',        // Slate 400
        'sidebar_hover' => '#334155',       // Slate 700
        'sidebar_active' => '#3b82f6',      // Primary blue

        // Navbar Colors
        'navbar_bg' => '#ffffff',           // White navbar
        'navbar_text' => '#1e293b',         // Dark text
        'navbar_variant' => 'light',

        // Button Colors - Will fallback to primary if empty
        'button_primary' => '#3b82f6',
        'button_primary_hover' => '#2563eb',
        'button_secondary' => '#64748b',
        'button_secondary_hover' => '#475569',

        // Typography
        'font_family' => "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
        'font_size_base' => '0.9375rem',
        'heading_font_family' => '',

        // Cards & Borders - Slightly rounded for modern look
        'card_border_radius' => '0.5rem',
        'button_border_radius' => '0.375rem',
        'input_border_radius' => '0.375rem',

        // Layout
        'sidebar_width' => '250px',
        'sidebar_collapsed_width' => '4.6rem',
        'content_bg' => '#f8fafc',          // Slate 50 - very light gray

        // Custom
        'custom_css' => '',
        'site_header_html' => '',
        'site_footer_html' => '',
        'email_header_html' => '',
        'email_footer_html' => '',

        // Invoice/Document Templates
        'invoice_template' => 'default',

        // Custom Domain
        'custom_domain' => '',

        // Platform Name
        'platform_name' => '',
        'company_name' => '',
        'tagline' => '',
    ];

    /**
     * Get all branding settings.
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $settings = Setting::where('group', 'branding')->pluck('value', 'key')->toArray();

            $result = self::$defaults;

            // Map database keys to result keys
            $keyMap = [
                'branding.logo_path' => 'logo_path',
                'branding.login_logo_path' => 'login_logo_path',
                'branding.dashboard_logo_path' => 'dashboard_logo_path',
                'branding.login_background_path' => 'login_background_path',
                'branding.favicon_path' => 'favicon_path',
                'branding.document_logo_path' => 'document_logo_path',
                'branding.colors.primary' => 'color_primary',
                'branding.colors.secondary' => 'color_secondary',
                'branding.colors.accent' => 'color_accent',
                'branding.colors.success' => 'color_success',
                'branding.colors.warning' => 'color_warning',
                'branding.colors.danger' => 'color_danger',
                'branding.colors.info' => 'color_info',
                'branding.colors.primary_dark' => 'color_primary_dark',
                'branding.colors.primary_light' => 'color_primary_light',
                'branding.buttons.primary' => 'button_primary',
                'branding.buttons.primary_hover' => 'button_primary_hover',
                'branding.buttons.secondary' => 'button_secondary',
                'branding.buttons.secondary_hover' => 'button_secondary_hover',
                'branding.sidebar.bg' => 'sidebar_bg',
                'branding.sidebar.text' => 'sidebar_text',
                'branding.sidebar.hover' => 'sidebar_hover',
                'branding.sidebar.active' => 'sidebar_active',
                'branding.navbar.bg' => 'navbar_bg',
                'branding.navbar.text' => 'navbar_text',
                'branding.navbar.variant' => 'navbar_variant',
                'branding.content.bg' => 'content_bg',
                'branding.invoice_template' => 'invoice_template',
                'branding.email.header_html' => 'email_header_html',
                'branding.email.footer_html' => 'email_footer_html',
                'branding.site.header_html' => 'site_header_html',
                'branding.site.footer_html' => 'site_footer_html',
                'branding.admin.header_html' => 'site_header_html', // fallback
                'branding.admin.footer_html' => 'site_footer_html', // fallback
                'branding.custom_domain' => 'custom_domain',
                'branding.custom_css' => 'custom_css',
                'branding.platform_name' => 'platform_name',
                'branding.company_name' => 'company_name',
                'branding.tagline' => 'tagline',
            ];

            foreach ($settings as $dbKey => $value) {
                if (isset($keyMap[$dbKey]) && $value !== null && $value !== '') {
                    $result[$keyMap[$dbKey]] = $value;
                }
            }

            // Ensure button colors fallback to brand colors if not set
            if (empty($result['button_primary'])) {
                $result['button_primary'] = $result['color_primary'];
            }
            if (empty($result['button_primary_hover'])) {
                $result['button_primary_hover'] = $result['color_primary_dark'];
            }
            if (empty($result['button_secondary'])) {
                $result['button_secondary'] = $result['color_secondary'];
            }

            // Ensure sidebar active fallback to primary
            if (empty($result['sidebar_active']) || $result['sidebar_active'] === '#007bff') {
                $result['sidebar_active'] = $result['color_primary'];
            }

            return $result;
        });
    }

    /**
     * Get a specific branding setting.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return $all[$key] ?? $default ?? (self::$defaults[$key] ?? null);
    }

    /**
     * Set a branding setting.
     */
    public function set(string $key, mixed $value): void
    {
        $dbKey = 'branding.' . str_replace('_', '.', $key);

        Setting::updateOrCreate(
            ['key' => $dbKey],
            [
                'value' => $value,
                'group' => 'branding',
            ]
        );

        $this->clearCache();
    }

    /**
     * Set multiple branding settings.
     */
    public function setMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $dbKey = 'branding.' . str_replace('_', '.', $key);

            Setting::updateOrCreate(
                ['key' => $dbKey],
                [
                    'value' => $value,
                    'group' => 'branding',
                ]
            );
        }

        $this->clearCache();
    }

    /**
     * Clear the branding cache.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Get logo URL with fallback.
     */
    public function logoUrl(string $type = 'main'): ?string
    {
        $key = match ($type) {
            'login' => 'login_logo_path',
            'dashboard' => 'dashboard_logo_path',
            'favicon' => 'favicon_path',
            'document' => 'document_logo_path',
            default => 'logo_path',
        };

        $path = $this->get($key);

        if (empty($path)) {
            // Fallback to main logo for login/dashboard
            if (in_array($type, ['login', 'dashboard', 'document'])) {
                $path = $this->get('logo_path');
            }
        }

        return $path ? asset('storage/' . $path) : null;
    }

    /**
     * Generate CSS variables for branding.
     */
    public function generateCssVariables(): string
    {
        $brand = $this->all();

        $css = ":root {\n";

        // Brand colors
        $css .= "    --brand-primary: {$brand['color_primary']};\n";
        $css .= "    --brand-primary-dark: {$brand['color_primary_dark']};\n";
        $css .= "    --brand-primary-light: {$brand['color_primary_light']};\n";
        $css .= "    --brand-secondary: {$brand['color_secondary']};\n";
        $css .= "    --brand-accent: {$brand['color_accent']};\n";
        $css .= "    --brand-success: {$brand['color_success']};\n";
        $css .= "    --brand-warning: {$brand['color_warning']};\n";
        $css .= "    --brand-danger: {$brand['color_danger']};\n";
        $css .= "    --brand-info: {$brand['color_info']};\n";

        // Sidebar
        $css .= "    --brand-sidebar-bg: {$brand['sidebar_bg']};\n";
        $css .= "    --brand-sidebar-text: {$brand['sidebar_text']};\n";
        $css .= "    --brand-sidebar-hover: {$brand['sidebar_hover']};\n";
        $css .= "    --brand-sidebar-active: {$brand['sidebar_active']};\n";

        // Navbar
        $css .= "    --brand-navbar-bg: {$brand['navbar_bg']};\n";
        $css .= "    --brand-navbar-text: {$brand['navbar_text']};\n";

        // Buttons
        $css .= "    --brand-btn-primary: {$brand['button_primary']};\n";
        $css .= "    --brand-btn-primary-hover: {$brand['button_primary_hover']};\n";
        $css .= "    --brand-btn-secondary: {$brand['button_secondary']};\n";

        // Layout
        $css .= "    --brand-content-bg: {$brand['content_bg']};\n";
        $css .= "    --brand-sidebar-width: {$brand['sidebar_width']};\n";

        // Border radius
        $css .= "    --brand-card-radius: {$brand['card_border_radius']};\n";
        $css .= "    --brand-btn-radius: {$brand['button_border_radius']};\n";
        $css .= "    --brand-input-radius: {$brand['input_border_radius']};\n";

        // Typography
        $css .= "    --brand-font-family: {$brand['font_family']};\n";
        $css .= "    --brand-font-size: {$brand['font_size_base']};\n";

        $css .= "}\n";

        return $css;
    }

    /**
     * Generate full custom CSS including overrides.
     */
    public function generateFullCss(): string
    {
        $brand = $this->all();
        $css = $this->generateCssVariables();

        // AdminLTE overrides using brand colors
        $css .= "
/* Brand Primary Button */
.btn-primary {
    background-color: var(--brand-primary);
    border-color: var(--brand-primary);
}
.btn-primary:hover, .btn-primary:focus {
    background-color: var(--brand-btn-primary-hover);
    border-color: var(--brand-btn-primary-hover);
}

/* Brand Outline Primary */
.btn-outline-primary {
    color: var(--brand-primary);
    border-color: var(--brand-primary);
}
.btn-outline-primary:hover {
    background-color: var(--brand-primary);
    border-color: var(--brand-primary);
}

/* Navbar Brand */
.main-header.navbar {
    background-color: var(--brand-navbar-bg);
}
.main-header.navbar .nav-link {
    color: var(--brand-navbar-text);
}

/* Sidebar */
.main-sidebar {
    background-color: var(--brand-sidebar-bg);
}
.sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active,
.sidebar-light-primary .nav-sidebar > .nav-item > .nav-link.active {
    background-color: var(--brand-sidebar-active);
    color: #fff;
}
.sidebar-dark-primary .nav-sidebar .nav-link,
.nav-sidebar .nav-link {
    color: var(--brand-sidebar-text);
}
.sidebar-dark-primary .nav-sidebar .nav-link:hover {
    background-color: var(--brand-sidebar-hover);
}

/* Content Background */
.content-wrapper {
    background-color: var(--brand-content-bg);
}

/* Card Border Radius */
.card {
    border-radius: var(--brand-card-radius);
}
.card-header:first-child {
    border-radius: calc(var(--brand-card-radius) - 1px) calc(var(--brand-card-radius) - 1px) 0 0;
}

/* Button Border Radius */
.btn {
    border-radius: var(--brand-btn-radius);
}

/* Input Border Radius */
.form-control {
    border-radius: var(--brand-input-radius);
}

/* Links */
a {
    color: var(--brand-primary);
}
a:hover {
    color: var(--brand-primary-dark);
}

/* Badge Primary */
.badge-primary {
    background-color: var(--brand-primary);
}

/* Background Primary */
.bg-primary {
    background-color: var(--brand-primary) !important;
}

/* Text Primary */
.text-primary {
    color: var(--brand-primary) !important;
}

/* Border Primary */
.border-primary {
    border-color: var(--brand-primary) !important;
}

/* Page/Brand Item */
.brand-link {
    background-color: var(--brand-sidebar-bg);
    border-bottom: 1px solid rgba(255,255,255,.1);
}
";

        // Add custom CSS from settings
        if (!empty($brand['custom_css'])) {
            $css .= "\n/* Custom CSS */\n" . $brand['custom_css'];
        }

        return $css;
    }

    /**
     * Get default branding values.
     */
    public static function defaults(): array
    {
        return self::$defaults;
    }

    /**
     * Get color presets for quick selection.
     */
    public static function colorPresets(): array
    {
        return [
            'blue' => [
                'name' => 'Professional Blue',
                'color_primary' => '#3b82f6',
                'color_primary_dark' => '#1d4ed8',
                'color_primary_light' => '#93c5fd',
                'color_secondary' => '#64748b',
                'color_accent' => '#0ea5e9',
                'sidebar_bg' => '#1e293b',
                'sidebar_text' => '#94a3b8',
                'navbar_bg' => '#ffffff',
                'navbar_text' => '#1e293b',
            ],
            'indigo' => [
                'name' => 'Indigo',
                'color_primary' => '#6366f1',
                'color_primary_dark' => '#4338ca',
                'color_primary_light' => '#a5b4fc',
                'color_secondary' => '#64748b',
                'color_accent' => '#f59e0b',
                'sidebar_bg' => '#1e1b4b',
                'sidebar_text' => '#a5b4fc',
                'navbar_bg' => '#ffffff',
                'navbar_text' => '#1e1b4b',
            ],
            'purple' => [
                'name' => 'Purple',
                'color_primary' => '#8b5cf6',
                'color_primary_dark' => '#6d28d9',
                'color_primary_light' => '#c4b5fd',
                'color_secondary' => '#64748b',
                'color_accent' => '#14b8a6',
                'sidebar_bg' => '#2e1065',
                'sidebar_text' => '#c4b5fd',
                'navbar_bg' => '#ffffff',
                'navbar_text' => '#2e1065',
            ],
            'teal' => [
                'name' => 'Teal',
                'color_primary' => '#14b8a6',
                'color_primary_dark' => '#0d9488',
                'color_primary_light' => '#5eead4',
                'color_secondary' => '#64748b',
                'color_accent' => '#f59e0b',
                'sidebar_bg' => '#134e4a',
                'sidebar_text' => '#99f6e4',
                'navbar_bg' => '#ffffff',
                'navbar_text' => '#134e4a',
            ],
            'green' => [
                'name' => 'Green',
                'color_primary' => '#22c55e',
                'color_primary_dark' => '#16a34a',
                'color_primary_light' => '#86efac',
                'color_secondary' => '#64748b',
                'color_accent' => '#3b82f6',
                'sidebar_bg' => '#14532d',
                'sidebar_text' => '#bbf7d0',
                'navbar_bg' => '#ffffff',
                'navbar_text' => '#14532d',
            ],
            'orange' => [
                'name' => 'Orange',
                'color_primary' => '#f97316',
                'color_primary_dark' => '#ea580c',
                'color_primary_light' => '#fdba74',
                'color_secondary' => '#64748b',
                'color_accent' => '#3b82f6',
                'sidebar_bg' => '#431407',
                'sidebar_text' => '#fed7aa',
                'navbar_bg' => '#ffffff',
                'navbar_text' => '#431407',
            ],
            'red' => [
                'name' => 'Red',
                'color_primary' => '#ef4444',
                'color_primary_dark' => '#dc2626',
                'color_primary_light' => '#fca5a5',
                'color_secondary' => '#64748b',
                'color_accent' => '#3b82f6',
                'sidebar_bg' => '#450a0a',
                'sidebar_text' => '#fecaca',
                'navbar_bg' => '#ffffff',
                'navbar_text' => '#450a0a',
            ],
            'dark' => [
                'name' => 'Dark Slate',
                'color_primary' => '#6366f1',
                'color_primary_dark' => '#4f46e5',
                'color_primary_light' => '#a5b4fc',
                'color_secondary' => '#475569',
                'color_accent' => '#06b6d4',
                'sidebar_bg' => '#0f172a',
                'sidebar_text' => '#94a3b8',
                'navbar_bg' => '#1e293b',
                'navbar_text' => '#f1f5f9',
            ],
        ];
    }
}
