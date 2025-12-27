<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('competitor_monitoring', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competitor_id')->constrained('brand_competitors')->cascadeOnDelete();
            $table->timestamp('monitored_at')->nullable();
            $table->json('changes_detected')->nullable();
            $table->boolean('alert_sent')->default(false);
            $table->timestamps();

            $table->index(['competitor_id', 'monitored_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitor_monitoring');
    }
};

