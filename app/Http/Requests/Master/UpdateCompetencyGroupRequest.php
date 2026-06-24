<?php

namespace App\Http\Requests\Master;

use App\Models\CompetencyGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompetencyGroupRequest extends FormRequest
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
        /** @var CompetencyGroup $kelompok */
        $kelompok = $this->route('kelompokKompetensi');

        return [
            'kode' => ['required', 'string', 'max:16', Rule::unique('ais_kelompok_kompetensi', 'kode')->ignore($kelompok->id)],
            'nama' => ['required', 'string', 'max:255'],
        ];
    }
}
