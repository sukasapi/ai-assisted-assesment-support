<?php

namespace App\Support;

use App\Enums\AssessmentToolPromptMode;
use App\Models\AiPromptTemplate;
use App\Models\Assessment;
use App\Models\AssessmentToolAiPrompt;
use Illuminate\Support\Collection;

/**
 * Menentukan template prompt efektif per asesmen × alat.
 */
final class AiPromptTemplateResolver
{
    public static function templateEfektif(?Assessment $asesmen, ?int $idAlat): ?AiPromptTemplate
    {
        if ($asesmen === null || $idAlat === null || $idAlat <= 0) {
            return null;
        }

        $override = self::overrideBaris($asesmen, $idAlat);

        if ($override !== null) {
            return match ($override->mode) {
                AssessmentToolPromptMode::None => null,
                AssessmentToolPromptMode::Custom => self::templateAktif($override->id_template_prompt_ai),
                AssessmentToolPromptMode::Master => self::templateMasterUntukAlat($idAlat)
                    ?? self::templateAktif($asesmen->id_template_prompt_ai),
            };
        }

        return self::templateMasterUntukAlat($idAlat)
            ?? self::templateAktif($asesmen->id_template_prompt_ai);
    }

    public static function teksInstruksiUntukAlat(?Assessment $asesmen, ?int $idAlat): string
    {
        $template = self::templateEfektif($asesmen, $idAlat);

        return $template !== null ? trim((string) $template->teks_instruksi) : '';
    }

    /**
     * @return array{kode: string|null, nama: string|null, sumber: string|null}
     */
    public static function metadataUntukAlat(?Assessment $asesmen, ?int $idAlat): array
    {
        $template = self::templateEfektif($asesmen, $idAlat);
        if ($template === null) {
            return ['kode' => null, 'nama' => null, 'sumber' => null];
        }

        $sumber = self::labelSumber($asesmen, $idAlat, $template);

        return [
            'kode' => $template->kode,
            'nama' => $template->nama,
            'sumber' => $sumber,
        ];
    }

    /**
     * @return list<array{
     *   id_alat: int,
     *   kode: string,
     *   nama: string,
     *   mode: string,
     *   mode_label: string,
     *   id_template: int|null,
     *   template_label: string,
     *   master_label: string|null,
     * }>
     */
    public static function ringkasanPerAlatAktif(Assessment $asesmen): array
    {
        $asesmen->loadMissing(['toolSelections.tool', 'toolAiPrompts.aiPromptTemplate']);

        $overrideByAlat = $asesmen->toolAiPrompts->keyBy('id_alat_penilaian');
        $masterByAlat = self::templateMasterPerAlat();

        $hasil = [];
        foreach ($asesmen->toolSelections->where('aktif', true) as $pemilihan) {
            $alat = $pemilihan->tool;
            if ($alat === null) {
                continue;
            }
            $idAlat = (int) $alat->id;
            $override = $overrideByAlat->get($idAlat);
            $mode = $override?->mode ?? AssessmentToolPromptMode::Master;
            $masterTpl = $masterByAlat->get($idAlat);
            $efektif = self::templateEfektif($asesmen, $idAlat);

            $hasil[] = [
                'id_alat' => $idAlat,
                'kode' => (string) $alat->kode,
                'nama' => (string) $alat->nama,
                'mode' => $mode->value,
                'mode_label' => $mode->label(),
                'id_template' => $mode === AssessmentToolPromptMode::Custom
                    ? $override?->id_template_prompt_ai
                    : null,
                'template_label' => $efektif !== null
                    ? $efektif->nama.' ('.$efektif->kode.')'
                    : '— tanpa template —',
                'master_label' => $masterTpl !== null
                    ? $masterTpl->nama.' ('.$masterTpl->kode.')'
                    : null,
            ];
        }

        usort($hasil, static fn (array $a, array $b): int => strcmp($a['kode'], $b['kode']));

        return $hasil;
    }

