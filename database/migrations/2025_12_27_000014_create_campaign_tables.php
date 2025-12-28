<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $supportsFullText = in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);

        Schema::create('campaigns', function (Blueprint $table) use ($supportsFullText) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('campaign_name');
            $table->string('campaign_type'); // social, email, ppc, content, seo, launch, event, seasonal
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('budget', 12, 2)->nullable();
            $table->json('goals')->nullable();
            $table->json('target_metrics')->nullable();
            $table->string('status')->default('planning'); // planning, active, paused, completed
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['client_id', 'campaign_type']);
            $table->index(['start_date', 'end_date']);
            if ($supportsFullText) {
                $table->fullText(['campaign_name', 'description']);
            }
        });

        Schema::create('campaign_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->string('asset_type'); // polymorphic type
            $table->unsignedBigInteger('asset_id');
            $table->boolean('is_primary')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'is_primary']);
            $table->index(['asset_type', 'asset_id']);
            $table->unique(['campaign_id', 'asset_type', 'asset_id']);
        });

        Schema::create('campaign_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->date('metric_date');
            $table->string('channel')->nullable(); // ads, social, email, website, etc.
            $table->unsignedBigInteger('impressions')->nullable();
            $table->unsignedBigInteger('clicks')->nullable();
            $table->unsignedBigInteger('conversions')->nullable();
            $table->decimal('spend', 12, 2)->nullable();
            $table->decimal('revenue', 12, 2)->nullable();
            $table->decimal('roi', 8, 2)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'metric_date']);
            $table->index(['campaign_id', 'channel']);
            $table->unique(['campaign_id', 'metric_date', 'channel']);
        });

        Schema::create('campaign_links', function (Blueprint $table) use ($supportsFullText) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->text('original_url');
            $table->string('original_url_hash', 64);
            $table->text('short_url')->nullable();
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 50)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->unsignedBigInteger('clicks')->default(0);
            $table->unsignedBigInteger('conversions')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['campaign_id']);
            $table->index(['utm_source', 'utm_medium']);
            $table->index(['utm_campaign']);
            $table->unique(['campaign_id', 'original_url_hash', 'utm_source', 'utm_medium', 'utm_campaign'], 'campaign_links_unique');
            if ($supportsFullText) {
                $table->fullText(['original_url', 'short_url', 'utm_source', 'utm_medium', 'utm_campaign'], 'campaign_links_fulltext');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_links');
        Schema::dropIfExists('campaign_metrics');
        Schema::dropIfExists('campaign_assets');
        Schema::dropIfExists('campaigns');
    }
};
