<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates audit_logs table for storing audit trail records.
     * Used for tracking significant platform events (project creation, phase updates, etc.)
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('correlation_id')->index();
            $table->string('request_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('action'); // e.g., 'project.created', 'phase.updated'
            $table->string('resource_type'); // e.g., 'Project', 'Phase', 'Task'
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->json('old_values')->nullable(); // Previous state (for updates)
            $table->json('new_values')->nullable(); // New state (for creates/updates)
            $table->string('status')->default('success'); // success, failed, pending
            $table->string('error_code')->nullable(); // Error code if operation failed
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->unsignedInteger('duration_ms')->default(0); // Operation duration
            $table->timestamps(); // created_at only (no updated_at for audit logs)

            // Indexes for common queries
            $table->index(['user_id', 'created_at']);
            $table->index(['correlation_id', 'created_at']);
            $table->index(['resource_type', 'resource_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
