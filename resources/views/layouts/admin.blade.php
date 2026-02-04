<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ ($title ?? 'Admin') . ' · ' . config('branding.company.name') }}</title>

    <meta name="theme-color" content="{{ config('branding.colors.primary') }}">
    <link rel="icon" href="/{{ config('branding.logo.favicon') }}">
    <link rel="apple-touch-icon" href="/{{ config('branding.logo.icon') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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

    <!-- Chart.js for Dashboard Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

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
</head>

<body class="bg-slate-50 font-sans antialiased">
    <!-- Skip to Main Content Link -->
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-[60]
              focus:px-4 focus:py-2 focus:bg-indigo-600 focus:text-white focus:rounded-lg
              focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
        Skip to main content
    </a>

    <div class="h-screen flex flex-col overflow-hidden">
        <!-- Mobile Menu Button -->
        <div
            class="lg:hidden fixed top-0 left-0 right-0 z-50 bg-white border-b border-slate-200 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = !sidebarOpen"
                        class="text-slate-600 hover:text-slate-900"
                        aria-label="Toggle admin navigation menu"
                        aria-expanded="false"
                        x-bind:aria-expanded="sidebarOpen.toString()">
                    <x-icon name="bars" class="w-6 h-6" />
                </button>
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
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
                    <button @click="open = !open"
                            class="flex items-center gap-2 text-sm"
                            aria-label="User menu"
                            aria-expanded="false"
                            x-bind:aria-expanded="open.toString()">
                        <span class="hidden sm:block text-slate-700">{{ auth()->user()->name }}</span>
                        <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center">
                            <x-icon name="user" class="w-4 h-4 text-slate-600" />
                        </div>
                    </button>
                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         @click.away="open = false"
                        class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-slate-200 py-1">
                        <a href="{{ route('profile.edit') }}"
                            class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                            <x-icon name="user-circle" class="w-4 h-4" />
                            <span>Profile</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="w-full flex items-center gap-2 text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                <x-icon name="logout" class="w-4 h-4" />
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div x-data="{ sidebarOpen: false }" class="flex flex-1 overflow-hidden">
            <!-- Sidebar -->
            @include('layouts.partials.sidebar-tailwind')

            <!-- Main Content -->
            <div class="flex-1 flex flex-col overflow-hidden lg:ml-0">
                <!-- Top Navigation (Desktop) -->
                <header class="hidden lg:block bg-white border-b border-slate-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            @if(isset($header))
                                <h1 class="text-2xl font-bold text-slate-900">{{ $header }}</h1>
                            @endif
                        </div>
                        <div class="flex items-center gap-4">
                            <!-- Theme Toggle -->
                            <button onclick="window.__toggleTheme && window.__toggleTheme()"
                                class="flex items-center gap-2 px-3 py-2 text-sm text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors"
                                aria-label="Toggle light and dark theme">
                                <x-icon name="adjust" class="w-4 h-4" />
                                <span class="hidden xl:inline">Theme</span>
                            </button>

                            <!-- Notifications -->
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open"
                                    class="relative px-3 py-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors"
                                    aria-label="View admin notifications"
                                    aria-expanded="false"
                                    x-bind:aria-expanded="open.toString()">
                                    <x-icon name="bell" class="w-5 h-5" />
                                </button>
                                <div x-show="open"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-150"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95"
                                     @click.away="open = false"
                                    class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-slate-200 py-2">
                                    <div class="px-4 py-2 border-b border-slate-200">
                                        <h3 class="font-semibold text-slate-900">Notifications</h3>
                                    </div>
                                    <div class="px-4 py-3 text-sm text-slate-500">
                                        No new notifications
                                    </div>
                                </div>
                            </div>

                            <!-- User Menu -->
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open"
                                    class="flex items-center gap-3 px-3 py-2 hover:bg-slate-100 rounded-lg transition-colors"
                                    aria-label="Admin user menu"
                                    aria-expanded="false"
                                    x-bind:aria-expanded="open.toString()">
                                    <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center">
                                        <x-icon name="user" class="w-4 h-4 text-slate-600" />
                                    </div>
                                    <span class="text-sm font-medium text-slate-700">{{ auth()->user()->name }}</span>
                                    <x-icon name="chevron-down" class="w-3 h-3 text-slate-400" />
                                </button>
                                <div x-show="open"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-150"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95"
                                     @click.away="open = false"
                                    class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-slate-200 py-1">
                                    <a href="{{ route('profile.edit') }}"
                                        class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                        <x-icon name="user-circle" class="w-4 h-4" />
                                        <span>Profile</span>
                                    </a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit"
                                            class="w-full flex items-center gap-2 text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                            <x-icon name="logout" class="w-4 h-4" />
                                            <span>Logout</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main id="main-content" class="flex-1 overflow-y-auto p-6 lg:p-8" tabindex="-1">
                    @yield('content')
                    {{ $slot ?? '' }}
                </main>

                <!-- Footer -->
                <footer class="bg-white border-t border-slate-200 px-6 py-4">
                    <div class="flex items-center justify-between text-sm text-slate-600">
                        <div>
                            <strong>&copy; {{ date('Y') }} <a href="{{ config('branding.company.website') }}"
                                    class="text-blue-600 hover:text-blue-700">{{ config('branding.company.name') }}</a>.</strong>
                            All rights reserved.
                        </div>
                        <div>
                            <b>Version</b> 1.0.0
                        </div>
                    </div>
                </footer>
            </div>
        </div>
    </div>

    <!-- Livewire Scripts -->
    @livewireScripts

    <!-- Theme Toggle Script -->
    <script>
        (function () {
            window.__toggleTheme = function () {
                const current = document.documentElement.getAttribute('data-theme') || 'light';
                const next = current === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', next);
                localStorage.setItem('theme', next);
            };
        })();
    </script>

    @stack('scripts')
</body>

</html>