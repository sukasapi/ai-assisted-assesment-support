<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMatrixVersionRequest extends FormRequest
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
            'kode_versi' => ['required', 'string', 'max:64', Rule::unique('ais_versi_matriks', 'kode_versi')],
            'nama_versi' => ['required', 'string', 'max:255'],
            'kunci_kamus' => ['nullable', 'string', 'max:64'],
            'catatan_konteks' => ['nullable', 'string'],
            'aktif' => ['sometimes', 'boolean'],
            'bawaan' => ['sometimes', 'boolean'],
            'dipublikasikan_pada' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'aktif' => $this->boolean('aktif'),
            'bawaan' => $this->boolean('bawaan'),
            'dipublikasikan_pada' => $this->filled('dipublikasikan_pada') ? $this->input('dipublikasikan_pada') : null,
        ]);
    }
}
