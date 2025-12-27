<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            if (!Schema::hasColumn('proposals', 'signed_at')) {
                $table->timestamp('signed_at')->nullable()->after('accepted_at');
            }
            if (!Schema::hasColumn('proposals', 'signed_by')) {
                $table->string('signed_by')->nullable()->after('signed_at');
            }
            if (!Schema::hasColumn('proposals', 'signature_ip')) {
                $table->string('signature_ip')->nullable()->after('signed_by');
            }
            if (!Schema::hasColumn('proposals', 'signature_data')) {
                $table->text('signature_data')->nullable()->after('signature_ip');
            }
            if (!Schema::hasColumn('proposals', 'contract_id')) {
                $table->foreignId('contract_id')->nullable()->after('request_id')->constrained('contracts')->nullOnDelete();
                $table->index(['contract_id']);
            }
            if (!Schema::hasColumn('proposals', 'invoice_id')) {
                $table->foreignId('invoice_id')->nullable()->after('contract_id')->constrained('invoices')->nullOnDelete();
                $table->index(['invoice_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            if (Schema::hasColumn('proposals', 'invoice_id')) {
                $table->dropForeign(['invoice_id']);
                $table->dropIndex(['invoice_id']);
                $table->dropColumn('invoice_id');
            }
            if (Schema::hasColumn('proposals', 'contract_id')) {
                $table->dropForeign(['contract_id']);
                $table->dropIndex(['contract_id']);
                $table->dropColumn('contract_id');
            }
            foreach (['signature_data', 'signature_ip', 'signed_by', 'signed_at'] as $col) {
                if (Schema::hasColumn('proposals', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

