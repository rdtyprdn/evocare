<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvoCareTest extends TestCase
{
    use RefreshDatabase;

    private function organizer(): User
    {
        return User::factory()->create(['role' => 'organizer']);
    }

    // ---------- Halaman publik, pencarian & filter ----------

    public function test_home_lists_published_upcoming_events_only(): void
    {
        $pub = Event::factory()->create(['title' => 'Bersih Sungai Citarum']);
        Event::factory()->create(['title' => 'Draf Rahasia', 'status' => 'draft']);
        Event::factory()->create(['title' => 'Acara Lampau', 'start_at' => now()->subDays(3), 'end_at' => now()->subDays(2)]);

        $this->get('/')->assertOk()->assertSee('Bersih Sungai Citarum')->assertDontSee('Draf Rahasia')->assertDontSee('Acara Lampau');
    }

    public function test_search_by_keyword(): void
    {
        Event::factory()->create(['title' => 'Donor Darah Massal']);
        Event::factory()->create(['title' => 'Tanam Pohon']);

        $this->get('/?q=donor')->assertSee('Donor Darah Massal')->assertDontSee('Tanam Pohon');
    }

    public function test_filter_by_category_city_and_date(): void
    {
        $health = Category::factory()->create(['name' => 'Kesehatan', 'slug' => 'kesehatan']);
        Event::factory()->create(['title' => 'Cek Gula Makassar', 'category_id' => $health->id, 'city' => 'Makassar', 'start_at' => now()->addDays(5), 'end_at' => now()->addDays(5)->addHour()]);
        Event::factory()->create(['title' => 'Cek Gula Bandung', 'category_id' => $health->id, 'city' => 'Bandung', 'start_at' => now()->addDays(20), 'end_at' => now()->addDays(20)->addHour()]);
        Event::factory()->create(['title' => 'Kelas Coding', 'city' => 'Makassar']);

        $this->get('/?category=kesehatan')->assertSee('Cek Gula Makassar')->assertSee('Cek Gula Bandung')->assertDontSee('Kelas Coding');
        $this->get('/?category=kesehatan&city=Makassar')->assertSee('Cek Gula Makassar')->assertDontSee('Cek Gula Bandung');
        $this->get('/?from='.now()->addDays(15)->toDateString().'&to='.now()->addDays(25)->toDateString())
            ->assertSee('Cek Gula Bandung')->assertDontSee('Cek Gula Makassar');
    }

    public function test_event_detail_page(): void
    {
        $event = Event::factory()->create(['title' => 'Mengajar Anak Pesisir']);
        $this->get(route('events.show', $event))->assertOk()->assertSee('Mengajar Anak Pesisir')->assertSee('Masuk untuk mendaftar');
    }

    public function test_draft_event_detail_is_hidden_from_public(): void
    {
        $event = Event::factory()->create(['status' => 'draft']);
        $this->get(route('events.show', $event))->assertNotFound();
    }

    // ---------- Pendaftaran & kuota ----------

    public function test_guest_must_login_to_register(): void
    {
        $event = Event::factory()->create();
        $this->post(route('registrations.store', $event), ['type' => 'relawan'])->assertRedirect(route('login'));
    }

    public function test_user_can_register_and_see_it_in_my_registrations(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['title' => 'Bersih Pantai']);

        $this->actingAs($user)->post(route('registrations.store', $event), ['type' => 'relawan'])->assertRedirect(route('registrations.index'));
        $this->assertDatabaseHas('registrations', ['user_id' => $user->id, 'event_id' => $event->id, 'status' => 'confirmed', 'type' => 'relawan']);
        $this->actingAs($user)->get(route('registrations.index'))->assertSee('Bersih Pantai');
    }

    public function test_registration_type_is_validated(): void
    {
        $event = Event::factory()->create();
        $this->actingAs(User::factory()->create())->post(route('registrations.store', $event), ['type' => 'hacker'])->assertSessionHasErrors('type');
    }

    public function test_user_cannot_register_twice(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($user)->post(route('registrations.store', $event), ['type' => 'relawan']);
        $this->actingAs($user)->post(route('registrations.store', $event), ['type' => 'donatur'])->assertSessionHas('error');

        $this->assertSame(1, Registration::where('event_id', $event->id)->count());
    }

    public function test_quota_cannot_be_exceeded(): void
    {
        $event = Event::factory()->create(['quota' => 2]);
        $users = User::factory()->count(3)->create();

        foreach ($users as $u) {
            $this->actingAs($u)->post(route('registrations.store', $event), ['type' => 'peserta']);
        }

        $this->assertSame(2, $event->confirmedRegistrations()->count());
        $this->assertDatabaseMissing('registrations', ['user_id' => $users[2]->id]);
    }

    public function test_cancel_frees_quota_and_allows_reregistration(): void
    {
        $event = Event::factory()->create(['quota' => 1]);
        [$a, $b] = User::factory()->count(2)->create();

        $this->actingAs($a)->post(route('registrations.store', $event), ['type' => 'relawan']);
        $reg = Registration::first();
        $this->actingAs($a)->delete(route('registrations.destroy', $reg))->assertRedirect();
        $this->assertSame('cancelled', $reg->fresh()->status);

        $this->actingAs($b)->post(route('registrations.store', $event), ['type' => 'relawan']);
        $this->assertSame(1, $event->confirmedRegistrations()->count());

        // A tidak bisa daftar lagi karena kuota penuh oleh B
        $this->actingAs($a)->post(route('registrations.store', $event), ['type' => 'relawan'])->assertSessionHas('error');
    }

    public function test_user_cannot_cancel_someone_elses_registration(): void
    {
        $event = Event::factory()->create();
        $reg = Registration::create(['user_id' => User::factory()->create()->id, 'event_id' => $event->id, 'type' => 'relawan']);

        $this->actingAs(User::factory()->create())->delete(route('registrations.destroy', $reg))->assertForbidden();
    }

    public function test_cannot_register_to_draft_or_cancelled_event(): void
    {
        $event = Event::factory()->create(['status' => 'cancelled']);
        $this->actingAs(User::factory()->create())->post(route('registrations.store', $event), ['type' => 'relawan'])->assertSessionHas('error');
        $this->assertDatabaseCount('registrations', 0);
    }

    // ---------- Otorisasi & dashboard penyelenggara ----------

    public function test_dashboard_redirects_by_role(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertRedirect(route('registrations.index'));
        $this->actingAs($this->organizer())->get('/dashboard')->assertRedirect(route('organizer.events.index'));
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/dashboard')->assertRedirect(route('admin.categories.index'));
    }

    public function test_regular_user_cannot_access_organizer_dashboard(): void
    {
        $this->actingAs(User::factory()->create())->get(route('organizer.events.index'))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('admin.categories.index'))->assertForbidden();
    }

    public function test_organizer_can_create_event(): void
    {
        $org = $this->organizer();
        $cat = Category::factory()->create();

        $this->actingAs($org)->post(route('organizer.events.store'), [
            'title' => 'Bakti Sosial Ramadan', 'category_id' => $cat->id, 'description' => 'Berbagi takjil.',
            'city' => 'Makassar', 'location' => 'Masjid Raya', 'start_at' => now()->addDays(3)->format('Y-m-d H:i'),
            'end_at' => now()->addDays(3)->addHours(3)->format('Y-m-d H:i'), 'quota' => 25, 'status' => 'published',
        ])->assertRedirect(route('organizer.events.index'));

        $event = Event::where('title', 'Bakti Sosial Ramadan')->firstOrFail();
        $this->assertSame($org->id, $event->user_id);
        $this->assertNotNull($event->organization_id);
    }

    public function test_event_validation(): void
    {
        $this->actingAs($this->organizer())->post(route('organizer.events.store'), ['title' => ''])
            ->assertSessionHasErrors(['title', 'category_id', 'description', 'city', 'start_at', 'end_at', 'quota']);
    }

    public function test_organizer_can_update_and_delete_own_event(): void
    {
        $org = $this->organizer();
        $event = Event::factory()->create(['user_id' => $org->id]);
        $data = $event->only(['title', 'category_id', 'description', 'city', 'location', 'status']) + [
            'start_at' => $event->start_at->format('Y-m-d H:i'), 'end_at' => $event->end_at->format('Y-m-d H:i'), 'quota' => 99,
        ];

        $this->actingAs($org)->put(route('organizer.events.update', $event), array_merge($data, ['title' => 'Judul Baru']))->assertRedirect();
        $this->assertSame('Judul Baru', $event->fresh()->title);

        $this->actingAs($org)->delete(route('organizer.events.destroy', $event))->assertRedirect();
        $this->assertModelMissing($event);
    }

    public function test_organizer_cannot_edit_others_event(): void
    {
        $event = Event::factory()->create();
        $other = $this->organizer();

        $this->actingAs($other)->get(route('organizer.events.edit', $event))->assertForbidden();
        $this->actingAs($other)->delete(route('organizer.events.destroy', $event))->assertForbidden();
        $this->actingAs($other)->get(route('organizer.events.registrants', $event))->assertForbidden();
    }

    public function test_organizer_can_view_registrants(): void
    {
        $org = $this->organizer();
        $event = Event::factory()->create(['user_id' => $org->id]);
        $u = User::factory()->create(['name' => 'Relawan Hebat']);
        Registration::create(['user_id' => $u->id, 'event_id' => $event->id, 'type' => 'relawan']);

        $this->actingAs($org)->get(route('organizer.events.registrants', $event))->assertOk()->assertSee('Relawan Hebat');
    }

    public function test_quota_cannot_be_lowered_below_confirmed_registrations(): void
    {
        $org = $this->organizer();
        $event = Event::factory()->create(['user_id' => $org->id, 'quota' => 5]);
        foreach (User::factory()->count(3)->create() as $u) {
            Registration::create(['user_id' => $u->id, 'event_id' => $event->id, 'type' => 'relawan']);
        }
        $data = $event->only(['title', 'category_id', 'description', 'city', 'location', 'status']) + [
            'start_at' => $event->start_at->format('Y-m-d H:i'), 'end_at' => $event->end_at->format('Y-m-d H:i'), 'quota' => 2,
        ];

        $this->actingAs($org)->put(route('organizer.events.update', $event), $data)->assertSessionHasErrors('quota');
    }

    public function test_admin_can_manage_categories(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('admin.categories.store'), ['name' => 'Kemanusiaan'])->assertRedirect();
        $this->assertDatabaseHas('categories', ['slug' => 'kemanusiaan']);
    }

    public function test_seeder_runs(): void
    {
        $this->seed();
        $this->assertDatabaseHas('users', ['email' => 'organizer@evocare.test', 'role' => 'organizer']);
        $this->assertSame(13, Event::count());
        $this->get('/')->assertOk()->assertSee('Bersih Pantai Losari');
    }
}
