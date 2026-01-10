<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_task_assignees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('staff_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 30)->default('assignee'); // assignee, reviewer, watcher
            $table->timestamp('assigned_at')->useCurrent();

            $table->unique(['task_id', 'user_id']);
            $table->index(['user_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_task_assignees');
    }
};
