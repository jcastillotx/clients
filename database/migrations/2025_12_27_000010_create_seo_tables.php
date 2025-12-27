<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $supportsFullText = in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);

        Schema::create('seo_keywords', function (Blueprint $table) use ($supportsFullText) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('website_url', 2048);
            $table->string('keyword', 255);

            $table->unsignedInteger('search_volume')->nullable();
            $table->unsignedTinyInteger('difficulty')->nullable(); // 0-100
            $table->decimal('cpc', 10, 2)->nullable();

            $table->unsignedSmallInteger('current_position')->nullable();
            $table->unsignedSmallInteger('target_position')->nullable();
            $table->boolean('tracking_enabled')->default(true);

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'tracking_enabled']);
            $table->index(['client_id', 'website_url']);
            $table->index(['website_url']);
            $table->index(['keyword']);
            $table->unique(['client_id', 'website_url', 'keyword']);

            if ($supportsFullText) {
                $table->fullText(['keyword']);
            }
        });

        Schema::create('keyword_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seo_keyword_id')->constrained('seo_keywords')->cascadeOnDelete();

            $table->unsignedSmallInteger('position')->nullable();
            $table->string('url_ranking', 2048)->nullable();

            $table->string('search_engine')->default('google'); // google, bing
            $table->string('location')->nullable(); // e.g. "Austin, TX" or ISO region
            $table->string('device')->default('desktop'); // desktop, mobile

            $table->timestamp('tracked_at');
            $table->timestamps();

            $table->index(['seo_keyword_id', 'tracked_at']);
            $table->index(['search_engine', 'location', 'device']);
            $table->unique(['seo_keyword_id', 'search_engine', 'location', 'device', 'tracked_at']);
        });

        Schema::create('backlinks', function (Blueprint $table) use ($supportsFullText) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();

            $table->string('source_url', 2048);
            $table->string('target_url', 2048);
            $table->string('anchor_text')->nullable();

            $table->unsignedTinyInteger('domain_authority')->nullable(); // 0-100 (best-effort)
            $table->string('link_type')->default('dofollow'); // dofollow, nofollow
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->string('status')->default('active'); // active, lost

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['client_id', 'target_url']);
            $table->index(['target_url']);
            $table->index(['source_url']);
            $table->index(['last_checked_at']);
            $table->unique(['source_url', 'target_url']);

            if ($supportsFullText) {
                $table->fullText(['source_url', 'target_url', 'anchor_text']);
            }
        });

        Schema::create('seo_recommendations', function (Blueprint $table) use ($supportsFullText) {
            $table->id();
            $table->foreignId('website_audit_id')->nullable()->constrained('website_audits')->nullOnDelete();

            $table->string('category')->default('seo'); // seo, content, technical, links
            $table->string('priority')->default('medium'); // critical, high, medium, low

            $table->string('title', 255);
            $table->text('description');
            $table->string('impact_estimate')->nullable(); // e.g. "high", "medium", "low" or % range
            $table->string('implementation_effort')->nullable(); // S/M/L

            $table->string('status')->default('pending'); // pending, in_progress, completed
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['website_audit_id', 'priority']);
            $table->index(['website_audit_id', 'status']);
            $table->index(['assigned_to', 'status']);

            if ($supportsFullText) {
                $table->fullText(['title', 'description']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_recommendations');
        Schema::dropIfExists('backlinks');
        Schema::dropIfExists('keyword_rankings');
        Schema::dropIfExists('seo_keywords');
    }
};

