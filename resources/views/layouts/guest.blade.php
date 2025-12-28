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

    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <!-- Brand Styles -->
    <style>
        :root {
            --brand-primary: {{ $brand['color_primary'] ?? '#3b82f6' }};
            --brand-secondary: {{ $brand['color_secondary'] ?? '#64748b' }};
            --brand-accent: {{ $brand['color_accent'] ?? '#10b981' }};
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .login-page {
            @if(!empty($brand['login_background_path']))
                background: linear-gradient(135deg, rgba(30, 41, 59, 0.9), rgba(51, 65, 85, 0.9)), 
                            url('{{ asset('storage/' . $brand['login_background_path']) }}') no-repeat center center;
                background-size: cover;
            @else
                background: linear-gradient(135deg, {{ $brand['color_primary'] ?? '#1e293b' }} 0%, {{ $brand['sidebar_bg'] ?? '#334155' }} 100%);
            @endif
            min-height: 100vh;
        }
        
        .login-box {
            width: 420px;
            max-width: 95vw;
        }
        
        .login-logo {
            margin-bottom: 1.5rem;
        }
        
        .login-logo a {
            color: #ffffff;
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
        }
        
        .login-logo a:hover {
            color: #ffffff;
        }
        
        .card {
            border-radius: 0.75rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            border: none;
        }
        
        .card-header {
            background: transparent;
            border-bottom: 1px solid #e5e7eb;
            padding: 1.25rem 1.5rem;
        }
        
        .card-body.login-card-body {
            padding: 1.5rem;
        }
        
        .form-control {
            border-radius: 0.5rem;
            border: 1px solid #d1d5db;
            padding: 0.625rem 0.875rem;
        }
        
        .form-control:focus {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .input-group-text {
            border-radius: 0.5rem 0 0 0.5rem;
            background: #f8fafc;
            border: 1px solid #d1d5db;
            border-right: none;
        }
        
        .input-group .form-control {
            border-radius: 0 0.5rem 0.5rem 0;
        }
        
        .btn-primary {
            background-color: var(--brand-primary);
            border-color: var(--brand-primary);
            border-radius: 0.5rem;
            font-weight: 600;
            padding: 0.625rem 1rem;
        }
        
        .btn-primary:hover,
        .btn-primary:focus {
            background-color: color-mix(in srgb, var(--brand-primary) 85%, black);
            border-color: color-mix(in srgb, var(--brand-primary) 85%, black);
        }
        
        .card-outline.card-primary {
            border-top: 3px solid var(--brand-primary);
        }
        
        .icheck-primary input:checked + label::before {
            background-color: var(--brand-primary);
            border-color: var(--brand-primary);
        }
        
        .text-white-50 {
            color: rgba(255, 255, 255, 0.6) !important;
        }
        
        /* AdminLTE iCheck override */
        .icheck-primary > input:first-child:checked + label::before,
        .icheck-primary > input:first-child:checked + input[type="hidden"] + label::before {
            background-color: var(--brand-primary);
            border-color: var(--brand-primary);
        }
    </style>
</head>
<body class="hold-transition login-page">
    {{ $slot }}

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
