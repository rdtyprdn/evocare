<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\User;
use App\Notifications\OrganizationReviewed;
use App\Notifications\RegistrationCancelled;
use App\Notifications\RegistrationConfirmed;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use App\Notifications\WelcomeEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Transport\ResendTransport;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmailDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function event(): Event
    {
        return Event::factory()->create(['status' => 'published', 'start_at' => now()->addDays(3), 'end_at' => now()->addDays(3)->addHours(2)]);
    }

    // ---------- Pengguna bisa langsung memakai fitur tanpa verifikasi email ----------

    public function test_unverified_user_can_register_for_event(): void
    {
        Notification::fake();
        $event = $this->event();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('registrations.create', $event))->assertOk();
        $this->actingAs($user)->post(route('registrations.store', $event), ['type' => 'relawan'])->assertRedirect(route('registrations.index'));
        $this->assertDatabaseCount('registrations', 1);
    }

    public function test_unverified_user_can_apply_organization(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('organizations.create'))->assertOk();
        $this->actingAs($user)->post(route('organizations.store'), [
            'name' => 'Komunitas Baru',
            'type' => 'komunitas',
            'city' => 'Denpasar',
            'description' => 'Komunitas sosial.',
            'contact' => 'komunitas@contoh.test',
        ])->assertRedirect();
        $this->assertDatabaseCount('organizations', 1);
        $this->assertSame('pending', Organization::first()->status);
    }

    public function test_unverified_organizer_can_create_event(): void
    {
        $organizer = User::factory()->unverified()->organizer()->create();
        $category = Category::factory()->create();

        $this->actingAs($organizer)->get(route('organizer.events.create'))->assertOk();
        $this->actingAs($organizer)->post(route('organizer.events.store'), [
            'title' => 'Aksi Bersih Pantai',
            'category_id' => $category->id,
            'description' => 'Membersihkan pantai bersama relawan.',
            'city' => 'Denpasar',
            'location' => 'Pantai Sanur',
            'start_at' => now()->addDays(5)->format('Y-m-d H:i'),
            'end_at' => now()->addDays(5)->addHours(2)->format('Y-m-d H:i'),
            'quota' => 20,
            'status' => 'published',
        ])->assertRedirect(route('organizer.events.index'));
        $this->assertDatabaseHas('events', ['title' => 'Aksi Bersih Pantai', 'user_id' => $organizer->id]);
    }

    public function test_unverified_user_can_browse_dashboard_and_profile_without_banner(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('registrations.index'))->assertOk()->assertDontSee('Email Anda belum diverifikasi');
        $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertDontSee('Alamat email Anda belum diverifikasi');
        $this->get(route('events.show', $this->event()))->assertOk();
    }

    public function test_verified_user_registers_and_gets_queued_confirmation(): void
    {
        Notification::fake();
        $event = $this->event();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('registrations.store', $event), ['type' => 'relawan'])->assertRedirect(route('registrations.index'));

        Notification::assertSentTo($user, RegistrationConfirmed::class, function ($n) use ($user, $event) {
            $mail = $n->toMail($user);
            $this->assertSame('Pendaftaran kegiatan berhasil: '.$event->title, $mail->subject);
            $this->assertSame('Lihat Tiket', $mail->actionText);

            return true;
        });
    }

    public function test_cancelling_registration_sends_queued_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $reg = Registration::create(['user_id' => $user->id, 'event_id' => $this->event()->id, 'type' => 'relawan']);

        $this->actingAs($user)->delete(route('registrations.destroy', $reg))->assertRedirect();

        Notification::assertSentToTimes($user, RegistrationCancelled::class, 1);
    }

    public function test_admin_review_notifies_organization_owner(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create();
        $org = Organization::factory()->pending()->create(['owner_id' => $owner->id]);
        $other = User::factory()->create();
        $rejected = Organization::factory()->pending()->create(['owner_id' => $other->id]);

        $this->actingAs($admin)->post(route('admin.organizations.verify', $org))->assertRedirect();
        $this->actingAs($admin)->post(route('admin.organizations.reject', $rejected), ['admin_note' => 'Data kurang lengkap'])->assertRedirect();

        Notification::assertSentTo($owner, OrganizationReviewed::class, fn ($n) => str_contains($n->toMail($owner)->subject, 'terverifikasi'));
        Notification::assertSentTo($other, OrganizationReviewed::class, function ($n) use ($other) {
            $mail = $n->toMail($other);

            return str_contains($mail->subject, 'ditolak') && in_array('Alasan: Data kurang lengkap', $mail->introLines, true);
        });
    }

    // ---------- Halaman pemberitahuan verifikasi ----------

    public function test_verification_notice_is_indonesian_and_orange(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('verification.notice'))->assertOk()
            ->assertSee('Verifikasi email Anda')
            ->assertSee('Kirim Ulang Email Verifikasi')
            ->assertSee('bg-orange-600', false)
            ->assertSee('<svg', false)
            ->assertDontSee('Resend Verification Email');
    }

    public function test_resend_verification_sends_indonesian_email_and_is_throttled(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->from(route('verification.notice'))->post(route('verification.send'))
            ->assertRedirect(route('verification.notice'))->assertSessionHas('status', 'verification-link-sent');
        Notification::assertSentTo($user, VerifyEmailNotification::class, function ($n) use ($user) {
            $mail = $n->toMail($user);

            return $mail->subject === 'Verifikasi alamat email EvoCare Anda' && $mail->actionText === 'Verifikasi Email';
        });

        $this->actingAs($user)->from(route('verification.notice'))->post(route('verification.send'));
        $this->actingAs($user)->from(route('verification.notice'))->post(route('verification.send'))
            ->assertRedirect(route('verification.notice'))->assertSessionHasErrors('email');
        Notification::assertSentToTimes($user, VerifyEmailNotification::class, 2);
    }

    // ---------- Antrian ----------

    public function test_all_email_notifications_are_queued(): void
    {
        foreach ([WelcomeEmail::class, VerifyEmailNotification::class, ResetPasswordNotification::class,
            RegistrationConfirmed::class, RegistrationCancelled::class, OrganizationReviewed::class] as $class) {
            $this->assertTrue(is_subclass_of($class, ShouldQueue::class), $class.' harus ShouldQueue');
        }
    }

    public function test_register_pushes_welcome_email_to_queue_once(): void
    {
        Queue::fake();

        $this->post(route('register'), ['name' => 'Dewi', 'email' => 'dewi@contoh.test', 'password' => 'password123', 'password_confirmation' => 'password123'])
            ->assertRedirect();

        Queue::assertPushed(SendQueuedNotifications::class, 1);
        Queue::assertPushed(SendQueuedNotifications::class, fn ($job) => $job->notification instanceof WelcomeEmail);
        $this->assertNull(User::where('email', 'dewi@contoh.test')->first()->email_verified_at);
    }

    public function test_forgot_password_pushes_reset_email_to_queue(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');

        Queue::assertPushed(SendQueuedNotifications::class, fn ($job) => $job->notification instanceof ResetPasswordNotification);
    }

    public function test_emails_are_delivered_with_sync_queue(): void
    {
        config(['queue.default' => 'sync', 'mail.default' => 'array']);
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');

        $messages = app('mail.manager')->mailer('array')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $email = $messages[0]->getOriginalMessage();
        $this->assertSame('Atur ulang kata sandi EvoCare', $email->getSubject());
        $this->assertSame($user->email, $email->getTo()[0]->getAddress());
        $this->assertStringContainsString('Atur Ulang Kata Sandi', $email->getHtmlBody());
    }

    public function test_forgot_password_shows_error_when_mail_fails(): void
    {
        config(['queue.default' => 'sync']);
        Notification::shouldReceive('send')->andThrow(new \RuntimeException('Resend tidak terjangkau'));
        $user = User::factory()->create();

        $this->from(route('password.request'))->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.request'))->assertSessionHasErrors('email');
    }

    // ---------- Konfigurasi Resend ----------

    public function test_resend_mailer_is_configured(): void
    {
        config(['services.resend.key' => 're_contoh_bukan_kunci_asli']);

        $this->assertSame('resend', config('mail.mailers.resend.transport'));
        $this->assertInstanceOf(ResendTransport::class, Mail::mailer('resend')->getSymfonyTransport());
    }
}
