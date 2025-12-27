<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_message_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_message_id')->constrained('ai_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->enum('rating', ['up', 'down'])->nullable()->index();
            $table->boolean('helpful')->nullable()->index();
            $table->text('comment')->nullable();
            $table->longText('edited_text')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['ai_message_id', 'user_id']);
        });

        Schema::create('prompt_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // e.g. admin_assistant_system
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('prompt_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prompt_template_id')->constrained('prompt_templates')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft')->index();
            $table->longText('system_prompt');
            $table->json('variables')->nullable(); // allowed variable names + defaults
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['prompt_template_id', 'version']);
        });

        Schema::create('knowledge_base_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->json('tags')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['document_id']);
        });

        Schema::create('ai_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('status', ['active', 'inactive'])->default('inactive')->index();
            $table->json('definition'); // nodes/edges/conditions serialized
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_workflows');
        Schema::dropIfExists('knowledge_base_documents');
        Schema::dropIfExists('prompt_template_versions');
        Schema::dropIfExists('prompt_templates');
        Schema::dropIfExists('ai_message_feedback');
    }
};
