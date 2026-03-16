<?php

namespace App\Http\Requests;

use App\Enums\TaskPriorityEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assigned_to_user_id' => ['nullable', 'exists:users,id', 'required_without:assigned_to_area_id'],
            'assigned_to_area_id' => ['nullable', 'exists:areas,id', 'required_without:assigned_to_user_id'],
            'priority' => ['sometimes', Rule::enum(TaskPriorityEnum::class)],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'requires_attachment' => ['sometimes', 'boolean'],
            'requires_completion_comment' => ['sometimes', 'boolean'],
            'requires_manager_approval' => ['sometimes', 'boolean'],
            'requires_completion_notification' => ['sometimes', 'boolean'],
            'requires_due_date' => ['sometimes', 'boolean'],
        ];
    }
}
