@props([
    'heading' => null,
    'subheading' => null,
    'icon' => null,
])

@php
    $hasTitleSlot = trim($title ?? '') !== '';
    $hasRightSlot = trim($right ?? '') !== '';
    $titleColumn = $hasRightSlot ? 'col-sm-6' : 'col-12';
    $rightColumn = $hasRightSlot ? 'col-sm-6' : 'd-none';
@endphp

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="{{ $titleColumn }}">
                @if($hasTitleSlot)
                    {{ $title }}
                @else
                    <h1 class="m-0 d-flex align-items-center">
                        @if($icon)
                            <i class="{{ $icon }} text-primary mr-2"></i>
                        @endif
                        <span>{{ $heading }}</span>
                    </h1>
                @endif
                @if($subheading)
                    <p class="text-muted mt-2 mb-0">{{ $subheading }}</p>
                @endif
            </div>
            @if($hasRightSlot)
                <div class="{{ $rightColumn }}">
                    {{ $right }}
                </div>
            @endif
        </div>
    </div>
</div>
