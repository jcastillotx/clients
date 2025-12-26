<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The repo already creates `document_versions` in the earlier document workflow
        // migration set. Avoid creating the table twice after merges.
        if (Schema::hasTable('document_versions')) {
            return;
        }

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);

            // Storage location for this version (either local disk path or provider file id/path)
            $table->enum('provider', ['local', 'aws_s3', 'dropbox', 'google_drive'])->default('local');
            $table->string('provider_file_id')->nullable();
            $table->string('file_path')->nullable();

            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('checksum', 64)->nullable();

            // Optional plain text snapshot for comparisons (small text docs)
            $table->longText('text_snapshot')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['document_id', 'version'], 'document_versions_doc_version_uq');
            $table->index(['document_id', 'created_at']);
        });

        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'current_version_id')) {
                $table->foreignId('current_version_id')->nullable()->after('id')->constrained('document_versions')->nullOnDelete();
                $table->index(['current_version_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'current_version_id')) {
                $table->dropColumn('current_version_id');
            }
        });
        Schema::dropIfExists('document_versions');
    }
};

