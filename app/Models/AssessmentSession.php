<?php

namespace App\Models;

use App\Enums\AssessmentSessionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentSession extends Model
{
    protected $table = 'ais_sesi_asesmen';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'kode_sesi',
        'nama',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'catatan',
        'id_pengguna_pembuat',
    ];

    protected function casts(): array
    {
        return [
            'status' => AssessmentSessionStatus::class,
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengguna_pembuat');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class, 'id_sesi_asesmen')->orderByDesc('id');
    }

    public function consultantAssignments(): HasMany
    {
        return $this->hasMany(ConsultantAssignment::class, 'id_sesi_asesmen');
    }
}
