<?php

namespace App\Http\Requests;

use App\Enums\EvidenceSourceType;
use App\Http\Requests\Concerns\ValidatesEvidenceForAssessment;
use App\Models\Assessment;
use App\Models\Evidence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateEvidenceRequest extends FormRequest
{
    use ValidatesEvidenceForAssessment;

    public function authorize(): bool
    {
        $asesmen = $this->route('asesmen');
        $bukti = $this->route('bukti');
        if (! $asesmen instanceof Assessment || ! $bukti instanceof Evidence) {
            return false;
        }

        if ((int) $bukti->id_asesmen !== (int) $asesmen->id) {
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
            'jenis_sumber' => ['required', 'string', 'in:'.EvidenceSourceType::Teks->value.','.EvidenceSourceType::Wawancara->value],
            'teks_mentah' => ['nullable', 'string'],
            'teks_mentah_rich' => ['nullable', 'string'],
            'teks_mentah_normalized' => ['nullable', 'string'],
            'teks_kerja' => ['nullable', 'string'],
            'berkas_audio' => ['nullable', 'file', 'max:'.max(1, (int) config('stt.maks_file_mb', 25)) * 1024, 'mimes:'.implode(',', config('stt.ekstensi', ['mp3', 'wav', 'm4a', 'webm']))],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validasiKonteksBukti($validator);

            /** @var Evidence|null $bukti */
            $bukti = $this->route('bukti');
            if (! $bukti instanceof Evidence) {
                return;
            }

            $jenisBaru = EvidenceSourceType::from($this->string('jenis_sumber')->toString());
            $adaTeks = $this->filled('teks_mentah');
            $adaAudio = $this->hasFile('berkas_audio');
            $punyaAudioLama = filled($bukti->path_audio);

            if ($jenisBaru === EvidenceSourceType::Teks) {
                if (! $adaTeks) {
                    $validator->errors()->add('teks_mentah', 'Isi teks bukti.');
                }

                return;
            }

            $punyaSumberWawancara = $adaAudio || $adaTeks || ($bukti->jenis_sumber === EvidenceSourceType::Wawancara && $punyaAudioLama);

            if (! $punyaSumberWawancara) {
                $validator->errors()->add(
                    'berkas_audio',
                    'Unggah berkas audio wawancara atau isi transkrip manual.',
                );
            }
        });
    }

    protected function validasiKonteksBukti(Validator $validator): void
    {
        /** @var Assessment|null $asesmen */
        $asesmen = $this->route('asesmen');
        if (! $asesmen instanceof Assessment) {
            return;
        }

        if ($asesmen->status === \App\Enums\AssessmentStatus::SelesaiFinal) {
            $validator->errors()->add('asesmen', 'Asesmen sudah difinalisasi. Bukti tidak dapat diubah.');

            return;
        }

        if ($asesmen->metode_koleksi_bukti === \App\Enums\AssessmentEvidenceCollectionMode::PayloadAlat) {
            $validator->errors()->add(
                'metode_koleksi_bukti',
                'Asesmen memakai metode otomatis (payload alat). Ubah bukti manual dinonaktifkan.',
            );
        }
    }
}
