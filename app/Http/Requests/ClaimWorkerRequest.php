<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClaimWorkerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperAdmin() || $this->user()->isAreaManager();
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'area_id' => ['required', 'exists:areas,id'],
        ];
    }
}
