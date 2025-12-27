<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('website_url', 2048);
            $table->string('audit_type')->default('full'); // full, seo, performance, accessibility
            $table->string('status')->default('pending'); // pending, running, completed, failed
            $table->unsignedTinyInteger('score')->nullable(); // 0-100

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Optional raw report payload (for re-rendering + AI analysis)
            $table->json('report')->nullable();
            $table->json('scores')->nullable(); // {seo, performance, accessibility, security, mobile, overall}
            $table->json('meta')->nullable();   // {max_pages, crawled_pages, api_sources, ...}

            $table->text('failure_reason')->nullable();

            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['client_id', 'website_url']);
            $table->index(['audit_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_audits');
    }
};
