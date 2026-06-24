<?php

namespace App\Http\Requests\Master;

use App\Models\CompetencyLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompetencyLevelRequest extends FormRequest
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
        /** @var CompetencyLevel $tingkatKompetensi */
        $tingkatKompetensi = $this->route('tingkatKompetensi');

        return [
            'id_kompetensi' => ['required', 'integer', Rule::exists('ais_kompetensi', 'id')->whereNull('dihapus_pada')],
            'tingkat' => [
                'required',
                'integer',
                'min:1',
                'max:20',
                Rule::unique('ais_tingkat_kompetensi', 'tingkat')
                    ->where(fn ($q) => $q->where('id_kompetensi', $this->integer('id_kompetensi'))->whereNull('dihapus_pada'))
                    ->ignore($tingkatKompetensi->id),
            ],
            'indikator_perilaku' => ['required', 'string'],
            'etiket' => ['nullable', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
        ];
    }
}
