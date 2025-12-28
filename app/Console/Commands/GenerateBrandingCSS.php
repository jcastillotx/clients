<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateBrandingCSS extends Command
{
    protected $signature = 'branding:generate';

    protected $description = 'Generate branding CSS file from configuration';

    public function handle(): int
    {
        $cssPath = public_path('css/brand.css');
        $config = config('branding');

        $this->info('Generating branding CSS...');

        // Ensure directory exists
        $dir = dirname($cssPath);
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
            $this->info("Created directory: {$dir}");
        }

        $css = $this->buildCSSContent($config);

        // Write CSS file
        if (File::put($cssPath, $css)) {
            $this->info("✓ Branding CSS generated successfully!");
            $this->info("  Location: {$cssPath}");
            $this->info("  Size: " . number_format(strlen($css)) . " bytes");
            return self::SUCCESS;
        }

        $this->error("Failed to write CSS file to: {$cssPath}");
        $this->error("Please check directory permissions.");
        return self::FAILURE;
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
    --brand-primary: {$primary};
    --brand-primary-dark: {$primaryDark};
    --brand-primary-light: {$primaryLight};
    --brand-secondary: {$secondary};
    --brand-accent: {$accent};
    --brand-text-primary: {$textPrimary};
    --brand-text-secondary: {$textSecondary};
    --brand-text-muted: #b5bdca;
    --brand-bg: {$background};
    --brand-bg-alt: {$backgroundAlt};
    --brand-sidebar-bg: {$sidebarBg};
    --brand-sidebar-text: {$sidebarText};
    --brand-sidebar-hover: {$sidebarHover};
    --brand-sidebar-active: {$sidebarActive};
    --brand-sidebar-header: {$sidebarHeader};
    --brand-header-start: {$headerStart};
    --brand-header-end: {$headerEnd};
    --brand-success: {$success};
    --brand-warning: {$warning};
    --brand-danger: {$danger};
    --brand-info: {$info};
    --brand-font-primary: {$fontPrimary};
    --brand-font-secondary: {$fontSecondary};
    --brand-font-mono: {$fontMono};
    --brand-radius: {$radius};
    --brand-radius-lg: {$radiusLg};
    --brand-radius-sm: {$radiusSm};
    --brand-shadow-sm: {$shadowSm};
    --brand-shadow: {$shadow};
    --brand-shadow-lg: {$shadowLg};
    --page-padding-comfy: {$pagePadding};
    --page-padding-compact: 15px;
    --page-padding-extreme: 10px;
    --page-padding: var(--page-padding-comfy);
    --sidebar-width: 264px;
}

html[data-density="comfy"] { --page-padding: var(--page-padding-comfy); }
html[data-density="compact"] { --page-padding: var(--page-padding-compact); }
html[data-density="extreme"] { --page-padding: var(--page-padding-extreme); }

html[data-theme="dark"] {
    --brand-bg: #1a1f2b;
    --brand-bg-alt: #242a38;
    --brand-text-primary: #e4e6eb;
    --brand-text-secondary: #a8b0bf;
    --brand-sidebar-bg: #141820;
    --brand-sidebar-header: #0f1318;
}

body {
    font-family: var(--brand-font-secondary);
    color: var(--brand-text-primary);
    background-color: var(--brand-bg);
    font-size: 14px;
}

h1, h2, h3, h4, h5, h6 { font-family: var(--brand-font-primary); color: var(--brand-text-primary); font-weight: 600; }
a { color: var(--brand-primary); text-decoration: none; }
a:hover { color: var(--brand-primary-dark); }

.main-header.navbar {
    background: linear-gradient(to right, var(--brand-header-start), var(--brand-header-end)) !important;
    border: none !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
    min-height: 60px;
}

