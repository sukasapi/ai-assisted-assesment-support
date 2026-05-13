<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiLog extends Model
{
    protected $table = 'ais_log_ai';

    public $timestamps = false;

    protected $fillable = [
        'id_pengguna',
        'id_asesmen',
        'id_bukti_penilaian',
        'id_payload_alat_asesmen',
        'jalur',
        'nama_model',
        'status',
        'kode_http',
        'metadata',
        'pesan_kesalahan',
        'dibuat_pada',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'dibuat_pada' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengguna');
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'id_asesmen');
    }

    public function evidence(): BelongsTo
    {
        return $this->belongsTo(Evidence::class, 'id_bukti_penilaian');
    }

    public function toolPayload(): BelongsTo
    {
        return $this->belongsTo(AssessmentToolPayload::class, 'id_payload_alat_asesmen');
    }
}
