<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();

            // Either set per-role OR per-user.
            $table->string('role')->nullable(); // client|staff|admin|...
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();

            $table->boolean('can_view')->default(true);
            $table->boolean('can_download')->default(true);
            $table->boolean('can_upload_version')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->boolean('can_share')->default(false);

            $table->timestamps();

            $table->index(['document_id', 'role']);
            $table->index(['document_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_permissions');
    }
};

