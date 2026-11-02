<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Notifications\WelcomeVerifyEmail;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthReturnAndEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function event(): Event
    {
        return Event::factory()->create(['status' => 'published', 'start_at' => now()->addDays(3), 'end_at' => now()->addDays(3)->addHours(2)]);
    }

    public function test_event_page_links_carry_redirect_to_event(): void
    {
        $event = $this->event();
        $path = route('events.show', $event, false);
        $this->get(route('events.show', $event))->assertOk()
            ->assertSee(route('login', ['redirect' => $path]), false)
            ->assertSee(route('register', ['redirect' => $path]), false);
    }

    public function test_login_returns_to_event_page(): void
    {
        $event = $this->event();
        $user = User::factory()->create();
        $this->get(route('login', ['redirect' => route('events.show', $event, false)]))->assertOk();
        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('events.show', $event));
    }

    public function test_register_returns_to_event_page(): void
    {
        $event = $this->event();
        $this->get(route('register', ['redirect' => route('events.show', $event, false)]))->assertOk();
        $this->post(route('register'), ['name' => 'Budi', 'email' => 'budi@contoh.test', 'password' => 'password123', 'password_confirmation' => 'password123'])
            ->assertRedirect(route('events.show', $event));
    }

    public function test_guest_registering_for_event_returns_to_event_after_login(): void
    {
        $event = $this->event();
        $user = User::factory()->create();
        $this->post(route('registrations.store', $event), ['type' => 'relawan'])->assertRedirect(route('login'));
        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('events.show', $event));
    }

    public function test_external_redirect_is_ignored_and_default_kept(): void
    {
        $user = User::factory()->create();
        foreach (['https://jahat.test/x', '//jahat.test', '/\\jahat.test', 'javascript:alert(1)'] as $bad) {
            $this->flushSession();
            $this->get(route('login', ['redirect' => $bad]))->assertOk();
            $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
                ->assertRedirect(route('dashboard', absolute: false));
            $this->post(route('logout'));
        }
    }

    public function test_changing_email_sends_verification_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->actingAs($user)->patch(route('profile.update'), ['name' => $user->name, 'email' => 'baru@contoh.test'])
            ->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'))->assertSessionHas('status', 'email-verification-sent');
        $this->assertNull($user->fresh()->email_verified_at);
        Notification::assertSentTo($user->fresh(), VerifyEmail::class);

        $this->actingAs($user->fresh())->get(route('profile.edit'))->assertSee('Tautan verifikasi telah dikirim ke alamat email baru Anda');
    }

    public function test_same_email_does_not_send_verification(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->actingAs($user)->patch(route('profile.update'), ['name' => 'Nama Baru', 'email' => $user->email])
            ->assertSessionHas('status', 'profile-updated');
        Notification::assertNothingSent();
    }

    public function test_verification_link_verifies_new_email(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->actingAs($user)->get($url)->assertRedirect();
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_registration_sends_indonesian_welcome_email_with_verification_link(): void
    {
        Notification::fake();
        $this->post(route('register'), ['name' => 'Sari', 'email' => 'sari@contoh.test', 'password' => 'password123', 'password_confirmation' => 'password123'])
            ->assertRedirect();
        $user = User::where('email', 'sari@contoh.test')->firstOrFail();
        Notification::assertSentToTimes($user, WelcomeVerifyEmail::class, 1);
        Notification::assertNotSentTo($user, VerifyEmail::class);
        Notification::assertSentTo($user, WelcomeVerifyEmail::class, function ($n) use ($user) {
            $mail = $n->toMail($user);
            $this->assertSame('Selamat datang di EvoCare, verifikasi email Anda', $mail->subject);
            $this->assertSame('Verifikasi Email', $mail->actionText);
            $this->assertStringContainsString('/verify-email/'.$user->id.'/', $mail->actionUrl);
            $this->actingAs($user)->get($mail->actionUrl)->assertRedirect();
            $this->assertNotNull($user->fresh()->email_verified_at);

            return true;
        });
    }

    public function test_registration_succeeds_even_if_mail_fails(): void
    {
        $this->mock(Dispatcher::class, function ($m) {
            $m->shouldReceive('send')->andThrow(new \RuntimeException('SMTP tidak terjangkau'));
        });
        $this->withoutExceptionHandling([\RuntimeException::class]);

        $this->post(route('register'), ['name' => 'Tono', 'email' => 'tono@contoh.test', 'password' => 'password123', 'password_confirmation' => 'password123'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('users', ['email' => 'tono@contoh.test']);
        $this->assertAuthenticated();
    }

    public function test_registration_works_with_log_mailer(): void
    {
        config(['mail.default' => 'log']);
        $this->post(route('register'), ['name' => 'Rina', 'email' => 'rina@contoh.test', 'password' => 'password123', 'password_confirmation' => 'password123'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('users', ['email' => 'rina@contoh.test']);
    }
}
