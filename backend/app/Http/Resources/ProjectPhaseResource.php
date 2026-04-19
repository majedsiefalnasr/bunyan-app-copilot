<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ProjectPhase;
use Illuminate\Http\Request;

/**
 * @mixin ProjectPhase
 */
class ProjectPhaseResource extends BaseApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'sort_order' => $this->sort_order,
            'status' => $this->status->value,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'completion_percentage' => $this->completion_percentage,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
