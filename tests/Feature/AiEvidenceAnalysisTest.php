<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentTool;
use App\Models\Competency;
use App\Models\CompetencyLevel;
use App\Models\Evidence;
use App\Models\MatrixVersion;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiEvidenceAnalysisTest extends TestCase
{
    use RefreshDatabase;

    public function test_analisis_ai_incremental_ditolak_jika_fitur_mati(): void
    {
        $this->seed(DatabaseSeeder::class);
        config(['ai.aktif' => false]);

        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmenDenganSatuBukti($admin);

        $bukti = Evidence::query()->where('id_asesmen', $asesmen->id)->firstOrFail();

        $this->actingAs($admin)
            ->from(route('asesmen.show', $asesmen))
            ->post(route('asesmen.bukti.analisis-ai', [$asesmen, $bukti]))
            ->assertRedirect(route('asesmen.show', $asesmen))
            ->assertSessionHasErrors('ai');
    }

    public function test_analisis_ai_incremental_memperbarui_bukti_dan_menulis_log(): void
    {
        $this->seed(DatabaseSeeder::class);
        config([
            'ai.aktif' => true,
            'ai.openrouter.kunci_api' => 'kunci-uji',
            'ai.openrouter.url_dasar' => 'https://openrouter.ai/api/v1',
            'ai.openrouter.nama_model' => 'model-uji',
            'ai.queue.koneksi' => 'sync',
        ]);

        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmenDenganSatuBukti($admin);
        $bukti = Evidence::query()->where('id_asesmen', $asesmen->id)->firstOrFail();

        $tingkat = CompetencyLevel::query()
            ->where('id_kompetensi', $bukti->id_kompetensi)
            ->where('tingkat', 2)
            ->firstOrFail();

        $isiModel = json_encode([
            'tingkat' => 2,
            'id_tingkat_kompetensi' => $tingkat->id,
            'kutipan_dari_teks_mentah' => 'KUTIPAN_TEGAS',
            'alasan' => 'Indikator terlihat pada kutipan.',
            'konfirmatori' => 'Kutipan menunjukkan indikator level 2 secara langsung.',
            'keyakinan' => 0.88,
        ], JSON_THROW_ON_ERROR);

        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => $isiModel]],
                ],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 20],
            ], 200),
        ]);

        $this->actingAs($admin)
            ->from(route('asesmen.show', $asesmen))
            ->post(route('asesmen.bukti.analisis-ai', [$asesmen, $bukti]))
            ->assertRedirect(route('asesmen.show', $asesmen))
            ->assertSessionHas('status');

        $bukti->refresh();
        $this->assertSame('2', $bukti->ai_tingkat);
        $this->assertNotNull($bukti->ai_dinilai_pada);
        $this->assertDatabaseHas('ais_log_ai', [
            'id_bukti_penilaian' => $bukti->id,
            'jalur' => 'analisis_bukti_incremental',
            'status' => 'berhasil',
        ]);
    }

    private function buatAsesmenDenganSatuBukti(User $admin): Assessment
    {
        $peserta = Participant::query()->where('kode_peserta', 'DEMO-001')->firstOrFail();
        $versi = MatrixVersion::query()->where('kode_versi', 'KAMUS-17-DEFAULT')->firstOrFail();
        $kompetensi = Competency::query()->orderBy('id')->firstOrFail();
        $alat = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('asesmen.store'), [
                'id_peserta' => $peserta->id,
                'id_versi_matriks' => $versi->id,
                'tujuan' => 'promosi',
                'tanpa_intray' => '0',
                'id_asesor' => [$admin->id],
            ])
            ->assertRedirect();

        /** @var Assessment $asesmen */
        $asesmen = Assessment::query()->latest('id')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('asesmen.bukti.store', $asesmen), [
                'id_alat_penilaian' => $alat->id,
                'id_kompetensi' => $kompetensi->id,
                'teks_mentah' => "Ringkasan perilaku.\nKUTIPAN_TEGAS dalam konteks kerja.",
            ])
            ->assertRedirect();

        return $asesmen;
    }
}
