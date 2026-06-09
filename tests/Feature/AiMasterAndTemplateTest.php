<?php

namespace Tests\Feature;

use App\Enums\AssessmentToolPromptMode;
use App\Models\AiOpenRouterModel;
use App\Models\AiPromptTemplate;
use App\Models\Assessment;
use App\Models\AssessmentTool;
use App\Models\User;
use App\Support\AiPromptTemplateResolver;
use App\Support\AiModelCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiMasterAndTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dapat_mengelola_template_prompt(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('master.template-prompt-ai.store'), [
                'kode' => 'CAR',
                'nama' => 'CAR Challenge Action Result',
                'deskripsi' => 'Varian STAR',
                'teks_instruksi' => 'Gunakan kerangka CAR.',
                'urutan' => 5,
                'aktif' => true,
            ])
            ->assertRedirect(route('master.template-prompt-ai.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('ais_template_prompt_ai', ['kode' => 'CAR']);
    }

    public function test_asesmen_dapat_memilih_template(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $star = AiPromptTemplate::query()->where('kode', 'STAR')->firstOrFail();
        $asesmen = Assessment::query()->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('asesmen.template-prompt-ai.update', $asesmen), [
                'id_template_prompt_ai' => $star->id,
            ])
            ->assertRedirect(route('asesmen.show', $asesmen).'#konfigurasi')
            ->assertSessionHas('status');

        $this->assertSame($star->id, $asesmen->fresh()->id_template_prompt_ai);
    }

    public function test_admin_sync_alat_pada_template(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $bei = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('master.template-prompt-ai.update', ['templatePromptAi' => AiPromptTemplate::query()->where('kode', 'UMUM')->firstOrFail()]), [
                'kode' => 'UMUM',
                'nama' => 'Umum (tanpa kerangka khusus)',
                'deskripsi' => 'Test',
                'teks_instruksi' => '',
                'urutan' => 99,
                'aktif' => true,
                'id_alat_penilaian' => [$bei->id],
            ])
            ->assertRedirect(route('master.template-prompt-ai.index'));

        $this->assertTrue(
            AiPromptTemplate::query()->where('kode', 'UMUM')->firstOrFail()
                ->tools()->where('ais_alat_penilaian.id', $bei->id)->exists()
        );
    }

    public function test_update_template_per_alat_asesmen(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = Assessment::query()->firstOrFail();
        $bei = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('asesmen.template-prompt-alat.update', $asesmen), [
                'prompt_alat' => [
                    $bei->id => [
                        'id_alat_penilaian' => $bei->id,
                        'mode' => AssessmentToolPromptMode::None->value,
                    ],
                ],
            ])
            ->assertRedirect(route('asesmen.show', $asesmen).'#konfigurasi');

        $this->assertSame('', AiPromptTemplateResolver::teksInstruksiUntukAlat($asesmen->fresh(), (int) $bei->id));
    }

    public function test_daftar_model_dari_database(): void
    {
        $this->seed(DatabaseSeeder::class);

        AiOpenRouterModel::query()->update(['utama' => false, 'urutan' => 100]);
        AiOpenRouterModel::query()->create([
            'id_model_openrouter' => 'test/model-db',
            'label' => 'Model DB',
            'urutan' => 0,
            'utama' => true,
            'aktif' => true,
        ]);

        $ids = array_column(AiModelCatalog::daftarModel(), 'id');
        $this->assertContains('test/model-db', $ids);
        $this->assertSame('test/model-db', AiModelCatalog::modelDefault());
    }
}
