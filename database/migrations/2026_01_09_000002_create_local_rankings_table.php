<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('local_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('keyword');
            $table->string('business_name')->nullable();
            $table->decimal('center_lat', 10, 6)->nullable();
            $table->decimal('center_lng', 10, 6)->nullable();
            $table->unsignedTinyInteger('grid_size')->default(5);
            $table->decimal('radius_miles', 5, 2)->default(5);
            $table->json('grid_data')->nullable();
            $table->decimal('average_position', 4, 1)->nullable();
            $table->unsignedSmallInteger('top_3_count')->default(0);
            $table->decimal('visibility_score', 5, 1)->default(0);
            $table->date('tracked_date');
            $table->timestamp('tracked_at')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'keyword', 'tracked_date']);
            $table->index(['client_id', 'tracked_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('local_rankings');
    }
};
