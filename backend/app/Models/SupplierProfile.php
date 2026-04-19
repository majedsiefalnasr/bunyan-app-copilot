<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SupplierVerificationStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $company_name_ar
 * @property string $company_name_en
 * @property string $commercial_reg
 * @property string|null $tax_number
 * @property string $city
 * @property string|null $district
 * @property string|null $address
 * @property string $phone
 * @property SupplierVerificationStatus $verification_status
 * @property Carbon|null $verified_at
 * @property int|null $verified_by
 * @property string|null $rating_avg
 * @property int $total_ratings
 * @property string|null $description_ar
 * @property string|null $description_en
 * @property string|null $logo
 * @property string|null $website
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class SupplierProfile extends BaseModel
{
    /**
     * SEC-FINDING-A: verification_status, user_id, verified_at, verified_by,
     * rating_avg, and total_ratings are intentionally excluded from $fillable
     * to prevent mass-assignment privilege escalation.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_name_ar',
        'company_name_en',
        'commercial_reg',
        'tax_number',
        'city',
        'district',
        'address',
        'phone',
        'description_ar',
        'description_en',
        'logo',
        'website',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'verification_status' => SupplierVerificationStatus::class,
            'verified_at' => 'datetime',
            'rating_avg' => 'decimal:2',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Products relationship — stub until STAGE_08
    // public function products(): HasMany { ... }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('verification_status', SupplierVerificationStatus::Verified->value);
    }

    public function scopeByCity(Builder $query, string $city): Builder
    {
        return $query->where('city', $city);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('company_name_ar', 'LIKE', "%{$term}%")
                ->orWhere('company_name_en', 'LIKE', "%{$term}%");
        });
    }

    /**
     * Role-based visibility scope.
     *
     * - Admin: all statuses (including pending/suspended)
     * - Contractor: verified + own profile
     * - Guest/others: verified only
     */
    public function scopeVisibleTo(Builder $query, ?User $actor): Builder
    {
        if ($actor !== null && $actor->hasEnumRole(UserRole::ADMIN)) {
            return $query;
        }

        if ($actor !== null && $actor->hasEnumRole(UserRole::CONTRACTOR)) {
            return $query->where(function (Builder $q) use ($actor) {
                $q->where('verification_status', SupplierVerificationStatus::Verified->value)
                    ->orWhere('user_id', $actor->id);
            });
        }

        return $query->where('verification_status', SupplierVerificationStatus::Verified->value);
    }
}
