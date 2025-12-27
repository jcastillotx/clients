<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('knowledge_base_articles')) {
            return;
        }

        Schema::table('knowledge_base_articles', function (Blueprint $table) {
            // Requested schema fields (kept alongside newer KB schema for compatibility).
            if (! Schema::hasColumn('knowledge_base_articles', 'content')) {
                $table->longText('content')->nullable()->after('body');
            }
            if (! Schema::hasColumn('knowledge_base_articles', 'category')) {
                $table->string('category')->nullable()->after('content');
                $table->index(['category']);
            }
            if (! Schema::hasColumn('knowledge_base_articles', 'views_count')) {
                $table->unsignedBigInteger('views_count')->default(0)->after('category');
                $table->index(['views_count']);
            }
            if (! Schema::hasColumn('knowledge_base_articles', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('category_id')->constrained('users')->nullOnDelete();
                $table->index(['created_by', 'created_at']);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('knowledge_base_articles')) {
            return;
        }

        Schema::table('knowledge_base_articles', function (Blueprint $table) {
            if (Schema::hasColumn('knowledge_base_articles', 'created_by')) {
                $table->dropIndex(['created_by', 'created_at']);
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('knowledge_base_articles', 'views_count')) {
                $table->dropIndex(['views_count']);
                $table->dropColumn('views_count');
            }
            if (Schema::hasColumn('knowledge_base_articles', 'category')) {
                $table->dropIndex(['category']);
                $table->dropColumn('category');
            }
            if (Schema::hasColumn('knowledge_base_articles', 'content')) {
                $table->dropColumn('content');
            }
        });
    }
};
