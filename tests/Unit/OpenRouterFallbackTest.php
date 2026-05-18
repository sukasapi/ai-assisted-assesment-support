<?php

namespace Tests\Unit;

use App\Services\Ai\OpenRouterClient;
use App\Support\AiModelCatalog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenRouterFallbackTest extends TestCase
{
    public function test_rantai_fallback_memulai_dari_model_pilihan(): void
    {
        Config::set('ai.openrouter.daftar_model', [
            ['id' => 'model-a', 'label' => 'A'],
            ['id' => 'model-b', 'label' => 'B'],
            ['id' => 'model-c', 'label' => 'C'],
        ]);
        Config::set('ai.openrouter.nama_model', 'model-a');
        Config::set('ai.openrouter.fallback_otomatis', true);

        $this->assertSame(['model-b', 'model-a', 'model-c'], AiModelCatalog::rantaiFallback('model-b'));
    }

    public function test_fallback_ke_model_kedua_setelah_timeout(): void
    {
        Config::set('ai.openrouter.fallback_otomatis', true);
        Config::set('ai.openrouter.batas_waktu_per_model_detik', 45);
        Config::set('ai.openrouter.batas_waktu_koneksi_detik', 12);
        Config::set('ai.openrouter.kunci_api', 'kunci-uji');
        Config::set('ai.openrouter.url_dasar', 'https://openrouter.ai/api/v1');
        Config::set('ai.openrouter.daftar_model', [
            ['id' => 'model-lambat', 'label' => 'Lambat'],
            ['id' => 'model-cepat', 'label' => 'Cepat'],
        ]);
        Config::set('ai.openrouter.nama_model', 'model-lambat');

        $isiJson = json_encode(['usulan' => []], JSON_THROW_ON_ERROR);

        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => function ($request) use ($isiJson) {
                $body = $request->data();
                if (($body['model'] ?? '') === 'model-lambat') {
                    throw new ConnectionException('cURL error 28: Operation timed out');
                }

                return Http::response([
                    'choices' => [['message' => ['content' => $isiJson]]],
                ], 200);
            },
        ]);

        $client = app(OpenRouterClient::class);
        $hasil = $client->chatCompletionDenganFallback(
            [['role' => 'user', 'content' => 'tes']],
            'model-lambat',
        );

        $this->assertSame('model-cepat', $hasil['nama_model']);
        $this->assertSame(['model-lambat', 'model-cepat'], $hasil['dicoba_model']);
        $this->assertTrue($hasil['response']->successful());
    }
}
