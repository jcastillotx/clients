<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Provider Defaults
    |--------------------------------------------------------------------------
    */
    'default_provider' => env('AI_DEFAULT_PROVIDER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | Provider Configurations
    |--------------------------------------------------------------------------
    |
    | These are environment-driven defaults. The `ai_providers` database table
    | can override these at runtime.
    |
    */
    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'api_base' => env('OPENAI_API_BASE', 'https://api.openai.com/v1'),
            'default_model' => env('OPENAI_DEFAULT_MODEL', 'gpt-4o-mini'),
            'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
            // USD per 1K tokens (example values; tune in DB for precise billing)
            'cost_per_1k_input_tokens' => (float) env('OPENAI_COST_INPUT_1K', 0.00015),
            'cost_per_1k_output_tokens' => (float) env('OPENAI_COST_OUTPUT_1K', 0.00060),
        ],

        'claude' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'api_base' => env('ANTHROPIC_API_BASE', 'https://api.anthropic.com'),
            'default_model' => env('ANTHROPIC_DEFAULT_MODEL', 'claude-3-5-sonnet-latest'),
        ],

        'openrouter' => [
            'api_key' => env('OPENROUTER_API_KEY'),
            'api_base' => env('OPENROUTER_API_BASE', 'https://openrouter.ai/api/v1'),
            'default_model' => env('OPENROUTER_DEFAULT_MODEL', 'openai/gpt-4o-mini'),
        ],

        'perplexity' => [
            'api_key' => env('PERPLEXITY_API_KEY'),
            'api_base' => env('PERPLEXITY_API_BASE', 'https://api.perplexity.ai'),
            'default_model' => env('PERPLEXITY_DEFAULT_MODEL', 'sonar'),
        ],

        'asksage' => [
            'api_key' => env('ASKSAGE_API_KEY'),
            'api_base' => env('ASKSAGE_API_BASE'),
            'default_model' => env('ASKSAGE_DEFAULT_MODEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Task Model Defaults
    |--------------------------------------------------------------------------
    */
    'task_models' => [
        'triage_request' => [
            'provider' => env('AI_TASK_TRIAGE_PROVIDER', 'openai'),
            'model' => env('AI_TASK_TRIAGE_MODEL', 'gpt-4o-mini'),
        ],
        'generate_estimate' => [
            'provider' => env('AI_TASK_ESTIMATE_PROVIDER', 'openai'),
            'model' => env('AI_TASK_ESTIMATE_MODEL', 'gpt-4o-mini'),
        ],
        'draft_contract' => [
            'provider' => env('AI_TASK_CONTRACT_PROVIDER', 'openai'),
            'model' => env('AI_TASK_CONTRACT_MODEL', 'gpt-4o-mini'),
        ],
        'document_analysis' => [
            'provider' => env('AI_TASK_DOC_PROVIDER', 'openai'),
            'model' => env('AI_TASK_DOC_MODEL', 'gpt-4o-mini'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Strategy
    |--------------------------------------------------------------------------
    */
    'fallback' => [
        // If primary fails, try in order.
        'order' => array_values(array_filter(explode(',', env('AI_FALLBACK_ORDER', 'openai,openrouter,claude,perplexity,asksage')))),
        // Only fallback on these exceptions/errors (implementations should throw RuntimeException/RequestException etc.)
        'enabled' => env('AI_FALLBACK_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cost Limits & Alerts
    |--------------------------------------------------------------------------
    */
    'limits' => [
        'max_cost_per_task_usd' => (float) env('AI_MAX_COST_PER_TASK_USD', 1.00),
        'max_cost_per_day_usd' => (float) env('AI_MAX_COST_PER_DAY_USD', 50.00),
    ],
];

