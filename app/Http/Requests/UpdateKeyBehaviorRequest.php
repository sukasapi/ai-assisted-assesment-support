<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\PreservesAssessmentTab;
use App\Models\Assessment;
use App\Models\CompetencyLevel;
use App\Models\KeyBehavior;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKeyBehaviorRequest extends FormRequest
{
    use PreservesAssessmentTab;

    public function authorize(): bool
    {
        $asesmen = $this->route('asesmen');
        $pk = $this->route('perilaku');

        if (! $asesmen instanceof Assessment || ! $pk instanceof KeyBehavior) {
            return false;
        }

        if ((int) $pk->id_asesmen !== (int) $asesmen->id) {
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
            'id_tingkat_kompetensi' => ['nullable', 'integer', Rule::exists('ais_tingkat_kompetensi', 'id')->whereNull('dihapus_pada')],
            'alasan_pemilihan' => ['nullable', 'string'],
            'simpan_sebagai_mapping' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            /** @var KeyBehavior $pk */
            $pk = $this->route('perilaku');
            $raw = $this->input('id_tingkat_kompetensi');
            if ($raw === null || $raw === '') {
                return;
            }
            $level = CompetencyLevel::query()
                ->whereNull('dihapus_pada')
                ->find((int) $raw);
            if ($level === null || (int) $level->id_kompetensi !== (int) $pk->id_kompetensi) {
                $validator->errors()->add(
                    'id_tingkat_kompetensi',
                    'Tingkat harus untuk kompetensi yang sama dengan perilaku kunci ini.',
                );
            }
        });
    }
}
