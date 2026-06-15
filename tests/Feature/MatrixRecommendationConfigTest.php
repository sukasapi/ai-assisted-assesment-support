<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Assessment;
use App\Models\AssessmentTool;
use App\Models\CompetencyLevel;
use App\Models\CompetencyToolMapping;
use App\Models\MatrixVersion;
use App\Models\RecommendationConfigRevision;
use App\Models\User;
use App\Support\DefaultRecommendationConfig;
use Database\Seeders\DatabaseSeeder;
use Tests\Support\AssessmentTestHelpers;
use Tests\TestCase;

class MatrixRecommendationConfigTest extends TestCase
{

    public function test_seeder_membuat_revisi_awal_per_matriks(): void
    {
        $this->seed(DatabaseSeeder::class);

        $jumlahMatriks = MatrixVersion::query()->whereNull('dihapus_pada')->count();
        $jumlahRevisi = RecommendationConfigRevision::query()->count();

        $this->assertGreaterThan(0, $jumlahMatriks);
        $this->assertSame($jumlahMatriks, $jumlahRevisi);
    }

    public function test_simpan_revisi_baru_increment_nomor_dan_catat_audit(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $versi = MatrixVersion::query()->firstOrFail();

        AssessmentTestHelpers::buatAsesmen($this, $admin);

        $payload = $this->payloadKonfigurasi('Revisi kedua — ubah ambang.');

        $this->actingAs($admin)
            ->post(route('master.versi-matriks.konfigurasi-rekomendasi.store', $versi), $payload)
            ->assertRedirect(route('master.versi-matriks.konfigurasi-rekomendasi.index', $versi))
            ->assertSessionHas('status');

        $revisi2 = RecommendationConfigRevision::query()
            ->where('id_versi_matriks', $versi->id)
            ->orderByDesc('nomor_revisi')
            ->first();

        $this->assertNotNull($revisi2);
        $this->assertSame(2, $revisi2->nomor_revisi);

        $revisi1 = RecommendationConfigRevision::query()
            ->where('id_versi_matriks', $versi->id)
            ->where('nomor_revisi', 1)
            ->first();

        $this->assertNotNull($revisi1);
        $this->assertNotSame($revisi1->konfigurasi, $revisi2->konfigurasi);

        $log = ActivityLog::query()
            ->where('aksi', 'master.konfigurasi_rekomendasi.disimpan')
            ->where('subjek_id', $versi->id)
            ->latest('dibuat_pada')
            ->first();

        $this->assertNotNull($log);
        $this->assertGreaterThan(0, $log->properti['jumlah_asesmen_terdampak'] ?? 0);
    }

    public function test_hitung_integrasi_memakai_revisi_terbaru(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin);
        $versi = MatrixVersion::query()->findOrFail($asesmen->id_versi_matriks);

        $this->isiPkDisahkan($asesmen, $admin);

        $this->actingAs($admin)
            ->post(route('asesmen.integrasi.hitung', $asesmen))
            ->assertRedirect();

        $asesmen->refresh();
        $revisiPertama = $asesmen->id_revisi_konfigurasi_terakhir;
        $this->assertNotNull($revisiPertama);
        $this->assertNotNull($asesmen->kode_rekomendasi_agregat);

        $payload = $this->payloadKonfigurasi('Revisi setelah integrasi pertama.');
        $this->actingAs($admin)
            ->post(route('master.versi-matriks.konfigurasi-rekomendasi.store', $versi), $payload);

        $this->actingAs($admin)
            ->post(route('asesmen.integrasi.hitung', $asesmen))
            ->assertRedirect();

        $asesmen->refresh();
        $this->assertNotSame($revisiPertama, $asesmen->id_revisi_konfigurasi_terakhir);

        $log = ActivityLog::query()
            ->where('aksi', 'asesmen.integrasi_dihitung')
            ->where('subjek_id', $asesmen->id)
            ->latest('dibuat_pada')
            ->first();

        $this->assertNotNull($log->properti['id_revisi_konfigurasi'] ?? null);
        $this->assertNotNull($log->properti['nomor_revisi_konfigurasi'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadKonfigurasi(string $ringkasan): array
    {
        $bawaan = DefaultRecommendationConfig::bawaan();
        $dimensi = [];
        foreach ($bawaan['dimensi'] as $d) {
            $dimensi[] = [
                'kode' => $d['kode'],
                'label' => $d['label'],
                'logika' => $d['kriteria_qualified']['logika'],
                'filter_kelompok_kode' => $d['filter_kelompok_kode'],
                'aturan' => $d['kriteria_qualified']['aturan'],
            ];
        }

        return [
            'ringkasan_perubahan' => $ringkasan,
            'logika_agregat' => $bawaan['hasil_agregat']['logika'],
            'label_qualified' => $bawaan['hasil_agregat']['label_qualified'],
            'label_not_qualified' => $bawaan['hasil_agregat']['label_not_qualified'].' (revisi)',
            'dimensi' => $dimensi,
        ];
    }

    private function isiPkDisahkan(Assessment $asesmen, User $admin): void
    {
        $idAlatAktif = $asesmen->toolSelections()->where('aktif', true)->pluck('id_alat_penilaian')->all();
        $idKompetensiWajib = CompetencyToolMapping::query()
            ->where('id_versi_matriks', $asesmen->id_versi_matriks)
            ->whereIn('id_alat_penilaian', $idAlatAktif)
            ->where('aktif', true)
            ->where('wajib', true)
            ->pluck('id_kompetensi')
            ->unique();

        $alat = AssessmentTool::query()->whereIn('id', $idAlatAktif)->orderBy('id')->firstOrFail();

        foreach ($idKompetensiWajib as $idKompetensi) {
            $idTingkat = CompetencyLevel::query()
                ->where('id_kompetensi', $idKompetensi)
                ->whereNull('dihapus_pada')
                ->orderBy('tingkat')
                ->value('id');

            if ($idTingkat === null) {
                continue;
            }

            $asesmen->keyBehaviors()->updateOrCreate(
                [
                    'id_alat_penilaian' => $alat->id,
                    'id_kompetensi' => (int) $idKompetensi,
                ],
                [
                    'id_tingkat_kompetensi' => (int) $idTingkat,
                    'teks_perilaku' => 'PK disahkan uji rekomendasi',
                    'tervalidasi' => true,
                    'id_pengguna_validasi' => $admin->id,
                    'waktu_validasi' => now(),
                ],
            );
        }
    }
}
