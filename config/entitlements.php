<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature → Permission Mapping (Portal Entitlements)
    |--------------------------------------------------------------------------
    |
    | Clients "buy" marketing/services which we represent as feature keys
    | (see config/features.php and Client::hasFeature()).
    |
    | When a feature is enabled for a client, we automatically grant the
    | corresponding portal permissions to that client's users.
    |
    | Manual per-user permission overrides are stored separately and merged in.
    */

    'feature_permissions' => [
        // Core portal access
        'documents' => [
            'view_document',
            'upload_document',
        ],
        'invoices' => [
            'view_invoice',
            'process_payment',
        ],
        'service_requests' => [
            'view_request',
            'create_request',
            'update_request',
        ],

        // Optional: collaboration-related (if you later gate UI by permissions)
        // 'project_management' => [],
        // 'team_collaboration' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Which permissions can be manually assigned to CLIENT users
    |--------------------------------------------------------------------------
    |
    | This prevents accidentally giving client users admin/staff capabilities.
    */
    'client_assignable_permissions' => [
        'view_client',
        'view_request',
        'create_request',
        'update_request',
        'view_invoice',
        'process_payment',
        'view_document',
        'upload_document',
    ],
];

