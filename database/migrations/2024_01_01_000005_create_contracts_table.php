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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path')->nullable();
            $table->string('contract_number')->unique()->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('value', 12, 2)->default(0);
            $table->enum('status', [
                'draft',
                'pending_signature',
                'active',
                'expired',
                'terminated'
            ])->default('draft');
            $table->timestamp('signed_at')->nullable();
            $table->string('signed_by')->nullable();
            $table->string('signature_ip')->nullable();
            $table->text('signature_data')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index(['client_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
