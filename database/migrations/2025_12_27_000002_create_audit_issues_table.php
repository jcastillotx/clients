<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_audit_id')->constrained('website_audits')->cascadeOnDelete();

            $table->string('severity')->default('warning'); // critical, error, warning, info
            $table->string('category')->default('seo'); // seo, performance, accessibility, security, mobile

            $table->string('issue_type');
            $table->text('description');
            $table->string('affected_url', 2048)->nullable();
            $table->text('recommendation')->nullable();

            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();

            // Optional extra fields for prioritization / impact estimation
            $table->unsignedTinyInteger('priority_score')->nullable(); // 0-100
            $table->json('meta')->nullable(); // e.g. {impact:"high", evidence:{...}}

            $table->timestamps();

            $table->index(['website_audit_id', 'severity']);
            $table->index(['website_audit_id', 'category']);
            $table->index(['issue_type']);
            $table->index(['is_resolved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_issues');
    }
};

