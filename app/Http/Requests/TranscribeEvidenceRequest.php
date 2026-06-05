<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesEvidenceForAssessment;
use App\Models\Assessment;
use App\Models\Evidence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TranscribeEvidenceRequest extends FormRequest
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
        $maksKb = max(1, (int) config('stt.maks_file_mb', 25)) * 1024;
        $mimes = config('stt.ekstensi', ['mp3', 'wav', 'm4a', 'webm', 'ogg', 'flac']);

        return [
            'berkas_audio' => ['nullable', 'file', 'max:'.$maksKb, 'mimes:'.implode(',', $mimes)],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validasiKonteksAsesmenSaja($validator);

            if (! config('stt.aktif', false)) {
                $validator->errors()->add('berkas_audio', 'Transkripsi otomatis tidak aktif.');

                return;
            }

            /** @var Evidence|null $bukti */
            $bukti = $this->route('bukti');
            if (! $bukti instanceof Evidence) {
                return;
            }

            if (! $this->hasFile('berkas_audio') && ! filled($bukti->path_audio)) {
                $validator->errors()->add('berkas_audio', 'Pilih berkas audio atau unggah audio baru.');
            }
        });
    }
}
