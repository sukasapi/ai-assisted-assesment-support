<?php

namespace App\Http\Requests;

use App\Enums\AssessmentEvidenceCollectionMode;
use App\Models\Assessment;
use App\Models\AssessmentToolSelection;
use App\Models\CompetencyToolMapping;
use Illuminate\Foundation\Http\FormRequest;

class StoreAssessmentToolPayloadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $asesmen = $this->route('asesmen');
        if (! $asesmen instanceof Assessment) {
            return false;
        }

        return $this->user()?->can('update', $asesmen) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id_alat_penilaian' => ['required', 'integer', 'exists:ais_alat_penilaian,id'],
            'teks_muatan' => ['required', 'string', 'max:65535'],
            'teks_muatan_rich' => ['nullable', 'string'],
            'teks_muatan_normalized' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            /** @var Assessment $asesmen */
            $asesmen = $this->route('asesmen');
            $idAlat = $this->integer('id_alat_penilaian');
            $ada = AssessmentToolSelection::query()
                ->where('id_asesmen', $asesmen->id)
                ->where('id_alat_penilaian', $idAlat)
                ->where('aktif', true)
                ->exists();
            if (! $ada) {
                $validator->errors()->add('id_alat_penilaian', 'Alat tidak termasuk pemilihan asesmen ini.');
            }
            $dipakaiMatriks = CompetencyToolMapping::query()
                ->where('id_versi_matriks', $asesmen->id_versi_matriks)
                ->where('id_alat_penilaian', $idAlat)
                ->where(function ($query): void {
                    $query->where('aktif', true)->orWhereNull('aktif');
                })
                ->exists();
            if (! $dipakaiMatriks) {
                $validator->errors()->add('id_alat_penilaian', 'Alat tidak dipakai pada pemetaan matriks asesmen ini.');
            }
            if ($asesmen->metode_koleksi_bukti === AssessmentEvidenceCollectionMode::Manual) {
                $validator->errors()->add(
                    'metode_koleksi_bukti',
                    'Asesmen memakai metode manual (bukti per kompetensi). Unggah payload alat dinonaktifkan. Ubah metode koleksi bukti jika Anda ingin memakai alur otomatis.',
                );
            }
        });
    }
}
