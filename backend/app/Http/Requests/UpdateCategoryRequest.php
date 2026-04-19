<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only admins can update categories
        return $this->user()?->role === UserRole::ADMIN;
    }

    public function rules(): array
    {
        return [
            'name_ar' => ['sometimes', 'string', 'min:2', 'max:100'],
            'name_en' => ['sometimes', 'string', 'min:2', 'max:100'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id,deleted_at,NULL'],
            'icon' => ['sometimes', 'nullable', 'string', 'max:50'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'version' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name_ar.min' => 'اسم الفئة بالعربية يجب أن يكون 2 حروف على الأقل',
            'name_ar.max' => 'اسم الفئة بالعربية لا يجب أن يتجاوز 100 حرف',
            'name_en.min' => 'Category name in English must be at least 2 characters',
            'name_en.max' => 'Category name in English must not exceed 100 characters',
            'parent_id.exists' => 'الفئة الأب المحددة غير موجودة',
            'parent_id.integer' => 'معرف الفئة الأب يجب أن يكون رقماً',
            'icon.string' => 'أيقونة الفئة يجب أن تكون نصاً',
            'icon.max' => 'أيقونة الفئة لا يجب أن تتجاوز 50 حرف',
            'sort_order.integer' => 'ترتيب الفئة يجب أن يكون رقماً',
            'sort_order.min' => 'ترتيب الفئة يجب أن يكون 0 أو أكبر',
            'is_active.boolean' => 'حالة النشط يجب أن تكون صواب أو خطأ',
            'version.required' => 'Version field is required for optimistic locking',
            'version.integer' => 'Version must be an integer',
            'version.min' => 'Version cannot be negative',
        ];
    }
}
