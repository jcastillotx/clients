<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('request_id')->nullable()->constrained('requests')->nullOnDelete();
            $table->string('title');
            $table->string('proposal_number', 50)->unique();
            $table->string('template_id', 80)->nullable();
            $table->json('content')->nullable(); // sections, cover, terms, etc
            $table->json('pricing_data')->nullable(); // tiers/addons/payment plans
            $table->string('status', 40)->default('draft'); // draft|sent|viewed|accepted|rejected
            $table->date('valid_until')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
