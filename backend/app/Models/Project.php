<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Enums\UserRole;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $owner_id
 * @property string $name_ar
 * @property string $name_en
 * @property string|null $description
 * @property string $city
 * @property string|null $district
 * @property string|null $location_lat
 * @property string|null $location_lng
 * @property ProjectStatus $status
 * @property ProjectType $type
 * @property string|null $budget_estimated
 * @property string|null $budget_actual
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @method static ProjectFactory factory(...$parameters)
 */
class Project extends BaseModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'type' => ProjectType::class,
            'budget_estimated' => 'decimal:2',
            'budget_actual' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    // ── Relationships ──

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return HasMany<ProjectPhase, $this> */
    public function phases(): HasMany
    {
        return $this->hasMany(ProjectPhase::class)->orderBy('sort_order');
    }

    // ── Scopes ──

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            UserRole::ADMIN => $query,
            UserRole::CUSTOMER => $query->where('owner_id', $user->id),
            default => $query->whereRaw('1 = 0'),
        };
    }

    public function scopeStatus(Builder $query, ProjectStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeType(Builder $query, ProjectType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeCity(Builder $query, string $city): Builder
    {
        return $query->where('city', $city);
    }

    // ── Helpers ──

    public function isEditable(): bool
    {
        return $this->status !== ProjectStatus::CLOSED;
    }
}
