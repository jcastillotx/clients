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
        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();

            // Recurrence settings
            $table->enum('frequency', ['weekly', 'biweekly', 'monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->unsignedTinyInteger('day_of_month')->nullable(); // 1-28 for monthly/quarterly/yearly
            $table->unsignedTinyInteger('day_of_week')->nullable();  // 0-6 for weekly/biweekly (0=Sunday)
            $table->date('start_date');
            $table->date('end_date')->nullable(); // null = indefinite
            $table->date('next_generate_date')->nullable();
            $table->unsignedInteger('occurrences_limit')->nullable(); // max times to generate
            $table->unsignedInteger('occurrences_count')->default(0); // how many generated so far

            // Invoice template data
            $table->string('name'); // Internal name for this recurring invoice
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->string('template')->default('classic');
            $table->unsignedSmallInteger('payment_terms_days')->default(30); // Due X days after issue

            // Line items stored as JSON
            $table->json('line_items');

            // Status
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active');
            $table->boolean('auto_send')->default(false); // Auto-send to client when generated

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'next_generate_date']);
            $table->index(['client_id', 'status']);
        });

        // Track which invoices were generated from recurring templates
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('recurring_invoice_id')->nullable()->after('contract_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recurring_invoice_id');
        });

        Schema::dropIfExists('recurring_invoices');
    }
};
