<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompetencyToolMapping extends Model
{
    use SoftDeletes;

    protected $table = 'ais_pemetaan_kompetensi_alat';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    public const DELETED_AT = 'dihapus_pada';

    protected $fillable = [
        'id_versi_matriks',
        'id_kompetensi',
        'id_alat_penilaian',
        'wajib',
        'bobot',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'wajib' => 'boolean',
            'aktif' => 'boolean',
            'bobot' => 'decimal:4',
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

    public function tool(): BelongsTo
    {
        return $this->belongsTo(AssessmentTool::class, 'id_alat_penilaian');
    }
}
