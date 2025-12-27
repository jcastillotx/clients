<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storage_connections', function (Blueprint $table) {
            if (! Schema::hasColumn('storage_connections', 'auto_sync_enabled')) {
                $table->boolean('auto_sync_enabled')->default(true)->after('is_primary');
                $table->index('auto_sync_enabled');
            }

            if (! Schema::hasColumn('storage_connections', 'sync_frequency_minutes')) {
                $table->unsignedInteger('sync_frequency_minutes')->nullable()->after('auto_sync_enabled');
                $table->index('sync_frequency_minutes');
            }

            if (! Schema::hasColumn('storage_connections', 'conflict_strategy')) {
                $table->string('conflict_strategy')->default('prefer_primary')->after('sync_frequency_minutes');
                $table->index('conflict_strategy');
            }

            if (! Schema::hasColumn('storage_connections', 'quota_warned_80_at')) {
                $table->timestamp('quota_warned_80_at')->nullable()->after('last_synced_at');
            }

            if (! Schema::hasColumn('storage_connections', 'last_sync_failed_at')) {
                $table->timestamp('last_sync_failed_at')->nullable()->after('quota_warned_80_at');
            }

            if (! Schema::hasColumn('storage_connections', 'sync_failed_notified_at')) {
                $table->timestamp('sync_failed_notified_at')->nullable()->after('last_sync_failed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('storage_connections', function (Blueprint $table) {
            if (Schema::hasColumn('storage_connections', 'auto_sync_enabled')) {
                $table->dropColumn('auto_sync_enabled');
            }
            if (Schema::hasColumn('storage_connections', 'sync_frequency_minutes')) {
                $table->dropColumn('sync_frequency_minutes');
            }
            if (Schema::hasColumn('storage_connections', 'conflict_strategy')) {
                $table->dropColumn('conflict_strategy');
            }
            if (Schema::hasColumn('storage_connections', 'quota_warned_80_at')) {
                $table->dropColumn('quota_warned_80_at');
            }
            if (Schema::hasColumn('storage_connections', 'last_sync_failed_at')) {
                $table->dropColumn('last_sync_failed_at');
            }
            if (Schema::hasColumn('storage_connections', 'sync_failed_notified_at')) {
                $table->dropColumn('sync_failed_notified_at');
            }
        });
    }
};
