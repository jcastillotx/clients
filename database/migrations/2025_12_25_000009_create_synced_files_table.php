<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('synced_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storage_connection_id')->constrained('storage_connections')->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();

            $table->string('provider_file_id')->index();
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('mime_type')->nullable();

            $table->timestamp('last_modified_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->enum('sync_status', ['pending', 'synced', 'error'])->default('pending');

            $table->timestamps();

            $table->index(['storage_connection_id', 'sync_status']);
            $table->index(['document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('synced_files');
    }
};

