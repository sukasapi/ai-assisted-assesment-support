<?php

namespace Tests\Feature\Api;

use App\Models\Assessment;
use App\Models\AssessmentTool;
use App\Models\CompetencyLevel;
use App\Models\CompetencyToolMapping;
use App\Models\User;
use App\Services\Integration\CompetencyIntegrationService;
use Database\Seeders\DatabaseSeeder;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AssessmentTestHelpers;
use Tests\TestCase;

class PublicApiV1Test extends TestCase
{
    public function test_tanpa_token_ditolak_401(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->getJson('/api/v1/asesmen')->assertUnauthorized();
    }

    public function test_token_tanpa_ability_read_ditolak_403(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        // Token asli dengan ability selain "read".
        $plain = $admin->createToken('noread', ['ping'])->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$plain)->getJson('/api/v1/asesmen')->assertForbidden();
        // tetapi /me tetap dapat diakses (tanpa syarat ability read)
        $this->withHeader('Authorization', 'Bearer '.$plain)->getJson('/api/v1/me')
            ->assertOk()->assertJsonPath('data.pengguna.email', 'admin@example.com');
    }

    public function test_pemilik_token_nonaktif_ditolak_403(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $admin->update(['aktif' => false]);
        Sanctum::actingAs($admin, ['read']);

        $this->getJson('/api/v1/me')->assertForbidden();
    }

    public function test_daftar_dan_detail_asesmen_mengembalikan_hasil(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin);
        $this->isiPkDisahkan($asesmen, $admin);
        app(CompetencyIntegrationService::class)->hitungUlang($asesmen->fresh(), $admin->id);

        Sanctum::actingAs($admin, ['read']);

        $this->getJson('/api/v1/asesmen')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'peserta' => ['kode', 'nama'], 'status', 'job_fit_persen']], 'meta', 'links']);

        $this->getJson('/api/v1/asesmen/'.$asesmen->id)
            ->assertOk()
            ->assertJsonPath('data.id', $asesmen->id)
            ->assertJsonStructure(['data' => ['id', 'peserta', 'matriks', 'job_fit_persen', 'kompetensi', 'perilaku_kunci']])
            ->assertJsonPath('data.peserta.kode_peserta', $asesmen->participant->kode_peserta);

        // hasil integrasi & PK disahkan tampil
        $detail = $this->getJson('/api/v1/asesmen/'.$asesmen->id)->json('data');
        $this->assertNotEmpty($detail['kompetensi']);
        $this->assertNotEmpty($detail['perilaku_kunci']);
    }

    public function test_endpoint_lain_dapat_diakses(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin);
        Sanctum::actingAs($admin, ['read']);

        $this->getJson('/api/v1/sesi-asesmen')->assertOk()->assertJsonStructure(['data', 'meta']);
        $this->getJson('/api/v1/sesi-asesmen/'.$asesmen->id_sesi_asesmen)
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'kode_sesi', 'ringkasan', 'peringkat']]);
        $this->getJson('/api/v1/peserta')->assertOk()->assertJsonStructure(['data', 'meta']);
        $this->getJson('/api/v1/kompetensi')->assertOk()->assertJsonStructure(['data' => [['id', 'kode', 'nama', 'tingkat_maksimum']]]);
    }

    public function test_token_asli_dari_create_token_bekerja(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $plain = $admin->createToken('uji', ['read'])->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$plain)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.token.nama', 'uji');
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
                ->where('id_kompetensi', $idKompetensi)->whereNull('dihapus_pada')->orderBy('tingkat')->value('id');
            if ($idTingkat === null) {
                continue;
            }
            $asesmen->keyBehaviors()->updateOrCreate(
                ['id_alat_penilaian' => $alat->id, 'id_kompetensi' => (int) $idKompetensi],
                [
                    'id_tingkat_kompetensi' => (int) $idTingkat,
                    'teks_perilaku' => 'PK uji API',
                    'tervalidasi' => true,
                    'id_pengguna_validasi' => $admin->id,
                    'waktu_validasi' => now(),
                ],
            );
        }
    }
}
