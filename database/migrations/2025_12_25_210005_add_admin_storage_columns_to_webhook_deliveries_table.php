<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_deliveries', function (Blueprint $table) {
            // Requested schema fields (already present in current design, but ensure delivered_at exists)
            if (!Schema::hasColumn('webhook_deliveries', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('response_body');
                $table->index(['webhook_endpoint_id', 'delivered_at'], 'webhook_deliveries_endpoint_delivered_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('webhook_deliveries', function (Blueprint $table) {
            if (Schema::hasColumn('webhook_deliveries', 'delivered_at')) {
                $table->dropIndex('webhook_deliveries_endpoint_delivered_idx');
                $table->dropColumn('delivered_at');
            }
        });
    }
};

