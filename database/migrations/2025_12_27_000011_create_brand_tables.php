<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $supportsFullText = in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);

        Schema::create('brand_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->date('audit_date')->default(now()->toDateString());
            $table->string('status')->default('pending'); // pending, running, completed, failed

            $table->unsignedTinyInteger('overall_score')->nullable();
            $table->unsignedTinyInteger('visual_score')->nullable();
            $table->unsignedTinyInteger('messaging_score')->nullable();
            $table->unsignedTinyInteger('consistency_score')->nullable();
            $table->unsignedTinyInteger('perception_score')->nullable();

            $table->json('report')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'audit_date']);
            $table->index(['client_id', 'status']);
        });

        Schema::create('brand_assets', function (Blueprint $table) use ($supportsFullText) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('asset_type'); // logo, color, font, image
            $table->string('asset_name')->nullable();
            $table->string('asset_value')->nullable(); // hex, font name, file path, etc.
            $table->string('usage_context')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'asset_type']);
            $table->index(['client_id', 'is_approved']);
            if ($supportsFullText) {
                $table->fullText(['asset_name', 'asset_value', 'usage_context']);
            }
        });

        Schema::create('brand_inconsistencies', function (Blueprint $table) use ($supportsFullText) {
            $table->id();
            $table->foreignId('brand_audit_id')->constrained('brand_audits')->cascadeOnDelete();
            $table->string('category')->default('visual'); // visual, messaging, tone
            $table->string('severity')->default('warning'); // critical, error, warning, info
            $table->string('location', 2048)->nullable(); // URL or platform
            $table->text('description');
            $table->text('recommendation')->nullable();
            $table->string('status')->default('open'); // open, resolved
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['brand_audit_id', 'category']);
            $table->index(['brand_audit_id', 'severity']);
            $table->index(['brand_audit_id', 'status']);
            if ($supportsFullText) {
                $table->fullText(['description', 'recommendation', 'location']);
            }
        });

        Schema::create('brand_mentions', function (Blueprint $table) use ($supportsFullText) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('platform'); // google, yelp, facebook, x, linkedin, etc.
            $table->text('mention_text');
            $table->string('sentiment')->nullable(); // positive, neutral, negative
            $table->string('author')->nullable();
            $table->string('url', 2048)->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'platform']);
            $table->index(['client_id', 'sentiment']);
            $table->index(['posted_at']);
            if ($supportsFullText) {
                $table->fullText(['mention_text', 'author', 'url']);
            }
        });

        Schema::create('brand_competitors', function (Blueprint $table) use ($supportsFullText) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('competitor_name');
            $table->string('website_url', 2048)->nullable();
            $table->text('positioning')->nullable();
            $table->text('target_audience')->nullable();
            $table->json('key_differentiators')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'is_active']);
            $table->index(['client_id', 'competitor_name']);
            if ($supportsFullText) {
                $table->fullText(['competitor_name', 'website_url', 'positioning', 'target_audience']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_competitors');
        Schema::dropIfExists('brand_mentions');
        Schema::dropIfExists('brand_inconsistencies');
        Schema::dropIfExists('brand_assets');
        Schema::dropIfExists('brand_audits');
    }
};

