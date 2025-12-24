<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name') }}</title>

    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Livewire Styles -->
    @livewireStyles

    @stack('styles')
</head>
<body class="min-h-screen bg-slate-50 text-slate-900" style="font-family: Inter, ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, 'Apple Color Emoji', 'Segoe UI Emoji'">
@php
    $user = auth()->user();
    $clientName = $user?->client?->company_name ?? 'Client Portal';
@endphp

<div
    x-data="{ sidebarOpen: false, userMenuOpen: false }"
    x-on:keydown.escape.window="sidebarOpen = false; userMenuOpen = false"
    class="min-h-screen"
>
    <!-- Mobile overlay -->
    <div
        x-show="sidebarOpen"
        x-transition.opacity
        class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden"
        x-on:click="sidebarOpen = false"
        aria-hidden="true"
        style="display: none;"
    ></div>

    <!-- Sidebar -->
    <aside
        class="fixed inset-y-0 left-0 z-50 w-72 -translate-x-full bg-white shadow-lg ring-1 ring-black/5 transition-transform lg:translate-x-0"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        aria-label="Sidebar navigation"
    >
        <div class="flex h-16 items-center gap-3 border-b border-slate-200 px-4">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <img
                    src="{{ asset('images/logo.png') }}"
                    alt="{{ config('app.name') }}"
                    class="h-9 w-9 rounded-md object-contain"
                    onerror="this.style.display='none'"
                />
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-slate-900">{{ config('app.name') }}</div>
                    <div class="truncate text-xs text-slate-500">{{ $clientName }}</div>
                </div>
            </a>
        </div>

        <nav class="px-3 py-4">
            <div class="space-y-1">
                <a
                    href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}"
                >
                    <span class="h-2 w-2 rounded-full {{ request()->routeIs('dashboard') ? 'bg-white' : 'bg-slate-300' }}"></span>
                    Dashboard
                </a>

                <a
                    href="{{ route('requests.index') }}"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('requests.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}"
                >
                    <span class="h-2 w-2 rounded-full {{ request()->routeIs('requests.*') ? 'bg-white' : 'bg-slate-300' }}"></span>
                    Requests
                </a>

                <a
                    href="{{ route('contracts.index') }}"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('contracts.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}"
                >
                    <span class="h-2 w-2 rounded-full {{ request()->routeIs('contracts.*') ? 'bg-white' : 'bg-slate-300' }}"></span>
                    Contracts
                </a>

                <a
                    href="{{ route('invoices.index') }}"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('invoices.*') || request()->routeIs('payments.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}"
                >
                    <span class="h-2 w-2 rounded-full {{ request()->routeIs('invoices.*') || request()->routeIs('payments.*') ? 'bg-white' : 'bg-slate-300' }}"></span>
                    Invoices
                </a>

                <a
                    href="{{ route('documents.index') }}"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('documents.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}"
                >
                    <span class="h-2 w-2 rounded-full {{ request()->routeIs('documents.*') ? 'bg-white' : 'bg-slate-300' }}"></span>
                    Documents
                </a>
            </div>
        </nav>
    </aside>

    <!-- Main column -->
    <div class="lg:pl-72">
        <!-- Header -->
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/80 backdrop-blur">
            <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-lg p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 lg:hidden"
                        x-on:click="sidebarOpen = true"
                        aria-label="Open sidebar"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <div class="hidden sm:block">
                        <div class="text-sm text-slate-500">Welcome back</div>
                        <div class="text-base font-semibold text-slate-900">{{ $clientName }}</div>
                    </div>
                </div>

                <!-- User dropdown -->
                <div class="relative">
                    <button
                        type="button"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
                        x-on:click="userMenuOpen = !userMenuOpen"
                        aria-haspopup="true"
                        :aria-expanded="userMenuOpen.toString()"
                    >
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-900 text-xs font-bold text-white">
                            {{ $user?->initials ?? 'U' }}
                        </div>
                        <div class="hidden text-left sm:block">
                            <div class="leading-4 text-slate-900">{{ $user?->name }}</div>
                            <div class="text-xs text-slate-500">{{ $user?->email }}</div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div
                        x-show="userMenuOpen"
                        x-transition
                        x-on:click.outside="userMenuOpen = false"
                        class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"
                        style="display: none;"
                    >
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-3 text-sm text-slate-700 hover:bg-slate-50">
                            Profile
                        </a>
                        <div class="h-px bg-slate-200"></div>
                        <form method="POST" action="{{ route('logout') }}" class="px-4 py-3">
                            @csrf
                            <button type="submit" class="w-full rounded-lg bg-slate-900 px-3 py-2 text-left text-sm font-semibold text-white hover:bg-slate-800">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page -->
        <main class="px-4 py-6 sm:px-6 lg:px-8">
            @if(isset($header))
                <div class="mb-6">
                    <h1 class="text-2xl font-semibold text-slate-900">{{ $header }}</h1>
                    @if(isset($breadcrumb))
                        <div class="mt-1 text-sm text-slate-500">{{ $breadcrumb }}</div>
                    @endif
                </div>
            @endif

            <!-- Flash messages -->
            @if(session('success'))
                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-900">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-900">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Support both layouts: component slot + traditional @yield --}}
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>
</div>

<!-- Alpine.js -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<!-- Livewire Scripts -->
@livewireScripts

@stack('scripts')
</body>
</html>
