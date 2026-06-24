<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentTool;
use App\Models\CompetencyLevel;
use App\Models\CompetencyToolMapping;
use App\Models\User;
use App\Services\Integration\CompetencyIntegrationService;
use Database\Seeders\DatabaseSeeder;
use Tests\Support\AssessmentTestHelpers;
use Tests\TestCase;

class AssessmentReportPdfTest extends TestCase
{
    public function test_laporan_pdf_dapat_diunduh(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin);
        $this->isiPkDisahkan($asesmen, $admin);
        app(CompetencyIntegrationService::class)->hitungUlang($asesmen->fresh(), $admin->id);

        $response = $this->actingAs($admin)->get(route('asesmen.laporan-pdf', $asesmen));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_laporan_pratinjau_html(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin);

        $this->actingAs($admin)
            ->get(route('asesmen.laporan-pdf', ['asesmen' => $asesmen, 'format' => 'html']))
            ->assertOk()
            ->assertSee('Laporan Hasil Asesmen Kompetensi')
            ->assertSee($asesmen->participant->nama_lengkap);
    }

    private function isiPkDisahkan(Assessment $asesmen, User $admin): void
    {
        $idAlatAktif = $asesmen->toolSelections()->where('aktif', true)->pluck('id_alat_penilaian')->all();
        $idKompetensiWajib = CompetencyToolMapping::query()
            ->where('id_versi_matriks', $asesmen->id_versi_matriks)
            ->whereIn('id_alat_penilaian', $idAlatAktif)
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
                ['id_alat_penilaian' => $alat->id, 'id_kompetensi' => (int) $idKompetensi],
                [
                    'id_tingkat_kompetensi' => (int) $idTingkat,
                    'teks_perilaku' => 'PK disahkan uji laporan',
                    'tervalidasi' => true,
                    'id_pengguna_validasi' => $admin->id,
                    'waktu_validasi' => now(),
                ],
            );
        }
    }
}
