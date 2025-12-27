<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('tasks')) return;

        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'start_date')) {
                $table->date('start_date')->nullable()->after('priority');
            }
            if (!Schema::hasColumn('tasks', 'depends_on_task_id')) {
                $table->foreignId('depends_on_task_id')->nullable()->after('parent_task_id')->constrained('tasks')->nullOnDelete();
            }
            if (!Schema::hasColumn('tasks', 'meta')) {
                $table->json('meta')->nullable()->after('order');
            }

            $table->index(['request_id', 'start_date']);
            $table->index(['depends_on_task_id']);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tasks')) return;

        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'meta')) {
                $table->dropColumn('meta');
            }
            if (Schema::hasColumn('tasks', 'depends_on_task_id')) {
                $table->dropConstrainedForeignId('depends_on_task_id');
            }
            if (Schema::hasColumn('tasks', 'start_date')) {
                $table->dropColumn('start_date');
            }
        });
    }
};

