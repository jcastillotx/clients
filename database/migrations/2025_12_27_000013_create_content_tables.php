<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $supportsFullText = in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);

        Schema::create('content_calendar', function (Blueprint $table) use ($supportsFullText) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('content_type'); // blog, social, email, video
            $table->string('platform')->nullable(); // facebook, instagram, linkedin, etc.
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('status')->default('draft'); // draft, scheduled, published, failed
            $table->longText('content_text')->nullable();
            $table->json('media_urls')->nullable();
            $table->string('hashtags')->nullable();
            $table->string('campaign_tag')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['client_id', 'scheduled_for']);
            $table->index(['client_id', 'content_type']);
            $table->index(['platform']);
            $table->index(['campaign_tag']);
            if ($supportsFullText) {
                $table->fullText(['title', 'content_text', 'hashtags', 'campaign_tag'], 'content_calendar_fulltext');
            }
        });

        Schema::create('content_templates', function (Blueprint $table) use ($supportsFullText) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('template_name');
            $table->string('template_type'); // social, email, blog, ad
            $table->longText('content')->nullable();
            $table->json('variables')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'template_type']);
            $table->index(['client_id', 'usage_count']);
            if ($supportsFullText) {
                $table->fullText(['template_name', 'content'], 'content_templates_fulltext');
            }
        });

        Schema::create('content_themes', function (Blueprint $table) use ($supportsFullText) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('theme_name');
            $table->text('description')->nullable();
            $table->string('color', 32)->nullable();
            $table->json('assigned_days')->nullable(); // weekdays
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'is_active']);
            if ($supportsFullText) {
                $table->fullText(['theme_name', 'description'], 'content_themes_fulltext');
            }
        });

        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('platform'); // facebook, instagram, linkedin, x, tiktok, pinterest
            $table->string('account_name')->nullable();
            $table->string('account_id')->nullable();
            $table->text('access_token')->nullable(); // encrypted at app layer
            $table->boolean('is_connected')->default(false);
            $table->timestamp('last_post_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'platform']);
            $table->index(['client_id', 'is_connected']);
            $table->unique(['client_id', 'platform', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
        Schema::dropIfExists('content_themes');
        Schema::dropIfExists('content_templates');
        Schema::dropIfExists('content_calendar');
    }
};
