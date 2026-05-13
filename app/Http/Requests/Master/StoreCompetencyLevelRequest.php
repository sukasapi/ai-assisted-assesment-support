<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompetencyLevelRequest extends FormRequest
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
            'id_kompetensi' => ['required', 'integer', Rule::exists('ais_kompetensi', 'id')->whereNull('dihapus_pada')],
            'tingkat' => [
                'required',
                'integer',
                'min:1',
                'max:20',
                Rule::unique('ais_tingkat_kompetensi', 'tingkat')->where(
                    fn ($q) => $q->where('id_kompetensi', $this->integer('id_kompetensi'))->whereNull('dihapus_pada')
                ),
            ],
            'indikator_perilaku' => ['required', 'string'],
            'etiket' => ['nullable', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
        ];
    }
}
