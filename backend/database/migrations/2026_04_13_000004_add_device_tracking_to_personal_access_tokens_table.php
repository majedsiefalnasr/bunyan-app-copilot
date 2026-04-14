<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds device fingerprinting and user_id denormalization to personal_access_tokens table.
     * Used for:
     * - Session concurrency limits (max 2 tokens per user)
     * - Device fingerprinting (prevent session hijacking)
     *
     * @see T046 — Session Concurrency Limits + Device Fingerprinting
     */
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Denormalize user_id for faster token queries per user
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index('user_id');

            // Device fingerprint (hash of user agent + IP)
            // Used for device tracking and concurrency limiting
            $table->string('device_fingerprint')->nullable()->after('expires_at');
            $table->string('device_name')->nullable()->after('device_fingerprint'); // Optional: e.g., "Chrome on macOS"
            $table->ipAddress('ip_address')->nullable()->after('device_name');

            // Add indexes for efficient querying
            $table->index(['user_id', 'created_at']);
            $table->index('device_fingerprint');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['device_fingerprint']);
            $table->dropIndex(['user_id']);
            $table->dropColumn(['user_id', 'device_fingerprint', 'device_name', 'ip_address']);
        });
    }
};
