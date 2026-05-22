<?php

namespace Database\Seeders;

use App\Models\AiOpenRouterModel;
use App\Models\AiPromptTemplate;
use App\Models\AssessmentTool;
use App\Support\AiModelCatalog;
use Illuminate\Database\Seeder;

class AiMasterSeeder extends Seeder
{
    public function run(): void
    {
        $data = require __DIR__.'/data/ai_master_seed.php';

        foreach ($data['template_prompt'] as $row) {
            $template = AiPromptTemplate::query()->updateOrCreate(
                ['kode' => $row['kode']],
                [
                    'nama' => $row['nama'],
                    'deskripsi' => $row['deskripsi'] ?? null,
                    'teks_instruksi' => $row['teks_instruksi'] ?? '',
                    'urutan' => (int) ($row['urutan'] ?? 0),
                    'aktif' => true,
                ],
            );

            $kodeAlat = $row['alat_kode'] ?? [];
            if (is_array($kodeAlat) && $kodeAlat !== []) {
                $ids = AssessmentTool::query()
                    ->whereIn('kode', $kodeAlat)
                    ->pluck('id')
                    ->all();
                $template->tools()->sync($ids);
            }
        }

        if (AiOpenRouterModel::query()->exists()) {
            return;
        }

        $daftar = config('ai.openrouter.daftar_model', []);
        if (! is_array($daftar) || $daftar === []) {
            $daftar = AiModelCatalog::modelGratisBawaan();
        }

        $modelUtama = trim((string) config('ai.openrouter.nama_model', ''));
        $urutan = 0;
        foreach ($daftar as $baris) {
            if (! is_array($baris)) {
                continue;
            }
            $id = trim((string) ($baris['id'] ?? ''));
            if ($id === '') {
                continue;
            }
            $label = trim((string) ($baris['label'] ?? ''));
            AiOpenRouterModel::query()->create([
                'id_model_openrouter' => $id,
                'label' => $label !== '' ? $label : AiModelCatalog::labelDariId($id),
                'urutan' => $urutan,
                'utama' => $modelUtama !== '' ? $id === $modelUtama : $urutan === 0,
                'aktif' => true,
            ]);
            $urutan++;
        }
    }
}
