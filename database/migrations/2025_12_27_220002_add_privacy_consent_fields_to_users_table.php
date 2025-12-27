<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('users')) return;

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'consent_marketing')) {
                $table->boolean('consent_marketing')->default(false)->after('two_factor_confirmed_at');
            }
            if (!Schema::hasColumn('users', 'consent_updated_at')) {
                $table->timestamp('consent_updated_at')->nullable()->after('consent_marketing');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) return;

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'consent_updated_at')) {
                $table->dropColumn('consent_updated_at');
            }
            if (Schema::hasColumn('users', 'consent_marketing')) {
                $table->dropColumn('consent_marketing');
            }
        });
    }
};

