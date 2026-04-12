<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates request_logs table for storing HTTP request/response details.
     * Used for monitoring API usage, performance, and debugging.
     */
    public function up(): void
    {
        Schema::create('request_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('correlation_id')->index();
            $table->string('request_id')->nullable()->index();
            $table->string('method'); // GET, POST, PUT, PATCH, DELETE
            $table->text('uri');
            $table->string('status_code');
            $table->unsignedInteger('response_time_ms')->default(0); // Milliseconds
            $table->unsignedInteger('payload_size_bytes')->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('user_role')->nullable(); // Customer, Contractor, etc.
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Indexes for common queries
            $table->index(['user_id', 'created_at']);
            $table->index(['correlation_id', 'created_at']);
            $table->index(['status_code', 'created_at']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_logs');
    }
};
