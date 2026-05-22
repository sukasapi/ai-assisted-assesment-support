<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiPromptTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'ais_template_prompt_ai';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    public const DELETED_AT = 'dihapus_pada';

    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
        'teks_instruksi',
        'urutan',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class, 'id_template_prompt_ai');
    }

    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(
            AssessmentTool::class,
            'ais_pemetaan_template_alat',
            'id_template_prompt_ai',
            'id_alat_penilaian',
        );
    }

    public function toolAiPromptOverrides(): HasMany
    {
        return $this->hasMany(AssessmentToolAiPrompt::class, 'id_template_prompt_ai');
    }
}
