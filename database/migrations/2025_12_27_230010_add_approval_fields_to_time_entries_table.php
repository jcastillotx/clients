<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('time_entries')) {
            return;
        }

        Schema::table('time_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('time_entries', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('time_entries', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('time_entries', 'billed_at')) {
                $table->timestamp('billed_at')->nullable()->after('approved_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('time_entries')) {
            return;
        }

        Schema::table('time_entries', function (Blueprint $table) {
            if (Schema::hasColumn('time_entries', 'billed_at')) {
                $table->dropColumn('billed_at');
            }
            if (Schema::hasColumn('time_entries', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('time_entries', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }
        });
    }
};
