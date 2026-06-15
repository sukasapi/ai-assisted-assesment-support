<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentSession;
use App\Models\User;
use App\Support\ConsultantAccessSession;
use Database\Seeders\DatabaseSeeder;
use Tests\Support\AssessmentTestHelpers;
use Tests\TestCase;

class SessionUserAndTokenTest extends TestCase
{

    public function test_konsultan_tidak_boleh_crud_master_pengguna(): void
    {
        $this->seed(DatabaseSeeder::class);

        $konsultan = User::query()->where('alamat_surel', 'konsultan@example.com')->firstOrFail();

        $this->actingAs($konsultan)
            ->get(route('master.pengguna.create'))
            ->assertForbidden();
    }

    public function test_admin_dapat_membuat_pengguna_konsultan(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('master.pengguna.store'), [
                'nama' => 'Konsultan Baru',
                'alamat_surel' => 'baru-konsultan@example.com',
                'peran' => 'konsultan',
                'kata_sandi' => 'password123',
                'aktif' => '1',
            ])
            ->assertRedirect(route('master.pengguna.index'));

        $this->assertDatabaseHas('ais_pengguna', [
            'alamat_surel' => 'baru-konsultan@example.com',
            'peran' => 'konsultan',
            'aktif' => true,
        ]);
    }

    public function test_admin_dapat_membuat_sesi_asesmen(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('sesi-asesmen.store'), [
                'kode_sesi' => 'SES-UNIT',
                'nama' => 'Sesi unit test',
                'status' => 'aktif',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ais_sesi_asesmen', [
            'kode_sesi' => 'SES-UNIT',
            'nama' => 'Sesi unit test',
        ]);
    }

    public function test_asesmen_baru_terikat_sesi(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin);

        $this->assertNotNull($asesmen->id_sesi_asesmen);
        $this->assertInstanceOf(AssessmentSession::class, $asesmen->session);
    }

    public function test_admin_tidak_ditugaskan_tidak_boleh_update_asesmen(): void
    {
        $this->seed(DatabaseSeeder::class);

        $adminUtama = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $adminLain = User::factory()->admin()->create([
            'name' => 'Admin Kedua',
            'email' => 'admin2@example.com',
        ]);

        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $adminUtama);

        $this->actingAs($adminLain)
            ->patch(route('asesmen.metode-koleksi-bukti.update', $asesmen), [
                'metode_koleksi_bukti' => 'payload_alat',
            ])
            ->assertForbidden();
    }

    public function test_konsultan_wajib_token_sebelum_daftar_asesmen(): void
    {
        $this->seed(DatabaseSeeder::class);

        $konsultan = User::query()->where('alamat_surel', 'konsultan@example.com')->firstOrFail();

        $this->actingAs($konsultan)
            ->get(route('asesmen.index'))
            ->assertRedirect(route('asesmen.token'));
    }

    public function test_konsultan_hanya_melihat_asesmen_dalam_penugasan_token(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $konsultan = User::query()->where('alamat_surel', 'konsultan@example.com')->firstOrFail();

        $asesmenDitugaskan = AssessmentTestHelpers::buatAsesmen($this, $admin);
        AssessmentTestHelpers::buatAsesmen($this, $admin);

        $token = 'AB12CD34';
        AssessmentTestHelpers::buatPenugasanKonsultan($this, $konsultan, $asesmenDitugaskan, $admin, $token);

        AssessmentTestHelpers::masukTokenKonsultan($this, $konsultan, $token);

        $response = $this->actingAs($konsultan)->get(route('asesmen.index'));
        $response->assertOk();
        $response->assertSee((string) $asesmenDitugaskan->id);

        $lain = Assessment::query()
            ->where('id', '!=', $asesmenDitugaskan->id)
            ->latest('id')
            ->first();

        if ($lain !== null) {
            $response->assertDontSee('asesmen/'.$lain->id, false);
        }
    }

    public function test_konsultan_token_salah_ditolak(): void
    {
        $this->seed(DatabaseSeeder::class);

        $konsultan = User::query()->where('alamat_surel', 'konsultan@example.com')->firstOrFail();

        $this->actingAs($konsultan)
            ->post(route('asesmen.token.verify'), ['token_akses' => 'ZZZZZZZZ'])
            ->assertSessionHasErrors('token_akses');
    }

    public function test_hapus_token_mengosongkan_sesi_gate(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $konsultan = User::query()->where('alamat_surel', 'konsultan@example.com')->firstOrFail();
        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin);
        $token = 'GATE1234';

        AssessmentTestHelpers::buatPenugasanKonsultan($this, $konsultan, $asesmen, $admin, $token);
        AssessmentTestHelpers::masukTokenKonsultan($this, $konsultan, $token);

        $this->assertNotNull(ConsultantAccessSession::idAktif());

        $this->actingAs($konsultan)
            ->post(route('asesmen.token.clear'))
            ->assertRedirect(route('asesmen.token'));

        $this->assertNull(ConsultantAccessSession::idAktif());
    }
}
