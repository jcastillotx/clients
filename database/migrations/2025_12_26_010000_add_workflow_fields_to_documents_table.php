<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'invoice_id')) {
                $table->foreignId('invoice_id')->nullable()->after('request_id')->constrained('invoices')->nullOnDelete();
                $table->index(['invoice_id']);
            }
            if (!Schema::hasColumn('documents', 'contract_id')) {
                $table->foreignId('contract_id')->nullable()->after('invoice_id')->constrained('contracts')->nullOnDelete();
                $table->index(['contract_id']);
            }

            if (!Schema::hasColumn('documents', 'workflow_status')) {
                $table->enum('workflow_status', ['draft', 'pending_review', 'approved', 'rejected'])
                    ->default('draft')
                    ->after('is_public');
                $table->index(['workflow_status']);
            }
            if (!Schema::hasColumn('documents', 'review_requested_at')) {
                $table->timestamp('review_requested_at')->nullable()->after('workflow_status');
            }
            if (!Schema::hasColumn('documents', 'review_decided_at')) {
                $table->timestamp('review_decided_at')->nullable()->after('review_requested_at');
            }

            if (!Schema::hasColumn('documents', 'tags')) {
                $table->json('tags')->nullable()->after('category');
                $table->index(['category']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'review_decided_at')) {
                $table->dropColumn('review_decided_at');
            }
            if (Schema::hasColumn('documents', 'review_requested_at')) {
                $table->dropColumn('review_requested_at');
            }
            if (Schema::hasColumn('documents', 'workflow_status')) {
                $table->dropColumn('workflow_status');
            }
            if (Schema::hasColumn('documents', 'tags')) {
                $table->dropColumn('tags');
            }
            if (Schema::hasColumn('documents', 'contract_id')) {
                $table->dropColumn('contract_id');
            }
            if (Schema::hasColumn('documents', 'invoice_id')) {
                $table->dropColumn('invoice_id');
            }
        });
    }
};

