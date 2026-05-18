<?php

namespace Tests\Unit;

use App\Support\AiModelCatalog;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AiModelCatalogTest extends TestCase
{
    public function test_daftar_model_dari_konfigurasi(): void
    {
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

    public function test_model_gratis_bawaan_tidak_kosong(): void
    {
        $gratis = AiModelCatalog::modelGratisBawaan();
        $this->assertNotEmpty($gratis);
        foreach ($gratis as $baris) {
            $this->assertStringContainsString(':free', $baris['id']);
        }
    }
}
