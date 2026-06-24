<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Target tingkat per kompetensi untuk sebuah versi matriks (profil jabatan tujuan).
 */
class MatrixCompetencyTarget extends Model
{
    protected $table = 'ais_target_kompetensi_matriks';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_versi_matriks',
        'id_kompetensi',
        'tingkat_target',
    ];

    protected function casts(): array
    {
        return [
            'tingkat_target' => 'integer',
        ];
    }

    public function matrixVersion(): BelongsTo
    {
        return $this->belongsTo(MatrixVersion::class, 'id_versi_matriks');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class, 'id_kompetensi');
    }
}
