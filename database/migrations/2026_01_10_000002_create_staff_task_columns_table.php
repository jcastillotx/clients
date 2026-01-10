<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_task_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained('staff_task_boards')->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 20)->default('#94a3b8');
            $table->string('icon', 50)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('wip_limit')->nullable(); // Work in progress limit
            $table->boolean('is_done_column')->default(false); // Marks tasks as completed
            $table->timestamps();

            $table->index(['board_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_task_columns');
    }
};
