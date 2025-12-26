<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active'); // active|paused|completed|cancelled
            $table->unsignedTinyInteger('progress_percent')->nullable(); // optional override
            $table->decimal('budget_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['client_id', 'status']);
        });

        Schema::create('project_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name');
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'sort_order']);
        });

        Schema::create('project_deliverables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_done')->default(false);
            $table->date('due_date')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'is_done']);
        });

        Schema::create('project_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->nullable(); // dev, pm, designer, etc.
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
        });

        Schema::create('project_cost_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('description');
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('occurred_on')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'occurred_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_cost_entries');
        Schema::dropIfExists('project_team_members');
        Schema::dropIfExists('project_deliverables');
        Schema::dropIfExists('project_milestones');
        Schema::dropIfExists('projects');
    }
};

