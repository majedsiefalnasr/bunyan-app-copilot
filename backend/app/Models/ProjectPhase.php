<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PhaseStatus;
use Database\Factories\ProjectPhaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * ProjectPhase does NOT extend BaseModel — no SoftDeletes (hard-delete per spec).
 *
 * @property int $id
 * @property int $project_id
 * @property string $name_ar
 * @property string $name_en
 * @property int $sort_order
 * @property PhaseStatus $status
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property int $completion_percentage
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static ProjectPhaseFactory factory(...$parameters)
 */
class ProjectPhase extends Model
{
    /** @use HasFactory<ProjectPhaseFactory> */
    use HasFactory;

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => PhaseStatus::class,
            'sort_order' => 'integer',
            'completion_percentage' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
