<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role->value,
            'is_active' => $this->is_active,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at?->toISOString(),
        ];

        // Include permissions when roles.permissions are eager-loaded
        if ($this->relationLoaded('roles')) {
            /** @var Collection<int, Role> $roles */
            $roles = $this->roles;
            $data['permissions'] = $roles
                ->flatMap(fn (Role $role) => $role->permissions)
                ->pluck('name')
                ->unique()
                ->values()
                ->toArray();
        }

        return $data;
    }
}
