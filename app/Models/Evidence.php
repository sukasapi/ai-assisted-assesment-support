<?php

namespace App\Models;

use App\Enums\EvidenceSourceType;
use App\Enums\EvidenceTranscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evidence extends Model
{
    use SoftDeletes;

    protected $table = 'ais_bukti_penilaian';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    public const DELETED_AT = 'dihapus_pada';

    protected $fillable = [
        'id_asesmen',
        'id_alat_penilaian',
        'id_kompetensi',
        'jenis_sumber',
        'teks_mentah',
        'teks_mentah_rich',
        'teks_mentah_normalized',
        'teks_kerja',
        'path_audio',
        'mime_audio',
        'durasi_audio_detik',
        'status_transkripsi',
        'pesan_status_transkripsi',
        'ai_tingkat',
        'ai_alasan',
        'ai_keyakinan',
        'ai_muatan',
        'ai_dinilai_pada',
    ];

    protected function casts(): array
    {
        return [
            'jenis_sumber' => EvidenceSourceType::class,
            'status_transkripsi' => EvidenceTranscriptionStatus::class,
            'ai_muatan' => 'array',
            'ai_keyakinan' => 'decimal:4',
            'ai_dinilai_pada' => 'datetime',
        ];
    }

    public function resetAiFields(): void
    {
        $this->forceFill([
            'ai_tingkat' => null,
            'ai_alasan' => null,
            'ai_keyakinan' => null,
            'ai_muatan' => null,
            'ai_dinilai_pada' => null,
        ]);
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'id_asesmen');
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(AssessmentTool::class, 'id_alat_penilaian');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class, 'id_kompetensi');
    }

    public function keyBehaviors(): HasMany
    {
        return $this->hasMany(KeyBehavior::class, 'id_bukti_penilaian');
    }
}
