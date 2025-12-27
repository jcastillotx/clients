<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_ai_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('asked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ai_conversation_id')->nullable()->constrained('ai_conversations')->nullOnDelete();
            $table->string('category')->nullable()->index(); // business|technical|marketing|pricing|other
            $table->string('topic')->nullable()->index();
            $table->text('question');
            $table->longText('answer')->nullable();
            $table->json('sources')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('is_opportunity')->default(false)->index();
            $table->string('opportunity_type')->nullable(); // upsell|cross_sell|retention|consulting
            $table->foreignId('request_id')->nullable()->constrained('requests')->nullOnDelete();
            $table->timestamp('answered_at')->nullable()->index();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('industry_monitors', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('industry')->index();
            $table->string('region')->nullable()->index();
            $table->json('keywords')->nullable();
            $table->enum('cadence', ['daily', 'weekly', 'monthly'])->default('weekly')->index();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->timestamp('last_run_at')->nullable()->index();
            $table->foreignId('last_report_id')->nullable()->constrained('ai_insight_reports')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('industry_monitors');
        Schema::dropIfExists('client_ai_questions');
    }
};

