<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('account_health', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->unsignedTinyInteger('health_score')->nullable(); // 0-100
            $table->unsignedTinyInteger('engagement_score')->nullable(); // 0-100
            $table->unsignedTinyInteger('satisfaction_score')->nullable(); // 0-100
            $table->string('revenue_trend', 30)->nullable(); // up|flat|down
            $table->decimal('growth_rate', 8, 2)->nullable();
            $table->json('risk_factors')->nullable();
            $table->json('opportunities')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_health');
    }
};

