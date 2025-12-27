<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            // Trigger key, e.g. "request.created", "schedule.daily"
            $table->string('trigger');
            $table->json('trigger_meta')->nullable();

            // Conditions DSL (AND/OR groups, dot-path selectors)
            $table->json('conditions')->nullable();

            // Array of actions to perform
            $table->json('actions');

            $table->unsignedInteger('run_order')->default(100);
            $table->timestamp('last_ran_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['is_active', 'trigger', 'run_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_rules');
    }
};
