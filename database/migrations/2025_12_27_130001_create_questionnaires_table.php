<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('questionnaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('questionnaire_type', 60); // e.g. brand_discovery, intake, content_brief, custom
            $table->string('title');
            $table->json('questions');
            $table->json('answers')->nullable();
            $table->string('status', 40)->default('draft'); // draft|sent|in_progress|submitted
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'questionnaire_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questionnaires');
    }
};

