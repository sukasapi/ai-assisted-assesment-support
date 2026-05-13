<?php

namespace App\Http\Requests;

use App\Enums\AssessmentEvidenceCollectionMode;
use App\Enums\AssessmentPurpose;
use App\Models\Assessment;
use App\Models\Participant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Assessment::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id_peserta' => ['required', 'integer', Rule::exists('ais_peserta', 'id')->whereNull('dihapus_pada')->where('aktif', true)],
            'id_versi_matriks' => ['required', 'integer', Rule::exists('ais_versi_matriks', 'id')->whereNull('dihapus_pada')],
            'tujuan' => ['required', 'string', Rule::enum(AssessmentPurpose::class)],
            'metode_koleksi_bukti' => ['required', 'string', Rule::enum(AssessmentEvidenceCollectionMode::class)],
            'tanpa_intray' => ['sometimes', 'boolean'],
            'id_asesor' => ['nullable', 'array'],
            'id_asesor.*' => [
                'integer',
                Rule::exists('ais_pengguna', 'id')->whereIn('peran', ['admin', 'konsultan']),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $idPeserta = $this->integer('id_peserta');
            $idVersi = $this->integer('id_versi_matriks');
            $peserta = Participant::query()->find($idPeserta);
            if ($peserta !== null && $peserta->id_versi_matriks !== null && (int) $peserta->id_versi_matriks !== $idVersi) {
                $validator->errors()->add(
                    'id_versi_matriks',
                    'Versi matriks harus sama dengan yang terpasang pada peserta terpilih.'
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tanpa_intray' => $this->boolean('tanpa_intray'),
            'metode_koleksi_bukti' => $this->filled('metode_koleksi_bukti')
                ? $this->string('metode_koleksi_bukti')->toString()
                : AssessmentEvidenceCollectionMode::Manual->value,
        ]);
    }
}
