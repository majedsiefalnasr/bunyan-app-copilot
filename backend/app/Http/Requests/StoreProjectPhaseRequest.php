<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PhaseStatus;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProjectPhaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by policy/middleware
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'min:2', 'max:255'],
            'name_en' => ['required', 'string', 'min:2', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'status' => ['nullable', Rule::enum(PhaseStatus::class)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'completion_percentage' => ['nullable', 'integer', 'between:0,100'],
        ];
    }

    /**
     * Validate date containment within project date range (R4).
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                /** @var Project|null $project */
                $project = $this->route('project');

                if (! $project) {
                    return;
                }

                $this->validateDateContainment($validator, $project);
            },
        ];
    }

    private function validateDateContainment(Validator $validator, Project $project): void
    {
        $phaseStart = $this->input('start_date');
        $phaseEnd = $this->input('end_date');

        if ($phaseStart && $project->start_date && $phaseStart < $project->start_date->toDateString()) {
            $validator->errors()->add('start_date', __('projects.validation.phase_start_before_project'));
        }

        if ($phaseEnd && $project->end_date && $phaseEnd > $project->end_date->toDateString()) {
            $validator->errors()->add('end_date', __('projects.validation.phase_end_after_project'));
        }
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name_ar.required' => __('projects.validation.phase_name_ar_required'),
            'name_en.required' => __('projects.validation.phase_name_en_required'),
            'sort_order.required' => __('projects.validation.sort_order_required'),
            'sort_order.min' => __('projects.validation.sort_order_min'),
            'completion_percentage.between' => __('projects.validation.completion_range'),
            'end_date.after_or_equal' => __('projects.validation.end_date_after_start'),
        ];
    }
}
