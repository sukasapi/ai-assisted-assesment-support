<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentTool;
use App\Models\AssessmentToolPayload;
use App\Models\Competency;
use App\Models\CompetencyLevel;
use App\Models\Evidence;
use App\Models\MatrixVersion;
use App\Models\Participant;
use App\Models\User;
use App\Services\Ai\BulkToolPayloadAiAnalyzer;
use App\Services\Ai\EvidenceAiAnalyzer;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\AssessmentTestHelpers;
use Tests\TestCase;

class AiQueueAndSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_trigger_incremental_mengeksekusi_langsung(): void
    {
        $this->seed(DatabaseSeeder::class);
        config([
            'ai.aktif' => true,
            'ai.openrouter.kunci_api' => 'kunci-uji',
            'ai.openrouter.url_dasar' => 'https://openrouter.ai/api/v1',
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
            'konfirmatori' => 'Kutipan relevan dengan indikator level 2.',
            'keyakinan' => 0.82,
        ], JSON_THROW_ON_ERROR);
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => $isiModel]]],
            ], 200),
        ]);

        $this->actingAs($admin)
            ->post(route('asesmen.bukti.analisis-ai', [$asesmen, $bukti]))
            ->assertRedirect(route('asesmen.show', $asesmen));

        $bukti->refresh();
        $this->assertSame('2', $bukti->ai_tingkat);
        $this->assertNotNull($bukti->ai_dinilai_pada);
    }

    public function test_trigger_bulk_mengeksekusi_langsung(): void
    {
        $this->seed(DatabaseSeeder::class);
        config([
            'ai.aktif' => true,
            'ai.openrouter.kunci_api' => 'kunci-uji',
            'ai.openrouter.url_dasar' => 'https://openrouter.ai/api/v1',
        ]);

        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmenDenganSatuBukti($admin);
        $alat = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();

        /** @var AssessmentToolPayload $payload */
        $payload = $asesmen->toolPayloads()->create([
            'id_alat_penilaian' => $alat->id,
            'teks_muatan' => 'KUTIPAN BULK tes perilaku.',
            'id_pengguna_pengunggah' => $admin->id,
        ]);

        $asesmen->update(['metode_koleksi_bukti' => 'payload_alat']);
        $kodeKompetensi = Competency::query()->orderBy('id')->firstOrFail()->kode_kompetensi;
        $isiModel = json_encode([
            'usulan' => [[
                'kode_kompetensi' => $kodeKompetensi,
                'ringkasan' => 'Ringkas.',
                'kutipan' => 'KUTIPAN BULK',
                'alasan' => 'Alasan bulk.',
                'keyakinan' => 0.75,
                'tingkat' => 2,
                'teks_perilaku' => 'Teks perilaku bulk.',
                'konfirmatori' => 'Kutipan bulk mendukung indikator level 2.',
            ]],
        ], JSON_THROW_ON_ERROR);
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => $isiModel]]],
            ], 200),
        ]);

        $this->actingAs($admin)
            ->post(route('asesmen.payload-alat.analisis-ai', [$asesmen, $payload]))
            ->assertRedirect(route('asesmen.show', $asesmen));

        $payload->refresh();
        $this->assertNotNull($payload->diproses_pada);
        $this->assertNotEmpty($payload->hasil_analisis_ai['usulan'] ?? []);
    }

    public function test_schema_incremental_tidak_lengkap_ditolak(): void
    {
        $this->seed(DatabaseSeeder::class);
        config([
            'ai.aktif' => true,
            'ai.openrouter.kunci_api' => 'kunci-uji',
            'ai.openrouter.url_dasar' => 'https://openrouter.ai/api/v1',
        ]);

        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmenDenganSatuBukti($admin);
        $bukti = Evidence::query()->where('id_asesmen', $asesmen->id)->firstOrFail();

        $tingkat = CompetencyLevel::query()
            ->where('id_kompetensi', $bukti->id_kompetensi)
            ->where('tingkat', 2)
            ->firstOrFail();

        $isiModelTanpaKonfirmatori = json_encode([
            'tingkat' => 2,
            'id_tingkat_kompetensi' => $tingkat->id,
            'kutipan_dari_teks_mentah' => 'KUTIPAN_TEGAS',
            'alasan' => 'Alasan ada.',
            'keyakinan' => 0.6,
        ], JSON_THROW_ON_ERROR);

        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => $isiModelTanpaKonfirmatori]]],
            ], 200),
        ]);

        $hasil = app(EvidenceAiAnalyzer::class)->analisisInkremental($bukti, $admin);

        $this->assertFalse($hasil['berhasil']);
        $this->assertStringContainsString('format yang tidak valid', (string) ($hasil['pesan'] ?? ''));
    }

    public function test_bulk_menerima_kutipan_dengan_tanda_baca_curly(): void
    {
        $this->seed(DatabaseSeeder::class);
        config([
            'ai.aktif' => true,
            'ai.openrouter.kunci_api' => 'kunci-uji',
            'ai.openrouter.url_dasar' => 'https://openrouter.ai/api/v1',
        ]);

        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmenDenganSatuBukti($admin);
        $alat = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();

        /** @var AssessmentToolPayload $payload */
        $payload = $asesmen->toolPayloads()->create([
            'id_alat_penilaian' => $alat->id,
            'teks_muatan' => 'Log: KUTIPAN BULK tes perilaku.',
            'teks_muatan_normalized' => 'Log: KUTIPAN BULK tes perilaku.',
            'id_pengguna_pengunggah' => $admin->id,
        ]);

        $asesmen->update(['metode_koleksi_bukti' => 'payload_alat']);
        $kodeKompetensi = Competency::query()->orderBy('id')->firstOrFail()->kode_kompetensi;
        $isiModel = json_encode([
            'usulan' => [[
                'kode_kompetensi' => $kodeKompetensi,
                'ringkasan' => 'Ringkas.',
                'kutipan' => 'Log: KUTIPAN BULK tes perilaku.',
                'alasan' => 'Alasan bulk.',
                'keyakinan' => 0.75,
                'tingkat' => 2,
                'teks_perilaku' => 'Teks perilaku bulk.',
                'konfirmatori' => 'Kutipan bulk mendukung indikator level 2.',
            ]],
        ], JSON_THROW_ON_ERROR);

        $this->jalankanBulkFakeDanAssertBerhasil($admin, $asesmen, $payload, $isiModel);
    }

    public function test_bulk_menerima_kutipan_model_dengan_curly_quote(): void
    {
        $this->seed(DatabaseSeeder::class);
        config([
            'ai.aktif' => true,
            'ai.openrouter.kunci_api' => 'kunci-uji',
            'ai.openrouter.url_dasar' => 'https://openrouter.ai/api/v1',
        ]);

        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmenDenganSatuBukti($admin);
        $alat = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();

        /** @var AssessmentToolPayload $payload */
        $payload = $asesmen->toolPayloads()->create([
            'id_alat_penilaian' => $alat->id,
            'teks_muatan' => 'Peserta berkata "siap" menjalankan tugas.',
            'teks_muatan_normalized' => 'Peserta berkata "siap" menjalankan tugas.',
            'id_pengguna_pengunggah' => $admin->id,
        ]);

        $asesmen->update(['metode_koleksi_bukti' => 'payload_alat']);
        $kodeKompetensi = Competency::query()->orderBy('id')->firstOrFail()->kode_kompetensi;
        $isiModel = json_encode([
            'usulan' => [[
                'kode_kompetensi' => $kodeKompetensi,
                'ringkasan' => 'Ringkas.',
                'kutipan' => 'Peserta berkata “siap” menjalankan tugas.',
                'alasan' => 'Alasan bulk.',
                'keyakinan' => 0.75,
                'tingkat' => 2,
                'teks_perilaku' => 'Teks perilaku bulk.',
                'konfirmatori' => 'Kutipan bulk mendukung indikator level 2.',
            ]],
        ], JSON_THROW_ON_ERROR);

        $this->jalankanBulkFakeDanAssertBerhasil($admin, $asesmen, $payload, $isiModel);
    }

    public function test_bulk_menerima_json_dibungkus_markdown(): void
    {
        $this->seed(DatabaseSeeder::class);
        config([
            'ai.aktif' => true,
            'ai.openrouter.kunci_api' => 'kunci-uji',
            'ai.openrouter.url_dasar' => 'https://openrouter.ai/api/v1',
        ]);

        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmenDenganSatuBukti($admin);
        $alat = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();

        /** @var AssessmentToolPayload $payload */
        $payload = $asesmen->toolPayloads()->create([
            'id_alat_penilaian' => $alat->id,
            'teks_muatan' => 'KUTIPAN BULK tes perilaku.',
            'id_pengguna_pengunggah' => $admin->id,
        ]);

        $asesmen->update(['metode_koleksi_bukti' => 'payload_alat']);
        $kodeKompetensi = Competency::query()->orderBy('id')->firstOrFail()->kode_kompetensi;
        $isiModel = "```json\n".json_encode([
            'usulan' => [[
                'kode_kompetensi' => $kodeKompetensi,
                'ringkasan' => 'Ringkas.',
                'kutipan' => 'KUTIPAN BULK',
                'alasan' => 'Alasan bulk.',
                'keyakinan' => 0.75,
                'tingkat' => 2,
                'teks_perilaku' => 'Teks perilaku bulk.',
                'konfirmatori' => 'Kutipan bulk mendukung indikator level 2.',
            ]],
        ], JSON_THROW_ON_ERROR)."\n```";

        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => $isiModel]]],
            ], 200),
        ]);

        $this->jalankanBulkFakeDanAssertBerhasil($admin, $asesmen, $payload, $isiModel);
    }

    public function test_schema_bulk_tidak_lengkap_ditolak(): void
    {
        $this->seed(DatabaseSeeder::class);
        config([
            'ai.aktif' => true,
            'ai.openrouter.kunci_api' => 'kunci-uji',
            'ai.openrouter.url_dasar' => 'https://openrouter.ai/api/v1',
        ]);

        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmenDenganSatuBukti($admin);
        $alat = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();

        /** @var AssessmentToolPayload $payload */
        $payload = $asesmen->toolPayloads()->create([
            'id_alat_penilaian' => $alat->id,
            'teks_muatan' => 'KUTIPAN BULK tes perilaku.',
            'id_pengguna_pengunggah' => $admin->id,
        ]);

        $isiModelBulkTanpaKonfirmatori = json_encode([
            'usulan' => [[
                'kode_kompetensi' => Competency::query()->orderBy('id')->firstOrFail()->kode_kompetensi,
                'ringkasan' => 'Ringkas.',
                'kutipan' => 'KUTIPAN BULK',
                'alasan' => 'Alasan.',
                'keyakinan' => 0.75,
                'tingkat' => 2,
                'teks_perilaku' => 'Teks perilaku.',
            ]],
        ], JSON_THROW_ON_ERROR);

        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => $isiModelBulkTanpaKonfirmatori]]],
            ], 200),
        ]);

        $hasil = app(BulkToolPayloadAiAnalyzer::class)->analisisPayload($payload, $admin);

        $this->assertFalse($hasil['berhasil']);
        $this->assertStringContainsString('usulan bulk tidak valid', (string) ($hasil['pesan'] ?? ''));
    }

    private function jalankanBulkFakeDanAssertBerhasil(User $admin, Assessment $asesmen, AssessmentToolPayload $payload, string $isiModel): void
    {
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => $isiModel]]],
            ], 200),
        ]);

        $this->actingAs($admin)
            ->post(route('asesmen.payload-alat.analisis-ai', [$asesmen, $payload]))
            ->assertRedirect(route('asesmen.show', $asesmen));

        $payload->refresh();
        $this->assertNotNull($payload->diproses_pada);
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
                'teks_mentah' => "Ringkasan perilaku.\nKUTIPAN_TEGAS dalam konteks kerja.\nKUTIPAN BULK tes perilaku.",
            ])
            ->assertRedirect();

        return $asesmen;
    }
}
