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
    @if(config('branding.typography.google_fonts'))
        <link href="https://fonts.googleapis.com/css2?family={{ config('branding.typography.google_fonts') }}&display=swap"
            rel="stylesheet">
    @endif

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Vite Assets -->
    @if(!app()->runningUnitTests())
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <!-- Livewire Styles -->
    @livewireStyles

    <!-- Dynamic Brand Styles -->
    @include('layouts.partials.brand-styles')
</head>

<body class="bg-slate-50 font-sans antialiased">
    <div class="min-h-screen">
        <!-- Mobile Menu Button -->
        <div
            class="lg:hidden fixed top-0 left-0 right-0 z-50 bg-white border-b border-slate-200 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = !sidebarOpen" class="text-slate-600 hover:text-slate-900">
                    <i class="fas fa-bars text-xl"></i>
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
            <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                class="fixed lg:static inset-y-0 left-0 z-40 w-64 bg-slate-900 transform transition-transform duration-200 ease-in-out lg:translate-x-0 pt-16 lg:pt-0">

                <!-- Logo -->
                <div class="hidden lg:flex items-center justify-center h-16 border-b border-slate-800 px-4">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                        @if(config('branding.logo.header'))
                            <img src="/{{ config('branding.logo.header') }}" alt="Logo" class="h-8">
                        @else
                            <span class="text-lg font-bold text-white">{{ config('branding.company.name') }}</span>
                        @endif
                    </a>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                    <!-- Dashboard -->
                    <a href="{{ route('admin.dashboard') }}"
                        class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <i class="fas fa-home w-5"></i>
                        <span>Dashboard</span>
                    </a>

                    <!-- Services Section -->
                    <div class="pt-4 pb-2">
                        <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Services</p>
                    </div>

                    <a href="{{ route('admin.requests.index') }}"
                        class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.requests.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <i class="fas fa-tasks w-5"></i>
                        <span>Service Requests</span>
                    </a>

                    <a href="{{ route('admin.support-tickets.index') }}"
                        class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.support-tickets.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <i class="fas fa-life-ring w-5"></i>
                        <span>Support Tickets</span>
                    </a>

                    <a href="{{ route('admin.contracts.index') }}"
                        class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.contracts.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <i class="fas fa-file-contract w-5"></i>
                        <span>Contracts</span>
                    </a>

                    <a href="{{ route('admin.invoices.index') }}"
                        class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.invoices.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <i class="fas fa-file-invoice-dollar w-5"></i>
                        <span>Invoices & Payments</span>
                    </a>

                    <a href="{{ route('admin.documents.index') }}"
                        class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.documents.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <i class="fas fa-folder w-5"></i>
                        <span>Documents</span>
                    </a>

                    <!-- Admin Section -->
                    <div class="pt-4 pb-2">
                        <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Admin</p>
                    </div>

                    <a href="{{ route('admin.clients.index') }}"
                        class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.clients.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <i class="fas fa-users w-5"></i>
                        <span>Clients</span>
                    </a>

                    <a href="{{ route('admin.users.index') }}"
                        class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.users.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <i class="fas fa-user-friends w-5"></i>
                        <span>Users</span>
                    </a>

                    <a href="{{ route('admin.messages') }}"
                        class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.messages') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <i class="fas fa-comments w-5"></i>
                        <span>Messages</span>
                    </a>

                    <a href="{{ route('admin.reports') }}"
                        class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.reports*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <i class="fas fa-chart-line w-5"></i>
                        <span>Reporting</span>
                    </a>
                </nav>
            </aside>

            <!-- Overlay for mobile -->
            <div x-show="sidebarOpen" @click="sidebarOpen = false"
                class="fixed inset-0 bg-slate-900 bg-opacity-50 z-30 lg:hidden"></div>

            <!-- Main Content -->
            <main class="flex-1 lg:ml-0 pt-16 lg:pt-0">
                <!-- Top Bar (Desktop) -->
                <div class="hidden lg:flex items-center justify-between h-16 bg-white border-b border-slate-200 px-6">
                    <div>
                        <h1 class="text-xl font-semibold text-slate-900">@yield('title', 'Admin Dashboard')</h1>
                    </div>
                    <div class="flex items-center gap-4">
                        <!-- User Menu -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="flex items-center gap-2 text-sm">
                                <span class="text-slate-700">{{ auth()->user()->name }}</span>
                                <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center">
                                    <i class="fas fa-user text-slate-600 text-sm"></i>
                                </div>
                            </button>
                            <div x-show="open" @click.away="open = false"
                                class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-slate-200 py-1 z-50">
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

                <!-- Page Content -->
                <div class="p-6">
                    @yield('content')
                </div>

                <!-- Footer -->
                <footer class="bg-white border-t border-slate-200 py-4 px-6 mt-8">
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-2 text-sm text-slate-600">
                        <div>
                            © {{ date('Y') }} <a href="{{ config('branding.company.website') }}"
                                class="text-blue-600 hover:text-blue-700">{{ config('branding.company.name') }}</a>. All
                            rights reserved.
                        </div>
                        <div>
                            Version {{ config('app.version', '1.0.0') }}
                        </div>
                    </div>
                </footer>
            </main>
        </div>
    </div>

    <!-- Livewire Scripts -->
    @livewireScripts

    @stack('scripts')

    <!-- Alpine.js for dropdowns -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>

</html>