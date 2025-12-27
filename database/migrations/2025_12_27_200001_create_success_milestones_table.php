<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('success_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('milestone_name');
            $table->date('target_date')->nullable();
            $table->date('achieved_date')->nullable();
            $table->string('metric_value')->nullable();
            $table->string('status', 30)->default('open'); // open|achieved|missed
            $table->boolean('celebration_sent')->default(false);
            $table->timestamps();

            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('success_milestones');
    }
};

