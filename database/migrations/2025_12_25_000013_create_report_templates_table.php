<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('config')->nullable();
            $table->json('recipients')->nullable();
            $table->enum('schedule', ['none', 'daily', 'weekly', 'monthly'])->default('none');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('next_send_at')->nullable();
            $table->timestamps();

            $table->index(['schedule', 'is_active']);
            $table->index(['next_send_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_templates');
    }
};

