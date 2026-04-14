<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates otp_audit_logs table for tracking OTP verification attempts.
     * Used for rate limiting (max 5 attempts per 10 minutes).
     * Provides forensic audit trail for email verification and password reset flows.
     */
    public function up(): void
    {
        Schema::create('otp_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('otp_code_hash'); // Hash of the 6-digit code (security: never store plaintext)
            $table->integer('attempt_number'); // 1, 2, 3, 4, 5 (resets after success or expiry)
            $table->boolean('success')->default(false); // true if OTP matched, false if incorrect
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('expires_at'); // OTP expiry time (10 minutes from generation)
            $table->timestamps();

            // Indexes for audit queries and rate limiting
            $table->index('expires_at'); // For cleanup jobs
            $table->index(['email', 'created_at']);
            $table->index(['email', 'attempt_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_audit_logs');
    }
};
