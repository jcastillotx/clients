<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_template_id')->constrained('report_templates')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('frequency', ['daily', 'weekly', 'monthly'])->default('weekly');
            $table->json('recipients');
            $table->boolean('is_active')->default(true);
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'next_run_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_schedules');
    }
};
