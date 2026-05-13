<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompetencyRequest extends FormRequest
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
            'id_kelompok_kompetensi' => ['required', 'integer', Rule::exists('ais_kelompok_kompetensi', 'id')->whereNull('dihapus_pada')],
            'kode_kompetensi' => ['required', 'string', 'max:32', Rule::unique('ais_kompetensi', 'kode_kompetensi')],
            'nama' => ['required', 'string', 'max:255'],
            'definisi' => ['nullable', 'string'],
            'tingkat_maksimum' => ['required', 'integer', 'min:1', 'max:20'],
            'aktif' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'aktif' => $this->boolean('aktif'),
        ]);
    }
}
