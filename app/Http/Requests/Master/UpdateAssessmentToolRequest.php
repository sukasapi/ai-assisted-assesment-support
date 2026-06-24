<?php

namespace App\Http\Requests\Master;

use App\Models\AssessmentTool;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssessmentToolRequest extends FormRequest
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
        /** @var AssessmentTool $alatPenilaian */
        $alatPenilaian = $this->route('alatPenilaian');

        return [
            'kode' => ['required', 'string', 'max:32', Rule::unique('ais_alat_penilaian', 'kode')->ignore($alatPenilaian->id)],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'aktif' => ['sometimes', 'boolean'],
            'urutan' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'aktif' => $this->boolean('aktif'),
        ]);
    }
}
