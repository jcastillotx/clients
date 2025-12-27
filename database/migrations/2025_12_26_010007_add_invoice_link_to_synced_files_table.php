<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('synced_files', function (Blueprint $table) {
            if (! Schema::hasColumn('synced_files', 'invoice_id')) {
                $table->foreignId('invoice_id')->nullable()->after('contract_id')->constrained('invoices')->nullOnDelete();
                $table->index(['invoice_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('synced_files', function (Blueprint $table) {
            if (Schema::hasColumn('synced_files', 'invoice_id')) {
                $table->dropColumn('invoice_id');
            }
        });
    }
};
