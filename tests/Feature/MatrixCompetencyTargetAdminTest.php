<?php

namespace Tests\Feature;

use App\Models\CompetencyToolMapping;
use App\Models\MatrixVersion;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class MatrixCompetencyTargetAdminTest extends TestCase
{
    public function test_admin_dapat_melihat_dan_menyimpan_target(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $idVersi = CompetencyToolMapping::query()->value('id_versi_matriks');
        $versi = MatrixVersion::query()->findOrFail($idVersi);
        $idKompetensi = (int) CompetencyToolMapping::query()->where('id_versi_matriks', $idVersi)->value('id_kompetensi');

        $this->actingAs($admin)
            ->get(route('master.versi-matriks.target-kompetensi.index', $versi))
            ->assertOk()
            ->assertSee('Target kompetensi');

        $this->actingAs($admin)
            ->post(route('master.versi-matriks.target-kompetensi.store', $versi), [
                'target' => [$idKompetensi => 5],
            ])
            ->assertRedirect(route('master.versi-matriks.target-kompetensi.index', $versi));

        $this->assertDatabaseHas('ais_target_kompetensi_matriks', [
            'id_versi_matriks' => $idVersi,
            'id_kompetensi' => $idKompetensi,
            'tingkat_target' => 5,
        ]);

        // 0 menghapus target.
        $this->actingAs($admin)
            ->post(route('master.versi-matriks.target-kompetensi.store', $versi), [
                'target' => [$idKompetensi => 0],
            ]);
        $this->assertDatabaseMissing('ais_target_kompetensi_matriks', [
            'id_versi_matriks' => $idVersi,
            'id_kompetensi' => $idKompetensi,
        ]);
    }

    public function test_konsultan_tidak_dapat_menyimpan_target(): void
    {
        $this->seed(DatabaseSeeder::class);
        $konsultan = User::query()->where('alamat_surel', 'konsultan@example.com')->firstOrFail();
        $idVersi = CompetencyToolMapping::query()->value('id_versi_matriks');
        $versi = MatrixVersion::query()->findOrFail($idVersi);
        $idKompetensi = (int) CompetencyToolMapping::query()->where('id_versi_matriks', $idVersi)->value('id_kompetensi');

        $this->actingAs($konsultan)
            ->post(route('master.versi-matriks.target-kompetensi.store', $versi), [
                'target' => [$idKompetensi => 5],
            ])
            ->assertForbidden();
    }
}
