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

    <!-- Dynamic Brand Styles (CSS Variables Only) -->
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
        <div id="offline-indicator" x-data="{ show: false }" x-show="show" style="display: none;"
            class="fixed top-0 left-0 right-0 z-50 bg-amber-50 border-b border-amber-200 text-amber-800 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-wifi"></i>
                <span>You're offline. Some actions will be queued.</span>
            </div>
            <button @click="show = false" class="text-amber-800 hover:text-amber-900">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- PWA install prompt -->
        <div id="pwa-install-banner" x-data="{ show: false }" x-show="show" style="display: none;"
            class="fixed top-0 left-0 right-0 z-50 bg-blue-50 border-b border-blue-200 px-4 py-3">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <strong class="text-blue-900">Install the portal</strong>
                    <div class="text-sm text-blue-700">Get an app-like experience and offline support.</div>
                </div>
                <div class="flex gap-2">
                    <button id="pwa-install-btn" type="button" class="btn-brand-primary px-3 py-1.5 text-sm">Install</button>
                    <button id="pwa-install-dismiss" type="button" @click="show = false"
                        class="px-3 py-1.5 text-sm border border-slate-300 bg-transparent text-slate-700 hover:bg-slate-50 rounded-lg">
                        Not now
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Button -->
        <div x-data="{ sidebarOpen: false }"
            class="lg:hidden fixed top-0 left-0 right-0 z-50 bg-white border-b border-slate-200 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = !sidebarOpen" class="text-slate-600 hover:text-slate-900">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    @if(config('branding.logo.header'))
                        <img src="/{{ config('branding.logo.header') }}" alt="Logo" class="h-8">
                    @else
                        <span class="text-lg font-bold text-slate-900">{{ config('branding.company.name') }}</span>
                    @endif
                </a>
            </div>
            <div class="flex items-center gap-2">
                <!-- User Menu -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="flex items-center gap-2 text-sm">
                        <span class="hidden sm:block text-slate-700">{{ auth()->user()->name }}</span>
                        <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center">
                            <i class="fas fa-user text-slate-600 text-sm"></i>
                        </div>
                    </button>
                    <div x-show="open" @click.away="open = false"
                        class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-slate-200 py-1">
                        <a href="{{ route('profile.edit') }}"
                            class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                            <i class="fas fa-user-circle mr-2"></i> Profile
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div x-data="{ sidebarOpen: false }" class="flex">
            <!-- Sidebar -->
            @include('layouts.partials.sidebar')

            <!-- Overlay for mobile -->
            <div x-show="sidebarOpen" @click="sidebarOpen = false"
                class="fixed inset-0 bg-slate-900 bg-opacity-50 z-30 lg:hidden"></div>

            <!-- Main Content -->
            <main class="flex-1 lg:ml-0 pt-16 lg:pt-0">
                <!-- Top Bar (Desktop) -->
                @include('layouts.partials.navbar')

                <!-- Page Content -->
                <div class="p-6">
                    <!-- Page Header -->
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
                        <div x-data="{ show: true }" x-show="show"
                            class="alert alert-success mb-4 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check-circle"></i>
                                <span>{{ session('success') }}</span>
                            </div>
                            <button @click="show = false" class="alert-close">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div x-data="{ show: true }" x-show="show"
                            class="alert alert-danger mb-4 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-exclamation-circle"></i>
                                <span>{{ session('error') }}</span>
                            </div>
                            <button @click="show = false" class="alert-close">
                                <i class="fas fa-times"></i>
                            </button>
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

    <!-- Alpine.js for dropdowns -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Site footer HTML (branding setting from database) --}}
    {!! \App\Helpers\HtmlSanitizer::sanitizeClient(app(\App\Services\BrandingService::class)->get('site_footer_html')) !!}
</body>

</html>
