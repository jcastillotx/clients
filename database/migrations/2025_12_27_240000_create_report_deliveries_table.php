<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_schedule_id')->nullable()->constrained('report_schedules')->nullOnDelete();
            $table->foreignId('report_template_id')->nullable()->constrained('report_templates')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('category', 80)->nullable();
            $table->json('meta')->nullable(); // date range, filters, etc.
            $table->string('disk')->default('reports');
            $table->string('path');
            $table->json('recipients')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('status', 30)->default('generated'); // generated|sent|failed
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'generated_at']);
            $table->index(['report_schedule_id', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_deliveries');
    }
};
