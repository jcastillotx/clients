<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->enum('name', ['openai', 'claude', 'openrouter', 'perplexity', 'asksage'])->index();
            $table->text('api_key')->nullable(); // encrypted via model cast
            $table->string('api_endpoint')->nullable();
            $table->string('model_name')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('inactive')->index();
            $table->decimal('cost_per_1k_input_tokens', 10, 6)->nullable();
            $table->decimal('cost_per_1k_output_tokens', 10, 6)->nullable();
            $table->integer('rate_limit_per_minute')->nullable();
            $table->boolean('is_default')->default(false)->index();
            $table->unsignedInteger('priority_order')->default(100)->index();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Requested shape: context_type + context_id (enum-ish + linked entity ID).
            $table->enum('context_type', ['request', 'invoice', 'contract', 'document', 'general'])->default('general')->index();
            $table->unsignedBigInteger('context_id')->nullable()->index();

            $table->string('title')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant', 'system'])->index();
            $table->longText('content');
            $table->string('provider_used')->nullable()->index();
            $table->string('model_used')->nullable();
            $table->unsignedInteger('tokens_used')->nullable();
            $table->decimal('cost', 10, 6)->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('ai_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_type')->index(); // triage_request, generate_estimate, draft_contract, etc.
            $table->json('input_data')->nullable();
            $table->json('output_data')->nullable();
            $table->string('provider_used')->nullable()->index();
            $table->string('model_used')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending')->index();
            $table->unsignedInteger('tokens_used')->nullable();
            $table->decimal('cost', 10, 6)->nullable();
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
        });

        Schema::create('ai_usage_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('provider')->index();
            $table->string('model')->nullable();
            $table->unsignedInteger('tokens_input')->default(0);
            $table->unsignedInteger('tokens_output')->default(0);
            $table->decimal('cost', 10, 6)->default(0);
            $table->string('task_type')->nullable()->index();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_tracking');
        Schema::dropIfExists('ai_tasks');
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('ai_providers');
    }
};
