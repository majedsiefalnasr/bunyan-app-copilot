<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property UserRole $role The user's role enum value
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * Note: `role` is intentionally excluded — it is an enum column assigned
     * only via explicit `$user->role = UserRole::X; $user->save()` to prevent
     * privilege escalation (SEC-FINDING-A).
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole(Builder $query, string $role): Builder
    {
        return $query->whereHas('roles', fn (Builder $q) => $q->where('name', $role));
    }

    /**
     * Check role by string slug (via roles pivot relationship).
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->roles->contains('name', $roleSlug);
    }

    /**
     * Check role by UserRole enum (via the enum column on users table).
     */
    public function hasEnumRole(UserRole $role): bool
    {
        return ($this->role instanceof UserRole) && $this->role === $role;
    }

    public function hasAnyRole(UserRole ...$roles): bool
    {
        if (!($this->role instanceof UserRole)) {
            return false;
        }

        return in_array($this->role, $roles, true);
    }
}
