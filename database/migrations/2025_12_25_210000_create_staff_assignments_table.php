<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->enum('role', ['account_manager', 'project_lead']);
            $table->timestamp('created_at')->nullable();

            $table->unique(['staff_user_id', 'client_id', 'role']);
            $table->index(['client_id', 'role']);
            $table->index(['staff_user_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_assignments');
    }
};
