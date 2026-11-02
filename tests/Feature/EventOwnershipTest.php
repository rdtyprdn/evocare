<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Penyelenggara B tidak boleh mengelola kegiatan milik penyelenggara A. */
class EventOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private User $ownerA;

    private User $orgB;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->ownerA = User::factory()->organizer()->create(['name' => 'BEM']);
        $this->orgB = User::factory()->organizer()->create(['name' => 'HIMAIF']);
        $this->event = Event::factory()->create(['user_id' => $this->ownerA->id, 'organization_id' => $this->ownerA->organization_id]);
    }

    private function payload(): array
    {
        return $this->event->only(['title', 'category_id', 'description', 'city', 'location', 'status']) + [
            'start_at' => $this->event->start_at->format('Y-m-d H:i'),
            'end_at' => $this->event->end_at->format('Y-m-d H:i'),
            'quota' => $this->event->quota,
        ];
    }

    public function test_other_organizer_is_forbidden_everywhere(): void
    {
        $e = $this->event;
        $photo = $e->photos()->create(['path' => 'event-photos/a.jpg', 'sort_order' => 1]);
        $as = $this->actingAs($this->orgB);

        $as->get(route('organizer.events.edit', $e))->assertForbidden();
        $as->put(route('organizer.events.update', $e), ['title' => 'Dibajak'] + $this->payload())->assertForbidden();
        $as->delete(route('organizer.events.destroy', $e))->assertForbidden();
        $as->get(route('organizer.events.registrants', $e))->assertForbidden();
        $as->get(route('organizer.events.registrants.export', $e))->assertForbidden();
        $as->get(route('organizer.events.report', $e))->assertForbidden();
        $as->put(route('organizer.events.report.update', $e), ['report_summary' => 'x'])->assertForbidden();
        $as->post(route('organizer.events.photos.store', $e), ['photos' => [UploadedFile::fake()->image('a.jpg')]])->assertForbidden();
        $as->delete(route('organizer.events.photos.destroy', [$e, $photo]))->assertForbidden();

        $this->assertNotSame('Dibajak', $e->fresh()->title);
        $this->assertModelExists($e);
        $this->assertModelExists($photo);
    }

    public function test_other_organizer_does_not_see_event_in_dashboard_or_manage_links(): void
    {
        $this->actingAs($this->orgB)->get(route('organizer.events.index'))->assertOk()->assertDontSee($this->event->title);
        $this->actingAs($this->orgB)->get(route('events.show', $this->event))->assertOk()
            ->assertDontSee('Kelola galeri')->assertDontSee(route('organizer.events.edit', $this->event), false);
    }

    public function test_creator_moved_to_another_org_cannot_manage_old_org_event(): void
    {
        $this->ownerA->update(['organization_id' => $this->orgB->organization_id]);
        $this->actingAs($this->ownerA->fresh())->get(route('organizer.events.edit', $this->event))->assertForbidden();
    }

    public function test_owner_can_still_manage(): void
    {
        $e = $this->event;
        $as = $this->actingAs($this->ownerA);
        $as->get(route('organizer.events.edit', $e))->assertOk();
        $as->get(route('organizer.events.registrants', $e))->assertOk();
        $as->get(route('organizer.events.registrants.export', $e))->assertOk();
        $as->get(route('organizer.events.report', $e))->assertOk();
        $as->post(route('organizer.events.photos.store', $e), ['photos' => [UploadedFile::fake()->image('a.jpg')]])->assertRedirect();
        $as->put(route('organizer.events.update', $e), ['title' => 'Judul Baru'] + $this->payload())->assertRedirect();
        $this->assertSame('Judul Baru', $e->fresh()->title);
        $as->delete(route('organizer.events.destroy', $e))->assertRedirect();
        $this->assertModelMissing($e);
    }
}
