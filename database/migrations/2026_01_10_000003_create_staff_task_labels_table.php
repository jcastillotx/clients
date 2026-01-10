<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_task_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->nullable()->constrained('staff_task_boards')->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 20)->default('#6366f1');
            $table->text('description')->nullable();
            $table->boolean('is_global')->default(false); // Available across all boards
            $table->timestamps();

            $table->index(['board_id', 'is_global']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_task_labels');
    }
};
