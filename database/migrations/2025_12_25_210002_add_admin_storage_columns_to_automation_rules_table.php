<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automation_rules', function (Blueprint $table) {
            // Requested schema (kept alongside existing columns for backwards compatibility)
            if (! Schema::hasColumn('automation_rules', 'trigger_type')) {
                $table->string('trigger_type')->nullable()->after('trigger');
            }
            if (! Schema::hasColumn('automation_rules', 'trigger_config')) {
                $table->json('trigger_config')->nullable()->after('trigger_type');
            }
            if (! Schema::hasColumn('automation_rules', 'condition_config')) {
                $table->json('condition_config')->nullable()->after('trigger_config');
            }
            if (! Schema::hasColumn('automation_rules', 'action_config')) {
                $table->json('action_config')->nullable()->after('condition_config');
            }

            $table->index(['is_active', 'trigger_type'], 'automation_rules_active_trigger_type_idx');
        });

        // Backfill: copy existing fields into the new requested fields.
        try {
            DB::table('automation_rules')
                ->whereNull('trigger_type')
                ->update(['trigger_type' => DB::raw('`trigger`')]);
        } catch (\Throwable $e) {
            // ignore (sqlite/mysql syntax differences)
        }

        try {
            DB::table('automation_rules')
                ->whereNull('trigger_config')
                ->update(['trigger_config' => DB::raw('`trigger_meta`')]);
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            DB::table('automation_rules')
                ->whereNull('condition_config')
                ->update(['condition_config' => DB::raw('`conditions`')]);
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            DB::table('automation_rules')
                ->whereNull('action_config')
                ->update(['action_config' => DB::raw('`actions`')]);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        Schema::table('automation_rules', function (Blueprint $table) {
            if (Schema::hasColumn('automation_rules', 'action_config')) {
                $table->dropColumn('action_config');
            }
            if (Schema::hasColumn('automation_rules', 'condition_config')) {
                $table->dropColumn('condition_config');
            }
            if (Schema::hasColumn('automation_rules', 'trigger_config')) {
                $table->dropColumn('trigger_config');
            }
            if (Schema::hasColumn('automation_rules', 'trigger_type')) {
                $table->dropIndex('automation_rules_active_trigger_type_idx');
                $table->dropColumn('trigger_type');
            }
        });
    }
};
