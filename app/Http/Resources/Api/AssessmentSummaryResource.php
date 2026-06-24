<?php

namespace App\Http\Resources\Api;

use App\Models\Assessment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ringkasan asesmen untuk daftar / peringkat.
 *
 * @mixin Assessment
 */
class AssessmentSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $detail = is_array($this->detail_rekomendasi_agregat) ? $this->detail_rekomendasi_agregat : [];

        return [
            'id' => $this->id,
            'id_sesi_asesmen' => $this->id_sesi_asesmen,
            'peserta' => [
                'kode' => $this->participant?->kode_peserta,
                'nama' => $this->participant?->nama_lengkap,
            ],
            'tujuan' => $this->tujuan?->value,
            'status' => $this->status?->value,
            'job_fit_persen' => $this->job_fit_persen_pratinjau !== null ? (float) $this->job_fit_persen_pratinjau : null,
            'rekomendasi_agregat' => $this->kode_rekomendasi_agregat,
            'rekomendasi_label' => $detail['label'] ?? null,
            'integrasi_pratinjau_pada' => $this->integrasi_pratinjau_pada?->toIso8601String(),
            'difinalisasi' => $this->status?->value === 'selesai_final',
        ];
    }
}
