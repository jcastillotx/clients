<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('thumbnail_path')->nullable()->after('file_path');
        });

        Schema::table('request_attachments', function (Blueprint $table) {
            $table->string('thumbnail_path')->nullable()->after('file_path');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('thumbnail_path');
        });

        Schema::table('request_attachments', function (Blueprint $table) {
            $table->dropColumn('thumbnail_path');
        });
    }
};

