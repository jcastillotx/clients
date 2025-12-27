<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('request_id')->nullable()->constrained('requests')->nullOnDelete();
            $table->string('title');
            $table->string('meeting_type', 40)->default('kickoff'); // kickoff|strategy|review|other
            $table->string('status', 30)->default('requested'); // requested|scheduled|completed|cancelled
            $table->timestamp('scheduled_at')->nullable();
            $table->unsignedInteger('duration_minutes')->default(30);
            $table->string('meeting_link')->nullable();
            $table->json('attendees')->nullable();
            $table->text('agenda')->nullable();
            $table->text('notes')->nullable();
            $table->string('recording_url')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['request_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
