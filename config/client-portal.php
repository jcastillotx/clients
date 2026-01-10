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
