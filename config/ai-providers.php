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
            // USD per 1K tokens (fallback; per-model pricing table below is preferred)
            'cost_per_1k_input_tokens' => (float) env('OPENAI_COST_INPUT_1K', 0.0),
            'cost_per_1k_output_tokens' => (float) env('OPENAI_COST_OUTPUT_1K', 0.0),
        ],

        'claude' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'api_base' => env('ANTHROPIC_API_BASE', 'https://api.anthropic.com'),
            'default_model' => env('ANTHROPIC_DEFAULT_MODEL', 'claude-3-5-sonnet-latest'),
            'anthropic_version' => env('ANTHROPIC_VERSION', '2023-06-01'),
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

        'grok' => [
            'api_key' => env('GROK_API_KEY'),
            'api_base' => env('GROK_API_BASE', 'https://api.x.ai'),
            'default_model' => env('GROK_DEFAULT_MODEL', 'grok-2-latest'),
        ],

        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'api_base' => env('GEMINI_API_BASE', 'https://generativelanguage.googleapis.com'),
            'default_model' => env('GEMINI_DEFAULT_MODEL', 'gemini-1.5-flash'),
            'embedding_model' => env('GEMINI_EMBEDDING_MODEL', 'text-embedding-004'),
        ],

        'copilot' => [
            'api_key' => env('AZURE_OPENAI_API_KEY'),
            'api_base' => env('AZURE_OPENAI_ENDPOINT'),
            'deployment_name' => env('AZURE_OPENAI_DEPLOYMENT', 'gpt-4'),
            'api_version' => env('AZURE_OPENAI_API_VERSION', '2024-02-15-preview'),
            'default_model' => env('AZURE_OPENAI_MODEL', 'gpt-4'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Pricing (USD per 1K tokens)
    |--------------------------------------------------------------------------
    |
    | Provider implementations use this table to estimate cost.
    | Keep these updated to match your billing plans.
    |
    */
    'pricing' => [
        'openai' => [
            // Common OpenAI model tiers (examples; update as needed)
            'gpt-4' => ['input' => 0.030, 'output' => 0.060],
            'gpt-4-turbo' => ['input' => 0.010, 'output' => 0.030],
            'gpt-3.5-turbo' => ['input' => 0.0005, 'output' => 0.0015],
            // Your current defaults
            'gpt-4o-mini' => ['input' => 0.00015, 'output' => 0.00060],
            'text-embedding-3-small' => ['input' => 0.00002, 'output' => 0.0],
        ],
        'claude' => [
            'claude-3-5-sonnet-latest' => ['input' => 0.003, 'output' => 0.015],
            'claude-3-opus-latest' => ['input' => 0.015, 'output' => 0.075],
            'claude-3-haiku-20240307' => ['input' => 0.00025, 'output' => 0.00125],
        ],
        'openrouter' => [
            // OpenRouter pricing varies by model; these are safe defaults.
            // Prefer DB overrides for precise tracking.
        ],
        'perplexity' => [
            'sonar' => ['input' => 0.001, 'output' => 0.002],
            'sonar-pro' => ['input' => 0.003, 'output' => 0.006],
        ],
        'asksage' => [
            // Fill in based on your AskSage plan.
        ],
        'grok' => [
            'grok-2-latest' => ['input' => 0.002, 'output' => 0.010],
            'grok-2-1212' => ['input' => 0.002, 'output' => 0.010],
            'grok-2-vision-1212' => ['input' => 0.002, 'output' => 0.010],
        ],
        'gemini' => [
            'gemini-2.0-flash-exp' => ['input' => 0.0, 'output' => 0.0], // Free during preview
            'gemini-1.5-pro' => ['input' => 0.00125, 'output' => 0.005],
            'gemini-1.5-flash' => ['input' => 0.000075, 'output' => 0.0003],
            'gemini-1.5-flash-8b' => ['input' => 0.0000375, 'output' => 0.00015],
        ],
        'copilot' => [
            // Uses Azure OpenAI pricing - varies by deployment
            'gpt-4' => ['input' => 0.030, 'output' => 0.060],
            'gpt-4o' => ['input' => 0.005, 'output' => 0.015],
            'gpt-4o-mini' => ['input' => 0.00015, 'output' => 0.0006],
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
    | Smart Routing Defaults
    |--------------------------------------------------------------------------
    */
    'routing' => [
        // complexity: low|medium|high
        'complexity_models' => [
            'low' => [
                'openai' => 'gpt-4o-mini',
                'openrouter' => 'openai/gpt-4o-mini',
                'claude' => 'claude-3-haiku-20240307',
                'perplexity' => 'sonar',
                'gemini' => 'gemini-1.5-flash-8b',
                'grok' => 'grok-2-latest',
                'copilot' => 'gpt-4o-mini',
            ],
            'medium' => [
                'openai' => 'gpt-4o',
                'openrouter' => 'openai/gpt-4o',
                'claude' => 'claude-3-5-sonnet-latest',
                'perplexity' => 'sonar',
                'gemini' => 'gemini-1.5-flash',
                'grok' => 'grok-2-latest',
                'copilot' => 'gpt-4o',
            ],
            'high' => [
                'openai' => 'gpt-4-turbo',
                'openrouter' => 'openai/gpt-4-turbo',
                'claude' => 'claude-3-5-sonnet-latest',
                'perplexity' => 'sonar-pro',
                'gemini' => 'gemini-1.5-pro',
                'grok' => 'grok-2-latest',
                'copilot' => 'gpt-4',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Strategy
    |--------------------------------------------------------------------------
    */
    'fallback' => [
        // If primary fails, try in order.
        'order' => array_values(array_filter(explode(',', env('AI_FALLBACK_ORDER', 'openai,claude,gemini,grok,openrouter,perplexity,copilot,asksage')))),
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
