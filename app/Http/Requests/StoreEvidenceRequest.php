<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\PreservesAssessmentTab;
use App\Http\Requests\Concerns\ValidatesEvidenceForAssessment;
use App\Models\Assessment;
use Illuminate\Foundation\Http\FormRequest;

class StoreEvidenceRequest extends FormRequest
{
    use PreservesAssessmentTab;
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
        return $this->aturanBuktiDasar(audioWajib: true);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $this->validasiKonteksBukti($validator);
        });
    }
}
