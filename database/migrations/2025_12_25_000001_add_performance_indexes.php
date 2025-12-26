<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->index(['client_id', 'created_at'], 'requests_client_created_at_idx');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['client_id', 'created_at'], 'invoices_client_created_at_idx');
            $table->index(['client_id', 'due_date'], 'invoices_client_due_date_idx');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->index(['client_id', 'created_at'], 'contracts_client_created_at_idx');
            $table->index(['client_id', 'end_date'], 'contracts_client_end_date_idx');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->index(['client_id', 'created_at'], 'documents_client_created_at_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['client_id', 'processed_at'], 'payments_client_processed_at_idx');
            $table->index(['client_id', 'status', 'processed_at'], 'payments_client_status_processed_at_idx');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index(['client_id', 'created_at'], 'activity_logs_client_created_at_idx');
            $table->index(['client_id', 'log_name', 'created_at'], 'activity_logs_client_log_created_at_idx');
        });

        Schema::table('request_attachments', function (Blueprint $table) {
            $table->index(['request_id', 'created_at'], 'request_attachments_request_created_at_idx');
        });

        Schema::table('request_comments', function (Blueprint $table) {
            $table->index(['request_id', 'created_at'], 'request_comments_request_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropIndex('requests_client_created_at_idx');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_client_created_at_idx');
            $table->dropIndex('invoices_client_due_date_idx');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex('contracts_client_created_at_idx');
            $table->dropIndex('contracts_client_end_date_idx');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('documents_client_created_at_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_client_processed_at_idx');
            $table->dropIndex('payments_client_status_processed_at_idx');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('activity_logs_client_created_at_idx');
            $table->dropIndex('activity_logs_client_log_created_at_idx');
        });

        Schema::table('request_attachments', function (Blueprint $table) {
            $table->dropIndex('request_attachments_request_created_at_idx');
        });

        Schema::table('request_comments', function (Blueprint $table) {
            $table->dropIndex('request_comments_request_created_at_idx');
        });
    }
};

