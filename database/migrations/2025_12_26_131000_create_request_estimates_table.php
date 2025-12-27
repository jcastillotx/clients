<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_estimates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // admin/staff who prepared estimate

            $table->enum('status', ['draft', 'sent', 'approved', 'changes_requested'])->default('draft')->index();

            $table->foreignId('ai_task_id')->nullable()->constrained('ai_tasks')->nullOnDelete();
            $table->json('estimate_data')->nullable(); // tasks/timeline/risks/etc
            $table->json('pricing_data')->nullable(); // rate card + breakdown
            $table->json('client_selections')->nullable(); // optional selections + notes

            $table->foreignId('sow_contract_id')->nullable()->constrained('contracts')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('client_message')->nullable();

            $table->timestamps();

            $table->index(['request_id', 'status']);
            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_estimates');
    }
};

