{{-- 
    Dynamic Brand Styles
    This partial outputs CSS variables and overrides based on database branding settings.
    Uses a professional gray/blue/white theme as defaults.
    Include this in your layout's <head> section.
--}}
@php
    $brandingService = app(\App\Services\BrandingService::class);
    $brand = $brandingService->all();
    
    // Ensure all required keys exist with defaults
    $brand = array_merge([
        'color_primary' => '#3b82f6',
        'color_primary_dark' => '#1d4ed8',
        'color_primary_light' => '#93c5fd',
        'color_secondary' => '#64748b',
        'color_accent' => '#0ea5e9',
        'color_success' => '#22c55e',
        'color_warning' => '#f59e0b',
        'color_danger' => '#ef4444',
        'color_info' => '#06b6d4',
        'sidebar_bg' => '#1e293b',
        'sidebar_text' => '#94a3b8',
        'sidebar_hover' => '#334155',
        'sidebar_active' => '#3b82f6',
        'navbar_bg' => '#ffffff',
        'navbar_text' => '#1e293b',
        'button_primary' => '#3b82f6',
        'button_primary_hover' => '#2563eb',
        'button_secondary' => '#64748b',
        'content_bg' => '#f8fafc',
        'card_border_radius' => '0.5rem',
        'button_border_radius' => '0.375rem',
        'input_border_radius' => '0.375rem',
        'font_family' => "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
        'font_size_base' => '0.9375rem',
        'sidebar_width' => '250px',
        'custom_css' => '',
    ], $brand);
@endphp
<style id="brand-styles">
:root {
    /* Brand Colors */
    --brand-primary: {{ $brand['color_primary'] }};
    --brand-primary-dark: {{ $brand['color_primary_dark'] }};
    --brand-primary-light: {{ $brand['color_primary_light'] }};
    --brand-secondary: {{ $brand['color_secondary'] }};
    --brand-accent: {{ $brand['color_accent'] }};
    --brand-success: {{ $brand['color_success'] }};
    --brand-warning: {{ $brand['color_warning'] }};
    --brand-danger: {{ $brand['color_danger'] }};
    --brand-info: {{ $brand['color_info'] }};

    /* Sidebar */
    --brand-sidebar-bg: {{ $brand['sidebar_bg'] }};
    --brand-sidebar-text: {{ $brand['sidebar_text'] }};
    --brand-sidebar-hover: {{ $brand['sidebar_hover'] }};
    --brand-sidebar-active: {{ $brand['sidebar_active'] }};

    /* Navbar */
    --brand-navbar-bg: {{ $brand['navbar_bg'] }};
    --brand-navbar-text: {{ $brand['navbar_text'] }};

    /* Buttons */
    --brand-btn-primary: {{ $brand['button_primary'] }};
    --brand-btn-primary-hover: {{ $brand['button_primary_hover'] }};
    --brand-btn-secondary: {{ $brand['button_secondary'] }};

    /* Layout */
    --brand-content-bg: {{ $brand['content_bg'] }};
    --brand-sidebar-width: {{ $brand['sidebar_width'] }};

    /* Border Radius */
    --brand-card-radius: {{ $brand['card_border_radius'] }};
    --brand-btn-radius: {{ $brand['button_border_radius'] }};
    --brand-input-radius: {{ $brand['input_border_radius'] }};

    /* Typography */
    --brand-font-family: {{ $brand['font_family'] }};
    --brand-font-size: {{ $brand['font_size_base'] }};
}

/* ===== Modern Button Styles (inline fallback) ===== */
.btn-primary-modern {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.5rem !important;
    border-radius: 0.5rem !important;
    padding: 0.625rem 1rem !important;
    font-size: 0.875rem !important;
    font-weight: 600 !important;
    background-color: #0f172a !important;
    color: #ffffff !important;
    border: none !important;
    transition: all 0.15s ease !important;
    cursor: pointer !important;
    text-decoration: none !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
}
.btn-primary-modern:hover {
    background-color: #1e293b !important;
    color: #ffffff !important;
}
.btn-primary-modern:disabled {
    opacity: 0.5 !important;
    cursor: not-allowed !important;
}

.btn-secondary-modern {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.5rem !important;
    border-radius: 0.5rem !important;
    padding: 0.625rem 1rem !important;
    font-size: 0.875rem !important;
    font-weight: 600 !important;
    background-color: #ffffff !important;
    color: #0f172a !important;
    border: 1px solid #cbd5e1 !important;
    transition: all 0.15s ease !important;
    cursor: pointer !important;
}
.btn-secondary-modern:hover {
    background-color: #f8fafc !important;
}

/* Navbar theme/density toggle buttons */
.btn-theme-toggle,
.btn-density-toggle {
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.375rem !important;
    padding: 0.375rem 0.75rem !important;
    font-size: 0.8125rem !important;
    font-weight: 500 !important;
    border-radius: 0.375rem !important;
    border: 1px solid #e2e8f0 !important;
    background-color: #fff !important;
    color: #475569 !important;
    cursor: pointer !important;
    transition: all 0.15s ease !important;
}
.btn-theme-toggle:hover,
.btn-density-toggle:hover {
    background-color: #f1f5f9 !important;
    border-color: #cbd5e1 !important;
    color: #1e293b !important;
}

/* Dashboard cards equal height */
.row-cards {
    display: flex;
    flex-wrap: wrap;
}
.row-cards > [class*="col-"] {
    display: flex;
    flex-direction: column;
}
.row-cards > [class*="col-"] > .card {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
}
.row-cards > [class*="col-"] > .card > .card-body {
    flex: 1 1 auto;
}

