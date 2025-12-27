<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('white_label_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('custom_domain')->nullable()->unique(); // e.g. reports.clientdomain.com
            $table->string('logo_url')->nullable();
            $table->string('primary_color', 20)->nullable();
            $table->string('secondary_color', 20)->nullable();
            $table->string('font_family', 120)->nullable();
            $table->string('company_name')->nullable();
            $table->text('footer_text')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index(['client_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('white_label_configs');
    }
};
