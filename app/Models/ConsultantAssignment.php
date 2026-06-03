<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ConsultantAssignment extends Model
{
    protected $table = 'ais_penugasan_konsultan';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_pengguna',
        'id_sesi_asesmen',
        'token_akses',
        'aktif',
        'kedaluwarsa_pada',
        'id_pengguna_pembuat',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'kedaluwarsa_pada' => 'datetime',
        ];
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengguna');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AssessmentSession::class, 'id_sesi_asesmen');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengguna_pembuat');
    }

    public function assessments(): BelongsToMany
    {
        return $this->belongsToMany(
            Assessment::class,
            'ais_penugasan_konsultan_asesmen',
            'id_penugasan_konsultan',
            'id_asesmen',
        );
    }

    public function masihBerlaku(): bool
    {
        if (! $this->aktif) {
            return false;
        }

        if ($this->kedaluwarsa_pada !== null && $this->kedaluwarsa_pada->isPast()) {
            return false;
        }

        return true;
    }
}
