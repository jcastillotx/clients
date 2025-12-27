<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('request_id')->nullable()->constrained('requests')->nullOnDelete();
            $table->text('testimonial_text');
            $table->unsignedTinyInteger('rating')->nullable(); // 1-5
            $table->string('author_name')->nullable();
            $table->string('author_title')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->index(['client_id', 'is_approved']);
            $table->index(['is_public']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};

