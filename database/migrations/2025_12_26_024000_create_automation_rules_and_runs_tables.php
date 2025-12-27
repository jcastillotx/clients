<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // If another automation migration already created `automation_rules`, avoid
        // creating it twice after merges. Ensure the columns this codebase expects exist.
        if (! Schema::hasTable('automation_rules')) {
            Schema::create('automation_rules', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('trigger'); // request.created, schedule.daily, etc.
                $table->json('conditions')->nullable(); // {"operator":"and","rules":[...]} etc.
                $table->json('actions'); // [{type, config}, ...]
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['trigger', 'is_active']);
            });
        } else {
            Schema::table('automation_rules', function (Blueprint $table) {
                if (! Schema::hasColumn('automation_rules', 'description')) {
                    $table->text('description')->nullable()->after('name');
                }
                if (! Schema::hasColumn('automation_rules', 'conditions')) {
                    $table->json('conditions')->nullable()->after('trigger');
                }
                if (! Schema::hasColumn('automation_rules', 'actions')) {
                    $table->json('actions')->nullable()->after('conditions');
                }
                if (! Schema::hasColumn('automation_rules', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('actions');
                }
                if (! Schema::hasColumn('automation_rules', 'sort_order')) {
                    $table->unsignedInteger('sort_order')->default(0)->after('is_active');
                }
                if (! Schema::hasColumn('automation_rules', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('automation_rules', 'updated_by')) {
                    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                }
            });
        }

        if (! Schema::hasTable('automation_runs')) {
            Schema::create('automation_runs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('automation_rule_id')->constrained('automation_rules')->cascadeOnDelete();
                $table->string('trigger');
                $table->unsignedBigInteger('client_id')->nullable()->index(); // not a hard FK (some runs may be global)
                $table->json('context')->nullable();
                $table->boolean('matched')->default(false);
                $table->boolean('succeeded')->default(false);
                $table->unsignedSmallInteger('actions_total')->default(0);
                $table->unsignedSmallInteger('actions_succeeded')->default(0);
                $table->unsignedSmallInteger('actions_failed')->default(0);
                $table->longText('error')->nullable();
                $table->timestamp('ran_at')->nullable();
                $table->timestamps();

                $table->index(['automation_rule_id', 'created_at']);
                $table->index(['trigger', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_runs');
        Schema::dropIfExists('automation_rules');
    }
};
