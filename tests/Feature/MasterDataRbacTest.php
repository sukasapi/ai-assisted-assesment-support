<?php

namespace Tests\Feature;

use App\Models\AssessmentTool;
use App\Models\Competency;
use App\Models\CompetencyGroup;
use App\Models\CompetencyToolMapping;
use App\Models\MatrixVersion;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MasterDataRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_konsultan_can_view_master_index(): void
    {
        $this->seed(DatabaseSeeder::class);
        $konsultan = User::query()->where('alamat_surel', 'konsultan@example.com')->firstOrFail();

        $this->actingAs($konsultan)
            ->get(route('master.index'))
            ->assertOk();
    }

    public function test_konsultan_cannot_store_competency_group(): void
    {
        $this->seed(DatabaseSeeder::class);
        $konsultan = User::query()->where('alamat_surel', 'konsultan@example.com')->firstOrFail();

        $this->actingAs($konsultan)
            ->post(route('master.kelompok-kompetensi.store'), [
                'kode' => 'TST',
                'nama' => 'Test Group',
            ])
            ->assertForbidden();
    }

    public function test_konsultan_cannot_view_activity_log(): void
    {
        $this->seed(DatabaseSeeder::class);
        $konsultan = User::query()->where('alamat_surel', 'konsultan@example.com')->firstOrFail();

        $this->actingAs($konsultan)
            ->get(route('master.log-aktivitas.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_activity_log_index(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('master.log-aktivitas.index'))
            ->assertOk();
    }

    public function test_konsultan_cannot_access_csv_import(): void
    {
        $this->seed(DatabaseSeeder::class);
        $konsultan = User::query()->where('alamat_surel', 'konsultan@example.com')->firstOrFail();

        $this->actingAs($konsultan)
            ->get(route('peserta.impor-csv'))
            ->assertForbidden();

        $this->actingAs($konsultan)
            ->get(route('peserta.impor-csv.template', ['delimiter' => ',']))
            ->assertForbidden();
    }

    public function test_admin_can_download_participant_csv_template(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();

        $response = $this->actingAs($admin)
            ->get(route('peserta.impor-csv.template', ['delimiter' => ';']));

        $response->assertOk();
        $this->assertStringContainsString('kode_peserta', $response->streamedContent());
        $this->assertStringContainsString('CONTOH-001', $response->streamedContent());
    }

    public function test_admin_can_import_participants_with_semicolon_delimiter(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();

        $isi = "kode_peserta;nama_lengkap;alamat_surel\nTEST-CSV-99;Nama Semicolon;test99@example.test\n";
        $berkas = UploadedFile::fake()->createWithContent('peserta.csv', $isi);

        $this->actingAs($admin)
            ->post(route('peserta.impor-csv.store'), [
                'berkas_csv' => $berkas,
                'delimiter' => ';',
            ])
            ->assertRedirect(route('peserta.impor-csv'));

        $this->assertDatabaseHas('ais_peserta', [
            'kode_peserta' => 'TEST-CSV-99',
            'nama_lengkap' => 'Nama Semicolon',
            'alamat_surel' => 'test99@example.test',
        ]);
    }

    public function test_konsultan_can_view_mappings_for_a_matrix_version(): void
    {
        $this->seed(DatabaseSeeder::class);
        $konsultan = User::query()->where('alamat_surel', 'konsultan@example.com')->firstOrFail();
        $versi = MatrixVersion::query()->firstOrFail();

        $this->actingAs($konsultan)
            ->get(route('master.versi-matriks.pemetaan.index', $versi))
            ->assertOk();
    }

    public function test_konsultan_cannot_sync_mapping_grid(): void
    {
        $this->seed(DatabaseSeeder::class);
        $konsultan = User::query()->where('alamat_surel', 'konsultan@example.com')->firstOrFail();
        $versi = MatrixVersion::query()->firstOrFail();
        $c = Competency::query()->firstOrFail();
        $t = AssessmentTool::query()->firstOrFail();

        $this->actingAs($konsultan)
            ->post(route('master.versi-matriks.pemetaan.sync', $versi), [
                'sel' => [$c->id.'-'.$t->id],
            ])
            ->assertForbidden();
    }

    public function test_admin_can_sync_mapping_grid_for_matrix_version(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $versi = MatrixVersion::query()->firstOrFail();
        $c = Competency::query()->orderBy('id')->firstOrFail();
        $t = AssessmentTool::query()->orderBy('id')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('master.versi-matriks.pemetaan.sync', $versi), [
                'sel' => [],
                'hapus_semua' => '1',
            ])
            ->assertRedirect(route('master.versi-matriks.pemetaan.index', $versi));

        $this->assertSame(0, CompetencyToolMapping::query()->where('id_versi_matriks', $versi->id)->count());

        $this->actingAs($admin)
            ->post(route('master.versi-matriks.pemetaan.sync', $versi), [
                'sel' => [$c->id.'-'.$t->id],
            ])
            ->assertRedirect(route('master.versi-matriks.pemetaan.index', $versi));

        $this->assertSame(1, CompetencyToolMapping::query()->where('id_versi_matriks', $versi->id)->count());
        $this->assertDatabaseHas('ais_pemetaan_kompetensi_alat', [
            'id_versi_matriks' => $versi->id,
            'id_kompetensi' => $c->id,
            'id_alat_penilaian' => $t->id,
            'dihapus_pada' => null,
        ]);

        $this->assertDatabaseHas('ais_log_aktivitas', [
            'id_pengguna' => $admin->id,
            'aksi' => 'master.pemetaan_matriks.disinkronkan',
            'subjek_id' => $versi->id,
        ]);
    }

    public function test_admin_cannot_sync_empty_grid_without_confirm_when_mappings_exist(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $versi = MatrixVersion::query()->where('kode_versi', 'KAMUS-17-DEFAULT')->firstOrFail();

        $this->assertGreaterThan(0, CompetencyToolMapping::query()->where('id_versi_matriks', $versi->id)->count());

        $this->actingAs($admin)
            ->from(route('master.versi-matriks.pemetaan.index', $versi))
            ->post(route('master.versi-matriks.pemetaan.sync', $versi), ['sel' => []])
            ->assertRedirect(route('master.versi-matriks.pemetaan.index', $versi))
            ->assertSessionHasErrors('hapus_semua');
    }

    public function test_admin_can_store_and_soft_delete_competency_group(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('master.kelompok-kompetensi.store'), [
                'kode' => 'TST',
                'nama' => 'Test Group',
            ])
            ->assertRedirect(route('master.kelompok-kompetensi.index'));

        $group = CompetencyGroup::query()->where('kode', 'TST')->firstOrFail();
        $this->assertDatabaseHas('ais_kelompok_kompetensi', ['kode' => 'TST', 'dihapus_pada' => null]);

        $this->actingAs($admin)
            ->delete(route('master.kelompok-kompetensi.destroy', $group))
            ->assertRedirect(route('master.kelompok-kompetensi.index'));

        $deleted = CompetencyGroup::query()->onlyTrashed()->where('kode', 'TST')->first();
        $this->assertNotNull($deleted);
        $this->assertNotNull($deleted->dihapus_pada);
    }
}
