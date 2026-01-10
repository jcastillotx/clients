<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            // SLA due timestamps
            $table->timestamp('sla_response_due_at')->nullable()->after('first_response_at');
            $table->timestamp('sla_resolution_due_at')->nullable()->after('sla_response_due_at');

            // SLA breach tracking
            $table->boolean('sla_response_breached')->default(false)->after('sla_resolution_due_at');
            $table->boolean('sla_resolution_breached')->default(false)->after('sla_response_breached');
            $table->timestamp('sla_response_breached_at')->nullable()->after('sla_resolution_breached');
            $table->timestamp('sla_resolution_breached_at')->nullable()->after('sla_response_breached_at');

            // Escalation tracking
            $table->tinyInteger('escalation_level')->default(0)->after('sla_resolution_breached_at');
            $table->timestamp('last_escalated_at')->nullable()->after('escalation_level');

            // SLA pausing (for waiting_on_client status)
            $table->boolean('sla_paused')->default(false)->after('last_escalated_at');
            $table->integer('sla_paused_duration_minutes')->default(0)->after('sla_paused');

            // Indexes for SLA monitoring queries
            $table->index('sla_response_due_at');
            $table->index('sla_resolution_due_at');
            $table->index('sla_response_breached');
            $table->index('sla_resolution_breached');
            $table->index('escalation_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropIndex(['sla_response_due_at']);
            $table->dropIndex(['sla_resolution_due_at']);
            $table->dropIndex(['sla_response_breached']);
            $table->dropIndex(['sla_resolution_breached']);
            $table->dropIndex(['escalation_level']);

            $table->dropColumn([
                'sla_response_due_at',
                'sla_resolution_due_at',
                'sla_response_breached',
                'sla_resolution_breached',
                'sla_response_breached_at',
                'sla_resolution_breached_at',
                'escalation_level',
                'last_escalated_at',
                'sla_paused',
                'sla_paused_duration_minutes',
            ]);
        });
    }
};
