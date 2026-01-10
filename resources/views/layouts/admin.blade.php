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
    <link href="https://fonts.googleapis.com/css2?family={{ config('branding.typography.google_fonts') }}&display=swap" rel="stylesheet">
    @endif

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <!-- Brand Theme CSS -->
    <link rel="stylesheet" href="{{ asset('css/brand.css') }}">

    <!-- Custom Styles & JavaScript -->
    @if(!app()->runningUnitTests())
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <!-- Livewire Styles -->
    @livewireStyles

    <!-- Dynamic Brand Styles from Database -->
    @include('layouts.partials.brand-styles')

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
    {!! $brandingService->get('site_header_html') !!}
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- Navbar -->
        @include('layouts.partials.navbar')

        <!-- Main Sidebar Container -->
        @include('layouts.partials.sidebar')

<<<<<<< Updated upstream
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <section class="content pt-3">
                <div class="container-fluid">
                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle mr-2"></i>
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
=======
            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">
                <span class="fw-bold">{{ config('app.name') }}</span>
                <div class="text-muted small">Admin Panel</div>
            </a>

            <div class="collapse navbar-collapse" id="admin-sidebar">
                <ul class="navbar-nav pt-lg-3">
                    {{-- DASHBOARD --}}
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            <span class="nav-link-title">Dashboard</span>
                        </a>
                    </li>

                    {{-- CLIENT MANAGEMENT --}}
                    <li class="nav-item dropdown {{ request()->routeIs('admin.clients.*', 'admin.requests.*', 'admin.contracts*', 'admin.invoices.*', 'admin.documents*') ? 'active' : '' }}">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.clients.*', 'admin.requests.*', 'admin.contracts*', 'admin.invoices.*', 'admin.documents*') ? 'show' : '' }}"
                           href="#navbar-client-mgmt"
                           data-bs-toggle="dropdown"
                           data-bs-auto-close="false"
                           role="button"
                           aria-expanded="{{ request()->routeIs('admin.clients.*', 'admin.requests.*', 'admin.contracts*', 'admin.invoices.*', 'admin.documents*') ? 'true' : 'false' }}">
                            <i class="fas fa-users me-2"></i>
                            <span class="nav-link-title">Client Management</span>
                        </a>
                        <div class="dropdown-menu {{ request()->routeIs('admin.clients.*', 'admin.requests.*', 'admin.contracts*', 'admin.invoices.*', 'admin.documents*') ? 'show' : '' }}" id="navbar-client-mgmt">
                            <a class="dropdown-item {{ request()->routeIs('admin.clients.*') ? 'active' : '' }}" href="{{ route('admin.clients.index') }}">
                                <i class="fas fa-building me-2"></i> Clients
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('admin.requests.*') ? 'active' : '' }}" href="{{ route('admin.requests.index') }}">
                                <i class="fas fa-clipboard-list me-2"></i> Service Requests
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('admin.contracts*') ? 'active' : '' }}" href="{{ route('admin.contracts') }}">
                                <i class="fas fa-file-contract me-2"></i> Contracts
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}" href="{{ route('admin.invoices.index') }}">
                                <i class="fas fa-file-invoice-dollar me-2"></i> Invoices & Payments
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('admin.documents*') ? 'active' : '' }}" href="{{ route('admin.documents') }}">
                                <i class="fas fa-folder-open me-2"></i> Documents
                            </a>
                        </div>
                    </li>

                    {{-- USER MANAGEMENT --}}
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                            <i class="fas fa-user-shield me-2"></i>
                            <span class="nav-link-title">Users & Permissions</span>
                        </a>
                    </li>

                    {{-- AI & AUTOMATION --}}
                    <li class="nav-item dropdown {{ request()->routeIs('admin.ai.*', 'admin.automation.*', 'admin.brand-guidelines*') ? 'active' : '' }}">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.ai.*', 'admin.automation.*', 'admin.brand-guidelines*') ? 'show' : '' }}"
                           href="#navbar-ai"
                           data-bs-toggle="dropdown"
                           data-bs-auto-close="false"
                           role="button"
                           aria-expanded="{{ request()->routeIs('admin.ai.*', 'admin.automation.*', 'admin.brand-guidelines*') ? 'true' : 'false' }}">
                            <i class="fas fa-robot me-2"></i>
                            <span class="nav-link-title">AI & Automation</span>
                        </a>
                        <div class="dropdown-menu {{ request()->routeIs('admin.ai.*', 'admin.automation.*', 'admin.brand-guidelines*') ? 'show' : '' }}" id="navbar-ai">
                            <a class="dropdown-item {{ request()->routeIs('admin.ai.assistants*') ? 'active' : '' }}" href="{{ route('admin.ai.assistants') }}">
                                <i class="fas fa-comments me-2"></i> AI Assistants
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('admin.brand-guidelines*') ? 'active' : '' }}" href="{{ route('admin.brand-guidelines') }}">
                                <i class="fas fa-palette me-2"></i> Brand Guidelines (Gemini)
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('admin.automation.*') ? 'active' : '' }}" href="{{ route('admin.automation.index') }}">
                                <i class="fas fa-magic me-2"></i> Automation Workflows
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item {{ request()->routeIs('admin.ai.usage*') ? 'active' : '' }}" href="{{ route('admin.ai.usage') }}">
                                <i class="fas fa-chart-line me-2"></i> AI Usage & Costs
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('admin.ai.providers*') ? 'active' : '' }}" href="{{ route('admin.ai.providers') }}">
                                <i class="fas fa-cog me-2"></i> AI Providers
                            </a>
                        </div>
                    </li>

                    {{-- MARKETING & CONTENT --}}
                    <li class="nav-item dropdown {{ request()->routeIs('admin.marketing.*', 'admin.social.*', 'admin.brand-monitoring.*') ? 'active' : '' }}">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.marketing.*', 'admin.social.*', 'admin.brand-monitoring.*') ? 'show' : '' }}"
                           href="#navbar-marketing"
                           data-bs-toggle="dropdown"
                           data-bs-auto-close="false"
                           role="button"
                           aria-expanded="{{ request()->routeIs('admin.marketing.*', 'admin.social.*', 'admin.brand-monitoring.*') ? 'true' : 'false' }}">
                            <i class="fas fa-bullhorn me-2"></i>
                            <span class="nav-link-title">Marketing & Content</span>
                        </a>
                        <div class="dropdown-menu {{ request()->routeIs('admin.marketing.*', 'admin.social.*', 'admin.brand-monitoring.*') ? 'show' : '' }}" id="navbar-marketing">
                            <a class="dropdown-item {{ request()->routeIs('admin.marketing.website-auditor*') ? 'active' : '' }}" href="{{ route('admin.marketing.website-auditor') }}">
                                <i class="fas fa-search me-2"></i> Website Auditor
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('admin.social.posts*') ? 'active' : '' }}" href="{{ route('admin.social.posts') }}">
                                <i class="fas fa-share-alt me-2"></i> Social Media
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('admin.brand-monitoring.*') ? 'active' : '' }}" href="{{ route('admin.brand-monitoring') }}">
                                <i class="fas fa-eye me-2"></i> Brand Monitoring
                            </a>
                        </div>
                    </li>

                    {{-- REPORTS & ANALYTICS --}}
                    <li class="nav-item dropdown {{ request()->routeIs('admin.reports*', 'admin.analytics.*', 'admin.workload*') ? 'active' : '' }}">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.reports*', 'admin.analytics.*', 'admin.workload*') ? 'show' : '' }}"
                           href="#navbar-analytics"
                           data-bs-toggle="dropdown"
                           data-bs-auto-close="false"
                           role="button"
                           aria-expanded="{{ request()->routeIs('admin.reports*', 'admin.analytics.*', 'admin.workload*') ? 'true' : 'false' }}">
                            <i class="fas fa-chart-pie me-2"></i>
                            <span class="nav-link-title">Reports & Analytics</span>
                        </a>
                        <div class="dropdown-menu {{ request()->routeIs('admin.reports*', 'admin.analytics.*', 'admin.workload*') ? 'show' : '' }}" id="navbar-analytics">
                            <a class="dropdown-item {{ request()->routeIs('admin.reports') ? 'active' : '' }}" href="{{ route('admin.reports') }}">
                                <i class="fas fa-chart-bar me-2"></i> Dashboard
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('admin.workload*') ? 'active' : '' }}" href="{{ route('admin.workload') }}">
                                <i class="fas fa-tasks me-2"></i> Team Workload
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('admin.analytics.*') ? 'active' : '' }}" href="{{ route('admin.analytics') }}">
                                <i class="fas fa-chart-line me-2"></i> Advanced Analytics
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('admin.activity') ? 'active' : '' }}" href="{{ route('admin.activity') }}">
                                <i class="fas fa-history me-2"></i> Activity Log
                            </a>
                        </div>
                    </li>

                    {{-- SYSTEM & SETTINGS --}}
                    <li class="nav-item dropdown {{ request()->routeIs('admin.settings*', 'admin.storage*', 'admin.webhooks*', 'api.documentation') ? 'active' : '' }}">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.settings*', 'admin.storage*', 'admin.webhooks*', 'api.documentation') ? 'show' : '' }}"
                           href="#navbar-system"
                           data-bs-toggle="dropdown"
                           data-bs-auto-close="false"
                           role="button"
                           aria-expanded="{{ request()->routeIs('admin.settings*', 'admin.storage*', 'admin.webhooks*', 'api.documentation') ? 'true' : 'false' }}">
                            <i class="fas fa-cogs me-2"></i>
                            <span class="nav-link-title">System</span>
                        </a>
                        <div class="dropdown-menu {{ request()->routeIs('admin.settings*', 'admin.storage*', 'admin.webhooks*', 'api.documentation') ? 'show' : '' }}" id="navbar-system">
                            <a class="dropdown-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}" href="{{ route('admin.settings') }}">
                                <i class="fas fa-sliders-h me-2"></i> System Settings
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('admin.storage') ? 'active' : '' }}" href="{{ route('admin.storage') }}">
                                <i class="fas fa-database me-2"></i> Storage
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('admin.settings.webhooks') ? 'active' : '' }}" href="{{ route('admin.settings.webhooks') }}">
                                <i class="fas fa-plug me-2"></i> Webhooks
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item {{ request()->routeIs('api.documentation') ? 'active' : '' }}" href="{{ route('api.documentation') }}">
                                <i class="fas fa-code me-2"></i> API Documentation
                            </a>
                        </div>
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
                            <i class="fas fa-moon"></i>
>>>>>>> Stashed changes
                        </button>
                    </div>
                    @endif

<<<<<<< Updated upstream
                    @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
=======
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
                            <a href="{{ route('profile.edit') }}" class="dropdown-item">
                                <i class="fas fa-user me-2"></i> Profile
                            </a>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('logout') }}" class="px-2">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger w-100">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </button>
                            </form>
                        </div>
>>>>>>> Stashed changes
                    </div>
                    @endif

                    {{ $slot ?? '' }}
                    @yield('content')
                </div>
<<<<<<< Updated upstream
            </section>
=======

                <!-- Quick search -->
                <form class="d-none d-md-flex ms-2" role="search" action="{{ route('admin.reports') }}" method="GET">
                    <input class="form-control" type="search" name="q" placeholder="Quick search…" aria-label="Quick search">
                </form>
            </div>
        </header>

        <div class="page-body">
            <div class="container-fluid">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="status">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{ $slot ?? '' }}
                @yield('content')
            </div>
>>>>>>> Stashed changes
        </div>

        <!-- Footer -->
        @include('layouts.partials.footer')
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

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

    {{-- Site footer HTML (branding setting from database) --}}
    {!! app(\App\Services\BrandingService::class)->get('site_footer_html') !!}
</body>
</html>
