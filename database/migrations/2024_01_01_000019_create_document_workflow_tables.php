<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->boolean('is_internal')->default(false); // admin-only notes
            $table->timestamps();

            $table->index('document_id');
            $table->index(['document_id', 'is_internal']);
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('disk')->default('documents');
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['document_id', 'version']);
            $table->index(['document_id', 'created_at']);
        });

        // Links between a "source file" (Document or StorageFile) and an entity (Request/Invoice/Contract)
        Schema::create('document_links', function (Blueprint $table) {
            $table->id();
            $table->morphs('source'); // App\Models\Document or App\Models\StorageFile
            $table->morphs('linkable'); // Request/Invoice/Contract
            $table->string('purpose')->nullable();
            $table->timestamps();
        });

        Schema::create('document_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->morphs('subject'); // User or Role (future)
            $table->boolean('can_view')->default(true);
            $table->boolean('can_download')->default(true);
            $table->boolean('can_upload_version')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->timestamps();

            $table->unique(['document_id', 'subject_type', 'subject_id']);
            $table->index('document_id');
        });

        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->default('general'); // nda, proposal, contract, etc
            $table->longText('body')->nullable(); // text/html template
            $table->json('variables')->nullable(); // list of supported variables
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('category');
        });

        // Use existing storage_tags for tags; link tags to documents
        Schema::create('document_tag', function (Blueprint $table) {
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('storage_tag_id')->constrained('storage_tags')->cascadeOnDelete();
            $table->primary(['document_id', 'storage_tag_id']);
        });

        // External shares (temporary access links)
        Schema::create('document_shares', function (Blueprint $table) {
            $table->id();
            $table->morphs('source'); // Document or StorageFile
            $table->string('token')->unique();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('max_downloads')->nullable();
            $table->unsignedInteger('downloads')->default(0);
            $table->json('permissions')->nullable(); // e.g. {download:true}
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_shares');
        Schema::dropIfExists('document_tag');
        Schema::dropIfExists('document_templates');
        Schema::dropIfExists('document_permissions');
        Schema::dropIfExists('document_links');
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('document_comments');
    }
};
