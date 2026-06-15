<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendationConfigRevision extends Model
{
    protected $table = 'ais_revisi_konfigurasi_rekomendasi';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = null;

    protected $fillable = [
        'id_versi_matriks',
        'nomor_revisi',
        'konfigurasi',
        'ringkasan_perubahan',
        'id_pengguna_pembuat',
    ];

    protected function casts(): array
    {
        return [
            'konfigurasi' => 'array',
            'nomor_revisi' => 'integer',
        ];
    }

    public function matrixVersion(): BelongsTo
    {
        return $this->belongsTo(MatrixVersion::class, 'id_versi_matriks');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengguna_pembuat');
    }
}
