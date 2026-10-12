<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_locale_is_indonesian(): void
    {
        $this->assertSame('id', app()->getLocale());
    }

    public function test_auth_pages_are_in_indonesian(): void
    {
        $this->get('/login')->assertSee('Lupa kata sandi?')->assertSee('Ingat saya')->assertSee('Kata Sandi')
            ->assertDontSee('Forgot your password?')->assertDontSee('Remember me');
        $this->get('/register')->assertSee('Sudah punya akun?')->assertSee('Konfirmasi Kata Sandi')->assertDontSee('Already registered?');
        $this->get('/forgot-password')->assertSee('Kirim Tautan Atur Ulang Kata Sandi');
    }

    public function test_profile_page_is_in_indonesian(): void
    {
        $this->actingAs(User::factory()->create())->get('/profile')
            ->assertSee('Informasi Profil')->assertSee('Perbarui Kata Sandi')->assertSee('Hapus Akun')->assertSee('Simpan')
            ->assertDontSee('Delete Account')->assertDontSee('Profile Information');
    }

    public function test_validation_messages_are_in_indonesian(): void
    {
        $this->post('/register', [])->assertSessionHasErrors(['name' => 'Nama wajib diisi.', 'password' => 'Kata sandi wajib diisi.']);
    }

    public function test_failed_login_message_is_in_indonesian(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'salah'])
            ->assertSessionHasErrors(['email' => 'Email atau kata sandi yang Anda masukkan salah.']);
    }

    public function test_reset_password_email_is_in_indonesian(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($n) use ($user) {
            $mail = $n->toMail($user);

            return $mail->subject === 'Atur ulang kata sandi Anda' && $mail->actionText === 'Atur Ulang Kata Sandi';
        });
    }
}
