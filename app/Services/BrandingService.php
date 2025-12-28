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
     * Default branding values.
     */
    protected static array $defaults = [
        // Logos
        'logo_path' => '',
        'login_logo_path' => '',
        'dashboard_logo_path' => '',
        'favicon_path' => '',
        'login_background_path' => '',

        // Brand Colors
        'color_primary' => '#007bff',
        'color_primary_dark' => '#0056b3',
        'color_primary_light' => '#66b3ff',
        'color_secondary' => '#6c757d',
        'color_accent' => '#28a745',
        'color_success' => '#28a745',
        'color_warning' => '#ffc107',
        'color_danger' => '#dc3545',
        'color_info' => '#17a2b8',

        // Sidebar Colors
        'sidebar_bg' => '#343a40',
        'sidebar_text' => '#c2c7d0',
        'sidebar_hover' => '#495057',
        'sidebar_active' => '#007bff',

        // Navbar Colors
        'navbar_bg' => '#343a40',
        'navbar_text' => '#ffffff',
        'navbar_variant' => 'dark', // light or dark

        // Button Colors
        'button_primary' => '',
        'button_primary_hover' => '',
        'button_secondary' => '',
        'button_secondary_hover' => '',

        // Typography
        'font_family' => "'Source Sans Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
        'font_size_base' => '1rem',
        'heading_font_family' => '',

        // Cards & Borders
        'card_border_radius' => '0.25rem',
        'button_border_radius' => '0.25rem',
        'input_border_radius' => '0.25rem',

        // Layout
        'sidebar_width' => '250px',
        'sidebar_collapsed_width' => '4.6rem',
        'content_bg' => '#f4f6f9',

        // Custom
        'custom_css' => '',
        'site_header_html' => '',
        'site_footer_html' => '',
        'email_header_html' => '',
        'email_footer_html' => '',

        // Invoice/Document Templates
        'invoice_template' => 'default',
        'document_logo_path' => '',

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

            foreach ($settings as $key => $value) {
                // Convert DB key format (branding.logo_path) to array key (logo_path)
                $shortKey = str_replace('branding.', '', $key);
                $shortKey = str_replace('.', '_', $shortKey);

                if (array_key_exists($shortKey, $result)) {
                    $result[$shortKey] = $value;
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
                'name' => 'Ocean Blue',
                'color_primary' => '#007bff',
                'color_primary_dark' => '#0056b3',
                'color_primary_light' => '#66b3ff',
                'color_accent' => '#28a745',
                'sidebar_bg' => '#343a40',
            ],
            'indigo' => [
                'name' => 'Indigo',
                'color_primary' => '#6610f2',
                'color_primary_dark' => '#4709ac',
                'color_primary_light' => '#a370f7',
                'color_accent' => '#fd7e14',
                'sidebar_bg' => '#2c2540',
            ],
            'purple' => [
                'name' => 'Royal Purple',
                'color_primary' => '#6f42c1',
                'color_primary_dark' => '#4e2d89',
                'color_primary_light' => '#a98eda',
                'color_accent' => '#20c997',
                'sidebar_bg' => '#3d2b5a',
            ],
            'teal' => [
                'name' => 'Teal',
                'color_primary' => '#20c997',
                'color_primary_dark' => '#158765',
                'color_primary_light' => '#63e6be',
                'color_accent' => '#fd7e14',
                'sidebar_bg' => '#1a3a35',
            ],
            'green' => [
                'name' => 'Forest Green',
                'color_primary' => '#28a745',
                'color_primary_dark' => '#1c7430',
                'color_primary_light' => '#71dd8a',
                'color_accent' => '#007bff',
                'sidebar_bg' => '#1e3a28',
            ],
            'orange' => [
                'name' => 'Sunset Orange',
                'color_primary' => '#fd7e14',
                'color_primary_dark' => '#c35a02',
                'color_primary_light' => '#fdb36d',
                'color_accent' => '#007bff',
                'sidebar_bg' => '#4a3020',
            ],
            'red' => [
                'name' => 'Ruby Red',
                'color_primary' => '#dc3545',
                'color_primary_dark' => '#a71d2a',
                'color_primary_light' => '#f1aeb5',
                'color_accent' => '#007bff',
                'sidebar_bg' => '#4a2025',
            ],
            'dark' => [
                'name' => 'Dark Mode',
                'color_primary' => '#6c757d',
                'color_primary_dark' => '#4a5056',
                'color_primary_light' => '#adb5bd',
                'color_accent' => '#17a2b8',
                'sidebar_bg' => '#1a1a2e',
            ],
        ];
    }
}
