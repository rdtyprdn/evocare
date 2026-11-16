<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventPhoto;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** File sampul, galeri, dan avatar harus ikut terhapus saat datanya dihapus (skenario 9). */
class StoredFileCleanupTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
        $this->owner = User::factory()->organizer()->create();
    }

    /** Kegiatan dengan sampul, satu foto, satu video, satu YouTube, dan laporan berbasis file. */
    private function eventWithMedia(?User $owner = null): array
    {
        $event = Event::factory()->create(['user_id' => ($owner ?? $this->owner)->id]);
        $cover = "events/sampul-{$event->id}.jpg";
        $photo = "event-photos/{$event->id}/foto.jpg";
        $video = "event-videos/{$event->id}/video.mp4";
        foreach ([$cover, $photo, $video] as $path) {
            Storage::disk('public')->put($path, 'isi');
        }
        $event->update(['image_path' => $cover]);
        $event->photos()->create(['type' => 'image', 'path' => $photo]);
        $event->photos()->create(['type' => 'video', 'path' => $video]);
        $event->photos()->create(['type' => 'youtube', 'youtube_id' => 'dQw4w9WgXcQ']);
        Storage::disk('local')->put("reports/event-{$event->id}.json", '{}');

        return [$event, [$cover, $photo, $video]];
    }

    public function test_deleting_event_removes_cover_and_gallery_files(): void
    {
        [$event, $files] = $this->eventWithMedia();
        [$other, $otherFiles] = $this->eventWithMedia();

        $this->actingAs($this->owner)->delete(route('organizer.events.destroy', $event))->assertRedirect();

        $this->assertModelMissing($event);
        $this->assertSame(0, EventPhoto::where('event_id', $event->id)->count());
        foreach ($files as $path) {
            Storage::disk('public')->assertMissing($path);
        }
        Storage::disk('public')->assertMissing("event-photos/{$event->id}");
        Storage::disk('local')->assertMissing("reports/event-{$event->id}.json");

        foreach ($otherFiles as $path) {
            Storage::disk('public')->assertExists($path);
        }
        Storage::disk('local')->assertExists("reports/event-{$other->id}.json");
    }

    public function test_admin_deleting_event_removes_files(): void
    {
        [$event, $files] = $this->eventWithMedia();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->delete(route('organizer.events.destroy', $event))->assertRedirect();

        foreach ($files as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_deleting_account_removes_avatar_and_files_of_own_events(): void
    {
        [$event, $files] = $this->eventWithMedia();
        [, $files2] = $this->eventWithMedia();
        $stranger = User::factory()->organizer()->create();
        [$foreign, $foreignFiles] = $this->eventWithMedia($stranger);
        Storage::disk('public')->put('avatars/saya.jpg', 'isi');
        $this->owner->update(['avatar_path' => 'avatars/saya.jpg']);

        $this->actingAs($this->owner)
            ->delete('/profile', ['password' => 'password'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertNull($this->owner->fresh());
        $this->assertModelMissing($event);
        foreach (array_merge($files, $files2, ['avatars/saya.jpg']) as $path) {
            Storage::disk('public')->assertMissing($path);
        }
        Storage::disk('local')->assertMissing("reports/event-{$event->id}.json");

        $this->assertModelExists($foreign);
        foreach ($foreignFiles as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_wrong_password_keeps_account_and_files(): void
    {
        [$event, $files] = $this->eventWithMedia();

        $this->actingAs($this->owner)
            ->delete('/profile', ['password' => 'salah'])
            ->assertSessionHasErrorsIn('userDeletion', 'password');

        $this->assertModelExists($event);
        foreach ($files as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_deleting_account_keeps_organization_logo_still_in_use(): void
    {
        $org = Organization::factory()->create(['owner_id' => $this->owner->id, 'logo_path' => 'organizations/logo.png']);
        Storage::disk('public')->put('organizations/logo.png', 'isi');

        $this->actingAs($this->owner)->delete('/profile', ['password' => 'password'])->assertRedirect('/');

        $this->assertNull($org->fresh()->owner_id);
        Storage::disk('public')->assertExists('organizations/logo.png');
    }

    public function test_prune_command_is_dry_run_by_default_and_deletes_with_force(): void
    {
        [$event, $files] = $this->eventWithMedia();
        $orphans = ['events/lama.jpg', 'event-photos/999/sisa.jpg', 'event-videos/999/sisa.mp4', 'avatars/lama.jpg', 'organizations/lama.png'];
        foreach ($orphans as $path) {
            Storage::disk('public')->put($path, 'isi');
        }
        Storage::disk('local')->put('reports/event-999.json', '{}');

        $this->artisan('evocare:prune-files')
            ->expectsOutputToContain('Mode simulasi')
            ->assertSuccessful();
        foreach ($orphans as $path) {
            Storage::disk('public')->assertExists($path);
        }
        Storage::disk('local')->assertExists('reports/event-999.json');

        $this->artisan('evocare:prune-files', ['--force' => true])
            ->expectsOutputToContain('sudah dihapus')
            ->assertSuccessful();
        foreach ($orphans as $path) {
            Storage::disk('public')->assertMissing($path);
        }
        Storage::disk('public')->assertMissing('event-photos/999');
        Storage::disk('local')->assertMissing('reports/event-999.json');

        foreach ($files as $path) {
            Storage::disk('public')->assertExists($path);
        }
        Storage::disk('local')->assertExists("reports/event-{$event->id}.json");

        $this->artisan('evocare:prune-files')->expectsOutputToContain('Tidak ada file tidak terpakai')->assertSuccessful();
    }
}
