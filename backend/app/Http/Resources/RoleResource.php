<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Role;
use Illuminate\Http\Request;

/**
 * @mixin Role
 */
class RoleResource extends BaseApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'display_name_ar' => $this->display_name_ar,
            'description' => $this->description,
            'permissions_count' => $this->whenCounted('permissions', $this->permissions_count ?? $this->permissions->count()),
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
        ];
    }
}
