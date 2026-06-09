<?php

namespace App\Support;

use App\Models\MatrixVersion;
use App\Models\Participant;
use App\Models\User;
use App\Support\AiPromptComposer;

final class AssessmentFormOptions
{
    /**
     * @return array{
     *     peserta: \Illuminate\Support\Collection,
     *     versiMatriks: \Illuminate\Support\Collection,
     *     asesorKandidat: \Illuminate\Support\Collection,
     *     opsiTemplatePromptAi: list<array<string, mixed>>
     * }
     */
    public static function untukForm(): array
    {
        return [
            'peserta' => Participant::query()
                ->where('aktif', true)
                ->withCount('assessments')
                ->orderBy('nama_lengkap')
                ->get(),
            'versiMatriks' => MatrixVersion::query()->where('aktif', true)->orderBy('nama_versi')->get(),
            'asesorKandidat' => User::query()
                ->where('peran', 'admin')
                ->where('aktif', true)
                ->orderBy('nama')
                ->get(),
            'opsiTemplatePromptAi' => AiPromptComposer::opsiTemplateAktif(),
        ];
    }
}
