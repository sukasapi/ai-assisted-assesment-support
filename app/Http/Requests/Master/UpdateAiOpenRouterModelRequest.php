<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAiOpenRouterModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $model = $this->route('modelAi');

        return [
            'id_model_openrouter' => [
                'required',
                'string',
                'max:191',
                Rule::unique('ais_model_ai', 'id_model_openrouter')->ignore($model?->id),
            ],
            'label' => ['required', 'string', 'max:255'],
            'urutan' => ['required', 'integer', 'min:0', 'max:65535'],
            'utama' => ['sometimes', 'boolean'],
            'aktif' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'utama' => $this->boolean('utama'),
            'aktif' => $this->boolean('aktif'),
        ]);
    }
}