    /**
     * Terapkan template ke semua alat aktif pada asesmen (mode custom).
     */
    public static function terapkanKeSemuaAlatAktif(Assessment $asesmen, ?int $idTemplate): void
    {
        $asesmen->loadMissing('toolSelections');
        foreach ($asesmen->toolSelections->where('aktif', true) as $pemilihan) {
            self::simpanOverride(
                $asesmen->id,
                (int) $pemilihan->id_alat_penilaian,
                $idTemplate === null
                    ? AssessmentToolPromptMode::None
                    : AssessmentToolPromptMode::Custom,
                $idTemplate,
            );
        }
    }

    public static function simpanOverride(
        int $idAsesmen,
        int $idAlat,
        AssessmentToolPromptMode $mode,
        ?int $idTemplate = null,
    ): void {
        if ($mode === AssessmentToolPromptMode::Custom && $idTemplate === null) {
            $mode = AssessmentToolPromptMode::None;
        }

        AssessmentToolAiPrompt::query()->updateOrCreate(
            [
                'id_asesmen' => $idAsesmen,
                'id_alat_penilaian' => $idAlat,
            ],
            [
                'mode' => $mode,
                'id_template_prompt_ai' => $mode === AssessmentToolPromptMode::Custom ? $idTemplate : null,
            ],
        );
    }

    /**
     * Inisialisasi baris override mode=master untuk alat aktif (tanpa mengubah yang sudah ada).
     */
    public static function inisialisasiAlatAktifBilaPerlu(Assessment $asesmen): void
    {
        $asesmen->loadMissing('toolSelections');
        foreach ($asesmen->toolSelections->where('aktif', true) as $pemilihan) {
            AssessmentToolAiPrompt::query()->firstOrCreate(
                [
                    'id_asesmen' => $asesmen->id,
                    'id_alat_penilaian' => $pemilihan->id_alat_penilaian,
                ],
                ['mode' => AssessmentToolPromptMode::Master],
            );
        }
    }

    private static function overrideBaris(Assessment $asesmen, int $idAlat): ?AssessmentToolAiPrompt
    {
        if ($asesmen->relationLoaded('toolAiPrompts')) {
            return $asesmen->toolAiPrompts->firstWhere('id_alat_penilaian', $idAlat);
        }

        return AssessmentToolAiPrompt::query()
            ->where('id_asesmen', $asesmen->id)
            ->where('id_alat_penilaian', $idAlat)
            ->first();
    }

    private static function templateMasterUntukAlat(int $idAlat): ?AiPromptTemplate
    {
        return self::templateMasterPerAlat()->get($idAlat);
    }

    /**
     * @return Collection<int, AiPromptTemplate> keyed by id_alat
     */
    private static function templateMasterPerAlat(): Collection
    {
        $templates = AiPromptTemplate::query()
            ->where('aktif', true)
            ->whereNull('dihapus_pada')
            ->with(['tools:id,kode'])
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();

        $byAlat = [];
        foreach ($templates as $template) {
            foreach ($template->tools as $tool) {
                $idAlat = (int) $tool->id;
                if (! isset($byAlat[$idAlat])) {
                    $byAlat[$idAlat] = $template;
                }
            }
        }

        return collect($byAlat);
    }

    private static function templateAktif(?int $idTemplate): ?AiPromptTemplate
    {
        if ($idTemplate === null || $idTemplate <= 0) {
            return null;
        }

        return AiPromptTemplate::query()
            ->where('id', $idTemplate)
            ->where('aktif', true)
            ->whereNull('dihapus_pada')
            ->first();
    }

    private static function labelSumber(?Assessment $asesmen, int $idAlat, AiPromptTemplate $template): string
    {
        $override = $asesmen !== null ? self::overrideBaris($asesmen, $idAlat) : null;
        if ($override?->mode === AssessmentToolPromptMode::Custom) {
            return 'asesmen_per_alat';
        }
        if ($override?->mode === AssessmentToolPromptMode::None) {
            return 'asesmen_per_alat';
        }

        $master = self::templateMasterPerAlat()->get($idAlat);
        if ($master !== null && $master->id === $template->id) {
            return 'master';
        }

        return 'asesmen_global';
    }
}
