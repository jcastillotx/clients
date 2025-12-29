<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // e.g., 'onboarding', 'service_request', 'meeting'
            $table->string('name'); // Display name
            $table->text('description')->nullable();
            $table->json('fields'); // Array of field definitions
            $table->json('baseline_fields')->nullable(); // Keys of fields that cannot be deleted
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_templates');
    }
};
