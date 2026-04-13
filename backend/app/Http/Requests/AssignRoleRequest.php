<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class AssignRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $validRoles = implode(',', array_map(fn (UserRole $r) => $r->value, UserRole::cases()));

        return [
            'role' => ['required', 'string', "in:{$validRoles}"],
        ];
    }
}
