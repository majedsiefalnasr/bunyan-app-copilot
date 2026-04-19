<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionProjectStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by policy/middleware
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'expected_updated_at' => ['required', 'date'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'status.required' => __('projects.validation.status_required'),
            'expected_updated_at.required' => __('projects.validation.expected_updated_at_required'),
            'expected_updated_at.date' => __('projects.validation.expected_updated_at_date'),
        ];
    }
}
