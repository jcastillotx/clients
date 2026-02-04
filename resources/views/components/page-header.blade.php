@props([
    'heading' => null,
    'subheading' => null,
    'icon' => null,
])

@php
    $hasTitleSlot = trim($title ?? '') !== '';
    $hasRightSlot = trim($right ?? '') !== '';
@endphp

<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex-1">
            @if($hasTitleSlot)
                {{ $title }}
            @else
                <h1 class="flex items-center text-2xl font-semibold text-slate-900">
                    @if($icon)
                        <i class="{{ $icon }} text-blue-600 mr-3"></i>
                    @endif
                    <span>{{ $heading }}</span>
                </h1>
            @endif
            @if($subheading)
                <p class="text-slate-500 mt-2">{{ $subheading }}</p>
            @endif
        </div>
        @if($hasRightSlot)
            <div class="flex-shrink-0">
                {{ $right }}
            </div>
        @endif
    </div>
</div>
