@props(['loading' => false, 'loadingText' => 'Processing...', 'wireTarget' => 'submit', 'variant' => 'primary', 'size' => 'md'])

<x-button 
    {{ $attributes }}
    :variant="$variant"
    :size="$size"
    :loading="$loading"
    :loadingText="$loadingText"
    :wireTarget="$wireTarget"
>
    {{ $slot }}
</x-button>
