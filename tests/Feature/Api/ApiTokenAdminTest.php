<?php

namespace Tests\Feature\Api;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class ApiTokenAdminTest extends TestCase
{
    public function test_admin_dapat_membuat_dan_mencabut_token(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)->get(route('master.token-api.index'))->assertOk()->assertSee('Token API');

        $this->actingAs($admin)
            ->post(route('master.token-api.store'), ['nama' => 'Integrasi HRIS'])
            ->assertRedirect(route('master.token-api.index'))
            ->assertSessionHas('api_token_baru');

        $token = PersonalAccessToken::query()->where('name', 'Integrasi HRIS')->firstOrFail();
        $this->assertEquals(['read'], $token->abilities);

        $this->actingAs($admin)
            ->delete(route('master.token-api.destroy', $token))
            ->assertRedirect(route('master.token-api.index'));
        $this->assertDatabaseMissing('ais_token_akses_pribadi', ['id' => $token->id]);
    }

    public function test_konsultan_tidak_dapat_mengelola_token(): void
    {
        $this->seed(DatabaseSeeder::class);
        $konsultan = User::query()->where('alamat_surel', 'konsultan@example.com')->firstOrFail();

        $this->actingAs($konsultan)->get(route('master.token-api.index'))->assertForbidden();
        $this->actingAs($konsultan)->post(route('master.token-api.store'), ['nama' => 'X'])->assertForbidden();
    }
}
