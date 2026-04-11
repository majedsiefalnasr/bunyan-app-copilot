<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AuditLog — Audit Trail Model
 *
 * Stores audit trail records for significant platform events.
 * Used for compliance, debugging, and analytics.
 *
 * @property int $id
 * @property string $correlation_id UUID v4
 * @property string|null $request_id HTTP request ID
 * @property int|null $user_id User who performed the action
 * @property string $action Action type (e.g., 'project.created')
 * @property string $resource_type Resource type (e.g., 'Project')
 * @property int|null $resource_id Resource ID
 * @property array|null $old_values Previous state
 * @property array|null $new_values New state
 * @property string $status Success/failure status
 * @property string|null $error_code Error code if failed
 * @property string|null $ip_address Client IP
 * @property string|null $user_agent Client user agent
 * @property int $duration_ms Operation duration
 * @property Carbon $created_at
 */
class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false; // Audit logs are immutable

    protected $fillable = [
        'correlation_id',
        'request_id',
        'user_id',
        'action',
        'resource_type',
        'resource_id',
        'old_values',
        'new_values',
        'status',
        'error_code',
        'ip_address',
        'user_agent',
        'duration_ms',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
        'created_at' => 'datetime',
    ];

    /**
     * Relationship: Audit log belongs to a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Filter by correlation ID.
     */
    public function scopeByCorrelationId(Builder $query, string $correlationId): Builder
    {
        return $query->where('correlation_id', $correlationId);
    }

    /**
     * Scope: Filter by action.
     */
    public function scopeByAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: Filter by user.
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by resource type and ID.
     */
    public function scopeForResource(Builder $query, string $type, int $id): Builder
    {
        return $query
            ->where('resource_type', $type)
            ->where('resource_id', $id);
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Recent logs first.
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }
}
