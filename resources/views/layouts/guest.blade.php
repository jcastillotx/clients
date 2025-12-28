<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('branding.company.name') }} - Client Portal</title>

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

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Brand Custom CSS -->
    @if(file_exists(public_path(config('branding.custom_css'))))
    <link rel="stylesheet" href="/{{ config('branding.custom_css') }}?v={{ filemtime(public_path(config('branding.custom_css'))) }}">
    @endif

    {{-- Site header HTML (branding setting) --}}
    {!! config('branding.site.header_html') !!}

    <style>
        body {
            font-family: {{ config('branding.typography.font_secondary') }};
        }
        .login-page {
            @if(config('branding.auth.background_style') === 'gradient')
            background: linear-gradient(135deg, {{ config('branding.colors.primary') }} 0%, {{ config('branding.colors.primary_dark') }} 100%);
            @elseif(config('branding.auth.background_style') === 'image' && config('branding.auth.background_image'))
            background: url('/{{ config('branding.auth.background_image') }}') no-repeat center center;
            background-size: cover;
            @else
            background-color: {{ config('branding.auth.background_color') }};
            @endif
        }
        .login-box {
            width: 400px;
        }
        .login-card-body {
            border-radius: {{ config('branding.design.border_radius_lg') }};
            box-shadow: {{ config('branding.design.shadow_lg') }};
        }
        .login-logo a {
            color: white;
            font-family: {{ config('branding.typography.font_primary') }};
            font-weight: 700;
        }
        .login-logo img {
            max-width: {{ config('branding.logo.width') }}px;
            height: auto;
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

    {{-- Site footer HTML (branding setting) --}}
    {!! config('branding.site.footer_html') !!}
</body>
</html>
