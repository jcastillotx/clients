<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $supportsFullText = in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);

        Schema::create('brand_guides', function (Blueprint $table) use ($supportsFullText) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('status')->default('draft'); // draft, published

            $table->string('cover_image', 2048)->nullable();
            $table->string('slug')->unique();
            $table->boolean('is_public')->default(false);
            $table->boolean('password_protected')->default(false);
            $table->string('password')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['client_id', 'is_public']);
            if ($supportsFullText) {
                $table->fullText(['slug']);
            }
        });

        Schema::create('brand_guide_sections', function (Blueprint $table) use ($supportsFullText) {
            $table->id();
            $table->foreignId('brand_guide_id')->constrained('brand_guides')->cascadeOnDelete();
            $table->string('section_type'); // story, logo, colors, typography, imagery, voice, digital, print, social, elements
            $table->unsignedInteger('section_order')->default(0);
            $table->string('title')->nullable();
            $table->json('content')->nullable(); // rich JSON (structured)
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index(['brand_guide_id', 'section_type']);
            $table->index(['brand_guide_id', 'section_order']);
            if ($supportsFullText) {
                $table->fullText(['title']);
            }
        });

        Schema::create('brand_colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_guide_id')->constrained('brand_guides')->cascadeOnDelete();
            $table->string('color_name')->nullable();
            $table->string('color_type')->default('primary'); // primary, secondary, accent
            $table->string('hex_value', 16)->nullable();
            $table->string('rgb_value', 32)->nullable();
            $table->string('cmyk_value', 32)->nullable();
            $table->string('pantone_value', 32)->nullable();
            $table->string('usage_context')->nullable();
            $table->text('accessibility_notes')->nullable();
            $table->timestamps();

            $table->index(['brand_guide_id', 'color_type']);
            $table->index(['hex_value']);
        });

        Schema::create('brand_fonts', function (Blueprint $table) use ($supportsFullText) {
            $table->id();
            $table->foreignId('brand_guide_id')->constrained('brand_guides')->cascadeOnDelete();
            $table->string('font_name');
            $table->string('font_category')->default('primary'); // primary, secondary
            $table->json('font_weights')->nullable();
            $table->string('font_file_path', 2048)->nullable();
            $table->string('web_font_url', 2048)->nullable();
            $table->string('usage_context')->nullable();
            $table->text('licensing_info')->nullable();
            $table->timestamps();

            $table->index(['brand_guide_id', 'font_category']);
            $table->index(['font_name']);
            if ($supportsFullText) {
                $table->fullText(['font_name', 'usage_context', 'licensing_info']);
            }
        });

        Schema::create('brand_templates', function (Blueprint $table) use ($supportsFullText) {
            $table->id();
            $table->foreignId('brand_guide_id')->constrained('brand_guides')->cascadeOnDelete();
            $table->string('template_name');
            $table->string('template_type'); // email, social, print, presentation, ad, etc.
            $table->string('file_path', 2048);
            $table->string('thumbnail', 2048)->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->boolean('is_public')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['brand_guide_id', 'template_type']);
            $table->index(['is_public']);
            if ($supportsFullText) {
                $table->fullText(['template_name', 'template_type', 'file_path']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_templates');
        Schema::dropIfExists('brand_fonts');
        Schema::dropIfExists('brand_colors');
        Schema::dropIfExists('brand_guide_sections');
        Schema::dropIfExists('brand_guides');
    }
};

