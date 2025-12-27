<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'enabled_features')) {
                $table->json('enabled_features')->nullable()->after('notes');
            }
        });

        Schema::table('contracts', function (Blueprint $table) {
            // Meta already exists, we'll use it for feature configuration
            // Add a contract_type field to easily categorize contracts
            if (!Schema::hasColumn('contracts', 'contract_type')) {
                $table->string('contract_type')->default('standard')->after('title');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'enabled_features')) {
                $table->dropColumn('enabled_features');
            }
        });

        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'contract_type')) {
                $table->dropColumn('contract_type');
            }
        });
    }
};
