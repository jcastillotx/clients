<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected'])->default('draft')->after('is_public');
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('submitted_at');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
            $table->foreignId('reviewed_by')->nullable()->after('rejected_at')->constrained('users')->nullOnDelete();
            $table->unsignedInteger('current_version')->default(1)->after('reviewed_by');

            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['client_id', 'status']);
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['status', 'submitted_at', 'approved_at', 'rejected_at', 'current_version']);
        });
    }
};

