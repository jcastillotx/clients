<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_notes', function (Blueprint $table) {
            $table->id();
            $table->string('notable_type');
            $table->unsignedBigInteger('notable_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note');
            $table->boolean('is_private')->default(true);
            $table->timestamp('created_at')->nullable();

            $table->index(['notable_type', 'notable_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['is_private']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_notes');
    }
};

