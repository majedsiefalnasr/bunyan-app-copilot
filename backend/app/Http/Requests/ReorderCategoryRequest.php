<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class ReorderCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only admins can reorder categories
        return $this->user()?->role === UserRole::ADMIN;
    }

    public function rules(): array
    {
        return [
            'sort_order' => ['required', 'integer', 'min:0'],
            'version' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'sort_order.required' => 'ترتيب الفئة الجديد مطلوب',
            'sort_order.integer' => 'ترتيب الفئة يجب أن يكون رقماً',
            'sort_order.min' => 'ترتيب الفئة يجب أن يكون 0 أو أكبر',
            'version.required' => 'Version field is required for optimistic locking',
            'version.integer' => 'Version must be an integer',
            'version.min' => 'Version cannot be negative',
        ];
    }
}
