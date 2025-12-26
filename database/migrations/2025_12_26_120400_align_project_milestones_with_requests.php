<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('project_milestones')) {
            return;
        }

        Schema::table('project_milestones', function (Blueprint $table) {
            // Add request-linked milestone fields (requested schema) while keeping
            // existing project-linked milestone fields.
            if (!Schema::hasColumn('project_milestones', 'request_id')) {
                $table->foreignId('request_id')->nullable()->after('project_id')->constrained('requests')->nullOnDelete();
                $table->index(['request_id']);
            }
            if (!Schema::hasColumn('project_milestones', 'title')) {
                $table->string('title')->nullable()->after('request_id');
            }
            if (!Schema::hasColumn('project_milestones', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            if (!Schema::hasColumn('project_milestones', 'status')) {
                $table->string('status')->default('pending')->after('due_date');
                $table->index(['status']);
            }

            // Helpful composite indexes for filtering.
            $table->index(['request_id', 'status']);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('project_milestones')) {
            return;
        }

        Schema::table('project_milestones', function (Blueprint $table) {
            if (Schema::hasColumn('project_milestones', 'status')) {
                $table->dropIndex(['status']);
                // dropIndex for ['request_id','status'] may have an auto-generated name; ignore on down.
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('project_milestones', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('project_milestones', 'title')) {
                $table->dropColumn('title');
            }
            if (Schema::hasColumn('project_milestones', 'request_id')) {
                $table->dropIndex(['request_id']);
                $table->dropColumn('request_id');
            }
        });
    }
};

