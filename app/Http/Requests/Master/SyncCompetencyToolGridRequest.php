<?php

namespace App\Http\Requests\Master;

use App\Models\AssessmentTool;
use App\Models\Competency;
use App\Models\CompetencyToolMapping;
use App\Models\MatrixVersion;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SyncCompetencyToolGridRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sel' => ['nullable', 'array'],
            'sel.*' => [
                'required',
                'string',
                'regex:/^\d+-\d+$/',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || ! preg_match('/^(\d+)-(\d+)$/', $value, $m)) {
                        return;
                    }
                    $cId = (int) $m[1];
                    $tId = (int) $m[2];
                    if (! Competency::query()->whereKey($cId)->whereNull('dihapus_pada')->where('aktif', true)->exists()) {
                        $fail('Pasangan kompetensi–alat tidak valid.');
                    }
                    if (! AssessmentTool::query()->whereKey($tId)->whereNull('dihapus_pada')->where('aktif', true)->exists()) {
                        $fail('Pasangan kompetensi–alat tidak valid.');
                    }
                },
            ],
            'hapus_semua' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            /** @var MatrixVersion|null $versi */
            $versi = $this->route('versiMatriks');
            if (! $versi instanceof MatrixVersion) {
                return;
            }
            $desired = $this->desiredPairKeys();
            $existing = CompetencyToolMapping::query()
                ->where('id_versi_matriks', $versi->id)
                ->count();
            if (count($desired) === 0 && $existing > 0 && ! $this->boolean('hapus_semua')) {
                $v->errors()->add(
                    'hapus_semua',
                    'Menyimpan tanpa centang akan menghapus semua pemetaan di versi ini. Centang konfirmasi di bawah jika memang ingin mengosongkan, atau pilih minimal satu pasangan kompetensi–alat.',
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'hapus_semua' => $this->boolean('hapus_semua'),
        ]);
    }

    /**
     * @return array<string, true>
     */
    public function desiredPairKeys(): array
    {
        $out = [];
        foreach ($this->input('sel', []) as $item) {
            if (is_string($item) && preg_match('/^\d+-\d+$/', $item)) {
                $out[$item] = true;
            }
        }

        return $out;
    }
}
