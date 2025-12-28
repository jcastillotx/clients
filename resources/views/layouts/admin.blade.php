<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ ($title ?? 'Admin') . ' · ' . config('app.name') }}</title>

    <!-- Tabler (Bootstrap-based admin UI) -->
    <link rel="stylesheet" href="https://unpkg.com/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Tailwind CSS (shared styles) -->
    @if(!app()->runningUnitTests())
        @vite(['resources/css/app.css'])
    @endif

    <!-- Brand Custom CSS -->
    @if(file_exists(public_path(config('branding.custom_css'))))
    <link rel="stylesheet" href="/{{ config('branding.custom_css') }}?v={{ filemtime(public_path(config('branding.custom_css'))) }}">
    @endif

    @livewireStyles
    @stack('styles')
</head>
@php $user = auth()->user(); @endphp
<body class="layout-fluid">
<script>
    // Dark mode toggle (persists in localStorage)
    (function () {
        const key = 'admin_theme';
        const saved = localStorage.getItem(key);
        if (saved === 'dark' || saved === 'light') {
            document.documentElement.setAttribute('data-bs-theme', saved);
        }
        window.__toggleAdminTheme = function () {
            const current = document.documentElement.getAttribute('data-bs-theme') || 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-bs-theme', next);
            localStorage.setItem(key, next);
        };
    })();
</script>

<div class="page">
    <!-- Sidebar -->
    <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#admin-sidebar" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">
                <span class="fw-bold">{{ config('app.name') }}</span>
                <div class="text-muted small">Admin</div>
            </a>

            <div class="collapse navbar-collapse" id="admin-sidebar">
                <ul class="navbar-nav pt-lg-3">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                            <span class="nav-link-title">Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.clients.*') ? 'active' : '' }}" href="{{ route('admin.clients.index') }}">
                            <span class="nav-link-title">Clients Management</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.requests.*') ? 'active' : '' }}" href="{{ route('admin.requests.index') }}">
                            <span class="nav-link-title">Requests Management</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}" href="{{ route('admin.invoices.index') }}">
                            <span class="nav-link-title">Invoices &amp; Payments</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.contracts') ? 'active' : '' }}" href="{{ route('admin.contracts') }}">
                            <span class="nav-link-title">Contracts Management</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.documents') ? 'active' : '' }}" href="{{ route('admin.documents') }}">
                            <span class="nav-link-title">Documents</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                            <span class="nav-link-title">Users &amp; Permissions</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.storage') ? 'active' : '' }}" href="{{ route('admin.storage') }}">
                            <span class="nav-link-title">Storage Settings</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.reports*') ? 'active' : '' }}" href="{{ route('admin.reports') }}">
                            <span class="nav-link-title">Reports &amp; Analytics</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.automation*') ? 'active' : '' }}" href="{{ route('admin.automation.index') }}">
                            <span class="nav-link-title">Automation</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.settings*') ? 'active' : '' }}" href="{{ route('admin.settings') }}">
                            <span class="nav-link-title">System Settings</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.settings.webhooks') ? 'active' : '' }}" href="{{ route('admin.settings.webhooks') }}">
                            <span class="nav-link-title">Webhooks</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('api.documentation') ? 'active' : '' }}" href="{{ route('api.documentation') }}">
                            <span class="nav-link-title">API Documentation</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.activity') ? 'active' : '' }}" href="{{ route('admin.activity') }}">
                            <span class="nav-link-title">Activity Log</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </aside>

    <div class="page-wrapper">
        <!-- Topbar -->
        <header class="navbar navbar-expand-md d-print-none">
            <div class="container-fluid">
                <div class="navbar-nav flex-row order-md-last">
                    <!-- Dark mode toggle -->
                    <div class="nav-item">
                        <button type="button" class="btn btn-ghost-secondary" onclick="window.__toggleAdminTheme()" aria-label="Toggle dark mode">
                            Dark mode
                        </button>
                    </div>

                    <!-- Notifications -->
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open notifications">
                            <span class="avatar avatar-sm">{{ strtoupper(substr($user?->name ?? 'A', 0, 1)) }}</span>
                            <div class="d-none d-xl-block ps-2">
                                <div class="fw-semibold">{{ $user?->name }}</div>
                                <div class="mt-1 small text-muted">{{ $user?->email }}</div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <a href="{{ route('profile.edit') }}" class="dropdown-item">Profile</a>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('logout') }}" class="px-2">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger w-100">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Quick search -->
                <form class="d-none d-md-flex ms-2" role="search" action="{{ route('admin.reports') }}" method="GET">
                    <input class="form-control" type="search" name="q" placeholder="Quick search…" aria-label="Quick search">
                </form>
            </div>
        </header>

        <div class="page-body">
            <div class="container-fluid">
                @if(session('success'))
                    <div class="alert alert-success" role="status">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
                @endif

                {{ $slot ?? '' }}
                @yield('content')
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

@livewireScripts
@stack('scripts')
</body>
</html>
