<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('industry_insights', function (Blueprint $table) {
            $table->id();
            $table->string('industry', 120)->nullable();
            $table->string('insight_type', 60)->default('news'); // news|trend|report|alert
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['industry', 'insight_type']);
            $table->index(['published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('industry_insights');
    }
};
