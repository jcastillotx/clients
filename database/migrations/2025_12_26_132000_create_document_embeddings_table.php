<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('provider')->default('openai')->index();
            $table->string('model')->nullable();
            $table->string('content_hash', 64)->index();
            $table->json('embedding'); // array<float>
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['document_id', 'provider', 'model', 'content_hash'], 'doc_embed_unique_v1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_embeddings');
    }
};
