<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Insert Gemini as an available AI provider
        DB::table('ai_providers')->insert([
            'name' => 'gemini',
            'api_endpoint' => 'https://generativelanguage.googleapis.com/v1',
            'api_key' => null, // Set via admin panel or .env
            'model_name' => 'gemini-2.0-flash-exp',
            'status' => 'inactive', // Activate when API key is configured
            'is_default' => false,
            'priority_order' => 50,
            'cost_per_1k_input_tokens' => 0.000075, // $0.075 per 1M tokens
            'cost_per_1k_output_tokens' => 0.0003,   // $0.30 per 1M tokens
            'rate_limit_per_minute' => 1000,
            'supported_features' => json_encode([
                'chat',
                'vision',
                'embeddings',
                'document_analysis',
                'image_analysis',
                'multimodal',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('ai_providers')->where('name', 'gemini')->delete();
    }
};
