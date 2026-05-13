<?php

namespace Database\Seeders;

use App\Models\AssessmentTool;
use App\Models\Competency;
use App\Models\CompetencyLevel;
use App\Models\CompetencyToolMapping;
use App\Models\MatrixVersion;
use App\Models\Participant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Menambah versi matriks contoh, pemetaan kompetensi–alat per kelompok,
 * serta memperbarui beberapa baris indikator perilaku (tingkat kompetensi) sebagai ilustrasi.
 *
 * Prasyarat: MasterDataSeeder (atau setara) sudah dijalankan — tabel kompetensi, kelompok, alat, dan tingkat sudah terisi.
 */
class ContohVersiMatriksPaketSeeder extends Seeder
{
    public function run(): void
    {
        if (Competency::query()->whereNull('dihapus_pada')->doesntExist()) {
            $this->command?->warn('ContohVersiMatriksPaketSeeder: tidak ada kompetensi aktif. Jalankan MasterDataSeeder terlebih dahulu.');

            return;
        }

        $cfg = require __DIR__.'/data/contoh_paket_matriks.php';

        DB::transaction(function () use ($cfg): void {
            $versi = MatrixVersion::query()->updateOrCreate(
                ['kode_versi' => $cfg['versi_matriks']['kode_versi']],
                array_merge($cfg['versi_matriks'], [
                    'dipublikasikan_pada' => $cfg['versi_matriks']['dipublikasikan_pada'] ?? now(),
                ])
            );

            CompetencyToolMapping::query()
                ->withTrashed()
                ->where('id_versi_matriks', $versi->id)
                ->forceDelete();

            $toolIds = AssessmentTool::query()
                ->whereNull('dihapus_pada')
                ->pluck('id', 'kode');

            $pola = $cfg['pola_pemetaan_per_kelompok'];

            Competency::query()
                ->whereNull('dihapus_pada')
                ->with('group')
                ->orderBy('kode_kompetensi')
                ->each(function (Competency $c) use ($versi, $toolIds, $pola): void {
                    $kodeKelompok = $c->group?->kode;
                    if ($kodeKelompok === null || ! isset($pola[$kodeKelompok])) {
                        return;
                    }
                    foreach ($pola[$kodeKelompok] as $baris) {
                        $kodeAlat = $baris['alat'];
                        if (! isset($toolIds[$kodeAlat])) {
                            continue;
                        }
                        CompetencyToolMapping::query()->create([
                            'id_versi_matriks' => $versi->id,
                            'id_kompetensi' => $c->id,
                            'id_alat_penilaian' => $toolIds[$kodeAlat],
                            'wajib' => (bool) ($baris['wajib'] ?? false),
                            'bobot' => $baris['bobot'] ?? 1,
                            'aktif' => true,
                        ]);
                    }
                });

            foreach ($cfg['indikator_perilaku_contoh'] as $kodeKompetensi => $tingkatKeTeks) {
                $kompetensi = Competency::query()
                    ->where('kode_kompetensi', $kodeKompetensi)
                    ->whereNull('dihapus_pada')
                    ->first();
                if ($kompetensi === null) {
                    continue;
                }
                foreach ($tingkatKeTeks as $tingkat => $teks) {
                    $tingkat = (int) $tingkat;
                    $level = CompetencyLevel::query()
                        ->withTrashed()
                        ->where('id_kompetensi', $kompetensi->id)
                        ->where('tingkat', $tingkat)
                        ->first();
                    if ($level === null) {
                        CompetencyLevel::query()->create([
                            'id_kompetensi' => $kompetensi->id,
                            'tingkat' => $tingkat,
                            'indikator_perilaku' => $teks,
                        ]);

                        continue;
                    }
                    if ($level->trashed()) {
                        $level->restore();
                    }
                    $level->indikator_perilaku = $teks;
                    $level->save();
                }
            }

            Participant::query()->updateOrCreate(
                ['kode_peserta' => 'CONTOH-PM-001'],
                [
                    'nama_lengkap' => 'Peserta Paket Contoh',
                    'alamat_surel' => 'paket-contoh@example.test',
                    'id_versi_matriks' => $versi->id,
                    'aktif' => true,
                ]
            );
        });

        $this->command?->info('ContohVersiMatriksPaketSeeder: versi '.$cfg['versi_matriks']['kode_versi'].' dan pemetaan contoh siap.');
    }
}
