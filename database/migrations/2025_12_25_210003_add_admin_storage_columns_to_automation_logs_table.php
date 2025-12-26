<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automation_logs', function (Blueprint $table) {
            // Requested schema (kept alongside existing columns for backwards compatibility)
            if (!Schema::hasColumn('automation_logs', 'triggered_at')) {
                $table->timestamp('triggered_at')->nullable()->after('trigger');
            }
            if (!Schema::hasColumn('automation_logs', 'data')) {
                $table->json('data')->nullable()->after('triggered_at');
            }
            if (!Schema::hasColumn('automation_logs', 'error_message')) {
                $table->string('error_message')->nullable()->after('status');
            }
            if (!Schema::hasColumn('automation_logs', 'status_result')) {
                $table->enum('status_result', ['success', 'failed'])->nullable()->after('status');
                $table->index(['status_result', 'triggered_at']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('automation_logs', function (Blueprint $table) {
            if (Schema::hasColumn('automation_logs', 'status_result')) {
                $table->dropIndex(['status_result', 'triggered_at']);
                $table->dropColumn('status_result');
            }
            if (Schema::hasColumn('automation_logs', 'error_message')) {
                $table->dropColumn('error_message');
            }
            if (Schema::hasColumn('automation_logs', 'data')) {
                $table->dropColumn('data');
            }
            if (Schema::hasColumn('automation_logs', 'triggered_at')) {
                $table->dropColumn('triggered_at');
            }
        });
    }
};

