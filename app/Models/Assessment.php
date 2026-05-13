<?php

namespace App\Models;

use App\Enums\AssessmentEvidenceCollectionMode;
use App\Enums\AssessmentPurpose;
use App\Enums\AssessmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    protected $table = 'ais_asesmen';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_peserta',
        'id_versi_matriks',
        'tujuan',
        'status',
        'tanpa_intray',
        'metode_koleksi_bukti',
        'id_pengguna_pembuat',
        'id_pengguna_finalisasi',
        'waktu_finalisasi',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tujuan' => AssessmentPurpose::class,
            'status' => AssessmentStatus::class,
            'tanpa_intray' => 'boolean',
            'metode_koleksi_bukti' => AssessmentEvidenceCollectionMode::class,
            'waktu_finalisasi' => 'datetime',
        ];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'id_peserta');
    }

    public function matrixVersion(): BelongsTo
    {
        return $this->belongsTo(MatrixVersion::class, 'id_versi_matriks');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengguna_pembuat');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengguna_finalisasi');
    }

    public function assessorAssignments(): HasMany
    {
        return $this->hasMany(AssessmentAssessor::class, 'id_asesmen');
    }

    public function toolSelections(): HasMany
    {
        return $this->hasMany(AssessmentToolSelection::class, 'id_asesmen');
    }

    public function evidenceItems(): HasMany
    {
        return $this->hasMany(Evidence::class, 'id_asesmen');
    }

    public function keyBehaviors(): HasMany
    {
        return $this->hasMany(KeyBehavior::class, 'id_asesmen');
    }

    public function toolPayloads(): HasMany
    {
        return $this->hasMany(AssessmentToolPayload::class, 'id_asesmen')->orderByDesc('id');
    }
}
