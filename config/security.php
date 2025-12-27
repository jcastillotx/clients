<?php

return [
    /*
     * Comma-separated list of allowed IPs/CIDRs for admin routes.
     * Examples:
     * - "203.0.113.10"
     * - "203.0.113.0/24,198.51.100.2"
     *
     * Empty/null means "allow all".
     */
    'admin_ip_allowlist' => array_values(array_filter(array_map('trim', explode(',', (string) env('ADMIN_IP_ALLOWLIST', ''))))),

    /*
     * Enforce 2FA for staff/admin users on admin routes.
     */
    'enforce_admin_2fa' => (bool) env('ENFORCE_ADMIN_2FA', true),

    /*
     * Activity/audit retention (days).
     */
    'audit_retention_days' => (int) env('AUDIT_RETENTION_DAYS', 365),

    /*
     * Two-factor issuer name (displayed in authenticator apps).
     */
    'two_factor_issuer' => (string) env('TWO_FACTOR_ISSUER', env('APP_NAME', 'Client Portal')),
];
