<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetencyIntegration extends Model
{
    protected $table = 'ais_integrasi_kompetensi';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_asesmen',
        'id_kompetensi',
        'tingkat_target',
        'tingkat_tercapai',
        'skor_terbobot',
        'selisih_gap',
        'rekomendasi_kode',
        'rekomendasi_teks',
        'jumlah_pk_masuk',
        'detail_bobot',
        'sumber_utama',
        'versi_perhitungan',
        'dihitung_pada',
        'id_pengguna_pemicu',
    ];

    protected function casts(): array
    {
        return [
            'skor_terbobot' => 'decimal:4',
            'detail_bobot' => 'array',
            'dihitung_pada' => 'datetime',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'id_asesmen');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class, 'id_kompetensi');
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengguna_pemicu');
    }
}
