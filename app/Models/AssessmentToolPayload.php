<?php

namespace App\Models;

use App\Enums\PayloadAnalysisStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentToolPayload extends Model
{
    protected $table = 'ais_payload_alat_asesmen';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_asesmen',
        'id_alat_penilaian',
        'teks_muatan',
        'teks_muatan_rich',
        'teks_muatan_normalized',
        'id_pengguna_pengunggah',
        'hasil_analisis_ai',
        'diproses_pada',
        'status_analisis',
        'pesan_status_analisis',
        'dijadwalkan_pada',
    ];

    protected function casts(): array
    {
        return [
            'hasil_analisis_ai' => 'array',
            'diproses_pada' => 'datetime',
            'status_analisis' => PayloadAnalysisStatus::class,
            'dijadwalkan_pada' => 'datetime',
        ];
    }

    public function tandaiStatusAnalisis(PayloadAnalysisStatus $status, ?string $pesan = null): void
    {
        $data = [
            'status_analisis' => $status,
            'pesan_status_analisis' => $pesan,
        ];

        if ($status === PayloadAnalysisStatus::Antrian) {
            $data['dijadwalkan_pada'] = now();
        }

        if ($status === PayloadAnalysisStatus::Berhasil) {
            $data['pesan_status_analisis'] = null;
        }

        $this->update($data);
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'id_asesmen');
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(AssessmentTool::class, 'id_alat_penilaian');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengguna_pengunggah');
    }
}
