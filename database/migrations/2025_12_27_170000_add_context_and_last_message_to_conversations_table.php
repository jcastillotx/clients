<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('conversations')) {
            return;
        }

        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('conversations', 'context_type')) {
                $table->string('context_type', 80)->nullable()->after('client_id'); // request|project|general
            }
            if (!Schema::hasColumn('conversations', 'context_id')) {
                $table->unsignedBigInteger('context_id')->nullable()->after('context_type');
            }
            if (!Schema::hasColumn('conversations', 'last_message_at')) {
                $table->timestamp('last_message_at')->nullable()->after('is_closed');
            }

            $table->index(['client_id', 'context_type', 'context_id'], 'conversations_context_idx');
            $table->index(['client_id', 'last_message_at']);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('conversations')) {
            return;
        }

        Schema::table('conversations', function (Blueprint $table) {
            if (Schema::hasColumn('conversations', 'last_message_at')) {
                $table->dropIndex(['client_id', 'last_message_at']);
                $table->dropColumn('last_message_at');
            }
            if (Schema::hasColumn('conversations', 'context_id')) {
                $table->dropIndex('conversations_context_idx');
                $table->dropColumn('context_id');
            }
            if (Schema::hasColumn('conversations', 'context_type')) {
                $table->dropColumn('context_type');
            }
        });
    }
};

