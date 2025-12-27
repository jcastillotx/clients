<?php

return [
    'providers' => [
        's3' => [
            'label' => 'Amazon S3',
        ],
        'dropbox' => [
            'label' => 'Dropbox',
        ],
        'drive' => [
            'label' => 'Google Drive',
        ],
    ],

    // Cost estimates (used in admin overview)
    'costs' => [
        // USD per GB-month (placeholder, adjust to your plan/region)
        's3_per_gb_month' => env('S3_COST_PER_GB_MONTH', 0.023),
    ],
];
