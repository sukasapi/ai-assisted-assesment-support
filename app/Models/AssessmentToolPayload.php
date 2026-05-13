<?php

namespace App\Models;

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
    ];

    protected function casts(): array
    {
        return [
            'hasil_analisis_ai' => 'array',
            'diproses_pada' => 'datetime',
        ];
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
