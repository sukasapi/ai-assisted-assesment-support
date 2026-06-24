<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMatrixRecommendationConfigRequest extends FormRequest
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
            'ringkasan_perubahan' => ['required', 'string', 'min:3', 'max:2000'],
            'logika_agregat' => ['required', 'string', Rule::in(['and', 'or'])],
            'label_qualified' => ['required', 'string', 'max:255'],
            'label_not_qualified' => ['required', 'string', 'max:255'],
            'dimensi' => ['required', 'array', 'min:1'],
            'dimensi.*.kode' => ['required', 'string', 'max:64'],
            'dimensi.*.label' => ['required', 'string', 'max:255'],
            'dimensi.*.logika' => ['required', 'string', Rule::in(['and', 'or'])],
            'dimensi.*.filter_kelompok_kode' => ['nullable', 'array'],
            'dimensi.*.filter_kelompok_kode.*' => ['string', 'max:32'],
            'dimensi.*.aturan' => ['required', 'array', 'min:1'],
            'dimensi.*.aturan.*.jenis' => ['required', 'string', Rule::in(['forbid_tingkat', 'max_count_tingkat', 'min_tingkat_semua'])],
            'dimensi.*.aturan.*.tingkat' => ['required', 'integer', 'min:1', 'max:6'],
            'dimensi.*.aturan.*.maksimum' => ['nullable', 'integer', 'min:0', 'max:99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ringkasan_perubahan.required' => 'Ringkasan perubahan wajib diisi untuk jejak audit.',
        ];
    }
}