.main-header .navbar-nav .nav-link { color: rgba(255, 255, 255, 0.9) !important; }
.main-header .navbar-nav .nav-link:hover { color: #ffffff !important; }

html[data-theme="dark"] .main-header.navbar { background: linear-gradient(to right, #1e3a5f, #2d4a6f) !important; }

.main-sidebar { background-color: var(--brand-sidebar-bg) !important; box-shadow: 2px 0 6px rgba(0, 0, 0, 0.15); }
.main-sidebar .sidebar { background-color: var(--brand-sidebar-bg); }

.brand-link { background-color: var(--brand-sidebar-header) !important; border-bottom: none !important; padding: 15px 20px !important; min-height: 60px; }
.brand-link .brand-text { color: #ffffff !important; font-weight: 600; }

.main-sidebar .nav-link { color: var(--brand-sidebar-text) !important; padding: 12px 20px !important; border-left: 3px solid transparent; transition: all 0.2s ease; }
.main-sidebar .nav-link:hover { background-color: var(--brand-sidebar-hover) !important; color: #ffffff !important; }
.main-sidebar .nav-link.active { background-color: var(--brand-sidebar-hover) !important; color: #ffffff !important; border-left-color: var(--brand-sidebar-active) !important; }
.main-sidebar .nav-icon { color: var(--brand-sidebar-text); width: 24px; margin-right: 10px; }
.main-sidebar .nav-link:hover .nav-icon, .main-sidebar .nav-link.active .nav-icon { color: var(--brand-sidebar-active); }
.main-sidebar .nav-header { color: var(--brand-sidebar-text) !important; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 20px 20px 8px !important; opacity: 0.6; }
.nav-treeview { background-color: rgba(0, 0, 0, 0.15); }
.nav-treeview > .nav-item > .nav-link { padding-left: 45px !important; font-size: 13px; }
.main-sidebar .user-panel { border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding: 15px 20px; }
.main-sidebar .user-panel .info a { color: #ffffff !important; font-weight: 500; }

.content-wrapper { background-color: var(--brand-bg); }
.content-wrapper .content { padding: var(--page-padding); }
.content-wrapper .content > .container-fluid { padding-left: 0 !important; padding-right: 0 !important; }
html[data-theme="dark"] .content-wrapper { background-color: var(--brand-bg); }

.card { background-color: var(--brand-bg-alt); border: none; border-radius: var(--brand-radius); box-shadow: var(--brand-shadow); margin-bottom: 25px; }
.card:hover { box-shadow: var(--brand-shadow-lg); }
.card-header { background-color: transparent; border-bottom: 1px solid rgba(0, 0, 0, 0.06); padding: 20px 25px; font-weight: 600; }
.card-body { padding: 25px; }
.card-primary .card-header { background: linear-gradient(to right, var(--brand-header-start), var(--brand-header-end)); color: #ffffff; border-radius: var(--brand-radius) var(--brand-radius) 0 0; }
html[data-theme="dark"] .card { background-color: var(--brand-bg-alt); }

.btn { border-radius: var(--brand-radius); font-weight: 500; padding: 8px 20px; border: none; transition: all 0.2s ease; }
.btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15); }
.btn-primary { background: linear-gradient(to right, var(--brand-primary), #29b6f6); color: #ffffff; }
.btn-success { background: linear-gradient(to right, var(--brand-success), #4aedc4); color: #ffffff; }
.btn-danger { background: linear-gradient(to right, var(--brand-danger), #ef5350); color: #ffffff; }
.btn-warning { background: linear-gradient(to right, var(--brand-warning), #ffca28); color: #ffffff; }
.btn-info { background: linear-gradient(to right, var(--brand-info), #29b6f6); color: #ffffff; }
.btn-secondary { background-color: #919aa3; color: #ffffff; }

.badge { font-weight: 500; padding: 5px 10px; border-radius: var(--brand-radius-sm); }
.badge-primary, .bg-primary { background-color: var(--brand-primary) !important; }
.badge-success, .bg-success { background-color: var(--brand-success) !important; }
.badge-warning, .bg-warning { background-color: var(--brand-warning) !important; }
.badge-danger, .bg-danger { background-color: var(--brand-danger) !important; }
.badge-info, .bg-info { background-color: var(--brand-info) !important; }

.form-control { border: 1px solid #e3e7ed; border-radius: var(--brand-radius); padding: 10px 15px; }
.form-control:focus { border-color: var(--brand-primary); box-shadow: 0 0 0 3px rgba(4, 169, 245, 0.15); }

.table thead th { background-color: transparent; border-bottom: 2px solid #e3e7ed; color: var(--brand-text-secondary); font-weight: 600; font-size: 13px; text-transform: uppercase; }
.table tbody td { padding: 15px; border-bottom: 1px solid #f0f3f6; }

.modal-content { border: none; border-radius: var(--brand-radius-lg); }
.modal-header { background: linear-gradient(to right, var(--brand-header-start), var(--brand-header-end)); border-bottom: none; border-radius: var(--brand-radius-lg) var(--brand-radius-lg) 0 0; color: #ffffff; }

.login-page { background: linear-gradient(135deg, var(--brand-sidebar-bg) 0%, #2d3a4f 100%) !important; }
.login-card-body { border-radius: var(--brand-radius-lg); box-shadow: var(--brand-shadow-lg); }

.text-primary { color: var(--brand-primary) !important; }
.text-success { color: var(--brand-success) !important; }
.text-warning { color: var(--brand-warning) !important; }
.text-danger { color: var(--brand-danger) !important; }
.border-primary { border-color: var(--brand-primary) !important; }
.brand-gradient { background: linear-gradient(to right, var(--brand-header-start), var(--brand-header-end)); }

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

@media print { .main-header, .main-sidebar, .main-footer { display: none !important; } .content-wrapper { margin-left: 0 !important; } }

.text-rose-600 > svg, div[class*="text-rose"] > svg:first-child { width: 0.875rem !important; height: 0.875rem !important; max-width: 0.875rem !important; max-height: 0.875rem !important; flex-shrink: 0 !important; }

CSS;

        return $css;
    }
}
