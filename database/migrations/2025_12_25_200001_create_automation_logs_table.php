<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_rule_id')->nullable()->constrained('automation_rules')->nullOnDelete();
            $table->string('trigger');
            $table->enum('status', ['skipped', 'succeeded', 'failed', 'dry_run'])->default('succeeded');
            $table->string('message')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['trigger', 'status']);
            $table->index(['automation_rule_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_logs');
    }
};
