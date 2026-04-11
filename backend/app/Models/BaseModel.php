<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Abstract base model for all Bunyan domain models.
 *
 * Note: User cannot extend this class because it must extend
 * Illuminate\Foundation\Auth\User (Authenticatable) for Sanctum/Auth support.
 */
abstract class BaseModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $dateFormat = 'Y-m-d H:i:s';

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
