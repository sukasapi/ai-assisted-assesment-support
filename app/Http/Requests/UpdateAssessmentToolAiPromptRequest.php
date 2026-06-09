<?php

namespace App\Http\Requests;

use App\Enums\AssessmentToolPromptMode;
use App\Http\Requests\Concerns\PreservesAssessmentTab;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssessmentToolAiPromptRequest extends FormRequest
{
    use PreservesAssessmentTab;

    public function authorize(): bool
    {
        $asesmen = $this->route('asesmen');

        return $asesmen !== null && ($this->user()?->can('update', $asesmen) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $asesmen = $this->route('asesmen');
        $idAsesmen = $asesmen?->id;

        return [
            'prompt_alat' => ['required', 'array'],
            'prompt_alat.*.mode' => ['required', 'string', Rule::enum(AssessmentToolPromptMode::class)],
            'prompt_alat.*.id_template_prompt_ai' => [
                'nullable',
                'integer',
                Rule::exists('ais_template_prompt_ai', 'id')
                    ->where('aktif', true)
                    ->whereNull('dihapus_pada'),
            ],
            'prompt_alat.*.id_alat_penilaian' => [
                'required',
                'integer',
                Rule::exists('ais_pemilihan_alat_asesmen', 'id_alat_penilaian')
                    ->where('id_asesmen', $idAsesmen)
                    ->where('aktif', true),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            foreach ($this->input('prompt_alat', []) as $key => $baris) {
                if (! is_array($baris)) {
                    continue;
                }
                $mode = (string) ($baris['mode'] ?? '');
                if ($mode === AssessmentToolPromptMode::Custom->value
                    && empty($baris['id_template_prompt_ai'])) {
                    $validator->errors()->add(
                        "prompt_alat.{$key}.id_template_prompt_ai",
                        'Pilih template bila mode «Template khusus».'
                    );
                }
            }
        });
    }
}
