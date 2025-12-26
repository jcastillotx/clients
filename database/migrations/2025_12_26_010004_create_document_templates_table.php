<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The repo already creates `document_templates` in the earlier document workflow
        // migration set. Avoid creating the table twice after merges.
        if (Schema::hasTable('document_templates')) {
            return;
        }

        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['contract', 'proposal', 'nda', 'other'])->default('other');
            $table->longText('content');
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('default_provider')->default('local'); // local|aws_s3|dropbox|google_drive
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_templates');
    }
};

