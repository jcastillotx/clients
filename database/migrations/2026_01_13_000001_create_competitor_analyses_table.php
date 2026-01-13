<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('competitor_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('competitor_name');
            $table->string('competitor_url')->nullable();
            $table->string('competitor_industry')->nullable();
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->json('company_overview')->nullable();
            $table->json('products_services')->nullable();
            $table->json('market_position')->nullable();
            $table->json('strengths')->nullable();
            $table->json('weaknesses')->nullable();
            $table->json('opportunities')->nullable();
            $table->json('threats')->nullable();
            $table->json('pricing_strategy')->nullable();
            $table->json('marketing_channels')->nullable();
            $table->json('target_audience')->nullable();
            $table->json('technology_stack')->nullable();
            $table->json('online_presence')->nullable();
            $table->json('content_strategy')->nullable();
            $table->json('customer_reviews')->nullable();
            $table->json('gaps_limitations')->nullable();
            $table->json('competitive_advantages')->nullable();
            $table->json('recommendations')->nullable();
            $table->json('sources')->nullable();
            $table->json('raw_response')->nullable();
            $table->text('analysis_summary')->nullable();
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->integer('processing_time_ms')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['competitor_name', 'client_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competitor_analyses');
    }
};
