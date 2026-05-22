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
        'job_fit_persen_pratinjau',
        'integrasi_pratinjau_pada',
    ];

    protected function casts(): array
    {
        return [
            'tujuan' => AssessmentPurpose::class,
            'status' => AssessmentStatus::class,
            'tanpa_intray' => 'boolean',
            'metode_koleksi_bukti' => AssessmentEvidenceCollectionMode::class,
            'waktu_finalisasi' => 'datetime',
            'job_fit_persen_pratinjau' => 'decimal:2',
            'integrasi_pratinjau_pada' => 'datetime',
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

    public function competencyIntegrations(): HasMany
    {
        return $this->hasMany(CompetencyIntegration::class, 'id_asesmen');
    }

    /**
     * Scoped binding untuk route nested asesmen/{asesmen}/…/{child}.
     */
    public function resolveChildRouteBinding($childType, $value, $field): ?Model
    {
        if ($childType === 'payload') {
            return $this->toolPayloads()->whereKey($value)->first();
        }

        if ($childType === 'perilaku') {
            return $this->keyBehaviors()->whereKey($value)->first();
        }

        if ($childType === 'bukti') {
            return $this->evidenceItems()->whereKey($value)->first();
        }

        return parent::resolveChildRouteBinding($childType, $value, $field);
    }
}
