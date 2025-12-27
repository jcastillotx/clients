<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('onboarding_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('onboarding_workflow_id')->constrained('onboarding_workflows')->cascadeOnDelete();
            $table->string('task_name');
            $table->string('task_type', 60)->nullable(); // checklist|document|questionnaire|meeting|setup|other
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 40)->default('pending'); // pending|in_progress|completed|blocked
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['onboarding_workflow_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_tasks');
    }
};

