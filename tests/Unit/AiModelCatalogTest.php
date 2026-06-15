<?php

namespace Tests\Unit;

use App\Models\AiOpenRouterModel;
use App\Support\AiModelCatalog;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AiModelCatalogTest extends TestCase
{

    public function test_daftar_model_dari_konfigurasi(): void
    {
        if (Schema::hasTable('ais_model_ai')) {
            AiOpenRouterModel::query()->delete();
        }

        Config::set('ai.openrouter.daftar_model', [
            ['id' => 'model-a', 'label' => 'Model A'],
            ['id' => 'model-b', 'label' => 'Model B'],
        ]);
        Config::set('ai.openrouter.nama_model', 'model-a');

        $this->assertCount(2, AiModelCatalog::daftarModel());
        $this->assertSame('model-a', AiModelCatalog::modelDefault());
        $this->assertSame('model-b', AiModelCatalog::selesaikan('model-b'));
        $this->assertSame('model-a', AiModelCatalog::selesaikan('tidak-ada'));
    }

    public function test_daftar_model_prioritas_database(): void
    {
        $this->artisan('migrate', ['--force' => true]);
        \App\Models\AiOpenRouterModel::query()->create([
            'id_model_openrouter' => 'vendor/from-db',
            'label' => 'From DB',
            'urutan' => 0,
            'utama' => true,
            'aktif' => true,
        ]);

        Config::set('ai.openrouter.daftar_model', [
            ['id' => 'model-env-only', 'label' => 'Env'],
        ]);

        $this->assertSame('vendor/from-db', AiModelCatalog::daftarModel()[0]['id']);
        $this->assertSame('vendor/from-db', AiModelCatalog::modelDefault());
    }

    public function test_model_gratis_bawaan_tidak_kosong(): void
    {
        $gratis = AiModelCatalog::modelGratisBawaan();
        $this->assertNotEmpty($gratis);
        foreach ($gratis as $baris) {
            $this->assertStringContainsString(':free', $baris['id']);
        }
    }
}
