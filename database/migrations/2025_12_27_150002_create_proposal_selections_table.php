<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('proposal_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposals')->cascadeOnDelete();
            $table->string('selected_tier', 40)->nullable(); // good|better|best|custom
            $table->json('selected_addons')->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->timestamps();

            $table->index(['proposal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_selections');
    }
};

