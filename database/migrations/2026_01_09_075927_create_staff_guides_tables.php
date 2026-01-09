<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_guide_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->default('fas fa-book');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('staff_guides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('staff_guide_categories')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('content');
            $table->json('checklist')->nullable();
            $table->string('service_tier')->nullable(); // e.g., 'local_seo', 'growth_seo', 'authority_seo'
            $table->decimal('price', 10, 2)->nullable();
            $table->string('commitment')->nullable(); // e.g., '3-month minimum'
            $table->boolean('is_published')->default(true);
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['category_id', 'is_published']);
            $table->index('service_tier');
        });

        Schema::create('staff_guide_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guide_id')->constrained('staff_guides')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['guide_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_guide_views');
        Schema::dropIfExists('staff_guides');
        Schema::dropIfExists('staff_guide_categories');
    }
};
