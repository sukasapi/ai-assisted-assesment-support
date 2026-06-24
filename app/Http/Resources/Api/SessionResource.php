<?php

namespace App\Http\Resources\Api;

use App\Models\AssessmentSession;
use App\Support\SessionAggregate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AssessmentSession
 */
class SessionResource extends JsonResource
{
    public function __construct($resource, private readonly bool $denganPeringkat = false)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'kode_sesi' => $this->kode_sesi,
            'nama' => $this->nama,
            'status' => $this->status?->value,
            'tanggal_mulai' => $this->tanggal_mulai?->toDateString(),
            'tanggal_selesai' => $this->tanggal_selesai?->toDateString(),
            'jumlah_asesmen' => $this->assessments_count ?? $this->whenLoaded('assessments', fn () => $this->assessments->count()),
        ];

        if ($this->denganPeringkat && $this->relationLoaded('assessments')) {
            $ringkasan = SessionAggregate::untukSesi($this->resource);
            $data['ringkasan'] = [
                'total' => $ringkasan['total'],
                'rata_job_fit' => $ringkasan['rata_job_fit'],
                'qualified' => $ringkasan['qualified'],
                'not_qualified' => $ringkasan['not_qualified'],
                'final' => $ringkasan['final'],
                'belum_dinilai' => $ringkasan['belum_dinilai'],
            ];
            $data['peringkat'] = AssessmentSummaryResource::collection($ringkasan['peringkat']);
        }

        return $data;
    }
}
