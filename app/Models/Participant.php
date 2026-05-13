<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Participant extends Model
{
    use SoftDeletes;

    protected $table = 'ais_peserta';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    public const DELETED_AT = 'dihapus_pada';

    protected $fillable = [
        'kode_peserta',
        'nama_lengkap',
        'alamat_surel',
        'jabatan',
        'pendidikan',
        'tanggal_lahir',
        'catatan',
        'aktif',
        'id_versi_matriks',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'aktif' => 'boolean',
        ];
    }

    public function matrixVersion(): BelongsTo
    {
        return $this->belongsTo(MatrixVersion::class, 'id_versi_matriks');
    }
}
