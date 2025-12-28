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

/* ===== Tailwind Utility Overrides - Must come first for specificity ===== */
/* These ensure Tailwind classes work properly even in Bootstrap contexts */

/* Slate color buttons - primary action buttons */
.bg-slate-900,
button.bg-slate-900,
[class*="bg-slate-900"] {
    background-color: #0f172a !important;
    border-color: transparent !important;
}
.bg-slate-800,
button.bg-slate-800,
[class*="bg-slate-800"] {
    background-color: #1e293b !important;
    border-color: transparent !important;
}
.hover\:bg-slate-800:hover {
    background-color: #1e293b !important;
}
.text-white {
    color: #fff !important;
}

/* Ensure rounded corners work */
.rounded-lg {
    border-radius: 0.5rem !important;
}
.rounded-xl {
    border-radius: 0.75rem !important;
}
.rounded-2xl {
    border-radius: 1rem !important;
}

/* Tailwind flex utilities */
.flex { display: flex !important; }
.items-center { align-items: center !important; }
.gap-2 { gap: 0.5rem !important; }

/* Tailwind padding utilities */
.px-4 { padding-left: 1rem !important; padding-right: 1rem !important; }
.py-2\.5, [class*="py-2.5"] { padding-top: 0.625rem !important; padding-bottom: 0.625rem !important; }

/* Tailwind text utilities */
.text-sm { font-size: 0.875rem !important; line-height: 1.25rem !important; }
.font-semibold { font-weight: 600 !important; }

/* ===== Modern Button Classes (Fallback/Override) ===== */
/* These ensure modern buttons always look correct regardless of Tailwind compilation */
.btn-primary-modern {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.5rem !important;
    border-radius: 0.5rem !important;
    padding: 0.625rem 1rem !important;
    font-size: 0.875rem !important;
    font-weight: 600 !important;
    background-color: #0f172a !important; /* slate-900 */
    color: #ffffff !important;
    border: none !important;
    cursor: pointer !important;
    transition: background-color 0.15s ease-in-out !important;
}
.btn-primary-modern:hover {
    background-color: #1e293b !important; /* slate-800 */
    color: #ffffff !important;
}
.btn-primary-modern:focus {
    outline: none !important;
    box-shadow: 0 0 0 2px #0f172a40 !important;
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
    border: 1px solid #cbd5e1 !important; /* slate-300 */
    cursor: pointer !important;
    transition: background-color 0.15s ease-in-out !important;
}
.btn-secondary-modern:hover {
    background-color: #f8fafc !important; /* slate-50 */
    color: #0f172a !important;
}
.btn-secondary-modern:focus {
    outline: none !important;
    box-shadow: 0 0 0 2px #64748b40 !important;
}
.btn-secondary-modern:disabled {
    opacity: 0.5 !important;
    cursor: not-allowed !important;
}

/* ===== AdminLTE Brand Overrides ===== */
/* Note: These styles only apply to Bootstrap .btn classes */

/* Primary Button - Bootstrap only (must have BOTH .btn AND .btn-primary) */
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

/* Nav Tabs */
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
