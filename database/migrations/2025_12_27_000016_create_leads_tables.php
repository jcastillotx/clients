<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $supportsFullText = in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);

        Schema::create('leads', function (Blueprint $table) use ($supportsFullText) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('source')->nullable();
            $table->string('status')->default('new'); // new, contacted, qualified, converted, lost
            $table->unsignedTinyInteger('score')->nullable(); // 0-100
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['client_id', 'score']);
            $table->index(['assigned_to', 'status']);
            $table->index(['email']);
            if ($supportsFullText) {
                $table->fullText(['name', 'email', 'phone', 'company', 'source']);
            }
        });

        Schema::create('lead_activities', function (Blueprint $table) use ($supportsFullText) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->string('activity_type'); // form_submit, email_open, email_click, call, note, etc.
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'activity_type']);
            if ($supportsFullText) {
                $table->fullText(['description']);
            }
        });

        Schema::create('lead_nurture_sequences', function (Blueprint $table) use ($supportsFullText) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sequence_name');
            $table->json('steps'); // [{type,email/template,delay_days, ...}]
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'is_active']);
            if ($supportsFullText) {
                $table->fullText(['sequence_name']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_nurture_sequences');
        Schema::dropIfExists('lead_activities');
        Schema::dropIfExists('leads');
    }
};

