<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAiPromptTemplateRequest extends FormRequest
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
        $template = $this->route('templatePromptAi');

        return [
            'kode' => [
                'required',
                'string',
                'max:64',
                Rule::unique('ais_template_prompt_ai', 'kode')->ignore($template?->id),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'teks_instruksi' => ['nullable', 'string'],
            'urutan' => ['required', 'integer', 'min:0', 'max:65535'],
            'aktif' => ['sometimes', 'boolean'],
            'id_alat_penilaian' => ['nullable', 'array'],
            'id_alat_penilaian.*' => [
                'integer',
                Rule::exists('ais_alat_penilaian', 'id')->whereNull('dihapus_pada'),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'aktif' => $this->boolean('aktif'),
            'id_alat_penilaian' => array_values(array_unique(array_map(
                'intval',
                $this->input('id_alat_penilaian', [])
            ))),
        ]);
    }
}
