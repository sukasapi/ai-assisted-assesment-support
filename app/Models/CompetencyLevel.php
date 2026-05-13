<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompetencyLevel extends Model
{
    use SoftDeletes;

    protected $table = 'ais_tingkat_kompetensi';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    public const DELETED_AT = 'dihapus_pada';

    protected $fillable = [
        'id_kompetensi',
        'tingkat',
        'indikator_perilaku',
        'etiket',
        'deskripsi',
    ];

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class, 'id_kompetensi');
    }

    /**
     * Indikator perilaku resmi untuk perilaku kunci; null jika tidak ada / kosong.
     */
    public static function teksIndikatorResmi(?int $idTingkat): ?string
    {
        if ($idTingkat === null) {
            return null;
        }
        $row = self::query()->whereNull('dihapus_pada')->find($idTingkat);
        if ($row === null) {
            return null;
        }
        $t = trim((string) ($row->indikator_perilaku ?? ''));

        return $t !== '' ? $t : null;
    }
}
