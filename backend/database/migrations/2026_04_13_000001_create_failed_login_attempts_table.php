<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates failed_login_attempts table for tracking failed authentication attempts.
     * Used for account lockout enforcement (after 5 failures, lock account for 15 minutes).
     * Implements brute-force protection per email address.
     */
    public function up(): void
    {
        Schema::create('failed_login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->ipAddress('ip_address')->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->integer('attempt_count')->default(1); // Total attempts for this email in current window
            $table->timestamp('first_attempt_at')->nullable(); // Track window start
            $table->timestamp('locked_until')->nullable()->index(); // Lock expiry time (if 5+ failures)
            $table->timestamps();

            // Composite index for quick lockout check
            $table->unique(['email', 'ip_address']);
            $table->index(['email', 'locked_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_login_attempts');
    }
};
