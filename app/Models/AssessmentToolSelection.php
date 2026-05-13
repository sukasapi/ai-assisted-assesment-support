<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentToolSelection extends Model
{
    protected $table = 'ais_pemilihan_alat_asesmen';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_asesmen',
        'id_alat_penilaian',
        'wajib',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'wajib' => 'boolean',
            'aktif' => 'boolean',
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
}
