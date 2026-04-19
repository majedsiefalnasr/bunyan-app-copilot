<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ProjectType;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by policy/middleware
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'owner_id' => ['required', 'integer', 'exists:users,id,deleted_at,NULL'],
            'name_ar' => ['required', 'string', 'min:2', 'max:255'],
            'name_en' => ['required', 'string', 'min:2', 'max:255'],
            'description' => ['nullable', 'string'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'location_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'location_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'type' => ['required', Rule::enum(ProjectType::class)],
            'budget_estimated' => ['nullable', 'numeric', 'min:0.01'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $ownerId = $this->input('owner_id');
                $owner = User::find($ownerId);

                if ($owner && $owner->role !== UserRole::CUSTOMER) {
                    $validator->errors()->add('owner_id', __('projects.validation.owner_must_be_customer'));
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'owner_id.required' => __('projects.validation.owner_required'),
            'owner_id.exists' => __('projects.validation.owner_not_found'),
            'name_ar.required' => __('projects.validation.name_ar_required'),
            'name_ar.min' => __('projects.validation.name_ar_min'),
            'name_en.required' => __('projects.validation.name_en_required'),
            'name_en.min' => __('projects.validation.name_en_min'),
            'city.required' => __('projects.validation.city_required'),
            'type.required' => __('projects.validation.type_required'),
            'budget_estimated.min' => __('projects.validation.budget_min'),
            'end_date.after_or_equal' => __('projects.validation.end_date_after_start'),
            'location_lat.between' => __('projects.validation.lat_range'),
            'location_lng.between' => __('projects.validation.lng_range'),
        ];
    }
}
