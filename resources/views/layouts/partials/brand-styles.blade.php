{{--
Dynamic Brand Styles
This partial outputs CSS variables and overrides based on database branding settings.
Uses a professional gray/blue/white theme as defaults.
Include this in your layout's

<head> section.
    --}}
    @php
        $brandingService = app(\App\Services\BrandingService::class);
        $brand = $brandingService->all();

        // Ensure all required keys exist with defaults
        $brand = array_merge([
            'color_primary' => '#5F5F82',
            'color_primary_dark' => '#4A4A66',
            'color_primary_light' => '#E8E8F0',
            'color_secondary' => '#BFCEE0',
            'color_accent' => '#000000',
            'color_success' => '#22c55e',
            'color_warning' => '#f59e0b',
            'color_danger' => '#ef4444',
            'color_info' => '#5F5F82',
            'sidebar_bg' => '#5F5F82',
            'sidebar_text' => '#BFCEE0',
            'sidebar_hover' => '#4A4A66',
            'sidebar_active' => '#BFCEE0',
            'navbar_bg' => '#ffffff',
            'navbar_text' => '#000000',
            'button_primary' => '#5F5F82',
            'button_primary_hover' => '#4A4A66',
            'button_secondary' => '#6c757d',
            'button_secondary_hover' => '#5a6268',
            'button_dark' => '#1e293b',
            'button_dark_hover' => '#334155',
            'content_bg' => '#f8fafc',
            'content_wrapper_padding' => '1.5rem',
            // Border radius - default 6px
            'card_border_radius' => '6px',
            'button_border_radius' => '6px',
            'input_border_radius' => '6px',
            'border_radius' => '6px',
            // Typography - separate heading and body fonts
            'font_family' => "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
            'font_heading' => '',
            'font_body' => '',
            'font_size_base' => '0.9375rem',
            'sidebar_width' => '250px',
            'custom_css' => '',
        ], $brand);

        // Determine effective heading and body fonts (with fallbacks)
        $effectiveFontHeading = !empty($brand['font_heading']) ? $brand['font_heading'] : $brand['font_family'];
        $effectiveFontBody = !empty($brand['font_body']) ? $brand['font_body'] : $brand['font_family'];
    @endphp
    <style id="brand-styles">
        :root {
            /* Brand Colors */
            --brand-primary:
                {{ $brand['color_primary'] }}
            ;
            --brand-primary-dark:
                {{ $brand['color_primary_dark'] }}
            ;
            --brand-primary-light:
                {{ $brand['color_primary_light'] }}
            ;
            --brand-secondary:
                {{ $brand['color_secondary'] }}
            ;
            --brand-accent:
                {{ $brand['color_accent'] }}
            ;
            --brand-success:
                {{ $brand['color_success'] }}
            ;
            --brand-warning:
                {{ $brand['color_warning'] }}
            ;
            --brand-danger:
                {{ $brand['color_danger'] }}
            ;
            --brand-info:
                {{ $brand['color_info'] }}
            ;

            /* Sidebar */
            --brand-sidebar-bg:
                {{ $brand['sidebar_bg'] }}
            ;
            --brand-sidebar-text:
                {{ $brand['sidebar_text'] }}
            ;
            --brand-sidebar-hover:
                {{ $brand['sidebar_hover'] }}
            ;
            --brand-sidebar-active:
                {{ $brand['sidebar_active'] }}
            ;

            /* Navbar */
            --brand-navbar-bg:
                {{ $brand['navbar_bg'] }}
            ;
            --brand-navbar-text:
                {{ $brand['navbar_text'] }}
            ;

            /* Buttons - Solid colors */
            --brand-btn-primary:
                {{ $brand['button_primary'] }}
            ;
            --brand-btn-primary-hover:
                {{ $brand['button_primary_hover'] }}
            ;
            --brand-btn-secondary:
                {{ $brand['button_secondary'] ?? '#6c757d' }}
            ;
            --brand-btn-secondary-hover:
                {{ $brand['button_secondary_hover'] ?? '#5a6268' }}
            ;
            --brand-btn-dark:
                {{ $brand['button_dark'] ?? '#1e293b' }}
            ;
            --brand-btn-dark-hover:
                {{ $brand['button_dark_hover'] ?? '#334155' }}
            ;

            /* Layout */
            --brand-content-bg:
                {{ $brand['content_bg'] }}
            ;
            --brand-sidebar-width:
                {{ $brand['sidebar_width'] }}
            ;
            --sidebar-width:
                {{ $brand['sidebar_width'] }}
            ;
            --content-wrapper-padding:
                {{ $brand['content_wrapper_padding'] ?? '1.5rem' }}
            ;

            /* Border Radius - Default 6px */
            --brand-radius:
                {{ $brand['border_radius'] ?? '6px' }}
            ;
            --brand-radius-lg:
                {{ $brand['border_radius'] === '6px' ? '8px' : ($brand['border_radius'] ?? '8px') }}
            ;
            --brand-radius-sm:
                {{ $brand['border_radius'] === '6px' ? '4px' : ($brand['border_radius'] ?? '4px') }}
            ;
            --brand-card-radius:
                {{ $brand['card_border_radius'] ?? '6px' }}
            ;
            --brand-btn-radius:
                {{ $brand['button_border_radius'] ?? '6px' }}
            ;
            --brand-input-radius:
                {{ $brand['input_border_radius'] ?? '6px' }}
            ;

            /* Typography - Separate heading and body fonts */
            --brand-font-family:
                {{ $brand['font_family'] }}
            ;
            --brand-font-heading:
                {{ $effectiveFontHeading }}
            ;
            --brand-font-body:
                {{ $effectiveFontBody }}
            ;
            --brand-font-size:
                {{ $brand['font_size_base'] }}
            ;
        }

        /* ===== Modern Button Styles - Solid colors with opacity hover ===== */
        .btn-primary-modern {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.5rem !important;
            border-radius: var(--brand-btn-radius, 6px) !important;
            padding: 0.625rem 1rem !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            background-color: var(--brand-btn-primary, #5F5F82) !important;
            color: #ffffff !important;
            border: none !important;
            transition: opacity 0.15s ease !important;
            cursor: pointer !important;
            text-decoration: none !important;
        }

        .btn-primary-modern:hover {
            background-color: var(--brand-btn-primary, #5F5F82) !important;
            color: #ffffff !important;
            opacity: 0.85 !important;
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
            border-radius: var(--brand-btn-radius, 6px) !important;
            padding: 0.625rem 1rem !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            background-color: var(--brand-btn-secondary, #6c757d) !important;
            color: #ffffff !important;
            border: none !important;
            transition: opacity 0.15s ease !important;
            cursor: pointer !important;
        }

        .btn-secondary-modern:hover {
            background-color: var(--brand-btn-secondary, #6c757d) !important;
            color: #ffffff !important;
            opacity: 0.85 !important;
        }

        /* Dark/Black button */
        .btn-dark-modern {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.5rem !important;
            border-radius: var(--brand-btn-radius, 6px) !important;
            padding: 0.625rem 1rem !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            background-color: var(--brand-btn-dark, #1e293b) !important;
            color: #ffffff !important;
            border: none !important;
            transition: opacity 0.15s ease !important;
            cursor: pointer !important;
        }

        .btn-dark-modern:hover {
            background-color: var(--brand-btn-dark, #1e293b) !important;
            color: #ffffff !important;
            opacity: 0.85 !important;
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

        .row-cards>[class*="col-"] {
            display: flex;
            flex-direction: column;
        }

        .row-cards>[class*="col-"]>.card {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
        }

        .row-cards>[class*="col-"]>.card>.card-body {
            flex: 1 1 auto;
        }

        /* ===== AdminLTE/Bootstrap Brand Overrides - Solid colors with opacity hover ===== */

        /* All buttons - 6px border radius and opacity hover */
        .btn {
            border-radius: var(--brand-btn-radius, 6px) !important;
            transition: opacity 0.15s ease !important;
        }

        .btn:hover {
            opacity: 0.85;
        }

        .btn:active {
            opacity: 0.75;
        }

        /* Primary Button - Solid purple */
        .btn.btn-primary {
            background-color: var(--brand-btn-primary, var(--brand-primary)) !important;
            border-color: var(--brand-btn-primary, var(--brand-primary)) !important;
            color: #ffffff !important;
        }

        .btn.btn-primary:hover,
        .btn.btn-primary:focus,
        .btn.btn-primary:active {
            background-color: var(--brand-btn-primary, var(--brand-primary)) !important;
            border-color: var(--brand-btn-primary, var(--brand-primary)) !important;
            color: #ffffff !important;
            opacity: 0.85;
        }

        /* Secondary Button - Solid gray */
        .btn.btn-secondary {
            background-color: var(--brand-btn-secondary, #6c757d) !important;
            border-color: var(--brand-btn-secondary, #6c757d) !important;
            color: #ffffff !important;
        }

        .btn.btn-secondary:hover,
        .btn.btn-secondary:focus,
        .btn.btn-secondary:active {
            background-color: var(--brand-btn-secondary, #6c757d) !important;
            border-color: var(--brand-btn-secondary, #6c757d) !important;
            color: #ffffff !important;
            opacity: 0.85;
        }

        /* Dark Button - Solid black */
        .btn.btn-dark {
            background-color: var(--brand-btn-dark, #1e293b) !important;
            border-color: var(--brand-btn-dark, #1e293b) !important;
            color: #ffffff !important;
        }

        .btn.btn-dark:hover,
        .btn.btn-dark:focus,
        .btn.btn-dark:active {
            background-color: var(--brand-btn-dark, #1e293b) !important;
            border-color: var(--brand-btn-dark, #1e293b) !important;
            color: #ffffff !important;
            opacity: 0.85;
        }

        /* Success Button */
        .btn.btn-success {
            background-color: var(--brand-success, #22c55e) !important;
            border-color: var(--brand-success, #22c55e) !important;
            color: #ffffff !important;
        }

        .btn.btn-success:hover,
        .btn.btn-success:focus,
        .btn.btn-success:active {
            background-color: var(--brand-success, #22c55e) !important;
            border-color: var(--brand-success, #22c55e) !important;
            color: #ffffff !important;
            opacity: 0.85;
        }

        /* Danger Button - For delete actions */
        .btn.btn-danger {
            background-color: var(--brand-danger, #ef4444) !important;
            border-color: var(--brand-danger, #ef4444) !important;
            color: #ffffff !important;
        }

        .btn.btn-danger:hover,
        .btn.btn-danger:focus,
        .btn.btn-danger:active {
            background-color: var(--brand-danger, #ef4444) !important;
            border-color: var(--brand-danger, #ef4444) !important;
            color: #ffffff !important;
            opacity: 0.85;
        }

        /* Warning Button */
        .btn.btn-warning {
            background-color: var(--brand-warning, #f59e0b) !important;
            border-color: var(--brand-warning, #f59e0b) !important;
            color: #ffffff !important;
        }

        .btn.btn-warning:hover,
        .btn.btn-warning:focus,
        .btn.btn-warning:active {
            background-color: var(--brand-warning, #f59e0b) !important;
            border-color: var(--brand-warning, #f59e0b) !important;
            color: #ffffff !important;
            opacity: 0.85;
        }

        /* Info Button */
        .btn.btn-info {
            background-color: var(--brand-info, var(--brand-primary)) !important;
            border-color: var(--brand-info, var(--brand-primary)) !important;
            color: #ffffff !important;
        }

        .btn.btn-info:hover,
        .btn.btn-info:focus,
        .btn.btn-info:active {
            background-color: var(--brand-info, var(--brand-primary)) !important;
            border-color: var(--brand-info, var(--brand-primary)) !important;
            color: #ffffff !important;
            opacity: 0.85;
        }

        /* Outline Primary */
        .btn-outline-primary {
            color: var(--brand-primary) !important;
            border-color: var(--brand-primary) !important;
            background-color: transparent !important;
        }

        .btn-outline-primary:hover,
        .btn-outline-primary:focus {
            background-color: var(--brand-primary) !important;
            border-color: var(--brand-primary) !important;
            color: #fff !important;
            opacity: 0.85;
        }

        /* Outline Secondary */
        .btn-outline-secondary {
            color: var(--brand-btn-secondary, #6c757d) !important;
            border-color: var(--brand-btn-secondary, #6c757d) !important;
            background-color: transparent !important;
        }

        .btn-outline-secondary:hover,
        .btn-outline-secondary:focus {
            background-color: var(--brand-btn-secondary, #6c757d) !important;
            border-color: var(--brand-btn-secondary, #6c757d) !important;
            color: #fff !important;
            opacity: 0.85;
        }

        /* Outline Dark */
        .btn-outline-dark {
            color: var(--brand-btn-dark, #1e293b) !important;
            border-color: var(--brand-btn-dark, #1e293b) !important;
            background-color: transparent !important;
        }

        .btn-outline-dark:hover,
        .btn-outline-dark:focus {
            background-color: var(--brand-btn-dark, #1e293b) !important;
            border-color: var(--brand-btn-dark, #1e293b) !important;
            color: #fff !important;
            opacity: 0.85;
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
            width: var(--brand-sidebar-width) !important;
        }

        .sidebar-dark-primary .nav-sidebar>.nav-item>.nav-link.active,
        .sidebar-light-primary .nav-sidebar>.nav-item>.nav-link.active {
            background-color: var(--brand-sidebar-active) !important;
            color: #fff !important;
        }

        .sidebar-dark-primary .nav-sidebar .nav-link,
        .sidebar-light-primary .nav-sidebar .nav-link {
            color: var(--brand-sidebar-text) !important;
            display: flex !important;
            align-items: center !important;
        }

        .sidebar-dark-primary .nav-sidebar .nav-link:hover,
        .sidebar-light-primary .nav-sidebar .nav-link:hover {
            background-color: var(--brand-sidebar-hover) !important;
            color: #fff !important;
        }

        /* Ensure nav-link text is visible */
        .nav-sidebar .nav-link p {
            margin: 0 !important;
            flex: 1 !important;
            white-space: nowrap !important;
        }

        .sidebar-mini:not(.sidebar-collapse) .nav-sidebar .nav-link p {
            visibility: visible !important;
            opacity: 1 !important;
            display: inline-block !important;
        }

        .brand-link {
            background-color: var(--brand-sidebar-bg) !important;
            border-bottom: 1px solid rgba(255, 255, 255, .1) !important;
        }

        .nav-sidebar .nav-header {
            color: var(--brand-sidebar-text) !important;
            opacity: 0.7;
        }

        /* Content Background - Consistent white spacing */
        .content-wrapper {
            background-color: var(--brand-content-bg, #f8fafc) !important;
        }

        .content-wrapper>.content {
            padding: var(--content-wrapper-padding, 1.5rem) !important;
            background-color: var(--brand-content-bg, #f8fafc) !important;
        }

        /* Typography - Heading and body fonts */
        body {
            font-family: var(--brand-font-body, var(--brand-font-family)) !important;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        .h1,
        .h2,
        .h3,
        .h4,
        .h5,
        .h6 {
            font-family: var(--brand-font-heading, var(--brand-font-family)) !important;
        }

        .card-title {
            font-family: var(--brand-font-heading, var(--brand-font-family)) !important;
        }

        /* Cards - 6px border radius */
        .card {
            border-radius: var(--brand-card-radius, 6px) !important;
        }

        .card-header:first-child {
            border-radius: calc(var(--brand-card-radius, 6px) - 1px) calc(var(--brand-card-radius, 6px) - 1px) 0 0 !important;
        }

        .card-footer:last-child {
            border-radius: 0 0 calc(var(--brand-card-radius, 6px) - 1px) calc(var(--brand-card-radius, 6px) - 1px) !important;
        }

        .card-outline.card-primary {
            border-top-color: var(--brand-primary) !important;
        }

        /* Form Controls - 6px border radius */
        .form-control {
            border-radius: var(--brand-input-radius, 6px) !important;
            font-family: var(--brand-font-body, var(--brand-font-family)) !important;
        }

        .form-control:focus {
            border-color: var(--brand-primary) !important;
            box-shadow: 0 0 0 0.2rem rgba({{ 
        hexdec(substr($brand['color_primary'], 1, 2)) }}
                    ,
                    {{ 
        hexdec(substr($brand['color_primary'], 3, 2)) }}
                    , {{ 
        hexdec(substr($brand['color_primary'], 5, 2)) }}, 0.25) !important;

        }
.cus        tom-control-input:checked ~ .custom-control-label::before {
            background-color: var(--brand-primary) !important;
            border-color: var(--brand-primary) !important;
}
        
        /* Select and input group elements */
        .form-select,
sele        ct.form-control {
            border-radius: var(--brand-input-radius, 6px) !important;
}
        
        /* Alerts, badges, modals - 6px border radius */
.ale        rt {
            border-radius: var(--brand-radius, 6px) !important;
}
        
.bad        ge {
            border-radius: var(--brand-radius-sm, 4px) !important;
}
        
        .modal-content {
            border-radius: var(--brand-radius-lg, 8px) !important;
}
        
        /* Info boxes */
        .info-box {
            border-radius: var(--brand-radius, 6px) !important;
}
        
        .info-box-icon {
            border-radius: var(--brand-radius, 6px) !important;
}
        
        /* Progress bars */
        .progress {
            border-radius: var(--brand-radius, 6px) !important;
}
        
        /* Pagination */
        .pagination .page-link {
            border-radius: var(--brand-radius, 6px) !important;
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
        
/* ========================================
   Responsive Tables with Cell Spacing
           ======================================== */
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-family: var(--brand-font-body, var(--brand-font-family)) !important;
}
        
        .table thead th {
            background-color: var(--brand-content-bg, #f8fafc);
            font-family: var(--brand-font-heading, var(--brand-font-family)) !important;
            font-weight: 600;
            padding: 12px 16px;
            white-space: nowrap;
            vertical-align: middle;
}
        
        .table tbody td {
            padding: 14px 16px;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: 300px;
}
        
        /* Responsive table wrapper */
        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: var(--brand-radius, 6px);
}
        
        .table-responsive > .table {
            margin-bottom: 0;
}
        
        /* Ensure table cells don't overflow */
        .table td,
.tab        le th {
            overflow: hidden;
            text-overflow: ellipsis;
}
        
        /* Table inside cards */
.car        d .table {
            margin-bottom: 0;
}
        
        /* Actions column - prevent wrapping */
        .table td.actions,
        .table th.actions,
        .table td:last-child .btn-group,
.tab        le td:last-child .d-flex {
            white-space: nowrap;
}
        
        /* Mobile responsive table adjustments */

@med        ia (max-width: 768px) {
            .table thead th,
    .tab        le tbody td {
                padding: 10px 12px;
                font-size: 13px;
            }
}
        
/* ========================================
   Dark Theme Support
           ======================================== */
[dat        a-theme="dark"] body {
            background-color: #0f172a !important;
            color: #e2e8f0 !important;
}
        
[dat        a-theme="dark"] .content-wrapper {
            background-color: #0f172a !important;
}
        
[dat        a-theme="dark"] .card {
            background-color: #1e293b !important;
            border-color: #334155 !important;
            color: #e2e8f0 !important;
}
        
[dat        a-theme="dark"] .card-header {
            background-color: #1e293b !important;
            border-color: #334155 !important;
            color: #f1f5f9 !important;
}
        
[dat        a-theme="dark"] .card-footer {
            background-color: #1e293b !important;
            border-color: #334155 !important;
}
        
[dat        a-theme="dark"] .main-header.navbar {
            background-color: #1e293b !important;
            border-color: #334155 !important;
}
        
[dat        a-theme="dark"] .main-header.navbar .nav-link {
            color: #e2e8f0 !important;
}
        
        [data-theme="dark"] .form-control,
[dat        a-theme="dark"] .form-select {
            background-color: #1e293b !important;
            border-color: #475569 !important;
            color: #e2e8f0 !important;
}
        
        [data-theme="dark"] .form-control:focus,
[dat        a-theme="dark"] .form-select:focus {
            background-color: #1e293b !important;
            border-color: var(--brand-primary) !important;
            color: #f1f5f9 !important;
}
        
[dat        a-theme="dark"] .form-control::placeholder {
            color: #94a3b8 !important;
}
        
[dat        a-theme="dark"] .table {
            color: #e2e8f0 !important;
}
        
        [data-theme="dark"] .table th,
[dat        a-theme="dark"] .table td {
            border-color: #334155 !important;
}
        
[dat        a-theme="dark"] .table-hover tbody tr:hover {
            background-color: #334155 !important;
}
        
[dat        a-theme="dark"] .nav-tabs .nav-link {
            color: #94a3b8 !important;
}
        
[dat        a-theme="dark"] .nav-tabs .nav-link.active {
            background-color: #1e293b !important;
            border-color: #334155 #334155 #1e293b !important;
            color: #f1f5f9 !important;
}
        
[dat        a-theme="dark"] .dropdown-menu {
            background-color: #1e293b !important;
            border-color: #334155 !important;
}
        
[dat        a-theme="dark"] .dropdown-item {
            color: #e2e8f0 !important;
}
        
        [data-theme="dark"] .dropdown-item:hover,
[dat        a-theme="dark"] .dropdown-item:focus {
            background-color: #334155 !important;
            color: #f1f5f9 !important;
}
        
[dat        a-theme="dark"] .text-muted {
            color: #94a3b8 !important;
}
        
[dat        a-theme="dark"] .border {
            border-color: #334155 !important;
}
        
[dat        a-theme="dark"] .list-group-item {
            background-color: #1e293b !important;
            border-color: #334155 !important;
            color: #e2e8f0 !important;
}
        
[dat        a-theme="dark"] .modal-content {
            background-color: #1e293b !important;
            border-color: #334155 !important;
            color: #e2e8f0 !important;
}
        
        [data-theme="dark"] .modal-header,
[dat        a-theme="dark"] .modal-footer {
            border-color: #334155 !important;
}
        
        [data-theme="dark"] .btn-outline-secondary {
            color: #94a3b8 !important;
            border-color: #475569 !important;
}
        
        [data-theme="dark"] .btn-outline-secondary:hover {
            background-color: #334155 !important;
            border-color: #64748b !important;
            color: #f1f5f9 !important;
}
        
        [data-theme="dark"] .btn-theme-toggle,
        [data-theme="dark"] .btn-density-toggle {
            background-color: #334155 !important;
            border-color: #475569 !important;
            color: #e2e8f0 !important;
}
        
        [data-theme="dark"] .btn-theme-toggle:hover,
        [data-theme="dark"] .btn-density-toggle:hover {
            background-color: #475569 !important;
            border-color: #64748b !important;
            color: #f1f5f9 !important;
}
        
        [data-theme="dark"] .page-link {
            background-color: #1e293b !important;
            border-color: #334155 !important;
            color: #e2e8f0 !important;
}
        
        [data-theme="dark"] .page-item.active .page-link {
            background-color: var(--brand-primary) !important;
            border-color: var(--brand-primary) !important;
}
        

       
               [data-theme="dark"] h1,
        [data-theme="dark"] h2,
        [data-theme="dark"] h3,
        [data-theme="dark"] h4, 
       [data-theme="dark"] h5, [
       data-theme="dark"] h6,
        [data-theme="dark"] .h1,
        [data-theme="dark"] .h2,
        [data-theme="dark"] .h3,
        [data-theme="dark"] .h4, [data-theme="dark"] .h5, [data-theme="dark"] .h6 {
            color: #f1f5f9 !important;
}
        
        [data-theme="dark"] .form-label {
            color: #e2e8f0 !important;
}
        
        [data-theme="dark"] .alert {
            border-color: #334155 !important;
}
        
        [data-theme="dark"] .alert-success {
            background-color: rgba(34, 197, 94, 0.15) !important;
            color: #4ade80 !important;
}
        
        [data-theme="dark"] .alert-danger {
            background-color: rgba(239, 68, 68, 0.15) !important;
            color: #f87171 !important;
}
        
        [data-theme="dark"] .alert-warning {
            background-color: rgba(245, 158, 11, 0.15) !important;
            color: #fbbf24 !important;
}
        
        [data-theme="dark"] .alert-info {
            background-color: rgba(59, 130, 246, 0.15) !important;
            color: #60a5fa !important;
}
        
/* ========================================
   Density/Padding Variations
   ======================================== */
        
        /* Compact density */
        [data-density="compact"] .content-wrapper > .content {
            padding: 0.75rem 1rem !important;
}
        
        [data-density="compact"] .card-body {
            padding: 0.75rem !important;
}
        
        [data-density="compact"] .card-header {
            padding: 0.5rem 0.75rem !important;
}
        
        [data-density="compact"] .form-control,
        [data-density="compact"] .form-select {
            padding: 0.25rem 0.5rem !important;
            font-size: 0.875rem !important;
}
        
        [data-density="compact"] .btn {
            padding: 0.25rem 0.75rem !important;
            font-size: 0.875rem !important;
}
        
        [data-density="compact"] .table td,
        [data-density="compact"] .table th {
            padding: 0.375rem 0.5rem !important;
}
        
        /* Extreme compact density */
        [data-density="extreme"] .content-wrapper > .content {
            padding: 0.5rem 0.75rem !important;
}
        
        [data-density="extreme"] .card-body {
            padding: 0.5rem !important;
}
        
        [data-density="extreme"] .card-header {
            padding: 0.375rem 0.5rem !important;
}
        
        [data-density="extreme"] .form-control,
        [data-density="extreme"] .form-select {
            padding: 0.125rem 0.375rem !important;
            font-size: 0.8125rem !important;
}
        
        [data-density="extreme"] .btn {
            padding: 0.125rem 0.5rem !important;
            font-size: 0.8125rem !important;
}
        
        [data-density="extreme"] .table td,
        [data-density="extreme"] .table th {
            padding: 0.25rem 0.375rem !important;
            font-size: 0.8125rem !important;
}
        
        @if(!empty($brand['custom_css']))
            /* Custom CSS */
            {!! \App\Helpers\HtmlSanitizer::sanitizeAdmin($brand['custom_css']) !!}
        @endif
</style>
