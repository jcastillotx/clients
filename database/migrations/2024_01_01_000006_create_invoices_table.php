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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('issue_date');
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->enum('status', [
                'draft',
                'sent',
                'paid',
                'overdue',
                'cancelled',
                'refunded',
            ])->default('draft');
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('due_date');
            $table->index(['client_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
