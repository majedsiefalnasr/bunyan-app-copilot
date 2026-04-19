<?php

declare(strict_types=1);

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class SuspendSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // RBAC enforced via Policy in controller
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
