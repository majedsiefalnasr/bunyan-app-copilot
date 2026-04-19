<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;

/**
 * @mixin Project
 */
class ProjectResource extends BaseApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'owner_id' => $this->owner_id,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'description' => $this->description,
            'city' => $this->city,
            'district' => $this->district,
            'location_lat' => $this->location_lat,
            'location_lng' => $this->location_lng,
            'status' => $this->status->value,
            'type' => $this->type->value,
            'budget_estimated' => $this->budget_estimated,
            'budget_actual' => $this->budget_actual,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'phases_count' => $this->whenCounted('phases'),
            'phases' => ProjectPhaseResource::collection($this->whenLoaded('phases')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
