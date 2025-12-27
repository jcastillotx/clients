<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->text('refresh_token')->nullable()->after('access_token');
            $table->timestamp('token_expires_at')->nullable()->after('refresh_token');
            $table->string('account_username')->nullable()->after('account_name');
            $table->string('account_email')->nullable()->after('account_username');
            $table->string('profile_picture_url')->nullable()->after('account_email');
            $table->timestamp('connected_at')->nullable()->after('is_connected');
            $table->timestamp('last_token_refresh')->nullable()->after('connected_at');
            $table->json('scopes')->nullable()->after('last_token_refresh');
        });
    }

    public function down(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'refresh_token',
                'token_expires_at',
                'account_username',
                'account_email',
                'profile_picture_url',
                'connected_at',
                'last_token_refresh',
                'scopes',
            ]);
        });
    }
};
