<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_task_boards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('color', 20)->default('#6366f1');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->json('settings')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_archived', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_task_boards');
    }
};
