<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storage_sync_conflicts', function (Blueprint $table) {
            $table->json('chosen')->nullable()->after('candidates'); // chosen candidate for prefer_* rules
            $table->text('notes')->nullable()->after('resolution');
        });
    }

    public function down(): void
    {
        Schema::table('storage_sync_conflicts', function (Blueprint $table) {
            $table->dropColumn(['chosen', 'notes']);
        });
    }
};

