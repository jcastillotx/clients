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
            $table->text('gsc_refresh_token')->nullable()->after('meta');
            $table->string('gsc_site_url')->nullable()->after('gsc_refresh_token');
            $table->timestamp('gsc_connected_at')->nullable()->after('gsc_site_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['gsc_refresh_token', 'gsc_site_url', 'gsc_connected_at']);
        });
    }
};
