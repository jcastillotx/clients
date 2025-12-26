<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The repo already has a `document_comments` table in earlier migrations.
        // Avoid creating it twice if both migration sets are present after merges.
        if (Schema::hasTable('document_comments')) {
            return;
        }

        Schema::create('document_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();

            $table->index(['document_id', 'created_at']);
            $table->index(['is_internal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_comments');
    }
};

