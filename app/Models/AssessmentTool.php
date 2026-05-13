<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentTool extends Model
{
    use SoftDeletes;

    protected $table = 'ais_alat_penilaian';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    public const DELETED_AT = 'dihapus_pada';

    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
        'aktif',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(CompetencyToolMapping::class, 'id_alat_penilaian');
    }
}
