@props(['type' => 'text', 'count' => 1])

@php
$skeletons = [
    'text' => 'h-4 bg-slate-200 rounded',
    'heading' => 'h-8 bg-slate-200 rounded',
    'card' => 'h-32 bg-slate-200 rounded-lg',
    'avatar' => 'w-12 h-12 bg-slate-200 rounded-full',
    'button' => 'h-10 w-24 bg-slate-200 rounded',
];
$class = $skeletons[$type] ?? $skeletons['text'];
@endphp

<div class="animate-pulse space-y-3">
    @for ($i = 0; $i < $count; $i++)
        <div class="{{ $class }}"></div>
    @endfor
</div>
