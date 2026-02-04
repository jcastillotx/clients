<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('branding.company.name') }}</title>

    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="{{ config('branding.colors.primary') }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ config('branding.company.name') }}">
    <link rel="icon" href="/{{ config('branding.logo.favicon') }}">
    <link rel="apple-touch-icon" href="/{{ config('branding.logo.icon') }}">
    <meta name="vapid-public-key" content="{{ config('pwa.vapid_public_key') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @if(config('branding.typography.google_fonts'))
        <link href="https://fonts.googleapis.com/css2?family={{ config('branding.typography.google_fonts') }}&display=swap"
            rel="stylesheet">
    @endif

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Vite Assets (Tailwind CSS) -->
    @if(!app()->runningUnitTests())
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <!-- Custom Brand Styles (Tailwind Extension) -->
    <link rel="stylesheet" href="{{ asset('css/brand-tailwind.css') }}">

    <!-- Livewire Styles -->
    @livewireStyles

    <!-- Minimal Brand Styles (CSS Variables Only) -->
    @include('layouts.partials.brand-styles-tailwind')

    {{-- Apply theme/density before paint --}}
    <script>
        (function () {
            const theme = localStorage.getItem('theme') || 'light';
            const density = localStorage.getItem('density') || 'comfy';
            document.documentElement.setAttribute('data-theme', theme);
            document.documentElement.setAttribute('data-density', density);
        })();
    </script>

    @stack('styles')

    {{-- Site header HTML (branding setting from database) --}}
    @php
        $brandingService = app(\App\Services\BrandingService::class);
    @endphp
    {!! \App\Helpers\HtmlSanitizer::sanitizeClient($brandingService->get('site_header_html')) !!}
</head>

<body class="bg-slate-50 font-sans antialiased">
    <div class="min-h-screen">
        <!-- PWA offline indicator -->
        <div id="offline-indicator" x-data="{ show: false }" x-show="show" class="fixed top-0 left-0 right-0 z-50 mx-2 mt-2">
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-wifi text-amber-600"></i>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm text-amber-800">
                            You're offline. Some actions will be queued.
                        </p>
                    </div>
                    <div class="ml-auto pl-3">
                        <button @click="show = false" class="inline-flex rounded-md bg-amber-50 p-1.5 text-amber-500 hover:bg-amber-100">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- PWA install prompt -->
        <div id="pwa-install-banner" x-data="{ show: false }" x-show="show" class="fixed top-0 left-0 right-0 z-50 mx-2 mt-2">
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-blue-800">Install the portal</p>
                        <p class="text-xs text-blue-600">Get an app-like experience and offline support.</p>
                    </div>
                    <div class="flex gap-2">
                        <button id="pwa-install-btn" type="button" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">Install</button>
                        <button id="pwa-install-dismiss" type="button" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded-md hover:bg-slate-50">Not now</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Button -->
        <div class="lg:hidden fixed top-0 left-0 right-0 z-50 bg-white border-b border-slate-200 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = !sidebarOpen" class="text-slate-600 hover:text-slate-900">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <span class="text-lg font-bold text-slate-900">{{ config('branding.company.name') }}</span>
            </div>
        </div>

        <div x-data="{ sidebarOpen: false }" class="flex">
            <!-- Sidebar -->
            @include('layouts.partials.sidebar')

            <!-- Overlay for mobile -->
            <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-slate-900 bg-opacity-50 z-30 lg:hidden"></div>

            <!-- Main Content -->
            <main class="flex-1 lg:ml-0 pt-16 lg:pt-0">
                <!-- Top Bar -->
                @include('layouts.partials.navbar')

                <!-- Page Content -->
                <div class="p-6">
                    <!-- Content Header -->
                    @if(isset($header))
                        <x-page-header :heading="$header" :subheading="$subheader ?? null">
                            @if(!empty($breadcrumb))
                                <x-slot name="right">
                                    {{ $breadcrumb }}
                                </x-slot>
                            @endif
                        </x-page-header>
                    @endif

                    <!-- Flash Messages -->
                    @if(session('success'))
                        <div x-data="{ show: true }" x-show="show" x-transition class="relative rounded-lg border border-green-200 bg-green-50 p-4 mb-4">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle text-green-600"></i>
                                </div>
                                <div class="ml-3 flex-1">
                                    <p class="text-sm text-green-800">{{ session('success') }}</p>
                                </div>
                                <div class="ml-auto pl-3">
                                    <button @click="show = false" class="inline-flex rounded-md bg-green-50 p-1.5 text-green-500 hover:bg-green-100">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div x-data="{ show: true }" x-show="show" x-transition class="relative rounded-lg border border-red-200 bg-red-50 p-4 mb-4">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-red-600"></i>
                                </div>
                                <div class="ml-3 flex-1">
                                    <p class="text-sm text-red-800">{{ session('error') }}</p>
                                </div>
                                <div class="ml-auto pl-3">
                                    <button @click="show = false" class="inline-flex rounded-md bg-red-50 p-1.5 text-red-500 hover:bg-red-100">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{ $slot }}
                </div>

                <!-- Footer -->
                @include('layouts.partials.footer')
            </main>
        </div>
    </div>

    <!-- Theme/Density toggles -->
    <script>
        (function () {
            window.__toggleTheme = function () {
                const current = document.documentElement.getAttribute('data-theme') || 'light';
                const next = current === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', next);
                localStorage.setItem('theme', next);
            };
            window.__cycleDensity = function () {
                const order = ['comfy', 'compact', 'extreme'];
                const current = document.documentElement.getAttribute('data-density') || 'comfy';
                const idx = Math.max(0, order.indexOf(current));
                const next = order[(idx + 1) % order.length];
                document.documentElement.setAttribute('data-density', next);
                localStorage.setItem('density', next);
            };
        })();
    </script>

    <!-- Livewire Scripts -->
    @livewireScripts

    @stack('scripts')

    <!-- Alpine.js for dropdowns and interactive components -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Site footer HTML (branding setting from database) --}}
    {!! \App\Helpers\HtmlSanitizer::sanitizeClient(app(\App\Services\BrandingService::class)->get('site_footer_html')) !!}
</body>

</html>