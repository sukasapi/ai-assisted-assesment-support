<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MatrixVersion extends Model
{
    use SoftDeletes;

    protected $table = 'ais_versi_matriks';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    public const DELETED_AT = 'dihapus_pada';

    protected $fillable = [
        'kode_versi',
        'nama_versi',
        'kunci_kamus',
        'catatan_konteks',
        'aktif',
        'bawaan',
        'dipublikasikan_pada',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'bawaan' => 'boolean',
            'dipublikasikan_pada' => 'datetime',
        ];
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(CompetencyToolMapping::class, 'id_versi_matriks');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class, 'id_versi_matriks');
    }
}
