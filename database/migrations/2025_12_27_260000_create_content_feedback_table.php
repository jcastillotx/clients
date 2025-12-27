<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('content_calendar')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('feedback_text');
            $table->string('feedback_type')->default('comment'); // comment, revision_request, approval_note
            $table->boolean('is_resolved')->default(false);
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['content_id', 'is_resolved']);
            $table->index(['user_id']);
            $table->index(['feedback_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_feedback');
    }
};
