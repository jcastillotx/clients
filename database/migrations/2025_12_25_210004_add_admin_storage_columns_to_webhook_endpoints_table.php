<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_endpoints', function (Blueprint $table) {
            // Requested schema fields (kept alongside existing columns)
            if (!Schema::hasColumn('webhook_endpoints', 'url')) {
                $table->string('url')->nullable()->after('event_type');
            }
            if (!Schema::hasColumn('webhook_endpoints', 'last_triggered_at')) {
                $table->timestamp('last_triggered_at')->nullable()->after('is_active');
            }

            $table->index(['client_id', 'event_type', 'is_active', 'last_triggered_at'], 'webhook_endpoints_client_event_active_last_idx');
        });
    }

    public function down(): void
    {
        Schema::table('webhook_endpoints', function (Blueprint $table) {
            if (Schema::hasColumn('webhook_endpoints', 'last_triggered_at')) {
                $table->dropIndex('webhook_endpoints_client_event_active_last_idx');
                $table->dropColumn('last_triggered_at');
            }
            if (Schema::hasColumn('webhook_endpoints', 'url')) {
                $table->dropColumn('url');
            }
        });
    }
};

