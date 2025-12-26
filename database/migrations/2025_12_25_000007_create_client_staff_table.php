<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('relationship')->default('account_manager'); // account_manager|support|etc
            $table->timestamps();

            $table->unique(['client_id', 'user_id'], 'client_staff_client_user_uq');
            $table->index(['user_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_staff');
    }
};

