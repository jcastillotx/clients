<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('request_id')->nullable()->constrained('requests')->nullOnDelete();
            $table->enum('type', ['project_completion', 'satisfaction']);
            $table->json('responses')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['client_id', 'type']);
            $table->index(['request_id']);
            $table->index(['submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};

