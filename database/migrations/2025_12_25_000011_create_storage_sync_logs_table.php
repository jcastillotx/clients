<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // This project already defines `storage_sync_logs` in an earlier migration
        // (`2024_01_01_000015_create_storage_files_and_sync_tables.php`).
        // When both sets exist (e.g. after a merge), avoid creating it twice.
        if (Schema::hasTable('storage_sync_logs')) {
            return;
        }

        Schema::create('storage_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storage_connection_id')->constrained('storage_connections')->cascadeOnDelete();
            $table->enum('status', ['running', 'success', 'error'])->default('running');
            $table->unsignedInteger('files_processed')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['storage_connection_id', 'status']);
            $table->index(['started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_sync_logs');
    }
};
