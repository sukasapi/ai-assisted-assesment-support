<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Salinan beku pemetaan kompetensi–alat untuk satu asesmen (lihat AssessmentMatrix).
 * Kolomnya cermin dari CompetencyToolMapping agar dapat dipakai bergantian di scoring.
 */
class AssessmentMatrixMappingSnapshot extends Model
{
    protected $table = 'ais_snapshot_pemetaan_asesmen';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_asesmen',
        'id_kompetensi',
        'id_alat_penilaian',
        'wajib',
        'bobot',
        'tingkat_target',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'wajib' => 'boolean',
            'aktif' => 'boolean',
            'bobot' => 'decimal:4',
            'tingkat_target' => 'integer',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'id_asesmen');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class, 'id_kompetensi');
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(AssessmentTool::class, 'id_alat_penilaian');
    }
}
