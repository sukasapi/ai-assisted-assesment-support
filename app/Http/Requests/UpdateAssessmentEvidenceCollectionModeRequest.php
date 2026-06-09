<?php

namespace App\Http\Requests;

use App\Enums\AssessmentEvidenceCollectionMode;
use App\Http\Requests\Concerns\PreservesAssessmentTab;
use App\Models\Assessment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssessmentEvidenceCollectionModeRequest extends FormRequest
{
    use PreservesAssessmentTab;

    public function authorize(): bool
    {
        $asesmen = $this->route('asesmen');

        return $asesmen instanceof Assessment
            && ($this->user()?->can('update', $asesmen) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'metode_koleksi_bukti' => ['required', 'string', Rule::enum(AssessmentEvidenceCollectionMode::class)],
        ];
    }
}
