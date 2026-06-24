<?php

namespace App\Http\Requests\Concerns;

use App\Enums\AssessmentEvidenceCollectionMode;
use App\Enums\AssessmentStatus;
use App\Enums\EvidenceSourceType;
use App\Models\Assessment;
use App\Models\AssessmentToolSelection;
use App\Support\AssessmentMatrix;
use Illuminate\Validation\Validator;

trait ValidatesEvidenceForAssessment
{
    protected function aturanBuktiDasar(bool $audioWajib = true): array
    {
        $maksKb = max(1, (int) config('stt.maks_file_mb', 25)) * 1024;
        $mimes = config('stt.ekstensi', ['mp3', 'wav', 'm4a', 'webm', 'ogg', 'flac']);

        $aturanAudio = $audioWajib
            ? ['required_if:jenis_sumber,wawancara', 'file', 'max:'.$maksKb, 'mimes:'.implode(',', $mimes)]
            : ['nullable', 'file', 'max:'.$maksKb, 'mimes:'.implode(',', $mimes)];

        return [
            'id_alat_penilaian' => ['required', 'integer', 'exists:ais_alat_penilaian,id'],
            'id_kompetensi' => ['required', 'integer', 'exists:ais_kompetensi,id'],
            'jenis_sumber' => ['required', 'string', 'in:'.EvidenceSourceType::Teks->value.','.EvidenceSourceType::Wawancara->value],
            'teks_mentah' => ['required_if:jenis_sumber,teks', 'nullable', 'string'],
            'teks_mentah_rich' => ['nullable', 'string'],
            'teks_mentah_normalized' => ['nullable', 'string'],
            'teks_kerja' => ['nullable', 'string'],
            'berkas_audio' => $aturanAudio,
        ];
    }

    protected function validasiKonteksAsesmenSaja(Validator $validator): void
    {
        /** @var Assessment|null $asesmen */
        $asesmen = $this->route('asesmen');
        if (! $asesmen instanceof Assessment) {
            return;
        }

        if ($asesmen->status === AssessmentStatus::SelesaiFinal) {
            $validator->errors()->add('asesmen', 'Asesmen sudah difinalisasi. Bukti tidak dapat diubah.');

            return;
        }

        if ($asesmen->metode_koleksi_bukti === AssessmentEvidenceCollectionMode::PayloadAlat) {
            $validator->errors()->add(
                'metode_koleksi_bukti',
                'Asesmen memakai metode otomatis (payload alat). Koleksi bukti manual dinonaktifkan.',
            );
        }
    }

    protected function validasiKonteksBukti(Validator $validator): void
    {
        $this->validasiKonteksAsesmenSaja($validator);

        /** @var Assessment|null $asesmen */
        $asesmen = $this->route('asesmen');
        if (! $asesmen instanceof Assessment) {
            return;
        }

        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $idAlat = $this->integer('id_alat_penilaian');
        $ada = AssessmentToolSelection::query()
            ->where('id_asesmen', $asesmen->id)
            ->where('id_alat_penilaian', $idAlat)
            ->where('aktif', true)
            ->exists();
        if (! $ada) {
            $validator->errors()->add('id_alat_penilaian', 'Alat tidak termasuk pemilihan asesmen ini.');
        }

        $dipakaiMatriks = AssessmentMatrix::mappingQuery($asesmen)
            ->where('id_alat_penilaian', $idAlat)
            ->where(function ($query): void {
                $query->where('aktif', true)->orWhereNull('aktif');
            })
            ->exists();
        if (! $dipakaiMatriks) {
            $validator->errors()->add('id_alat_penilaian', 'Alat tidak dipakai pada pemetaan matriks asesmen ini.');
        }

        $idKompetensi = $this->integer('id_kompetensi');
        $pasanganValid = AssessmentMatrix::mappingQuery($asesmen)
            ->where('id_alat_penilaian', $idAlat)
            ->where('id_kompetensi', $idKompetensi)
            ->where(function ($query): void {
                $query->where('aktif', true)->orWhereNull('aktif');
            })
            ->exists();
        if (! $pasanganValid) {
            $validator->errors()->add('id_kompetensi', 'Kompetensi tidak dipetakan ke alat ini pada matriks asesmen.');
        }
    }
}
