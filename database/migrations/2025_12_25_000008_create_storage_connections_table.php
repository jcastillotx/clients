<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->enum('provider', ['aws_s3', 'dropbox', 'google_drive']);
            $table->json('credentials')->nullable(); // encrypted via model cast
            $table->enum('status', ['connected', 'disconnected', 'error'])->default('disconnected');
            $table->unsignedBigInteger('storage_used')->default(0);
            $table->unsignedBigInteger('storage_limit')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'provider']);
            $table->index(['client_id', 'is_primary']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_connections');
    }
};

