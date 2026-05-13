<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Competency extends Model
{
    use SoftDeletes;

    protected $table = 'ais_kompetensi';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    public const DELETED_AT = 'dihapus_pada';

    protected $fillable = [
        'id_kelompok_kompetensi',
        'kode_kompetensi',
        'nama',
        'definisi',
        'tingkat_maksimum',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(CompetencyGroup::class, 'id_kelompok_kompetensi');
    }

    public function levels(): HasMany
    {
        return $this->hasMany(CompetencyLevel::class, 'id_kompetensi')->orderBy('tingkat');
    }

    public function toolMappings(): HasMany
    {
        return $this->hasMany(CompetencyToolMapping::class, 'id_kompetensi');
    }
}
