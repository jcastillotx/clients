<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Branding
    |--------------------------------------------------------------------------
    |
    | Configure the visual branding for the client portal to match
    | Kre8ivDesigns Marketing's brand identity.
    |
    */

    'company' => [
        'name' => env('BRAND_COMPANY_NAME', 'Kre8ivDesigns Marketing'),
        'legal_name' => env('BRAND_LEGAL_NAME', 'Kre8ivDesigns Marketing, LLC'),
        'tagline' => env('BRAND_TAGLINE', 'Creating Awareness About Your Brand'),
        'phone' => env('BRAND_PHONE', '210-549-7907'),
        'email' => env('BRAND_EMAIL', 'info@kre8ivdesigns.com'),
        'support_email' => env('BRAND_SUPPORT_EMAIL', 'support@kre8ivdesigns.com'),
        'website' => env('BRAND_WEBSITE', 'https://www.kre8ivdesigns.com'),
        'address' => env('BRAND_ADDRESS', 'San Antonio, TX'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Color Palette
    |--------------------------------------------------------------------------
    |
    | Primary brand colors used throughout the portal.
    | These should match your website's color scheme.
    |
    */

    'colors' => [
        // Primary brand color (muted purple/gray)
        'primary' => env('BRAND_COLOR_PRIMARY', '#5F5F82'),

        // Secondary color (light blue/gray)
        'secondary' => env('BRAND_COLOR_SECONDARY', '#BFCEE0'),

        // Dark variant of primary (for hover states, etc.)
        'primary_dark' => env('BRAND_COLOR_PRIMARY_DARK', '#4A4A66'),

        // Light variant of primary (for backgrounds)
        'primary_light' => env('BRAND_COLOR_PRIMARY_LIGHT', '#E8E8F0'),

        // Accent color for CTAs and highlights (black)
        'accent' => env('BRAND_COLOR_ACCENT', '#000000'),

        // Text colors
        'text_primary' => env('BRAND_COLOR_TEXT_PRIMARY', '#000000'),
        'text_secondary' => env('BRAND_COLOR_TEXT_SECONDARY', '#5F5F82'),

        // Background colors
        'background' => env('BRAND_COLOR_BACKGROUND', '#f4f7fa'),
        'background_alt' => env('BRAND_COLOR_BACKGROUND_ALT', '#ffffff'),

        // Sidebar colors (matching new scheme)
        'sidebar_bg' => env('BRAND_COLOR_SIDEBAR_BG', '#5F5F82'),
        'sidebar_text' => env('BRAND_COLOR_SIDEBAR_TEXT', '#BFCEE0'),
        'sidebar_hover' => env('BRAND_COLOR_SIDEBAR_HOVER', '#4A4A66'),
        'sidebar_active' => env('BRAND_COLOR_SIDEBAR_ACTIVE', '#BFCEE0'),

        // Header/navbar gradient colors
        'header_start' => env('BRAND_COLOR_HEADER_START', '#5F5F82'),
        'header_end' => env('BRAND_COLOR_HEADER_END', '#BFCEE0'),

        // Status colors
        'success' => env('BRAND_COLOR_SUCCESS', '#1de9b6'),
        'warning' => env('BRAND_COLOR_WARNING', '#f4c22b'),
        'danger' => env('BRAND_COLOR_DANGER', '#f44236'),
        'info' => env('BRAND_COLOR_INFO', '#5F5F82'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Typography
    |--------------------------------------------------------------------------
    |
    | Font families and text styling preferences
    |
    */

    'typography' => [
        // Primary font (headings, navigation) - Admindek uses Open Sans
        'font_primary' => env('BRAND_FONT_PRIMARY', "'Open Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"),

        // Secondary font (body text)
        'font_secondary' => env('BRAND_FONT_SECONDARY', "'Open Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"),

        // Monospace font (code, numbers)
        'font_mono' => env('BRAND_FONT_MONO', "'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace"),

        // Google Fonts to load (comma separated)
        'google_fonts' => env('BRAND_GOOGLE_FONTS', 'Open+Sans:300,400,500,600,700'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logo & Images
    |--------------------------------------------------------------------------
    |
    | Logo files and branding images. Paths are relative to public/ directory.
    |
    */

    'logo' => [
        // Main logo (used in header, light background)
        'main' => env('BRAND_LOGO_MAIN', 'images/logo.png'),

        // Logo for dark backgrounds
        'dark' => env('BRAND_LOGO_DARK', 'images/logo-white.png'),

        // Small logo/icon (favicon, mobile)
        'icon' => env('BRAND_LOGO_ICON', 'images/icon.png'),

        // Logo dimensions (for proper aspect ratio)
        'width' => env('BRAND_LOGO_WIDTH', 200),
        'height' => env('BRAND_LOGO_HEIGHT', 50),

        // Favicon
        'favicon' => env('BRAND_FAVICON', 'favicon.ico'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Design Elements
    |--------------------------------------------------------------------------
    |
    | Additional design configuration
    |
    */

    'design' => [
        // Border radius (Admindek uses subtle rounding)
        'border_radius' => env('BRAND_BORDER_RADIUS', '5px'),
        'border_radius_lg' => env('BRAND_BORDER_RADIUS_LG', '10px'),
        'border_radius_sm' => env('BRAND_BORDER_RADIUS_SM', '3px'),

        // Box shadows (Admindek style - subtle but visible)
        'shadow_sm' => env('BRAND_SHADOW_SM', '0 1px 3px 0 rgba(0, 0, 0, 0.1)'),
        'shadow' => env('BRAND_SHADOW', '0 2px 6px 0 rgba(0, 0, 0, 0.1)'),
        'shadow_lg' => env('BRAND_SHADOW_LG', '0 5px 20px 0 rgba(0, 0, 0, 0.1)'),

        // Button style
        'button_style' => env('BRAND_BUTTON_STYLE', 'rounded'), // 'rounded', 'square', 'pill'

        // Use gradients? (Admindek uses gradients)
        'use_gradients' => env('BRAND_USE_GRADIENTS', true),

        // Gradient configuration
        'gradient_primary' => env('BRAND_GRADIENT_PRIMARY', 'linear-gradient(to right, #5F5F82, #BFCEE0)'),
        'gradient_accent' => env('BRAND_GRADIENT_ACCENT', 'linear-gradient(to right, #000000, #5F5F82)'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation & Layout
    |--------------------------------------------------------------------------
    */

    'layout' => [
        // Sidebar width (for admin panel)
        'sidebar_width' => env('BRAND_SIDEBAR_WIDTH', '16rem'), // 256px

        // Header height
        'header_height' => env('BRAND_HEADER_HEIGHT', '4rem'), // 64px

        // Container max width
        'container_max_width' => env('BRAND_CONTAINER_MAX_WIDTH', '1280px'),

        // Navigation style
        'nav_style' => env('BRAND_NAV_STYLE', 'fixed'), // 'fixed', 'static', 'sticky'

        // Show company tagline in header?
        'show_tagline' => env('BRAND_SHOW_TAGLINE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Media
    |--------------------------------------------------------------------------
    */

    'social' => [
        'facebook' => env('BRAND_SOCIAL_FACEBOOK', null),
        'twitter' => env('BRAND_SOCIAL_TWITTER', null),
        'linkedin' => env('BRAND_SOCIAL_LINKEDIN', null),
        'instagram' => env('BRAND_SOCIAL_INSTAGRAM', null),
        'youtube' => env('BRAND_SOCIAL_YOUTUBE', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Branding
    |--------------------------------------------------------------------------
    |
    | Colors and styling for email notifications
    |
    */

    'email' => [
        'header_color' => env('BRAND_EMAIL_HEADER_COLOR', '#5F5F82'),
        'button_color' => env('BRAND_EMAIL_BUTTON_COLOR', '#5F5F82'),
        'footer_color' => env('BRAND_EMAIL_FOOTER_COLOR', '#5F5F82'),
        'logo' => env('BRAND_EMAIL_LOGO', 'images/logo.png'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Site-wide Header/Footer HTML
    |--------------------------------------------------------------------------
    |
    | Optional raw HTML injected into every page:
    | - header_html: inserted inside <head>
    | - footer_html: inserted before </body>
    |
    */
    'site' => [
        'header_html' => env('BRAND_SITE_HEADER_HTML', ''),
        'footer_html' => env('BRAND_SITE_FOOTER_HTML', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Button Colors (optional)
    |--------------------------------------------------------------------------
    */
    'buttons' => [
        'primary' => env('BRAND_BUTTON_PRIMARY', ''),
        'primary_hover' => env('BRAND_BUTTON_PRIMARY_HOVER', ''),
        'secondary' => env('BRAND_BUTTON_SECONDARY', ''),
        'secondary_hover' => env('BRAND_BUTTON_SECONDARY_HOVER', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Area (optional)
    |--------------------------------------------------------------------------
    */
    'admin' => [
        'dashboard_logo' => env('BRAND_ADMIN_DASHBOARD_LOGO', null),
        'page_padding' => env('BRAND_ADMIN_PAGE_PADDING', '1.5rem'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom CSS
    |--------------------------------------------------------------------------
    |
    | Path to custom CSS file for additional brand-specific styles
    |
    */

    'custom_css' => env('BRAND_CUSTOM_CSS', 'css/brand.css'),

    /*
    |--------------------------------------------------------------------------
    | Login/Auth Page Customization
    |--------------------------------------------------------------------------
    */

    'auth' => [
        // Background style for login page
        'background_style' => env('BRAND_AUTH_BG_STYLE', 'gradient'), // 'solid', 'gradient', 'image'
        'background_image' => env('BRAND_AUTH_BG_IMAGE', null),
        'background_color' => env('BRAND_AUTH_BG_COLOR', '#f3f4f6'),

        // Optional explicit logo for the login page (overrides branding.logo.main)
        'login_logo' => env('BRAND_AUTH_LOGIN_LOGO', null),

        // Show logo on login page?
        'show_logo' => env('BRAND_AUTH_SHOW_LOGO', true),

        // Tagline/welcome message
        'welcome_message' => env('BRAND_AUTH_WELCOME', 'Welcome to Your Client Portal'),
    ],
];
