<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\PreservesAssessmentTab;
use App\Models\Assessment;
use App\Models\AssessmentToolSelection;
use App\Models\CompetencyToolMapping;
use App\Models\CompetencyLevel;
use App\Models\Evidence;
use App\Models\KeyBehavior;
use Illuminate\Foundation\Http\FormRequest;

class StoreKeyBehaviorRequest extends FormRequest
{
    use PreservesAssessmentTab;

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
            'id_kompetensi' => ['required', 'integer', 'exists:ais_kompetensi,id'],
            'id_bukti_penilaian' => ['nullable', 'integer', 'exists:ais_bukti_penilaian,id'],
            'id_tingkat_kompetensi' => ['nullable', 'integer', 'exists:ais_tingkat_kompetensi,id'],
            'teks_perilaku' => ['required', 'string'],
            'alasan_pemilihan' => ['nullable', 'string'],
            'kutipan_referensi' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            /** @var Assessment $asesmen */
            $asesmen = $this->route('asesmen');
            $idAlat = $this->integer('id_alat_penilaian');
            $adaAlat = AssessmentToolSelection::query()
                ->where('id_asesmen', $asesmen->id)
                ->where('id_alat_penilaian', $idAlat)
                ->where('aktif', true)
                ->exists();
            if (! $adaAlat) {
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

            $idBukti = $this->input('id_bukti_penilaian');
            if ($idBukti !== null && $idBukti !== '') {
                $bukti = Evidence::query()->find((int) $idBukti);
                if ($bukti === null || (int) $bukti->id_asesmen !== (int) $asesmen->id) {
                    $validator->errors()->add('id_bukti_penilaian', 'Bukti tidak valid untuk asesmen ini.');
                }
            }

            $idKompetensi = $this->integer('id_kompetensi');
            $pasanganMapped = CompetencyToolMapping::query()
                ->where('id_versi_matriks', $asesmen->id_versi_matriks)
                ->where('id_alat_penilaian', $idAlat)
                ->where('id_kompetensi', $idKompetensi)
                ->where(function ($query): void {
                    $query->where('aktif', true)->orWhereNull('aktif');
                })
                ->exists();
            if (! $pasanganMapped) {
                $validator->errors()->add('id_kompetensi', 'Kompetensi tidak dipetakan ke alat ini pada matriks asesmen.');
            }
            $idTingkat = $this->input('id_tingkat_kompetensi');
            if ($idTingkat !== null && $idTingkat !== '') {
                $level = CompetencyLevel::query()
                    ->whereNull('dihapus_pada')
                    ->find((int) $idTingkat);
                if ($level === null || (int) $level->id_kompetensi !== $idKompetensi) {
                    $validator->errors()->add('id_tingkat_kompetensi', 'Tingkat harus untuk kompetensi yang dipilih.');
                }
            }

            $duplikat = KeyBehavior::query()
                ->where('id_asesmen', $asesmen->id)
                ->where('id_alat_penilaian', $this->integer('id_alat_penilaian'))
                ->where('id_kompetensi', $this->integer('id_kompetensi'))
                ->exists();
            if ($duplikat) {
                $validator->errors()->add(
                    'id_kompetensi',
                    'Sudah ada perilaku kunci untuk alat dan kompetensi ini pada asesmen ini. Ubah lewat Edit pada tabel, bukan tambah ganda.',
                );
            }
        });
    }
}
