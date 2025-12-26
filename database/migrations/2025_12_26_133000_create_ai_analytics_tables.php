<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_health_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->unsignedSmallInteger('score')->index(); // 0-100
            $table->decimal('churn_probability', 5, 4)->nullable(); // 0.0000 - 1.0000
            $table->string('risk_level')->nullable(); // low|medium|high
            $table->json('breakdown')->nullable();
            $table->timestamp('computed_at')->useCurrent()->index();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['client_id', 'computed_at']);
        });

        Schema::create('ai_insight_reports', function (Blueprint $table) {
            $table->id();
            $table->string('kind')->index(); // weekly_trends|monthly_forecast|quarterly_bi|market_analysis|ad_hoc
            $table->date('period_start')->nullable()->index();
            $table->date('period_end')->nullable()->index();
            $table->json('payload')->nullable();
            $table->longText('narrative')->nullable();
            $table->string('provider_used')->nullable();
            $table->string('model_used')->nullable();
            $table->decimal('cost', 10, 6)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('anomaly_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index(); // activity_drop|payment_delay|volume_spike|timeline_overrun|health_drop
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning')->index();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('title');
            $table->text('message')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['client_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anomaly_alerts');
        Schema::dropIfExists('ai_insight_reports');
        Schema::dropIfExists('client_health_snapshots');
    }
};

