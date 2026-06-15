<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginRateLimitTest extends TestCase
{

    public function test_login_dibatasi_setelah_lima_percobaan_gagal(): void
    {
        $payload = [
            'email' => 'tidak-ada@example.com',
            'password' => 'salah-terus',
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), $payload)
                ->assertRedirect()
                ->assertSessionHasErrors('email');
        }

        $this->post(route('login.store'), $payload)->assertStatus(429);
    }
}
