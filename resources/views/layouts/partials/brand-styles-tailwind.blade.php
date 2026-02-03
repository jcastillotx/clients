{{-- Tailwind-Compatible Brand Variables --}}
@php
    $brandingService = app(\App\Services\BrandingService::class);
    $brand = $brandingService->all();

    // Defaults - Professional gray/slate theme
    $brand = array_merge([
        // Primary Colors
        'color_primary' => '#5F5F82',
        'color_primary_dark' => '#4A4A66',
        'color_primary_light' => '#E8E8F0',
        'color_secondary' => '#BFCEE0',
        'color_accent' => '#000000',

        // Status Colors
        'color_success' => '#22c55e',
        'color_warning' => '#f59e0b',
        'color_danger' => '#ef4444',
        'color_info' => '#3b82f6',

        // Sidebar
        'sidebar_bg' => '#1e293b',
        'sidebar_text' => '#94a3b8',
        'sidebar_hover' => '#334155',
        'sidebar_active' => '#3b82f6',

        // Buttons
        'button_primary' => '#5F5F82',
        'button_secondary' => '#6c757d',
        'button_dark' => '#1e293b',

        // Typography
        'font_family' => 'Inter, system-ui, -apple-system, sans-serif',
        'font_heading' => '',
        'font_body' => '',

        // Border Radius
        'border_radius' => '0.5rem',
        'border_radius_lg' => '0.75rem',
        'border_radius_sm' => '0.375rem',
    ], $brand);

    // Use heading/body fonts if specified, otherwise use main font
    $fontHeading = !empty($brand['font_heading']) ? $brand['font_heading'] : $brand['font_family'];
    $fontBody = !empty($brand['font_body']) ? $brand['font_body'] : $brand['font_family'];
@endphp

<style>
    :root {
        /* ===== Brand Colors ===== */
        /* Use with Tailwind: bg-[var(--brand-primary)] or text-[var(--brand-primary)] */
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

        /* Status Colors */
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

        /* ===== Sidebar Colors ===== */
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

        /* ===== Button Colors ===== */
        --brand-btn-primary:
            {{ $brand['button_primary'] }}
        ;
        --brand-btn-secondary:
            {{ $brand['button_secondary'] }}
        ;
        --brand-btn-dark:
            {{ $brand['button_dark'] }}
        ;

        /* ===== Typography ===== */
        --brand-font-family:
            {{ $brand['font_family'] }}
        ;
        --brand-font-heading:
            {{ $fontHeading }}
        ;
        --brand-font-body:
            {{ $fontBody }}
        ;

        /* ===== Border Radius ===== */
        --brand-radius:
            {{ $brand['border_radius'] }}
        ;
        --brand-radius-lg:
            {{ $brand['border_radius_lg'] }}
        ;
        --brand-radius-sm:
            {{ $brand['border_radius_sm'] }}
        ;
    }

    /* ===== Apply Brand Styles ===== */

    /* Body font */
    body {
        font-family: var(--brand-font-body);
    }

    /* Headings font */
    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
        font-family: var(--brand-font-heading);
    }

    /* Sidebar - Apply brand colors */
    aside.bg-slate-900 {
        background-color: var(--brand-sidebar-bg) !important;
    }

    aside.bg-slate-900 a {
        color: var(--brand-sidebar-text);
    }

    aside.bg-slate-900 a:hover {
        background-color: var(--brand-sidebar-hover) !important;
    }

    aside.bg-slate-900 a.bg-slate-800 {
        background-color: var(--brand-sidebar-hover) !important;
        border-left-color: var(--brand-sidebar-active) !important;
    }

    /* Primary buttons - Use brand primary color */
    .bg-blue-600 {
        background-color: var(--brand-btn-primary) !important;
    }

    .hover\:bg-blue-700:hover {
        background-color: var(--brand-primary-dark) !important;
    }

    .text-blue-600 {
        color: var(--brand-btn-primary) !important;
    }

    .border-blue-500 {
        border-color: var(--brand-btn-primary) !important;
    }

    .bg-blue-50 {
        background-color: var(--brand-primary-light) !important;
    }

    /* Focus rings */
    .focus\:ring-blue-500:focus {
        --tw-ring-color: var(--brand-btn-primary) !important;
    }

    .focus\:border-blue-500:focus {
        border-color: var(--brand-btn-primary) !important;
    }

    /* Border radius - Apply to common elements */
    .rounded-lg {
        border-radius: var(--brand-radius-lg);
    }

    .rounded-md,
    .rounded {
        border-radius: var(--brand-radius);
    }

    .rounded-sm {
        border-radius: var(--brand-radius-sm);
    }

    /* Custom Tailwind utility classes for brand colors */
    .bg-brand-primary {
        background-color: var(--brand-primary);
    }

    .bg-brand-secondary {
        background-color: var(--brand-secondary);
    }

    .bg-brand-accent {
        background-color: var(--brand-accent);
    }

    .bg-brand-success {
        background-color: var(--brand-success);
    }

    .bg-brand-warning {
        background-color: var(--brand-warning);
    }

    .bg-brand-danger {
        background-color: var(--brand-danger);
    }

    .bg-brand-info {
        background-color: var(--brand-info);
    }

    .text-brand-primary {
        color: var(--brand-primary);
    }

    .text-brand-secondary {
        color: var(--brand-secondary);
    }

    .text-brand-accent {
        color: var(--brand-accent);
    }

    .border-brand-primary {
        border-color: var(--brand-primary);
    }

    .border-brand-secondary {
        border-color: var(--brand-secondary);
    }

    /* Hover variants */
    .hover\:bg-brand-primary:hover {
        background-color: var(--brand-primary);
    }

    .hover\:bg-brand-primary-dark:hover {
        background-color: var(--brand-primary-dark);
    }

    .hover\:text-brand-primary:hover {
        color: var(--brand-primary);
    }
</style>

@if(!empty($brand['custom_css']))
    {{-- Custom CSS from branding settings --}}
    <style>
        {!! $brand['custom_css'] !!}
    </style>
@endif