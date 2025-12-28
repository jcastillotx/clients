<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_audit_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->text('website_url');
            $table->string('website_url_hash', 64);

            $table->string('audit_type')->default('full'); // full, seo, performance, accessibility
            $table->string('frequency')->default('weekly'); // weekly, monthly
            $table->boolean('is_active')->default(true);

            $table->unsignedInteger('max_pages')->default(50);
            $table->json('competitors')->nullable(); // array of URLs
            $table->json('recipients')->nullable();  // array of emails

            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->text('last_error')->nullable();

            $table->timestamps();

            $table->index(['client_id', 'is_active']);
            $table->index(['is_active', 'next_run_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_audit_schedules');
    }
};
