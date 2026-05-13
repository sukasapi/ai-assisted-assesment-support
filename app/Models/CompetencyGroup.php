<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompetencyGroup extends Model
{
    use SoftDeletes;

    protected $table = 'ais_kelompok_kompetensi';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    public const DELETED_AT = 'dihapus_pada';

    protected $fillable = [
        'kode',
        'nama',
    ];

    public function competencies(): HasMany
    {
        return $this->hasMany(Competency::class, 'id_kelompok_kompetensi');
    }
}
