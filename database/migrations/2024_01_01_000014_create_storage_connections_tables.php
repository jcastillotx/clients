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
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('provider'); // s3, dropbox, drive, custom
            $table->string('name'); // user-friendly label
            $table->string('disk')->nullable(); // Laravel filesystem disk name
            $table->enum('status', ['active', 'error', 'disconnected'])->default('disconnected');
            $table->boolean('is_primary')->default(false);
            $table->unsignedBigInteger('used_bytes')->default(0);
            $table->unsignedBigInteger('quota_bytes')->nullable(); // null => unlimited/unknown
            $table->timestamp('last_sync_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('settings')->nullable(); // folders, conflict rules, etc.
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'provider']);
            $table->index(['client_id', 'status']);
            $table->index(['client_id', 'is_primary']);
        });

        Schema::create('client_storage_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('auto_sync_enabled')->default(true);
            $table->enum('auto_sync_frequency', ['hourly', 'daily', 'weekly'])->default('daily');
            $table->enum('conflict_rule', ['prefer_primary', 'prefer_newest', 'keep_both'])->default('prefer_primary');
            $table->unsignedTinyInteger('quota_alert_percent')->default(80);
            $table->boolean('backup_enabled')->default(false);
            $table->foreignId('backup_connection_id')->nullable()->constrained('storage_connections')->nullOnDelete();
            $table->json('folders')->nullable(); // array of folders to sync (optional)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_storage_settings');
        Schema::dropIfExists('storage_connections');
    }
};
