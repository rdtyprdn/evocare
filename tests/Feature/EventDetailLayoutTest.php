<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventDetailLayoutTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->organizer()->create();
        $this->event = Event::factory()->create(['user_id' => $this->owner->id, 'quota' => 23, 'city' => 'Karangasem']);
    }

    public function test_layout_shows_breadcrumb_info_rows_and_about(): void
    {
        $this->get(route('events.show', $this->event))->assertOk()
            ->assertSee('Jelajahi Kegiatan')->assertSee($this->event->category->name)
            ->assertSeeInOrder(['Tanggal', 'Waktu', 'Lokasi', 'Kategori', 'Penyelenggara', 'Sisa kuota'])
            ->assertSee('role="progressbar"', false)
            ->assertSee('Bisa ikut sebagai')->assertSee('Tentang Kegiatan')
            ->assertSee($this->event->organization->name);
    }

    public function test_with_media_shows_main_viewer_and_thumbnail_strip(): void
    {
        $this->event->photos()->create(['type' => 'image', 'path' => 'event-photos/1/a.jpg', 'caption' => 'Foto pertama', 'sort_order' => 1]);
        $this->event->photos()->create(['type' => 'youtube', 'youtube_id' => 'dQw4w9WgXcQ', 'sort_order' => 2]);

        $this->get(route('events.show', $this->event))->assertOk()
            ->assertSee('aspect-video', false)->assertSee('x-ref="track"', false)
            ->assertSee('1 / 2')->assertSee('Tampilkan foto 1')->assertSee('Tampilkan youtube 2')
            ->assertSee('YouTube')->assertDontSee('Belum ada foto atau video dokumentasi');
    }

    public function test_empty_documentation_shows_cover_with_hint_and_no_strip(): void
    {
        $this->get(route('events.show', $this->event))->assertOk()
            ->assertSee('Sampul kegiatan')->assertSee('Belum ada foto atau video dokumentasi untuk kegiatan ini.')
            ->assertDontSee('x-ref="track"', false);
    }

    public function test_guest_sees_login_prompt_and_sticky_bar(): void
    {
        $this->get(route('events.show', $this->event))->assertOk()
            ->assertSee('Masuk untuk mendaftar')->assertSee('Masuk untuk daftar')
            ->assertDontSee('name="type"', false)->assertDontSee('Ubah kegiatan');
    }

    public function test_logged_in_user_sees_registration_bar(): void
    {
        $this->actingAs(User::factory()->create())->get(route('events.show', $this->event))->assertOk()
            ->assertDontSee('name="type"', false)->assertDontSee('name="note"', false)
            ->assertSee('Daftar Sekarang')
            ->assertSee(route('registrations.create', $this->event), false)
            ->assertSee('href="#daftar"', false);
    }

    public function test_registered_user_sees_green_bar_with_cancel(): void
    {
        $user = User::factory()->create();
        Registration::create(['event_id' => $this->event->id, 'user_id' => $user->id, 'type' => 'relawan', 'status' => 'confirmed']);

        $this->actingAs($user)->get(route('events.show', $this->event))->assertOk()
            ->assertSee('Anda terdaftar sebagai')->assertSee('Relawan')->assertSee('Batalkan Pendaftaran')
            ->assertDontSee('name="type"', false)->assertDontSee('href="#daftar"', false);
    }

    public function test_finished_event_shows_closed_state(): void
    {
        $this->event->update(['start_at' => now()->subDays(3), 'end_at' => now()->subDays(2)]);
        $this->actingAs(User::factory()->create())->get(route('events.show', $this->event))->assertOk()
            ->assertSee('Kegiatan ini sudah selesai.')->assertSee('Pendaftaran tidak tersedia')->assertDontSee('name="type"', false);
    }

    public function test_full_quota_shows_full_state(): void
    {
        $this->event->update(['quota' => 1]);
        Registration::create(['event_id' => $this->event->id, 'user_id' => User::factory()->create()->id, 'type' => 'peserta', 'status' => 'confirmed']);
        $this->actingAs(User::factory()->create())->get(route('events.show', $this->event))->assertOk()
            ->assertSee('Kuota sudah penuh.')->assertDontSee('name="type"', false);
    }

    public function test_owner_sees_manage_links(): void
    {
        $this->actingAs($this->owner)->get(route('events.show', $this->event))->assertOk()
            ->assertSee('Ubah kegiatan')->assertSee('Kelola galeri')->assertSee('Tambah foto atau video');
    }
}
