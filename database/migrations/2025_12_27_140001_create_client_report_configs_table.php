<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_report_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->json('visible_metrics')->nullable();
            $table->string('report_frequency', 30)->default('monthly'); // daily|weekly|monthly|quarterly
            $table->string('delivery_method', 30)->default('email'); // email|portal|both
            $table->json('recipients')->nullable();
            $table->json('custom_branding')->nullable();
            $table->timestamps();

            $table->unique('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_report_configs');
    }
};
