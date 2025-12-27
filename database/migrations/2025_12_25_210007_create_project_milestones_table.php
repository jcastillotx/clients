<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Superseded by the newer project schema in `2025_12_26_030000_create_projects_tables.php`,
        // which defines `project_milestones` as milestones for `projects` (not `requests`).
        // Keeping both would cause migration conflicts (same table name, different columns).
        return;

        Schema::create('project_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'blocked'])->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['request_id', 'status']);
            $table->index(['due_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_milestones');
    }
};
