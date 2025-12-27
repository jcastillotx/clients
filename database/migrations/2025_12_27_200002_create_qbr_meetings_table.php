<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('qbr_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->date('scheduled_date')->nullable();
            $table->string('presentation_url', 2048)->nullable();
            $table->text('notes')->nullable();
            $table->json('action_items')->nullable();
            $table->date('next_qbr_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'scheduled_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qbr_meetings');
    }
};

