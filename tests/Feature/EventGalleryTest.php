<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventGalleryTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->owner = User::factory()->organizer()->create();
        $this->event = Event::factory()->create(['user_id' => $this->owner->id, 'status' => 'published']);
    }

    private function upload(User $user, array $files, array $extra = [])
    {
        return $this->actingAs($user)->post(route('organizer.events.photos.store', $this->event), ['photos' => $files] + $extra);
    }

    public function test_owner_can_upload_multiple_photos_with_caption(): void
    {
        $this->upload($this->owner, [
            UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png'), UploadedFile::fake()->image('c.webp'),
        ], ['caption' => 'Bersih pantai'])->assertSessionHasNoErrors()->assertRedirect();

        $photos = $this->event->photos()->get();
        $this->assertCount(3, $photos);
        $this->assertSame([1, 2, 3], $photos->pluck('sort_order')->all());
        $this->assertSame('Bersih pantai', $photos->first()->caption);
        foreach ($photos as $p) {
            Storage::disk('public')->assertExists($p->path);
        }
    }

    public function test_upload_validates_type_and_size(): void
    {
        $this->upload($this->owner, [UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')])->assertSessionHasErrors('photos.0');
        $this->upload($this->owner, [UploadedFile::fake()->image('besar.jpg')->size(10241)])->assertSessionHasErrors('photos.0');
        $this->upload($this->owner, [UploadedFile::fake()->image('pas.jpg')->size(10240)])->assertSessionHasNoErrors();
        $this->assertSame(1, EventPhoto::count());
        EventPhoto::query()->delete();
        $this->upload($this->owner, [])->assertSessionHasErrors('photos');
        $this->assertSame(0, EventPhoto::count());
    }

    public function test_other_organizer_cannot_upload_or_delete(): void
    {
        $other = User::factory()->organizer()->create();
        $this->upload($other, [UploadedFile::fake()->image('a.jpg')])->assertForbidden();

        $photo = $this->event->photos()->create(['path' => 'event-photos/x.jpg', 'sort_order' => 1]);
        $this->actingAs($other)->delete(route('organizer.events.photos.destroy', [$this->event, $photo]))->assertForbidden();
        $this->assertModelExists($photo);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->post(route('organizer.events.photos.store', $this->event), ['photos' => [UploadedFile::fake()->image('a.jpg')]])
            ->assertRedirect(route('login'));
    }

    public function test_owner_can_delete_photo_and_file_is_removed(): void
    {
        $this->upload($this->owner, [UploadedFile::fake()->image('a.jpg')]);
        $photo = EventPhoto::firstOrFail();

        $this->actingAs($this->owner)->delete(route('organizer.events.photos.destroy', [$this->event, $photo]))->assertRedirect();

        $this->assertModelMissing($photo);
        Storage::disk('public')->assertMissing($photo->path);
    }

    public function test_photo_of_another_event_cannot_be_deleted_via_this_event(): void
    {
        $otherEvent = Event::factory()->create(['user_id' => $this->owner->id]);
        $photo = $otherEvent->photos()->create(['path' => 'event-photos/y.jpg', 'sort_order' => 1]);

        $this->actingAs($this->owner)->delete(route('organizer.events.photos.destroy', [$this->event, $photo]))->assertNotFound();
        $this->assertModelExists($photo);
    }

    public function test_public_page_shows_gallery_with_photos(): void
    {
        $this->event->photos()->create(['path' => 'event-photos/1/foto.jpg', 'caption' => 'Penanaman pohon', 'sort_order' => 1]);

        $this->get(route('events.show', $this->event))->assertOk()
            ->assertSee('Galeri Dokumentasi')->assertSee('storage/event-photos/1/foto.jpg')
            ->assertSee('Penanaman pohon')->assertSee('overflow-x-auto', false)->assertDontSee('Kelola galeri');
    }

    public function test_empty_state_and_upload_link_for_owner(): void
    {
        $this->get(route('events.show', $this->event))->assertOk()
            ->assertSee('Belum ada foto atau video dokumentasi untuk kegiatan ini.')->assertDontSee('Tambah foto atau video');

        $this->actingAs($this->owner)->get(route('events.show', $this->event))
            ->assertSee('Tambah foto atau video')->assertSee(route('organizer.events.edit', $this->event).'#galeri', false);
    }

    public function test_edit_page_shows_gallery_manager_with_custom_modal(): void
    {
        $this->event->photos()->create(['path' => 'event-photos/1/foto.jpg', 'sort_order' => 1]);

        $this->actingAs($this->owner)->get(route('organizer.events.edit', $this->event))->assertOk()
            ->assertSee('Galeri Dokumentasi')->assertSee('name="photos[]"', false)
            ->assertSee('Hapus Foto Dokumentasi?')->assertDontSee('confirm(', false);
    }

    public function test_photos_are_deleted_when_event_is_deleted(): void
    {
        $this->event->photos()->create(['path' => 'event-photos/1/foto.jpg', 'sort_order' => 1]);
        $this->event->delete();
        $this->assertSame(0, EventPhoto::count());
    }
}
