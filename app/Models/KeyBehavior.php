<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeyBehavior extends Model
{
    protected $table = 'ais_perilaku_kunci';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_asesmen',
        'id_alat_penilaian',
        'id_kompetensi',
        'id_bukti_penilaian',
        'id_tingkat_kompetensi',
        'teks_perilaku',
        'alasan_pemilihan',
        'kutipan_referensi',
        'keyakinan',
        'tervalidasi',
        'id_pengguna_validasi',
        'waktu_validasi',
    ];

    protected function casts(): array
    {
        return [
            'tervalidasi' => 'boolean',
            'keyakinan' => 'float',
            'waktu_validasi' => 'datetime',
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

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class, 'id_kompetensi');
    }

    public function evidence(): BelongsTo
    {
        return $this->belongsTo(Evidence::class, 'id_bukti_penilaian');
    }

    public function competencyLevel(): BelongsTo
    {
        return $this->belongsTo(CompetencyLevel::class, 'id_tingkat_kompetensi');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengguna_validasi');
    }
}
