<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_audit_id')->constrained('website_audits')->cascadeOnDelete();

            $table->text('url');
            $table->string('url_hash', 64); // SHA-256 hash for unique constraint
            $table->string('title')->nullable();
            $table->text('meta_description')->nullable();

            $table->string('h1_tag')->nullable();
            $table->unsignedInteger('word_count')->nullable();

            $table->unsignedInteger('load_time_ms')->nullable();
            $table->unsignedInteger('page_size_kb')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();

            $table->boolean('has_canonical')->default(false);
            $table->boolean('has_schema')->default(false);
            $table->boolean('mobile_friendly')->nullable();

            $table->json('headers')->nullable(); // H1-H6 structure, etc.
            $table->json('links')->nullable();   // internal/external links summary
            $table->json('images')->nullable();  // images + alt text summary

            $table->timestamps();

            $table->unique(['website_audit_id', 'url_hash'], 'audit_pages_audit_url_hash_unique');
            $table->index(['website_audit_id', 'status_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_pages');
    }
};
