<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Named per spec ("audit_history"), not pluralized.
        Schema::create('audit_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->text('website_url');
            $table->string('website_url_hash', 64); // SHA-256 hash for indexing
            $table->date('audit_date');

            $table->unsignedTinyInteger('overall_score')->nullable();
            $table->unsignedTinyInteger('seo_score')->nullable();
            $table->unsignedTinyInteger('performance_score')->nullable();
            $table->unsignedTinyInteger('accessibility_score')->nullable();

            $table->unsignedInteger('total_issues')->default(0);
            $table->unsignedInteger('critical_issues')->default(0);
            $table->unsignedInteger('pages_crawled')->default(0);

            $table->timestamps();

            $table->index(['client_id', 'website_url_hash'], 'audit_history_client_url_index');
            $table->index(['website_url_hash', 'audit_date'], 'audit_history_url_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_history');
    }
};
