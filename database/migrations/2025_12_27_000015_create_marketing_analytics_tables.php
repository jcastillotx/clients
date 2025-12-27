<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $supportsFullText = in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);

        Schema::create('marketing_metrics', function (Blueprint $table) use ($supportsFullText) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->date('metric_date');
            $table->string('metric_type')->nullable(); // e.g. ga4, gsc, ads, social, email
            $table->string('source')->nullable(); // website, seo, social, email, ads
            $table->string('metric_name');
            $table->decimal('metric_value', 18, 4)->nullable();
            $table->string('metric_value_text')->nullable(); // for non-numeric
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'metric_date']);
            $table->index(['client_id', 'source']);
            $table->index(['client_id', 'metric_name']);
            $table->unique(['client_id', 'metric_date', 'metric_type', 'source', 'metric_name']);

            if ($supportsFullText) {
                $table->fullText(['metric_name', 'metric_value_text']);
            }
        });

        Schema::create('custom_dashboards', function (Blueprint $table) use ($supportsFullText) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('dashboard_name');
            $table->json('configuration');
            $table->boolean('is_default')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'is_default']);
            $table->index(['client_id', 'dashboard_name']);
            if ($supportsFullText) {
                $table->fullText(['dashboard_name']);
            }
        });

        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('report_name');
            $table->string('report_type'); // marketing, seo, social, etc.
            $table->string('frequency')->default('monthly'); // daily, weekly, monthly
            $table->json('recipients')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('last_error')->nullable();
            $table->json('template')->nullable(); // report layout/config
            $table->timestamps();

            $table->index(['is_active', 'next_run_at']);
            $table->index(['client_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_reports');
        Schema::dropIfExists('custom_dashboards');
        Schema::dropIfExists('marketing_metrics');
    }
};

