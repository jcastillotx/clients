<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_review_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_task_id')->nullable()->constrained('ai_tasks')->nullOnDelete();
            $table->foreignId('ai_message_id')->nullable()->constrained('ai_messages')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('category')->index(); // legal|financial|complaint|privacy|other
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->text('reason')->nullable();
            $table->longText('output_preview')->nullable();
            $table->longText('approved_text')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->index();

            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('ai_compliance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_task_id')->nullable()->constrained('ai_tasks')->nullOnDelete();
            $table->foreignId('ai_conversation_id')->nullable()->constrained('ai_conversations')->nullOnDelete();
            $table->foreignId('ai_message_id')->nullable()->constrained('ai_messages')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('task_type')->nullable()->index();
            $table->string('provider')->nullable()->index();
            $table->string('model')->nullable();

            $table->string('input_hash')->nullable()->index();
            $table->longText('input_redacted')->nullable();
            $table->longText('output_preview')->nullable();
            $table->boolean('pii_detected')->default(false)->index();
            $table->boolean('flagged_for_review')->default(false)->index();
            $table->json('flags')->nullable();

            $table->timestamp('retention_until')->nullable()->index();
            $table->timestamp('deleted_at')->nullable()->index();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_compliance_logs');
        Schema::dropIfExists('ai_review_queue');
    }
};

