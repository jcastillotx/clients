<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_endpoint_id')->constrained('webhook_endpoints')->cascadeOnDelete();
            $table->uuid('delivery_id')->unique();
            $table->string('event_type');
            $table->json('payload');
            $table->enum('status', ['pending', 'running', 'succeeded', 'failed'])->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('next_attempt_at')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->longText('response_body')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'next_attempt_at']);
            $table->index(['webhook_endpoint_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};

