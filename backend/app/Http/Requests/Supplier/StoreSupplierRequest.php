<?php

declare(strict_types=1);

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
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
        return [
            'company_name_ar' => ['required', 'string', 'max:255'],
            'company_name_en' => ['required', 'string', 'max:255'],
            'commercial_reg' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^05\d{8}$/'],
            'city' => ['required', 'string', 'max:100'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'district' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'logo' => ['nullable', 'url', 'max:500'],
            'website' => ['nullable', 'url', 'max:255'],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ];
    }
}