/* ===== AdminLTE/Bootstrap Brand Overrides ===== */

/* Primary Button */
.btn.btn-primary {
    background-color: var(--brand-primary) !important;
    border-color: var(--brand-primary) !important;
}
.btn.btn-primary:hover, .btn.btn-primary:focus, .btn.btn-primary:active {
    background-color: var(--brand-btn-primary-hover) !important;
    border-color: var(--brand-btn-primary-hover) !important;
}

/* Outline Primary */
.btn-outline-primary {
    color: var(--brand-primary) !important;
    border-color: var(--brand-primary) !important;
}
.btn-outline-primary:hover, .btn-outline-primary:focus {
    background-color: var(--brand-primary) !important;
    border-color: var(--brand-primary) !important;
    color: #fff !important;
}

/* Links */
a:not(.btn):not(.nav-link):not(.dropdown-item) {
    color: var(--brand-primary);
}
a:not(.btn):not(.nav-link):not(.dropdown-item):hover {
    color: var(--brand-primary-dark);
}

/* Navbar */
.main-header.navbar {
    background-color: var(--brand-navbar-bg) !important;
}
.main-header.navbar .nav-link {
    color: var(--brand-navbar-text) !important;
}
.main-header.navbar .nav-link:hover {
    color: var(--brand-navbar-text) !important;
    opacity: 0.8;
}

/* Sidebar */
.main-sidebar {
    background-color: var(--brand-sidebar-bg) !important;
}
.sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active,
.sidebar-light-primary .nav-sidebar > .nav-item > .nav-link.active {
    background-color: var(--brand-sidebar-active) !important;
    color: #fff !important;
}
.sidebar-dark-primary .nav-sidebar .nav-link,
.sidebar-light-primary .nav-sidebar .nav-link {
    color: var(--brand-sidebar-text) !important;
}
.sidebar-dark-primary .nav-sidebar .nav-link:hover,
.sidebar-light-primary .nav-sidebar .nav-link:hover {
    background-color: var(--brand-sidebar-hover) !important;
    color: #fff !important;
}
.brand-link {
    background-color: var(--brand-sidebar-bg) !important;
    border-bottom: 1px solid rgba(255,255,255,.1) !important;
}
.nav-sidebar .nav-header {
    color: var(--brand-sidebar-text) !important;
    opacity: 0.7;
}

/* Content Background */
.content-wrapper {
    background-color: var(--brand-content-bg) !important;
}

/* Cards */
.card {
    border-radius: var(--brand-card-radius) !important;
}
.card-header:first-child {
    border-radius: calc(var(--brand-card-radius) - 1px) calc(var(--brand-card-radius) - 1px) 0 0 !important;
}
.card-footer:last-child {
    border-radius: 0 0 calc(var(--brand-card-radius) - 1px) calc(var(--brand-card-radius) - 1px) !important;
}
.card-outline.card-primary {
    border-top-color: var(--brand-primary) !important;
}

/* Buttons Border Radius */
.btn {
    border-radius: var(--brand-btn-radius) !important;
}

/* Form Controls */
.form-control {
    border-radius: var(--brand-input-radius) !important;
}
.form-control:focus {
    border-color: var(--brand-primary) !important;
    box-shadow: 0 0 0 0.2rem rgba({{ 
        hexdec(substr($brand['color_primary'], 1, 2)) }}, {{ 
        hexdec(substr($brand['color_primary'], 3, 2)) }}, {{ 
        hexdec(substr($brand['color_primary'], 5, 2)) }}, 0.25) !important;
}
.custom-control-input:checked ~ .custom-control-label::before {
    background-color: var(--brand-primary) !important;
    border-color: var(--brand-primary) !important;
}

/* Badges */
.badge-primary {
    background-color: var(--brand-primary) !important;
}

/* Backgrounds */
.bg-primary {
    background-color: var(--brand-primary) !important;
}

/* Text Colors */
.text-primary {
    color: var(--brand-primary) !important;
}

/* Borders */
.border-primary {
    border-color: var(--brand-primary) !important;
}

/* Progress Bars */
.progress-bar {
    background-color: var(--brand-primary) !important;
}
.progress-bar.bg-primary {
    background-color: var(--brand-primary) !important;
}

/* Pagination */
.page-item.active .page-link {
    background-color: var(--brand-primary) !important;
    border-color: var(--brand-primary) !important;
}
.page-link {
    color: var(--brand-primary) !important;
}
.page-link:hover {
    color: var(--brand-primary-dark) !important;
}

/* Nav Tabs - Use neutral colors, not brand primary for text */
.nav-tabs .nav-link.active {
    border-bottom-color: var(--brand-primary) !important;
    color: #495057 !important;
    background-color: #fff !important;
}
.nav-tabs .nav-link {
    color: #495057 !important;
}
.nav-tabs .nav-link:hover {
    color: #212529 !important;
}
.nav-pills .nav-link.active {
    background-color: var(--brand-primary) !important;
}

/* Alerts - Info uses brand primary */
.alert-info {
    background-color: var(--brand-primary-light);
    border-color: var(--brand-primary);
    color: var(--brand-primary-dark);
}

/* Dropdown */
.dropdown-item.active, .dropdown-item:active {
    background-color: var(--brand-primary) !important;
}

/* Callout */
.callout.callout-primary {
    border-left-color: var(--brand-primary) !important;
}

@if(!empty($brand['custom_css']))
/* Custom CSS */
{!! $brand['custom_css'] !!}
@endif
</style>
