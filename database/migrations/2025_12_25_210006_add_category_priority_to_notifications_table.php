<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Laravel default notifications table might not exist in all environments.
        if (! Schema::hasTable('notifications')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('notifications', 'category')) {
                $table->string('category')->nullable()->after('type');
                $table->index('category');
            }
            if (! Schema::hasColumn('notifications', 'priority')) {
                $table->enum('priority', ['low', 'normal', 'high'])->default('normal')->after('category');
                $table->index('priority');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'priority')) {
                $table->dropIndex(['priority']);
                $table->dropColumn('priority');
            }
            if (Schema::hasColumn('notifications', 'category')) {
                $table->dropIndex(['category']);
                $table->dropColumn('category');
            }
        });
    }
};
