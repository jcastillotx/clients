<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
            $table->decimal('budget_hours', 10, 2)->nullable();
            $table->decimal('budget_amount', 12, 2)->nullable();
            $table->decimal('spent_hours', 10, 2)->default(0);
            $table->decimal('spent_amount', 12, 2)->default(0);
            $table->boolean('is_exceeded')->default(false);
            $table->timestamps();

            $table->unique('request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_budgets');
    }
};

