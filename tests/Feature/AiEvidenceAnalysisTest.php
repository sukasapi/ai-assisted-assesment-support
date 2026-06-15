<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentTool;
use App\Models\Competency;
use App\Models\CompetencyLevel;
use App\Models\Evidence;
use App\Models\KeyBehavior;
use App\Models\MatrixVersion;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Http;
use Tests\Support\AssessmentTestHelpers;
use Tests\TestCase;

class AiEvidenceAnalysisTest extends TestCase
{

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
            ->assertRedirect(route('asesmen.show', [
                'asesmen' => $asesmen,
                'kompetensi' => $bukti->id_kompetensi,
                'alat' => $bukti->id_alat_penilaian,
                'bukti' => $bukti->id,
            ]).'#pengumpulan')
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
            ->assertRedirect(route('asesmen.show', [
                'asesmen' => $asesmen,
                'kompetensi' => $bukti->id_kompetensi,
                'alat' => $bukti->id_alat_penilaian,
                'bukti' => $bukti->id,
            ]).'#pengumpulan')
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

    public function test_analisis_ai_menerima_kutipan_dengan_tanda_baca_unicode(): void
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

        $bukti->forceFill([
            'teks_mentah' => 'Ringkasan perilaku. Peserta berkata "saya siap" untuk tugas.',
            'teks_mentah_normalized' => 'Ringkasan perilaku. Peserta berkata "saya siap" untuk tugas.',
        ])->save();

        $tingkat = CompetencyLevel::query()
            ->where('id_kompetensi', $bukti->id_kompetensi)
            ->where('tingkat', 2)
            ->firstOrFail();

        $isiModel = json_encode([
            'tingkat' => 2,
            'id_tingkat_kompetensi' => $tingkat->id,
            'kutipan_dari_teks_mentah' => 'Peserta berkata “saya siap” untuk tugas.',
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
            ->assertRedirect(route('asesmen.show', [
                'asesmen' => $asesmen,
                'kompetensi' => $bukti->id_kompetensi,
                'alat' => $bukti->id_alat_penilaian,
                'bukti' => $bukti->id,
            ]).'#pengumpulan')
            ->assertSessionHas('status');

        $bukti->refresh();
        $this->assertSame('2', $bukti->ai_tingkat);
        $this->assertStringContainsString('"saya siap"', (string) ($bukti->ai_muatan['kutipan_dari_teks_mentah'] ?? ''));
    }

    public function test_transfer_hasil_ai_bukti_ke_mapping_membuat_perilaku_kunci_draft(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmenDenganSatuBukti($admin);
        $bukti = Evidence::query()->where('id_asesmen', $asesmen->id)->firstOrFail();
        $tingkat = CompetencyLevel::query()
            ->where('id_kompetensi', $bukti->id_kompetensi)
            ->where('tingkat', 2)
            ->firstOrFail();

        $bukti->forceFill([
            'ai_tingkat' => '2',
            'ai_alasan' => 'AI menilai indikator level 2 terlihat.',
            'ai_muatan' => [
                'id_tingkat_kompetensi_usulan' => $tingkat->id,
                'kutipan_dari_teks_mentah' => 'KUTIPAN_TEGAS',
            ],
            'ai_dinilai_pada' => now(),
        ])->save();

        $this->actingAs($admin)
            ->post(route('asesmen.bukti.mapping', [$asesmen, $bukti]))
            ->assertRedirect(route('asesmen.show', $asesmen).'#hasil-mapping')
            ->assertSessionHas('status');

        $pk = KeyBehavior::query()
            ->where('id_asesmen', $asesmen->id)
            ->where('id_bukti_penilaian', $bukti->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($pk);
        $this->assertFalse((bool) $pk->tervalidasi);
        $this->assertSame($tingkat->id, $pk->id_tingkat_kompetensi);
        $this->assertSame('KUTIPAN_TEGAS', $pk->kutipan_referensi);
    }

    private function buatAsesmenDenganSatuBukti(User $admin): Assessment
    {
        $kompetensi = Competency::query()->orderBy('id')->firstOrFail();
        $alat = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();

        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin);

        $this->actingAs($admin)
            ->post(route('asesmen.bukti.store', $asesmen), [
                'id_alat_penilaian' => $alat->id,
                'id_kompetensi' => $kompetensi->id,
                'jenis_sumber' => 'teks',
                'teks_mentah' => "Ringkasan perilaku.\nKUTIPAN_TEGAS dalam konteks kerja.",
            ])
            ->assertRedirect();

        return $asesmen;
    }
}
