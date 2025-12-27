<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'contract_id')) {
                $table->foreignId('contract_id')->nullable()->after('request_id')->constrained()->nullOnDelete();
                $table->index(['contract_id']);
            }

            if (! Schema::hasColumn('invoices', 'template')) {
                $table->string('template')->nullable()->after('pdf_path');
                $table->index(['template']);
            }

            if (! Schema::hasColumn('invoices', 'reminded_due_7_at')) {
                $table->timestamp('reminded_due_7_at')->nullable()->after('paid_at');
            }

            if (! Schema::hasColumn('invoices', 'reminded_overdue_3_at')) {
                $table->timestamp('reminded_overdue_3_at')->nullable()->after('reminded_due_7_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'reminded_overdue_3_at')) {
                $table->dropColumn('reminded_overdue_3_at');
            }
            if (Schema::hasColumn('invoices', 'reminded_due_7_at')) {
                $table->dropColumn('reminded_due_7_at');
            }
            if (Schema::hasColumn('invoices', 'template')) {
                $table->dropIndex(['template']);
                $table->dropColumn('template');
            }
            if (Schema::hasColumn('invoices', 'contract_id')) {
                $table->dropForeign(['contract_id']);
                $table->dropIndex(['contract_id']);
                $table->dropColumn('contract_id');
            }
        });
    }
};
