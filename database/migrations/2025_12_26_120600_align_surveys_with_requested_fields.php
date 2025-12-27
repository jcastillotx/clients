<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('surveys')) {
            return;
        }

        Schema::table('surveys', function (Blueprint $table) {
            // Requested schema fields (kept alongside the richer surveys/questions schema).
            if (! Schema::hasColumn('surveys', 'request_id')) {
                $table->foreignId('request_id')->nullable()->after('client_id')->constrained('requests')->nullOnDelete();
                $table->index(['request_id']);
            }
            if (! Schema::hasColumn('surveys', 'type')) {
                $table->enum('type', ['project_completion', 'satisfaction'])->nullable()->after('request_id');
                $table->index(['client_id', 'type']);
            }
            if (! Schema::hasColumn('surveys', 'responses')) {
                $table->json('responses')->nullable()->after('type');
            }
            if (! Schema::hasColumn('surveys', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('responses');
                $table->index(['submitted_at']);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('surveys')) {
            return;
        }

        Schema::table('surveys', function (Blueprint $table) {
            if (Schema::hasColumn('surveys', 'submitted_at')) {
                $table->dropIndex(['submitted_at']);
                $table->dropColumn('submitted_at');
            }
            if (Schema::hasColumn('surveys', 'responses')) {
                $table->dropColumn('responses');
            }
            if (Schema::hasColumn('surveys', 'type')) {
                $table->dropIndex(['client_id', 'type']);
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('surveys', 'request_id')) {
                $table->dropIndex(['request_id']);
                $table->dropColumn('request_id');
            }
        });
    }
};
