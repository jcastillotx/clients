<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Superseded by the newer messaging schema in `2025_12_26_030100_create_messaging_tables.php`.
        // This older `chat_messages` table isn't used by the current app and can conflict
        // with alternate messaging implementations. Keep it as a no-op.
        return;

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->boolean('is_from_client')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['client_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['is_from_client', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
