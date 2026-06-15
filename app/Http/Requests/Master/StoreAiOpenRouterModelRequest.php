<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAiOpenRouterModelRequest extends FormRequest
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
        return [
            'id_model_openrouter' => ['required', 'string', 'max:191', Rule::unique('ais_model_ai', 'id_model_openrouter')],
            'label' => ['required', 'string', 'max:255'],
            'urutan' => ['required', 'integer', 'min:0', 'max:65535'],
            'utama' => ['sometimes', 'boolean'],
            'aktif' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [
            'utama' => $this->has('utama') ? $this->boolean('utama') : false,
            'aktif' => $this->has('aktif') ? $this->boolean('aktif') : true,
        ];
        $this->merge($merge);
    }
}
