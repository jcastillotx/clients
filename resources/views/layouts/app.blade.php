<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name') }}</title>

    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#3c8dbc">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
    <link rel="icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/favicon.ico">
    <meta name="vapid-public-key" content="{{ config('pwa.vapid_public_key') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <!-- Tailwind CSS (for custom components) -->
    @if(!app()->runningUnitTests())
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <!-- Livewire Styles -->
    @livewireStyles

    <style>
        :root {
            --primary-color: #3c8dbc;
            --secondary-color: #6c757d;
        }
        body {
            font-family: 'Inter', sans-serif;
        }
        .content-wrapper {
            background-color: #f4f6f9;
        }
        .brand-link {
            background-color: #343a40;
        }
        .sidebar-dark-primary {
            background-color: #343a40;
        }
    </style>

    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- PWA offline indicator -->
        <div id="offline-indicator" class="alert alert-warning alert-dismissible fade show d-none m-2" role="status" style="position: sticky; top: 0; z-index: 1050;">
            <i class="fas fa-wifi mr-2"></i>
            You’re offline. Some actions will be queued.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

        <!-- PWA install prompt -->
        <div id="pwa-install-banner" class="alert alert-info d-none m-2" role="status" style="position: sticky; top: 0; z-index: 1050;">
            <div class="d-flex align-items-center justify-content-between" style="gap: 12px;">
                <div>
                    <strong>Install the portal</strong>
                    <div class="small text-muted">Get an app-like experience and offline support.</div>
                </div>
                <div class="d-flex" style="gap: 8px;">
                    <button id="pwa-install-btn" type="button" class="btn btn-sm btn-primary">Install</button>
                    <button id="pwa-install-dismiss" type="button" class="btn btn-sm btn-outline-secondary">Not now</button>
                </div>
            </div>
        </div>

        <!-- Navbar -->
        @include('layouts.partials.navbar')

        <!-- Main Sidebar Container -->
        @include('layouts.partials.sidebar')

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            @if(isset($header))
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">{{ $header }}</h1>
                        </div>
                        <div class="col-sm-6">
                            {{ $breadcrumb ?? '' }}
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <!-- Flash Messages -->
                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle mr-2"></i>
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    @endif

                    @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    @endif

                    {{ $slot }}
                </div>
            </section>
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

    <!-- Livewire Scripts -->
    @livewireScripts

    @stack('scripts')
</body>
</html>
