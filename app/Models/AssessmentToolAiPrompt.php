<?php

namespace App\Models;

use App\Enums\AssessmentToolPromptMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentToolAiPrompt extends Model
{
    protected $table = 'ais_asesmen_template_alat';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_asesmen',
        'id_alat_penilaian',
        'mode',
        'id_template_prompt_ai',
    ];

    protected function casts(): array
    {
        return [
            'mode' => AssessmentToolPromptMode::class,
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'id_asesmen');
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(AssessmentTool::class, 'id_alat_penilaian');
    }

    public function aiPromptTemplate(): BelongsTo
    {
        return $this->belongsTo(AiPromptTemplate::class, 'id_template_prompt_ai');
    }
}
