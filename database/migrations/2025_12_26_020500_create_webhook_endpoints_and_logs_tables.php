<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // If another webhook migration already created `webhook_endpoints` (or this migration
        // set is present twice after a merge), avoid creating the same table again. Instead,
        // ensure the expected columns exist and create the delivery log table if missing.
        if (Schema::hasTable('webhook_endpoints')) {
            Schema::table('webhook_endpoints', function (Blueprint $table) {
                if (! Schema::hasColumn('webhook_endpoints', 'event_type')) {
                    $table->string('event_type')->after('client_id');
                }
                if (! Schema::hasColumn('webhook_endpoints', 'webhook_url')) {
                    $table->string('webhook_url');
                }
                if (! Schema::hasColumn('webhook_endpoints', 'secret')) {
                    $table->text('secret')->nullable();
                }
                if (! Schema::hasColumn('webhook_endpoints', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
                if (! Schema::hasColumn('webhook_endpoints', 'format')) {
                    $table->string('format')->default('generic');
                }
                if (! Schema::hasColumn('webhook_endpoints', 'headers')) {
                    $table->json('headers')->nullable();
                }
                if (! Schema::hasColumn('webhook_endpoints', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                }
            });

            if (! Schema::hasTable('webhook_delivery_logs')) {
                Schema::create('webhook_delivery_logs', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('webhook_endpoint_id')->constrained('webhook_endpoints')->cascadeOnDelete();
                    $table->string('event_type');
                    $table->json('payload');
                    $table->unsignedSmallInteger('attempt')->default(1);
                    $table->boolean('succeeded')->default(false);
                    $table->unsignedSmallInteger('http_status')->nullable();
                    $table->unsignedInteger('duration_ms')->nullable();
                    $table->longText('response_body')->nullable();
                    $table->longText('error')->nullable();
                    $table->timestamp('delivered_at')->nullable();
                    $table->timestamps();

                    $table->index(['webhook_endpoint_id', 'created_at']);
                    $table->index(['event_type', 'succeeded']);
                });
            }

            return;
        }

        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->cascadeOnDelete();
            $table->string('event_type'); // request.created, invoice.paid, etc.
            $table->string('webhook_url');
            $table->text('secret');
            $table->boolean('is_active')->default(true);
            $table->string('format')->default('generic'); // generic|slack|teams|zapier|make
            $table->json('headers')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['client_id', 'event_type']);
            $table->index(['event_type', 'is_active']);
        });

        Schema::create('webhook_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_endpoint_id')->constrained('webhook_endpoints')->cascadeOnDelete();
            $table->string('event_type');
            $table->json('payload');
            $table->unsignedSmallInteger('attempt')->default(1);
            $table->boolean('succeeded')->default(false);
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->longText('response_body')->nullable();
            $table->longText('error')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['webhook_endpoint_id', 'created_at']);
            $table->index(['event_type', 'succeeded']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_delivery_logs');
        Schema::dropIfExists('webhook_endpoints');
    }
};
