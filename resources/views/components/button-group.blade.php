@props([
    'orientation' => 'horizontal', // horizontal, vertical
    'spacing' => 'md', // sm, md, lg
    'align' => 'start', // start, center, end, between, around
    'wrap' => false
])

@php
$orientationClasses = [
    'horizontal' => 'flex-row',
    'vertical' => 'flex-col'
];

$spacingClasses = [
    'sm' => $orientation === 'horizontal' ? 'gap-2' : 'gap-2',
    'md' => $orientation === 'horizontal' ? 'gap-3' : 'gap-3', 
    'lg' => $orientation === 'horizontal' ? 'gap-4' : 'gap-4'
];

$alignClasses = [
    'start' => 'justify-start',
    'center' => 'justify-center',
    'end' => 'justify-end',
    'between' => 'justify-between',
    'around' => 'justify-around'
];

$wrapClass = $wrap ? 'flex-wrap' : '';

$classes = collect([
    'flex',
    $orientationClasses[$orientation] ?? $orientationClasses['horizontal'],
    $spacingClasses[$spacing] ?? $spacingClasses['md'],
    $alignClasses[$align] ?? $alignClasses['start'],
    $wrapClass
])->filter()->implode(' ');
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>