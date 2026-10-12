<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    private function orgPayload(array $extra = []): array
    {
        return array_merge(['name' => 'HIMAIF Udayana', 'type' => 'institusi', 'city' => 'Denpasar',
            'description' => 'Himpunan Mahasiswa Informatika Universitas Udayana.', 'contact' => 'himaif@contoh.test'], $extra);
    }

    private function eventPayload(array $extra = []): array
    {
        return array_merge(['title' => 'Informatika Mengajar', 'category_id' => Category::factory()->create()->id, 'description' => 'Mengajar.',
            'city' => 'Denpasar', 'location' => 'SD 1', 'start_at' => now()->addDays(5)->format('Y-m-d H:i'),
            'end_at' => now()->addDays(5)->addHours(2)->format('Y-m-d H:i'), 'quota' => 20, 'status' => 'published'], $extra);
    }

    public function test_user_can_register_organization_with_logo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('organizations.create'))->assertOk()->assertSee('Daftar sebagai Penyelenggara');
        $this->actingAs($user)->post(route('organizations.store'), $this->orgPayload(['logo' => UploadedFile::fake()->image('logo.png')]))
            ->assertRedirect(route('organizations.create'));

        $org = Organization::firstOrFail();
        $this->assertSame('pending', $org->status);
        $this->assertSame($user->id, $org->owner_id);
        $this->assertSame($org->id, $user->fresh()->organization_id);
        $this->assertSame('user', $user->fresh()->role);
        Storage::disk('public')->assertExists($org->logo_path);

        $this->actingAs($user->fresh())->get('/')->assertSee('menunggu verifikasi');
    }

    public function test_registration_validation(): void
    {
        $this->actingAs(User::factory()->create())->post(route('organizations.store'), ['type' => 'partai'])
            ->assertSessionHasErrors(['name', 'type', 'city', 'description', 'contact']);
        $this->actingAs(User::factory()->create())->post(route('organizations.store'), $this->orgPayload(['logo' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')]))
            ->assertSessionHasErrors('logo');
    }

    public function test_user_cannot_register_second_organization_while_pending(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('organizations.store'), $this->orgPayload());
        $this->actingAs($user->fresh())->post(route('organizations.store'), $this->orgPayload(['name' => 'Lain']))->assertSessionHas('error');
        $this->assertSame(1, Organization::count());
    }

    public function test_pending_user_cannot_create_events(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('organizations.store'), $this->orgPayload());

        $this->actingAs($user->fresh())->get(route('organizer.events.create'))->assertRedirect(route('organizations.create'));
        $this->actingAs($user->fresh())->post(route('organizer.events.store'), $this->eventPayload())->assertForbidden();
        $this->assertSame(0, Event::count());
    }

    public function test_admin_verifies_and_owner_becomes_organizer_and_can_create_event_with_own_org(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('organizations.store'), $this->orgPayload());
        $org = Organization::firstOrFail();

        $this->actingAs($admin)->get(route('admin.organizations.index'))->assertOk()->assertSee('HIMAIF Udayana');
        $this->actingAs($admin)->post(route('admin.organizations.verify', $org))->assertRedirect();

        $this->assertSame('verified', $org->fresh()->status);
        $this->assertSame('organizer', $user->fresh()->role);

        Organization::factory()->create(['name' => 'Organisasi Lain']);
        $this->actingAs($user->fresh())->post(route('organizer.events.store'), $this->eventPayload(['organization_id' => 999]))->assertRedirect();
        $event = Event::firstOrFail();
        $this->assertSame($org->id, $event->organization_id);

        $this->get(route('events.show', $event))->assertSee('HIMAIF Udayana')->assertSee('Terverifikasi');
    }

    public function test_admin_rejects_with_reason_and_user_can_resubmit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('organizations.store'), $this->orgPayload());
        $org = Organization::firstOrFail();

        $this->actingAs($admin)->post(route('admin.organizations.reject', $org), [])->assertSessionHasErrors('admin_note');
        $this->actingAs($admin)->post(route('admin.organizations.reject', $org), ['admin_note' => 'Lampirkan kontak resmi.']);
        $this->assertSame('rejected', $org->fresh()->status);
        $this->assertSame('user', $user->fresh()->role);
        $this->actingAs($user->fresh())->get('/')->assertSee('Lampirkan kontak resmi.');

        $this->actingAs($user->fresh())->post(route('organizations.store'), $this->orgPayload(['contact' => '08123456789']));
        $this->assertSame('pending', $org->fresh()->status);
        $this->assertSame('08123456789', $org->fresh()->contact);
        $this->assertSame(1, Organization::count());
    }

    public function test_non_admin_cannot_verify(): void
    {
        $org = Organization::factory()->pending()->create();
        $this->actingAs(User::factory()->create())->post(route('admin.organizations.verify', $org))->assertForbidden();
        $this->actingAs(User::factory()->organizer()->create())->post(route('admin.organizations.verify', $org))->assertForbidden();
        $this->assertSame('pending', $org->fresh()->status);
    }

    public function test_detached_user_keeps_events_which_attach_to_own_org_after_verification(): void
    {
        $demoOrg = Organization::factory()->create(['name' => 'Yayasan Demo']);
        $user = User::factory()->create(['role' => 'organizer', 'organization_id' => $demoOrg->id, 'email' => 'nyata@contoh.test']);
        $demoOrg->update(['owner_id' => $user->id]);
        $event = Event::factory()->create(['user_id' => $user->id, 'organization_id' => $demoOrg->id, 'title' => 'Informatika Mengajar 2027']);

        $this->artisan('evocare:detach-org', ['email' => ['nyata@contoh.test']])->assertSuccessful();

        $this->assertNull($user->fresh()->organization_id);
        $this->assertNull($event->fresh()->organization_id);
        $this->assertNull($demoOrg->fresh()->owner_id);
        $this->get('/')->assertSee('Informatika Mengajar 2027')->assertSee('Organisasi belum terverifikasi')->assertDontSee('Yayasan Demo');

        // Masih bisa mengelola kegiatannya sendiri, tapi tidak bisa membuat kegiatan baru.
        $this->actingAs($user->fresh())->get(route('organizer.events.edit', $event))->assertOk();
        $this->actingAs($user->fresh())->get(route('organizer.events.create'))->assertRedirect(route('organizations.create'));

        $this->actingAs($user->fresh())->post(route('organizations.store'), $this->orgPayload());
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post(route('admin.organizations.verify', Organization::where('name', 'HIMAIF Udayana')->first()));
        $this->assertSame('HIMAIF Udayana', $event->fresh()->organization->name);
    }

    public function test_other_user_cannot_edit_event_policy(): void
    {
        $event = Event::factory()->create();
        $this->actingAs(User::factory()->organizer()->create())->put(route('organizer.events.update', $event), [])->assertForbidden();
    }
}
