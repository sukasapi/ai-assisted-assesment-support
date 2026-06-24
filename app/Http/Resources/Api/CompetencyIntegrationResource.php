<?php

namespace App\Http\Resources\Api;

use App\Models\CompetencyIntegration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CompetencyIntegration
 */
class CompetencyIntegrationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'kompetensi' => $this->competency?->kode_kompetensi,
            'nama_kompetensi' => $this->competency?->nama,
            'kelompok' => $this->competency?->group?->kode,
            'tingkat_target' => $this->tingkat_target !== null ? (int) $this->tingkat_target : null,
            'tingkat_tercapai' => $this->tingkat_tercapai !== null ? (int) $this->tingkat_tercapai : null,
            'selisih_gap' => $this->selisih_gap !== null ? (int) $this->selisih_gap : null,
            'skor_terbobot' => $this->skor_terbobot !== null ? (float) $this->skor_terbobot : null,
            'rekomendasi' => $this->rekomendasi_kode,
            'jumlah_pk' => (int) ($this->jumlah_pk_masuk ?? 0),
            'sumber_utama' => $this->sumber_utama,
        ];
    }
}
