<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class SyncMatrixCompetencyTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'target' => ['nullable', 'array'],
            'target.*' => ['nullable', 'integer', 'min:0', 'max:10'],
        ];
    }
}
