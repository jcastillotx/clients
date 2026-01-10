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
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('discount_type', 20)->default('fixed')->after('discount');
        });

        Schema::table('recurring_invoices', function (Blueprint $table) {
            $table->string('discount_type', 20)->default('fixed')->after('discount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('discount_type');
        });

        Schema::table('recurring_invoices', function (Blueprint $table) {
            $table->dropColumn('discount_type');
        });
    }
};
