<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            if (! Schema::hasColumn('invoice_items', 'feature_key')) {
                $table->string('feature_key', 80)->nullable()->after('description');
                $table->index('feature_key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_items', 'feature_key')) {
                $table->dropIndex(['feature_key']);
                $table->dropColumn('feature_key');
            }
        });
    }
};

