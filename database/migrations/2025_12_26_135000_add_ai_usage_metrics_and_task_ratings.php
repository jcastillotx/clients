<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_usage_tracking', function (Blueprint $table) {
            if (! Schema::hasColumn('ai_usage_tracking', 'ai_task_id')) {
                $table->foreignId('ai_task_id')->nullable()->after('user_id')->constrained('ai_tasks')->nullOnDelete();
                $table->index(['ai_task_id']);
            }
            if (! Schema::hasColumn('ai_usage_tracking', 'response_time_ms')) {
                $table->unsignedInteger('response_time_ms')->nullable()->after('cost');
            }
            if (! Schema::hasColumn('ai_usage_tracking', 'success')) {
                $table->boolean('success')->default(true)->after('response_time_ms')->index();
            }
            if (! Schema::hasColumn('ai_usage_tracking', 'error_message')) {
                $table->string('error_message')->nullable()->after('success');
            }
        });

        Schema::table('ai_tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('ai_tasks', 'quality_rating')) {
                $table->unsignedTinyInteger('quality_rating')->nullable()->after('cost'); // 1-5
            }
            if (! Schema::hasColumn('ai_tasks', 'quality_notes')) {
                $table->text('quality_notes')->nullable()->after('quality_rating');
            }
            if (! Schema::hasColumn('ai_tasks', 'rated_by')) {
                $table->foreignId('rated_by')->nullable()->after('quality_notes')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('ai_tasks', 'rated_at')) {
                $table->timestamp('rated_at')->nullable()->after('rated_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_usage_tracking', function (Blueprint $table) {
            if (Schema::hasColumn('ai_usage_tracking', 'ai_task_id')) {
                $table->dropConstrainedForeignId('ai_task_id');
            }
            if (Schema::hasColumn('ai_usage_tracking', 'response_time_ms')) {
                $table->dropColumn('response_time_ms');
            }
            if (Schema::hasColumn('ai_usage_tracking', 'success')) {
                $table->dropColumn('success');
            }
            if (Schema::hasColumn('ai_usage_tracking', 'error_message')) {
                $table->dropColumn('error_message');
            }
        });

        Schema::table('ai_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('ai_tasks', 'rated_at')) {
                $table->dropColumn('rated_at');
            }
            if (Schema::hasColumn('ai_tasks', 'rated_by')) {
                $table->dropConstrainedForeignId('rated_by');
            }
            if (Schema::hasColumn('ai_tasks', 'quality_notes')) {
                $table->dropColumn('quality_notes');
            }
            if (Schema::hasColumn('ai_tasks', 'quality_rating')) {
                $table->dropColumn('quality_rating');
            }
        });
    }
};
