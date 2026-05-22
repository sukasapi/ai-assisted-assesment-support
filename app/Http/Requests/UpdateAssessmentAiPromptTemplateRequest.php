<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssessmentAiPromptTemplateRequest extends FormRequest
{
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
        return [
            'id_template_prompt_ai' => [
                'nullable',
                'integer',
                Rule::exists('ais_template_prompt_ai', 'id')
                    ->where('aktif', true)
                    ->whereNull('dihapus_pada'),
            ],
        ];
    }
}
