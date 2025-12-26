<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('synced_files', function (Blueprint $table) {
            if (!Schema::hasColumn('synced_files', 'request_id')) {
                $table->foreignId('request_id')->nullable()->after('document_id')->constrained('requests')->nullOnDelete();
                $table->index(['request_id']);
            }

            if (!Schema::hasColumn('synced_files', 'contract_id')) {
                $table->foreignId('contract_id')->nullable()->after('request_id')->constrained('contracts')->nullOnDelete();
                $table->index(['contract_id']);
            }

            if (!Schema::hasColumn('synced_files', 'tags')) {
                $table->json('tags')->nullable()->after('sync_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('synced_files', function (Blueprint $table) {
            if (Schema::hasColumn('synced_files', 'request_id')) {
                $table->dropColumn('request_id');
            }
            if (Schema::hasColumn('synced_files', 'contract_id')) {
                $table->dropColumn('contract_id');
            }
            if (Schema::hasColumn('synced_files', 'tags')) {
                $table->dropColumn('tags');
            }
        });
    }
};

