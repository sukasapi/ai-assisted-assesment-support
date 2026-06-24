<?php

namespace App\Http\Resources\Api;

use App\Models\Assessment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Detail asesmen lengkap dengan hasil integrasi & perilaku kunci disahkan.
 *
 * @mixin Assessment
 */
class AssessmentResource extends JsonResource
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
            'peserta' => new ParticipantResource($this->whenLoaded('participant')),
            'matriks' => [
                'kode_versi' => $this->matrixVersion?->kode_versi,
                'nama_versi' => $this->matrixVersion?->nama_versi,
            ],
            'tujuan' => $this->tujuan?->value,
            'status' => $this->status?->value,
            'strategi_agregasi' => $this->strategi_agregasi_alat?->value,
            'job_fit_persen' => $this->job_fit_persen_pratinjau !== null ? (float) $this->job_fit_persen_pratinjau : null,
            'rekomendasi_agregat' => $this->kode_rekomendasi_agregat,
            'rekomendasi_label' => $detail['label'] ?? null,
            'rekomendasi_dimensi' => $detail['dimensi'] ?? null,
            'integrasi_pratinjau_pada' => $this->integrasi_pratinjau_pada?->toIso8601String(),
            'waktu_finalisasi' => $this->waktu_finalisasi?->toIso8601String(),
            'asesor' => $this->whenLoaded('assessorAssignments', fn () => $this->assessorAssignments
                ->map(fn ($a) => $a->user?->nama)
                ->filter()
                ->values()),
            'kompetensi' => CompetencyIntegrationResource::collection($this->whenLoaded('competencyIntegrations')),
            'perilaku_kunci' => KeyBehaviorResource::collection($this->whenLoaded('keyBehaviors')),
        ];
    }
}
