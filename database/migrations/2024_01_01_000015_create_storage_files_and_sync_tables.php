<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storage_connection_id')->constrained('storage_connections')->cascadeOnDelete();
            $table->string('path'); // provider-relative path
            $table->string('filename');
            $table->string('extension')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->timestamp('modified_at')->nullable();
            $table->string('checksum')->nullable(); // optional if provider supplies
            $table->json('meta')->nullable(); // provider-specific metadata
            $table->timestamps();

            $table->unique(['storage_connection_id', 'path']);
            $table->index(['storage_connection_id', 'filename']);
            $table->index(['storage_connection_id', 'extension']);
            $table->index('modified_at');
        });

        Schema::create('storage_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->nullable(); // hex
            $table->timestamps();

            $table->unique(['client_id', 'name']);
        });

        Schema::create('storage_file_tag', function (Blueprint $table) {
            $table->foreignId('storage_file_id')->constrained('storage_files')->cascadeOnDelete();
            $table->foreignId('storage_tag_id')->constrained('storage_tags')->cascadeOnDelete();
            $table->primary(['storage_file_id', 'storage_tag_id']);
        });

        Schema::create('storage_file_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storage_file_id')->constrained('storage_files')->cascadeOnDelete();
            $table->morphs('linkable'); // Document, Request, Contract
            $table->string('purpose')->nullable(); // e.g. "attachment", "reference"
            $table->timestamps();
        });

        Schema::create('storage_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storage_connection_id')->constrained('storage_connections')->cascadeOnDelete();
            $table->enum('status', ['running', 'success', 'failed'])->default('running');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('files_scanned')->default(0);
            $table->unsignedInteger('files_added')->default(0);
            $table->unsignedInteger('files_updated')->default(0);
            $table->unsignedInteger('files_deleted')->default(0);
            $table->unsignedInteger('conflicts')->default(0);
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['storage_connection_id', 'status']);
            $table->index('started_at');
        });

        Schema::create('storage_sync_conflicts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->json('candidates'); // [{connection_id, path, checksum, size, modified_at}]
            $table->enum('resolution', ['unresolved', 'prefer_primary', 'prefer_newest', 'kept_both'])->default('unresolved');
            $table->timestamps();

            $table->index(['client_id', 'resolution']);
            $table->index('filename');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_sync_conflicts');
        Schema::dropIfExists('storage_sync_logs');
        Schema::dropIfExists('storage_file_links');
        Schema::dropIfExists('storage_file_tag');
        Schema::dropIfExists('storage_tags');
        Schema::dropIfExists('storage_files');
    }
};
