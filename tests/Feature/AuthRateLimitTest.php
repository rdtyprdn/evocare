<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pendaftaran akun dan permintaan reset kata sandi dibatasi 5 kali per menit per IP. */
class AuthRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_is_limited_per_ip(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->post('/register', [
                'name' => "Spam $i",
                'email' => "spam$i@example.com",
                'password' => 'password',
                'password_confirmation' => 'password',
            ])->assertRedirect();
            auth()->logout();
        }

        $this->from('/register')->post('/register', [
            'name' => 'Spam 6',
            'email' => 'spam6@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/register')->assertSessionHasErrors('email');

        $this->assertStringContainsString('Terlalu banyak percobaan', session('errors')->first('email'));
        $this->assertSame(5, User::count());
        $this->assertGuest();
    }

    public function test_register_limit_is_separate_per_ip(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.$i"])->post('/register', [
                'name' => "User $i",
                'email' => "user$i@example.com",
                'password' => 'password',
                'password_confirmation' => 'password',
            ])->assertSessionHasNoErrors();
            auth()->logout();
        }

        $this->assertSame(6, User::count());
    }

    public function test_forgot_password_is_limited_per_ip(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->from('/forgot-password')->post('/forgot-password', ['email' => "orang$i@example.com"])
                ->assertRedirect('/forgot-password');
        }

        $this->from('/forgot-password')->post('/forgot-password', ['email' => 'orang6@example.com'])
            ->assertRedirect('/forgot-password')->assertSessionHasErrors('email');
        $this->assertStringContainsString('Terlalu banyak percobaan', session('errors')->first('email'));
    }

    public function test_json_request_gets_429(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->postJson('/forgot-password', ['email' => "orang$i@example.com"]);
        }

        $response = $this->postJson('/forgot-password', ['email' => 'orang6@example.com'])->assertStatus(429)->assertHeader('Retry-After');
        $this->assertMatchesRegularExpression('/^Terlalu banyak percobaan\. Silakan coba lagi dalam \d+ detik\.$/', $response->json('message'));
    }
}
