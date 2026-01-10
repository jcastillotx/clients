<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Client Portal Settings
    |--------------------------------------------------------------------------
    */

    'support_email' => env('CLIENT_PORTAL_SUPPORT_EMAIL', 'support@kre8ivdesigns.com'),

    /*
    |--------------------------------------------------------------------------
    | File Upload Settings
    |--------------------------------------------------------------------------
    */

    'max_upload_size' => env('CLIENT_PORTAL_MAX_UPLOAD_SIZE', 10240), // in KB

    // Documents can be larger than request attachments.
    'max_document_upload_size' => env('CLIENT_PORTAL_MAX_DOCUMENT_UPLOAD_SIZE', 51200), // in KB (50MB)

    'allowed_file_types' => explode(',', env('CLIENT_PORTAL_ALLOWED_FILE_TYPES', 'pdf,doc,docx,xls,xlsx,png,jpg,jpeg,gif,zip')),

    /*
    |--------------------------------------------------------------------------
    | Invoice Settings
    |--------------------------------------------------------------------------
    */

    'invoice' => [
        'prefix' => env('INVOICE_PREFIX', 'INV-'),
        'tax_rate' => env('INVOICE_TAX_RATE', 0),
        'templates' => [
            'classic' => 'Classic',
            'modern' => 'Modern',
        ],
        'branding' => [
            'primary_color' => env('INVOICE_PRIMARY_COLOR', '#0f172a'),
            'accent_color' => env('INVOICE_ACCENT_COLOR', '#2563eb'),
            'logo_path' => env('INVOICE_LOGO_PATH', 'images/logo.png'), // relative to public/
        ],
        'company' => [
            'name' => env('INVOICE_COMPANY_NAME', 'Kre8iv Designs LLC'),
            'address' => env('INVOICE_COMPANY_ADDRESS', 'Your Company Address'),
            'email' => env('INVOICE_COMPANY_EMAIL', 'billing@kre8ivdesigns.com'),
            'phone' => env('INVOICE_COMPANY_PHONE', '(555) 123-4567'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Types
    |--------------------------------------------------------------------------
    */

    'request_types' => [
        'web_development' => 'Web Development',
        'graphic_design' => 'Graphic Design',
        'marketing' => 'Marketing',
        'seo' => 'SEO',
        'social_media' => 'Social Media',
        'branding' => 'Branding',
        'consulting' => 'Consulting',
        'maintenance' => 'Maintenance',
        'support' => 'Support',
        'other' => 'Other',
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Statuses
    |--------------------------------------------------------------------------
    */

    'request_statuses' => [
        'pending' => 'Pending',
        'in_review' => 'In Review',
        'approved' => 'Approved',
        'in_progress' => 'In Progress',
        'on_hold' => 'On Hold',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Priorities
    |--------------------------------------------------------------------------
    */

    'request_priorities' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Estimates
    |--------------------------------------------------------------------------
    | Base hour estimates and hourly rates by request type.
    | These provide clients with estimated time and cost when submitting requests.
    | Admins can adjust estimates on individual requests.
    */

    'request_estimates' => [
        'hourly_rate' => env('REQUEST_HOURLY_RATE', 125.00), // Default hourly rate

        // Base hour estimates by type (minimum hours typically needed)
        'base_hours' => [
            'web_development' => ['min' => 8, 'max' => 40, 'label' => '1-5 days'],
            'graphic_design' => ['min' => 2, 'max' => 16, 'label' => '2-16 hours'],
            'marketing' => ['min' => 4, 'max' => 20, 'label' => '4-20 hours'],
            'seo' => ['min' => 4, 'max' => 16, 'label' => '4-16 hours'],
            'social_media' => ['min' => 2, 'max' => 8, 'label' => '2-8 hours'],
            'branding' => ['min' => 8, 'max' => 40, 'label' => '1-5 days'],
            'consulting' => ['min' => 1, 'max' => 4, 'label' => '1-4 hours'],
            'maintenance' => ['min' => 1, 'max' => 4, 'label' => '1-4 hours'],
            'support' => ['min' => 0.5, 'max' => 2, 'label' => '30 min - 2 hours'],
            'other' => ['min' => 1, 'max' => 8, 'label' => '1-8 hours'],
        ],

        // Priority multipliers (urgent costs more)
        'priority_multipliers' => [
            'low' => 1.0,
            'medium' => 1.0,
            'high' => 1.25,
            'urgent' => 1.5,
        ],

        // Whether to show estimates to clients on request creation
        'show_to_clients' => true,

        // Disclaimer shown with estimates
        'disclaimer' => 'Estimates are based on typical project scope. Actual time and cost may vary based on specific requirements. A detailed quote will be provided after review.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Client Tiers
    |--------------------------------------------------------------------------
    */

    'client_tiers' => [
        'basic' => 'Basic',
        'standard' => 'Standard',
        'premium' => 'Premium',
        'enterprise' => 'Enterprise',
    ],

    /*
    |--------------------------------------------------------------------------
    | Contract Statuses
    |--------------------------------------------------------------------------
    */

    'contract_statuses' => [
        'draft' => 'Draft',
        'pending_signature' => 'Pending Signature',
        'active' => 'Active',
        'expired' => 'Expired',
        'terminated' => 'Terminated',
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoice Statuses
    |--------------------------------------------------------------------------
    */

    'invoice_statuses' => [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'paid' => 'Paid',
        'overdue' => 'Overdue',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
    ],

    /*
    |--------------------------------------------------------------------------
    | Support Ticket Categories
    |--------------------------------------------------------------------------
    */

    'support_ticket_categories' => [
        'technical_issue' => 'Technical Issue',
        'bug_report' => 'Bug Report',
        'general_question' => 'General Question',
        'account_issue' => 'Account Issue',
        'billing_question' => 'Billing Question',
        'feature_request' => 'Feature Request',
        'other' => 'Other',
    ],

    /*
    |--------------------------------------------------------------------------
    | Support Ticket Statuses
    |--------------------------------------------------------------------------
    */

    'support_ticket_statuses' => [
        'open' => 'Open',
        'in_progress' => 'In Progress',
        'waiting_on_client' => 'Waiting on Client',
        'waiting_on_vendor' => 'Waiting on Vendor',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ],

    /*
    |--------------------------------------------------------------------------
    | Support Ticket SLA Configuration
    |--------------------------------------------------------------------------
    | Define response and resolution time targets (in hours) by priority.
    | Response time: Time to first reply from staff.
    | Resolution time: Time to resolve/close the ticket.
    */

    'support_ticket_sla' => [
        // SLA targets in hours by priority
        'targets' => [
            'urgent' => [
                'response_hours' => 1,      // 1 hour response time
                'resolution_hours' => 4,    // 4 hours resolution time
            ],
            'high' => [
                'response_hours' => 4,      // 4 hours response time
                'resolution_hours' => 24,   // 24 hours resolution time
            ],
            'medium' => [
                'response_hours' => 8,      // 8 hours response time (1 business day)
                'resolution_hours' => 72,   // 72 hours resolution time (3 business days)
            ],
            'low' => [
                'response_hours' => 24,     // 24 hours response time
                'resolution_hours' => 168,  // 168 hours resolution time (7 days)
            ],
        ],

        // Business hours configuration (for future enhancement)
        'business_hours' => [
            'enabled' => false, // When true, SLA only counts during business hours
            'start' => '09:00',
            'end' => '17:00',
            'timezone' => 'America/New_York',
            'working_days' => [1, 2, 3, 4, 5], // Monday to Friday
        ],

        // Escalation configuration
        'escalation' => [
            'enabled' => true,
            // Escalate after this percentage of SLA time has passed
            'warning_threshold' => 75,  // Send warning at 75% of SLA time
            'levels' => [
                1 => ['after_breach_minutes' => 0, 'notify' => ['assigned_staff', 'ticket_creator']],
                2 => ['after_breach_minutes' => 60, 'notify' => ['team_lead']],
                3 => ['after_breach_minutes' => 240, 'notify' => ['manager']],
            ],
        ],

        // Statuses that pause SLA timer
        'pause_on_statuses' => ['waiting_on_client'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Plan Statuses
    |--------------------------------------------------------------------------
    */

    'maintenance_plan_statuses' => [
        'active' => 'Active',
        'paused' => 'Paused',
        'expired' => 'Expired',
        'cancelled' => 'Cancelled',
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Categories
    |--------------------------------------------------------------------------
    */

    'document_categories' => [
        'contract' => 'Contract',
        // Requested categories
        'deliverable' => 'Deliverable',
        'misc' => 'Misc',
        'invoice' => 'Invoice',
        'proposal' => 'Proposal',
        'report' => 'Report',
        'design' => 'Design',
        'content' => 'Content',
        'other' => 'Other',
    ],

];
