<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ProjectType;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by policy
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name_ar' => ['sometimes', 'string', 'min:2', 'max:255'],
            'name_en' => ['sometimes', 'string', 'min:2', 'max:255'],
            'description' => ['nullable', 'string'],
            'city' => ['sometimes', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'location_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'location_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'type' => ['sometimes', Rule::enum(ProjectType::class)],
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
                /** @var Project|null $project */
                $project = $this->route('project');

                if ($project && ! $project->isEditable()) {
                    $validator->errors()->add('status', __('projects.validation.closed_immutable'));
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name_ar.min' => __('projects.validation.name_ar_min'),
            'name_en.min' => __('projects.validation.name_en_min'),
            'budget_estimated.min' => __('projects.validation.budget_min'),
            'end_date.after_or_equal' => __('projects.validation.end_date_after_start'),
            'location_lat.between' => __('projects.validation.lat_range'),
            'location_lng.between' => __('projects.validation.lng_range'),
        ];
    }
}
