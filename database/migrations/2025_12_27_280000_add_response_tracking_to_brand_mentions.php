<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brand_mentions', function (Blueprint $table) {
            $table->timestamp('responded_at')->nullable()->after('meta');
            $table->foreignId('responded_by')->nullable()->after('responded_at')->constrained('users')->nullOnDelete();
            $table->text('response_notes')->nullable()->after('responded_by');
            
            $table->index(['sentiment', 'responded_at']);
        });
    }

    public function down(): void
    {
        Schema::table('brand_mentions', function (Blueprint $table) {
            $table->dropIndex(['sentiment', 'responded_at']);
            $table->dropConstrainedForeignId('responded_by');
            $table->dropColumn(['responded_at', 'response_notes']);
        });
    }
};
