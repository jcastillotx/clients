<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposals')->cascadeOnDelete();
            $table->timestamp('viewed_at')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->unsignedInteger('time_spent_seconds')->nullable();
            $table->json('sections_viewed')->nullable();
            $table->timestamps();

            $table->index(['proposal_id', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_views');
    }
};
