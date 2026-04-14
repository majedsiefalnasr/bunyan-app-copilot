<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class AvatarUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                'file',
                'max:5120', // 5MB
                'mimes:jpeg,png,jpg,webp',
                'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
                'image',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'avatar.required' => 'الصورة مطلوبة',
            'avatar.file' => 'يجب أن يكون الملف صورة',
            'avatar.max' => 'حجم الصورة يجب أن لا يتجاوز 5MB',
            'avatar.mimes' => 'صيغة الصورة يجب أن تكون JPEG أو PNG أو WebP',
            'avatar.image' => 'الملف يجب أن يكون صورة صحيحة',
            'avatar.dimensions' => 'الصورة يجب أن تكون بين 100x100 و 2000x2000 بكسل',
        ];
    }
}
