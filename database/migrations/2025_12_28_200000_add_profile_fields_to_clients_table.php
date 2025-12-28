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
        Schema::table('clients', function (Blueprint $table) {
            // Internal notes visible only to staff/admin
            $table->text('internal_notes')->nullable()->after('notes');
            
            // Client business profile
            $table->text('mission')->nullable()->after('internal_notes');
            $table->text('vision')->nullable()->after('mission');
            $table->text('competitors')->nullable()->after('vision');
            
            // AI-generated marketing strategy
            $table->longText('marketing_strategy')->nullable()->after('competitors');
            $table->timestamp('marketing_strategy_generated_at')->nullable()->after('marketing_strategy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'internal_notes',
                'mission',
                'vision',
                'competitors',
                'marketing_strategy',
                'marketing_strategy_generated_at',
            ]);
        });
    }
};
