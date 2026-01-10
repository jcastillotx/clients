<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_task_label', function (Blueprint $table) {
            $table->foreignId('task_id')->constrained('staff_tasks')->cascadeOnDelete();
            $table->foreignId('label_id')->constrained('staff_task_labels')->cascadeOnDelete();

            $table->primary(['task_id', 'label_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_task_label');
    }
};
