@php
    $brandingService = app(\App\Services\BrandingService::class);
    $brand = $brandingService->all();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $brand['company_name'] ?? config('app.name') }} - Client Portal</title>

    @if(!empty($brand['favicon_path']))
        <link rel="icon" href="{{ asset('storage/' . $brand['favicon_path']) }}">
    @endif

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Vite Assets (Tailwind CSS) -->
    @if(!app()->runningUnitTests())
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <!-- Custom Brand Styles (Tailwind Extension) -->
    <link rel="stylesheet" href="{{ asset('css/brand-tailwind.css') }}">

    <!-- Dynamic Brand Styles (CSS Variables) -->
    @include('layouts.partials.brand-styles-tailwind')

    <!-- Page-specific background style -->
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            @if(!empty($brand['login_background_path']))
                background: linear-gradient(135deg, rgba(30, 41, 59, 0.9), rgba(51, 65, 85, 0.9)),
                            url('{{ asset('storage/' . $brand['login_background_path']) }}') no-repeat center center;
                background-size: cover;
            @else
                background: linear-gradient(135deg, {{ $brand['color_primary'] ?? '#1e293b' }} 0%, {{ $brand['sidebar_bg'] ?? '#334155' }} 100%);
            @endif
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center antialiased">
    <div class="w-full max-w-md px-4">
        {{ $slot }}
    </div>

    <!-- Alpine.js for interactivity -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
