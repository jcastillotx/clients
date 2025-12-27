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
            $table->string('website_url', 2048);
            $table->date('audit_date');

            $table->unsignedTinyInteger('overall_score')->nullable();
            $table->unsignedTinyInteger('seo_score')->nullable();
            $table->unsignedTinyInteger('performance_score')->nullable();
            $table->unsignedTinyInteger('accessibility_score')->nullable();

            $table->unsignedInteger('total_issues')->default(0);
            $table->unsignedInteger('critical_issues')->default(0);
            $table->unsignedInteger('pages_crawled')->default(0);

            $table->timestamps();

            $table->index(['client_id', 'website_url']);
            $table->index(['website_url', 'audit_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_history');
    }
};

