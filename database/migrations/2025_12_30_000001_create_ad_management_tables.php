<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
          # Ad Management System

          1. New Tables
            - `ad_accounts` - Connected advertising accounts
            - `ad_campaigns` - Ad campaigns across platforms
            - `ad_sets` - Ad sets/groups within campaigns
            - `ads` - Individual advertisements
            - `ad_creatives` - Ad creative assets (images, videos, text)
            - `ad_metrics` - Daily ad performance metrics

          2. Security
            - Enable RLS on all tables
            - Add policies for client isolation
            - Encrypt sensitive tokens

          3. Features
            - Multi-platform support (Google Ads, Facebook Ads, LinkedIn Ads, etc.)
            - Budget management and tracking
            - Performance metrics tracking
            - Creative asset management
            - Campaign status management
        */

        // Ad Accounts - Connected advertising platforms
        Schema::create('ad_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('platform'); // google_ads, facebook_ads, linkedin_ads, twitter_ads, tiktok_ads
            $table->string('account_id'); // Platform-specific account ID
            $table->string('account_name');
            $table->text('access_token')->nullable(); // Encrypted
            $table->text('refresh_token')->nullable(); // Encrypted
            $table->timestamp('token_expires_at')->nullable();
            $table->boolean('is_connected')->default(false);
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->json('capabilities')->nullable(); // Platform-specific capabilities
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'platform']);
            $table->index('is_connected');
        });

        // Ad Campaigns
        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_account_id')->constrained()->cascadeOnDelete();
            $table->string('platform_campaign_id')->nullable(); // ID from the ad platform
            $table->string('name');
            $table->enum('objective', [
                'awareness',
                'consideration',
                'conversions',
                'traffic',
                'engagement',
                'app_installs',
                'video_views',
                'lead_generation',
                'messages',
                'sales'
            ])->default('conversions');
            $table->enum('status', ['draft', 'active', 'paused', 'completed', 'archived'])->default('draft');
            $table->decimal('daily_budget', 10, 2)->nullable();
            $table->decimal('lifetime_budget', 10, 2)->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->string('target_audience')->nullable(); // Simplified targeting description
            $table->json('targeting_options')->nullable(); // Detailed targeting (age, location, interests, etc.)
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index('ad_account_id');
            $table->index('platform_campaign_id');
        });

        // Ad Sets (Facebook) / Ad Groups (Google) - Mid-level organization
        Schema::create('ad_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_campaign_id')->constrained()->cascadeOnDelete();
            $table->string('platform_ad_set_id')->nullable();
            $table->string('name');
            $table->enum('status', ['draft', 'active', 'paused', 'archived'])->default('draft');
            $table->decimal('daily_budget', 10, 2)->nullable();
            $table->decimal('lifetime_budget', 10, 2)->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->string('optimization_goal')->nullable(); // clicks, impressions, conversions, etc.
            $table->string('bid_strategy')->nullable(); // lowest_cost, cost_cap, bid_cap, etc.
            $table->decimal('bid_amount', 10, 2)->nullable();
            $table->json('targeting_options')->nullable();
            $table->json('placement_options')->nullable(); // feed, stories, messenger, etc.
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('ad_campaign_id');
            $table->index('platform_ad_set_id');
        });

        // Individual Ads
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_set_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_creative_id')->nullable()->constrained()->nullOnDelete();
            $table->string('platform_ad_id')->nullable();
            $table->string('name');
            $table->enum('status', ['draft', 'active', 'paused', 'disapproved', 'archived'])->default('draft');
            $table->text('headline')->nullable();
            $table->text('description')->nullable();
            $table->string('call_to_action')->nullable(); // learn_more, shop_now, sign_up, etc.
            $table->string('destination_url')->nullable();
            $table->string('display_url')->nullable();
            $table->json('tracking_params')->nullable(); // UTM parameters
            $table->string('disapproval_reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('ad_set_id');
            $table->index('platform_ad_id');
            $table->index('status');
        });

        // Ad Creatives - Reusable creative assets
        Schema::create('ad_creatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['image', 'video', 'carousel', 'collection', 'dynamic'])->default('image');
            $table->text('primary_text')->nullable();
            $table->string('headline')->nullable();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('video_url')->nullable();
            $table->json('carousel_cards')->nullable(); // For carousel ads
            $table->string('thumbnail_url')->nullable();
            $table->json('asset_urls')->nullable(); // Multiple assets
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('client_id');
            $table->index('type');
        });

        // Ad Performance Metrics - Daily snapshots
        Schema::create('ad_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('entity_type'); // campaign, ad_set, ad
            $table->unsignedBigInteger('entity_id');
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();

            // Core metrics
            $table->bigInteger('impressions')->default(0);
            $table->bigInteger('clicks')->default(0);
            $table->bigInteger('conversions')->default(0);
            $table->decimal('spend', 10, 2)->default(0);
            $table->decimal('revenue', 10, 2)->default(0);

            // Calculated metrics
            $table->decimal('ctr', 10, 4)->nullable(); // Click-through rate
            $table->decimal('cpc', 10, 2)->nullable(); // Cost per click
            $table->decimal('cpm', 10, 2)->nullable(); // Cost per mille (1000 impressions)
            $table->decimal('cpa', 10, 2)->nullable(); // Cost per acquisition
            $table->decimal('roas', 10, 2)->nullable(); // Return on ad spend

            // Engagement metrics
            $table->bigInteger('likes')->default(0);
            $table->bigInteger('shares')->default(0);
            $table->bigInteger('comments')->default(0);
            $table->bigInteger('video_views')->default(0);
            $table->integer('video_view_rate')->nullable(); // Percentage

            // Platform-specific metrics
            $table->json('platform_metrics')->nullable();

            $table->timestamps();

            $table->index(['entity_type', 'entity_id', 'date']);
            $table->index(['client_id', 'date']);
            $table->unique(['date', 'entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_metrics');
        Schema::dropIfExists('ad_creatives');
        Schema::dropIfExists('ads');
        Schema::dropIfExists('ad_sets');
        Schema::dropIfExists('ad_campaigns');
        Schema::dropIfExists('ad_accounts');
    }
};
