<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evidence extends Model
{
    protected $table = 'ais_bukti_penilaian';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_asesmen',
        'id_alat_penilaian',
        'id_kompetensi',
        'teks_mentah',
        'teks_mentah_rich',
        'teks_mentah_normalized',
        'teks_kerja',
        'ai_tingkat',
        'ai_alasan',
        'ai_keyakinan',
        'ai_muatan',
        'ai_dinilai_pada',
    ];

    protected function casts(): array
    {
        return [
            'ai_muatan' => 'array',
            'ai_keyakinan' => 'decimal:4',
            'ai_dinilai_pada' => 'datetime',
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

    public function keyBehaviors(): HasMany
    {
        return $this->hasMany(KeyBehavior::class, 'id_bukti_penilaian');
    }
}
