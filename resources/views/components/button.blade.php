@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'loading' => false,
    'loadingText' => 'Loading...',
    'disabled' => false,
    'icon' => null,
    'iconPosition' => 'left',
    'fullWidth' => false,
    'wireTarget' => null
])

@php
// Base classes for all buttons
$baseClasses = 'inline-flex items-center justify-center font-medium transition-all duration-200 ease-out focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

// Size variants
$sizeClasses = [
    'xs' => 'px-2.5 py-1.5 text-xs rounded-md gap-1',
    'sm' => 'px-3 py-2 text-sm rounded-md gap-1.5',
    'md' => 'px-4 py-2.5 text-sm rounded-lg gap-2',
    'lg' => 'px-6 py-3 text-base rounded-lg gap-2',
    'xl' => 'px-8 py-4 text-lg rounded-xl gap-3'
];

// Variant classes
$variantClasses = [
    'primary' => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500 shadow-sm hover:shadow-md',
    'secondary' => 'bg-slate-600 text-white hover:bg-slate-700 focus:ring-slate-500 shadow-sm hover:shadow-md',
    'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500 shadow-sm hover:shadow-md',
    'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 shadow-sm hover:shadow-md',
    'warning' => 'bg-yellow-500 text-white hover:bg-yellow-600 focus:ring-yellow-500 shadow-sm hover:shadow-md',
    'info' => 'bg-cyan-600 text-white hover:bg-cyan-700 focus:ring-cyan-500 shadow-sm hover:shadow-md',
    'outline' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus:ring-slate-500 shadow-sm hover:shadow-md theme-bg-card theme-text-primary theme-border-primary hover:theme-bg-secondary',
    'outline-primary' => 'border border-blue-300 bg-white text-blue-700 hover:bg-blue-50 focus:ring-blue-500 shadow-sm hover:shadow-md',
    'outline-danger' => 'border border-red-300 bg-white text-red-700 hover:bg-red-50 focus:ring-red-500 shadow-sm hover:shadow-md',
    'ghost' => 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:ring-slate-500 theme-text-secondary hover:theme-text-primary hover:theme-bg-secondary',
    'link' => 'text-blue-600 hover:text-blue-700 underline-offset-4 hover:underline focus:ring-blue-500 p-0'
];

// Full width
$widthClass = $fullWidth ? 'w-full' : '';

// Combine classes
$classes = collect([
    $baseClasses,
    $sizeClasses[$size] ?? $sizeClasses['md'],
    $variantClasses[$variant] ?? $variantClasses['primary'],
    $widthClass
])->filter()->implode(' ');

// Determine if this should be a link or button
$isLink = !is_null($href);
$tag = $isLink ? 'a' : 'button';

// Prepare attributes
$attributes = $attributes->merge([
    'class' => $classes,
    'type' => $isLink ? null : $type,
    'href' => $isLink ? $href : null,
    'disabled' => (!$isLink && ($disabled || $loading)) ? true : null,
]);

// Wire loading attributes
$wireLoadingAttrs = $wireTarget ? [
    'wire:loading.attr' => 'disabled',
    'wire:target' => $wireTarget
] : [];

foreach ($wireLoadingAttrs as $key => $value) {
    $attributes = $attributes->merge([$key => $value]);
}
@endphp

<{{ $tag }} {{ $attributes }}>
    @if($loading && $wireTarget)
        {{-- Loading state --}}
        <span wire:loading.remove wire:target="{{ $wireTarget }}" class="flex items-center {{ $sizeClasses[$size] ? str_contains($sizeClasses[$size], 'gap-1.5') ? 'gap-1.5' : (str_contains($sizeClasses[$size], 'gap-1') ? 'gap-1' : 'gap-2') : 'gap-2' }}">
            @if($icon && $iconPosition === 'left')
                <x-icon name="{{ $icon }}" class="{{ $size === 'xs' ? 'w-3 h-3' : ($size === 'sm' ? 'w-4 h-4' : ($size === 'lg' ? 'w-5 h-5' : ($size === 'xl' ? 'w-6 h-6' : 'w-4 h-4'))) }}" />
            @endif
            {{ $slot }}
            @if($icon && $iconPosition === 'right')
                <x-icon name="{{ $icon }}" class="{{ $size === 'xs' ? 'w-3 h-3' : ($size === 'sm' ? 'w-4 h-4' : ($size === 'lg' ? 'w-5 h-5' : ($size === 'xl' ? 'w-6 h-6' : 'w-4 h-4'))) }}" />
            @endif
        </span>
        
        <span wire:loading wire:target="{{ $wireTarget }}" class="flex items-center {{ $sizeClasses[$size] ? str_contains($sizeClasses[$size], 'gap-1.5') ? 'gap-1.5' : (str_contains($sizeClasses[$size], 'gap-1') ? 'gap-1' : 'gap-2') : 'gap-2' }}">
            <svg class="animate-spin {{ $size === 'xs' ? 'w-3 h-3' : ($size === 'sm' ? 'w-4 h-4' : ($size === 'lg' ? 'w-5 h-5' : ($size === 'xl' ? 'w-6 h-6' : 'w-4 h-4'))) }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ $loadingText }}
        </span>
    @else
        {{-- Normal state --}}
        <span class="flex items-center {{ $sizeClasses[$size] ? str_contains($sizeClasses[$size], 'gap-1.5') ? 'gap-1.5' : (str_contains($sizeClasses[$size], 'gap-1') ? 'gap-1' : 'gap-2') : 'gap-2' }}">
            @if($icon && $iconPosition === 'left')
                <x-icon name="{{ $icon }}" class="{{ $size === 'xs' ? 'w-3 h-3' : ($size === 'sm' ? 'w-4 h-4' : ($size === 'lg' ? 'w-5 h-5' : ($size === 'xl' ? 'w-6 h-6' : 'w-4 h-4'))) }}" />
            @endif
            {{ $slot }}
            @if($icon && $iconPosition === 'right')
                <x-icon name="{{ $icon }}" class="{{ $size === 'xs' ? 'w-3 h-3' : ($size === 'sm' ? 'w-4 h-4' : ($size === 'lg' ? 'w-5 h-5' : ($size === 'xl' ? 'w-6 h-6' : 'w-4 h-4'))) }}" />
            @endif
        </span>
    @endif
</{{ $tag }}>