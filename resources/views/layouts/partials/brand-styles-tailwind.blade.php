{{-- Tailwind-Compatible Brand Variables --}}
@php
    $brandingService = app(\App\Services\BrandingService::class);
    $brand = $brandingService->all();

    // Defaults
    $brand = array_merge([
        'color_primary' => '#5F5F82',
        'color_secondary' => '#BFCEE0',
        'color_accent' => '#000000',
        'sidebar_bg' => '#1e293b',
        'sidebar_text' => '#94a3b8',
    ], $brand);
@endphp

<style>
    :root {
        /* Brand Colors as CSS Variables - Can be used with Tailwind's arbitrary values */
        --brand-primary:
            {{ $brand['color_primary'] ?? '#5F5F82' }}
        ;
        --brand-secondary:
            {{ $brand['color_secondary'] ?? '#BFCEE0' }}
        ;
        --brand-accent:
            {{ $brand['color_accent'] ?? '#000000' }}
        ;
        --brand-sidebar-bg:
            {{ $brand['sidebar_bg'] ?? '#1e293b' }}
        ;
        --brand-sidebar-text:
            {{ $brand['sidebar_text'] ?? '#94a3b8' }}
        ;
    }

    /* Apply brand colors to Tailwind layout */
    .sidebar-brand-bg {
        background-color: var(--brand-sidebar-bg);
    }

    .sidebar-brand-text {
        color: var(--brand-sidebar-text);
    }
</style>