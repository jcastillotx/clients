<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $supportsFullText = in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);

        Schema::create('marketing_assets', function (Blueprint $table) use ($supportsFullText) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('asset_name');
            $table->string('asset_type'); // image, video, document, template, font, guideline
            $table->string('file_path', 2048);
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('dimensions')->nullable(); // e.g. "1200x628"
            $table->json('tags')->nullable();
            $table->text('usage_rights')->nullable();
            $table->date('expiration_date')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_latest')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'asset_type']);
            $table->index(['client_id', 'is_latest']);
            $table->index(['expiration_date']);
            if ($supportsFullText) {
                $table->fullText(['asset_name', 'file_path', 'mime_type', 'dimensions'], 'marketing_assets_fulltext');
            }
        });

        Schema::create('reviews', function (Blueprint $table) use ($supportsFullText) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('platform'); // google, yelp, facebook, etc.
            $table->string('reviewer_name')->nullable();
            $table->unsignedTinyInteger('rating')->nullable(); // 1-5
            $table->text('review_text')->nullable();
            $table->string('review_url', 2048)->nullable();
            $table->string('sentiment')->nullable(); // positive, neutral, negative
            $table->timestamp('responded_at')->nullable();
            $table->text('response_text')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'platform']);
            $table->index(['client_id', 'sentiment']);
            $table->index(['client_id', 'rating']);
            $table->index(['responded_at']);
            if ($supportsFullText) {
                $table->fullText(['review_text', 'response_text', 'reviewer_name', 'review_url'], 'reviews_fulltext');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('marketing_assets');
    }
};
