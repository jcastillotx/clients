<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
            $table->string('provider')->default('openai')->index();
            $table->string('model')->nullable();
            $table->string('content_hash', 64)->index();
            $table->json('embedding'); // array<float>
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['request_id', 'provider', 'model', 'content_hash'], 'req_embed_unique_v1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_embeddings');
    }
};

