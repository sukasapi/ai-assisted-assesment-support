<?php

namespace Database\Seeders;

use App\Enums\AssessmentEvidenceCollectionMode;
use App\Enums\AssessmentPurpose;
use App\Enums\AssessmentStatus;
use App\Models\Assessment;
use App\Models\AssessmentAssessor;
use App\Models\AssessmentTool;
use App\Models\AssessmentToolPayload;
use App\Models\Competency;
use App\Models\CompetencyLevel;
use App\Models\Evidence;
use App\Models\KeyBehavior;
use App\Models\MatrixVersion;
use App\Models\Participant;
use App\Models\User;
use App\Services\Assessment\AlatAsesmenPreset;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Data contoh asesmen untuk pengembangan / demo UI (bukti manual + payload alat).
 * Harus dijalankan setelah MasterDataSeeder dan pengguna demo (mis. DatabaseSeeder).
 * Idempotent: melewati jika catatan ber-tag seed sudah ada.
 */
class ContohAssessmentSeeder extends Seeder
{
    public function run(): void
    {
        $versi = MatrixVersion::query()->where('kode_versi', 'KAMUS-17-DEFAULT')->first();
        $konsultan = User::query()->where('alamat_surel', 'konsultan@example.com')->first()
            ?? User::query()->where('peran', 'admin')->first();

        if ($versi === null || $konsultan === null) {
            $this->command?->warn('ContohAssessmentSeeder dilewati: versi matriks KAMUS-17-DEFAULT atau pengguna konsultan/admin tidak ditemukan.');

            return;
        }

        DB::transaction(function () use ($versi, $konsultan): void {
            $this->seedManualExample($versi, $konsultan);
            $this->seedPayloadExample($versi, $konsultan);
        });
    }

    private function seedManualExample(MatrixVersion $versi, User $konsultan): void
    {
        $tag = '[seed:contoh-manual]';
        if (Assessment::query()->where('catatan', 'like', '%'.$tag.'%')->exists()) {
            return;
        }

        $peserta = Participant::query()->firstOrCreate(
            ['kode_peserta' => 'DEMO-ASESMEN-MANUAL'],
            [
                'nama_lengkap' => 'Peserta Demo — koleksi manual',
                'alamat_surel' => 'demo-manual-asesmen@example.test',
                'id_versi_matriks' => $versi->id,
                'aktif' => true,
            ]
        );

        /** @var Assessment $asesmen */
        $asesmen = Assessment::query()->create([
            'id_peserta' => $peserta->id,
            'id_versi_matriks' => $versi->id,
            'tujuan' => AssessmentPurpose::Promosi,
            'status' => AssessmentStatus::Berlangsung,
            'tanpa_intray' => false,
            'metode_koleksi_bukti' => AssessmentEvidenceCollectionMode::Manual,
            'id_pengguna_pembuat' => $konsultan->id,
            'catatan' => $tag.' Contoh asesmen dengan bukti per kompetensi dan perilaku kunci.',
        ]);

        AssessmentAssessor::query()->create([
            'id_asesmen' => $asesmen->id,
            'id_pengguna' => $konsultan->id,
        ]);

        AlatAsesmenPreset::buatPemilihan(
            $asesmen->id,
            $asesmen->id_versi_matriks,
            $asesmen->tujuan,
            $asesmen->tanpa_intray
        );

        $bei = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();
        $kompetensiInf = Competency::query()->where('kode_kompetensi', 'INF')->firstOrFail();
        $kompetensiAch = Competency::query()->where('kode_kompetensi', 'ACH')->firstOrFail();

        /** @var Evidence $bukti */
        $bukti = Evidence::query()->create([
            'id_asesmen' => $asesmen->id,
            'id_alat_penilaian' => $bei->id,
            'id_kompetensi' => $kompetensiInf->id,
            'teks_mentah' => "Pada proyek peningkatan layanan, peserta mengumpulkan informasi dari beberapa unit sebelum menyusun opsi.\n"
                .'KUTIPAN_SEED: menggali dokumen lapangan dan meminta klarifikasi kepada pemangku kepentingan terkait.',
        ]);

        Evidence::query()->create([
            'id_asesmen' => $asesmen->id,
            'id_alat_penilaian' => $bei->id,
            'id_kompetensi' => $kompetensiAch->id,
            'teks_mentah' => 'Target kuartal terlampaui melalui inisiatif perbaikan proses yang diinisiasi peserta bersama tim.',
        ]);

        $tingkatInf3 = CompetencyLevel::query()
            ->where('id_kompetensi', $kompetensiInf->id)
            ->where('tingkat', 3)
            ->whereNull('dihapus_pada')
            ->first();

        KeyBehavior::query()->create([
            'id_asesmen' => $asesmen->id,
            'id_alat_penilaian' => $bei->id,
            'id_kompetensi' => $kompetensiInf->id,
            'id_bukti_penilaian' => $bukti->id,
            'id_tingkat_kompetensi' => $tingkatInf3?->id,
            'teks_perilaku' => 'Menggali informasi tambahan dari sumber primer sebelum menyimpulkan rekomendasi.',
        ]);
    }

    private function seedPayloadExample(MatrixVersion $versi, User $konsultan): void
    {
        $tag = '[seed:contoh-payload]';
        if (Assessment::query()->where('catatan', 'like', '%'.$tag.'%')->exists()) {
            return;
        }

        $peserta = Participant::query()->firstOrCreate(
            ['kode_peserta' => 'DEMO-ASESMEN-AUTO'],
            [
                'nama_lengkap' => 'Peserta Demo — payload alat',
                'alamat_surel' => 'demo-payload-asesmen@example.test',
                'id_versi_matriks' => $versi->id,
                'aktif' => true,
            ]
        );

        /** @var Assessment $asesmen */
        $asesmen = Assessment::query()->create([
            'id_peserta' => $peserta->id,
            'id_versi_matriks' => $versi->id,
            'tujuan' => AssessmentPurpose::PemetaanTalenta,
            'status' => AssessmentStatus::Draf,
            'tanpa_intray' => false,
            'metode_koleksi_bukti' => AssessmentEvidenceCollectionMode::PayloadAlat,
            'id_pengguna_pembuat' => $konsultan->id,
            'catatan' => $tag.' Contoh asesmen dengan teks muatan alat (alur otomatis / AI bulk).',
        ]);

        AssessmentAssessor::query()->create([
            'id_asesmen' => $asesmen->id,
            'id_pengguna' => $konsultan->id,
        ]);

        AlatAsesmenPreset::buatPemilihan(
            $asesmen->id,
            $asesmen->id_versi_matriks,
            $asesmen->tujuan,
            $asesmen->tanpa_intray
        );

        $mi = AssessmentTool::query()->where('kode', 'MI')->firstOrFail();

        AssessmentToolPayload::query()->create([
            'id_asesmen' => $asesmen->id,
            'id_alat_penilaian' => $mi->id,
            'teks_muatan' => "Cuplikan perilaku MI (contoh seed):\n"
                ."INF — Peserta meminta data tambahan saat briefing proyek.\n"
                .'ACH — Peserta menyepakati target di atas baseline bersama atasan langsung.',
            'id_pengguna_pengunggah' => $konsultan->id,
        ]);
    }
}
