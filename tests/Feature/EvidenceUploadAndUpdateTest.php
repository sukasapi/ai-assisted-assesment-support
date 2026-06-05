<?php

namespace Tests\Feature;

use App\Enums\AssessmentStatus;
use App\Enums\EvidenceSourceType;
use App\Enums\EvidenceTranscriptionStatus;
use App\Models\Assessment;
use App\Models\CompetencyToolMapping;
use App\Models\Evidence;
use App\Models\User;
use App\Services\Stt\TranscriberContract;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\AssessmentTestHelpers;
use Tests\TestCase;

class EvidenceUploadAndUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_bukti_teks_berhasil(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin);
        [$idAlat, $idKompetensi] = $this->pasanganAlatKompetensi($asesmen);

        $this->actingAs($admin)
            ->post(route('asesmen.bukti.store', $asesmen), [
                'id_alat_penilaian' => $idAlat,
                'id_kompetensi' => $idKompetensi,
                'jenis_sumber' => EvidenceSourceType::Teks->value,
                'teks_mentah' => 'Observasi peserta menunjukkan inisiatif.',
            ])
            ->assertRedirect(route('asesmen.show', $asesmen).'#pengumpulan');

        $this->assertDatabaseHas('ais_bukti_penilaian', [
            'id_asesmen' => $asesmen->id,
            'id_alat_penilaian' => $idAlat,
            'jenis_sumber' => EvidenceSourceType::Teks->value,
            'teks_mentah' => 'Observasi peserta menunjukkan inisiatif.',
        ]);
    }

    public function test_store_bukti_wawancara_dengan_mock_transcriber(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
        config(['stt.aktif' => true, 'stt.queue.koneksi' => 'sync']);

        $this->mock(TranscriberContract::class, function ($mock): void {
            $mock->shouldReceive('transcribe')
                ->once()
                ->andReturn([
                    'text' => "Pembukaan wawancara.\n\nJawaban peserta panjang.",
                    'durasi_detik' => 90.0,
                    'segments' => [
                        ['start' => 0.0, 'end' => 5.0, 'text' => 'Pembukaan wawancara.'],
                        ['start' => 8.0, 'end' => 20.0, 'text' => 'Jawaban peserta panjang.'],
                    ],
                ]);
        });

        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin);
        [$idAlat, $idKompetensi] = $this->pasanganAlatKompetensi($asesmen);

        $audio = UploadedFile::fake()->create('wawancara.mp3', 120, 'audio/mpeg');

        $this->actingAs($admin)
            ->post(route('asesmen.bukti.store', $asesmen), [
                'id_alat_penilaian' => $idAlat,
                'id_kompetensi' => $idKompetensi,
                'jenis_sumber' => EvidenceSourceType::Wawancara->value,
                'berkas_audio' => $audio,
            ])
            ->assertRedirect(route('asesmen.show', $asesmen).'#pengumpulan');

        $bukti = Evidence::query()->where('id_asesmen', $asesmen->id)->latest('id')->first();
        $this->assertNotNull($bukti);
        $this->assertSame(EvidenceSourceType::Wawancara, $bukti->jenis_sumber);
        $this->assertSame(EvidenceTranscriptionStatus::Selesai, $bukti->status_transkripsi);
        $this->assertStringContainsString('Pembukaan wawancara', (string) $bukti->teks_mentah);
        $this->assertNotNull($bukti->path_audio);
    }

    public function test_update_manual_setelah_transkripsi_gagal(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin);
        [$idAlat, $idKompetensi] = $this->pasanganAlatKompetensi($asesmen);

        $bukti = $asesmen->evidenceItems()->create([
            'id_alat_penilaian' => $idAlat,
            'id_kompetensi' => $idKompetensi,
            'jenis_sumber' => EvidenceSourceType::Wawancara,
            'teks_mentah' => 'Transkripsi sedang diproses…',
            'status_transkripsi' => EvidenceTranscriptionStatus::Gagal,
            'pesan_status_transkripsi' => 'Kredit habis',
            'ai_tingkat' => '2',
            'ai_alasan' => 'Lama',
        ]);

        $this->actingAs($admin)
            ->from(route('asesmen.show', $asesmen))
            ->patch(route('asesmen.bukti.update', [$asesmen, $bukti]), [
                'jenis_sumber' => EvidenceSourceType::Wawancara->value,
                'teks_mentah' => 'Transkrip manual oleh asesor.',
            ])
            ->assertRedirect(route('asesmen.show', $asesmen).'#pengumpulan');

        $bukti->refresh();
        $this->assertSame('Transkrip manual oleh asesor.', $bukti->teks_mentah);
        $this->assertSame(EvidenceTranscriptionStatus::Selesai, $bukti->status_transkripsi);
        $this->assertNull($bukti->ai_tingkat);
        $this->assertNull($bukti->ai_alasan);
    }

    public function test_update_ditolak_saat_final(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin);
        [$idAlat, $idKompetensi] = $this->pasanganAlatKompetensi($asesmen);

        $bukti = $asesmen->evidenceItems()->create([
            'id_alat_penilaian' => $idAlat,
            'id_kompetensi' => $idKompetensi,
            'jenis_sumber' => EvidenceSourceType::Teks,
            'teks_mentah' => 'Teks awal',
        ]);

        $asesmen->forceFill([
            'status' => AssessmentStatus::SelesaiFinal,
            'waktu_finalisasi' => now(),
        ])->save();

        $this->actingAs($admin)
            ->from(route('asesmen.show', $asesmen))
            ->patch(route('asesmen.bukti.update', [$asesmen, $bukti]), [
                'jenis_sumber' => EvidenceSourceType::Teks->value,
                'teks_mentah' => 'Teks baru',
            ])
            ->assertRedirect(route('asesmen.show', $asesmen))
            ->assertSessionHasErrors('asesmen');

        $bukti->refresh();
        $this->assertSame('Teks awal', $bukti->teks_mentah);
    }

    public function test_update_ubah_jenis_teks_ke_wawancara_dengan_audio(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
        config(['stt.aktif' => true, 'stt.queue.koneksi' => 'sync']);

        $this->mock(TranscriberContract::class, function ($mock): void {
            $mock->shouldReceive('transcribe')
                ->once()
                ->andReturn([
                    'text' => "Hasil transkripsi wawancara.\n\nParagraf kedua.",
                    'durasi_detik' => 60.0,
                    'segments' => [],
                ]);
        });

        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin);
        [$idAlat, $idKompetensi] = $this->pasanganAlatKompetensi($asesmen);

        $bukti = $asesmen->evidenceItems()->create([
            'id_alat_penilaian' => $idAlat,
            'id_kompetensi' => $idKompetensi,
            'jenis_sumber' => EvidenceSourceType::Teks,
            'teks_mentah' => 'Bukti observasi awal.',
        ]);

        $audio = UploadedFile::fake()->create('wawancara.mp3', 120, 'audio/mpeg');

        $this->actingAs($admin)
            ->from(route('asesmen.show', $asesmen))
            ->patch(route('asesmen.bukti.update', [$asesmen, $bukti]), [
                'jenis_sumber' => EvidenceSourceType::Wawancara->value,
                'berkas_audio' => $audio,
            ])
            ->assertRedirect(route('asesmen.show', $asesmen).'#pengumpulan');

        $bukti->refresh();
        $this->assertSame(EvidenceSourceType::Wawancara, $bukti->jenis_sumber);
        $this->assertSame(EvidenceTranscriptionStatus::Selesai, $bukti->status_transkripsi);
        $this->assertStringContainsString('Hasil transkripsi wawancara', (string) $bukti->teks_mentah);
        $this->assertNotNull($bukti->path_audio);
    }

    public function test_update_ubah_jenis_wawancara_ke_teks(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');

        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin);
        [$idAlat, $idKompetensi] = $this->pasanganAlatKompetensi($asesmen);

        $pathAudio = 'asesmen/'.$asesmen->id.'/bukti/test-audio.mp3';
        Storage::disk('local')->put($pathAudio, 'fake audio');

        $bukti = $asesmen->evidenceItems()->create([
            'id_alat_penilaian' => $idAlat,
            'id_kompetensi' => $idKompetensi,
            'jenis_sumber' => EvidenceSourceType::Wawancara,
            'teks_mentah' => 'Transkrip wawancara lama.',
            'path_audio' => $pathAudio,
            'mime_audio' => 'audio/mpeg',
            'status_transkripsi' => EvidenceTranscriptionStatus::Selesai,
        ]);

        $this->actingAs($admin)
            ->from(route('asesmen.show', $asesmen))
            ->patch(route('asesmen.bukti.update', [$asesmen, $bukti]), [
                'jenis_sumber' => EvidenceSourceType::Teks->value,
                'teks_mentah' => 'Diubah menjadi bukti teks.',
            ])
            ->assertRedirect(route('asesmen.show', $asesmen).'#pengumpulan');

        $bukti->refresh();
        $this->assertSame(EvidenceSourceType::Teks, $bukti->jenis_sumber);
        $this->assertSame('Diubah menjadi bukti teks.', $bukti->teks_mentah);
        $this->assertNull($bukti->path_audio);
        $this->assertNull($bukti->status_transkripsi);
        Storage::disk('local')->assertMissing($pathAudio);
    }

    public function test_update_ubah_jenis_teks_ke_wawancara_manual_tanpa_audio(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin);
        [$idAlat, $idKompetensi] = $this->pasanganAlatKompetensi($asesmen);

        $bukti = $asesmen->evidenceItems()->create([
            'id_alat_penilaian' => $idAlat,
            'id_kompetensi' => $idKompetensi,
            'jenis_sumber' => EvidenceSourceType::Teks,
            'teks_mentah' => 'Observasi awal.',
        ]);

        $this->actingAs($admin)
            ->from(route('asesmen.show', $asesmen))
            ->patch(route('asesmen.bukti.update', [$asesmen, $bukti]), [
                'jenis_sumber' => EvidenceSourceType::Wawancara->value,
                'teks_mentah' => 'Transkrip wawancara manual tanpa audio.',
            ])
            ->assertRedirect(route('asesmen.show', $asesmen).'#pengumpulan');

        $bukti->refresh();
        $this->assertSame(EvidenceSourceType::Wawancara, $bukti->jenis_sumber);
        $this->assertSame('Transkrip wawancara manual tanpa audio.', $bukti->teks_mentah);
        $this->assertNull($bukti->path_audio);
        $this->assertSame(EvidenceTranscriptionStatus::Selesai, $bukti->status_transkripsi);
    }

    public function test_transkrip_preview_mengembalikan_teks(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
        config(['stt.aktif' => true]);

        $this->mock(TranscriberContract::class, function ($mock): void {
            $mock->shouldReceive('transcribe')
                ->once()
                ->andReturn([
                    'text' => "Baris pertama.\n\nBaris kedua.",
                    'durasi_detik' => 30.0,
                    'segments' => [],
                ]);
        });

        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin);
        $audio = UploadedFile::fake()->create('wawancara.wav', 200, 'audio/wav');

        $this->actingAs($admin)
            ->postJson(route('asesmen.bukti.transkrip.preview', $asesmen), [
                'berkas_audio' => $audio,
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'text' => "Baris pertama.\n\nBaris kedua.",
            ]);
    }

    public function test_store_wawancara_dengan_transkrip_sudah_diisi_tanpa_job_stt(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
        config(['stt.aktif' => true, 'stt.queue.koneksi' => 'sync']);

        $this->mock(TranscriberContract::class, function ($mock): void {
            $mock->shouldReceive('transcribe')->never();
        });

        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin);
        [$idAlat, $idKompetensi] = $this->pasanganAlatKompetensi($asesmen);
        $audio = UploadedFile::fake()->create('wawancara.mp3', 120, 'audio/mpeg');

        $this->actingAs($admin)
            ->post(route('asesmen.bukti.store', $asesmen), [
                'id_alat_penilaian' => $idAlat,
                'id_kompetensi' => $idKompetensi,
                'jenis_sumber' => EvidenceSourceType::Wawancara->value,
                'berkas_audio' => $audio,
                'teks_mentah' => 'Transkrip dari tombol Transcript.',
            ])
            ->assertRedirect(route('asesmen.show', $asesmen).'#pengumpulan');

        $bukti = Evidence::query()->where('id_asesmen', $asesmen->id)->latest('id')->first();
        $this->assertNotNull($bukti);
        $this->assertSame('Transkrip dari tombol Transcript.', $bukti->teks_mentah);
        $this->assertSame(EvidenceTranscriptionStatus::Selesai, $bukti->status_transkripsi);
    }

    public function test_stream_audio_bukti_wawancara(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');

        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin);
        [$idAlat, $idKompetensi] = $this->pasanganAlatKompetensi($asesmen);

        $pathAudio = 'evidence-audio/'.$asesmen->id.'/sample.mp3';
        Storage::disk('local')->put($pathAudio, 'fake-audio-bytes');

        $bukti = $asesmen->evidenceItems()->create([
            'id_alat_penilaian' => $idAlat,
            'id_kompetensi' => $idKompetensi,
            'jenis_sumber' => EvidenceSourceType::Wawancara,
            'teks_mentah' => 'Transkrip contoh.',
            'path_audio' => $pathAudio,
            'mime_audio' => 'audio/mpeg',
        ]);

        $this->actingAs($admin)
            ->get(route('asesmen.bukti.audio', [$asesmen, $bukti]))
            ->assertOk()
            ->assertHeader('content-type', 'audio/mpeg');
    }

    public function test_store_ditolak_saat_metode_payload_alat(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin, extra: [
            'metode_koleksi_bukti' => 'payload_alat',
        ]);
        [$idAlat, $idKompetensi] = $this->pasanganAlatKompetensi($asesmen);

        $this->actingAs($admin)
            ->post(route('asesmen.bukti.store', $asesmen), [
                'id_alat_penilaian' => $idAlat,
                'id_kompetensi' => $idKompetensi,
                'jenis_sumber' => EvidenceSourceType::Teks->value,
                'teks_mentah' => 'Teks uji',
            ])
            ->assertSessionHasErrors('metode_koleksi_bukti');
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function pasanganAlatKompetensi(Assessment $asesmen): array
    {
        $idAlatAktif = $asesmen->toolSelections()->where('aktif', true)->pluck('id_alat_penilaian');

        $mapping = CompetencyToolMapping::query()
            ->where('id_versi_matriks', $asesmen->id_versi_matriks)
            ->whereIn('id_alat_penilaian', $idAlatAktif)
            ->where(function ($q): void {
                $q->where('aktif', true)->orWhereNull('aktif');
            })
            ->firstOrFail();

        return [(int) $mapping->id_alat_penilaian, (int) $mapping->id_kompetensi];
    }
}
