<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesEvidenceForAssessment;
use App\Models\Assessment;
use Illuminate\Foundation\Http\FormRequest;

class TranscribeEvidencePreviewRequest extends FormRequest
{
    use ValidatesEvidenceForAssessment;

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
        $maksKb = max(1, (int) config('stt.maks_file_mb', 25)) * 1024;
        $mimes = config('stt.ekstensi', ['mp3', 'wav', 'm4a', 'webm', 'ogg', 'flac']);

        return [
            'berkas_audio' => ['required', 'file', 'max:'.$maksKb, 'mimes:'.implode(',', $mimes)],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $this->validasiKonteksAsesmenSaja($validator);

            if (! config('stt.aktif', false)) {
                $validator->errors()->add('berkas_audio', 'Transkripsi otomatis tidak aktif.');
            }
        });
    }
}
