<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventCreateGalleryTest extends TestCase
{
    use RefreshDatabase;

    private User $org;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->org = User::factory()->organizer()->create();
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'title' => 'Aksi Galeri', 'category_id' => Category::factory()->create()->id, 'description' => 'Deskripsi.',
            'city' => 'Denpasar', 'location' => 'Pantai Sanur', 'start_at' => now()->addDays(3)->format('Y-m-d H:i'),
            'end_at' => now()->addDays(3)->addHours(2)->format('Y-m-d H:i'), 'quota' => 10, 'status' => 'published',
        ], $extra);
    }

    public function test_create_form_shows_gallery_card(): void
    {
        $this->actingAs($this->org)->get(route('organizer.events.create'))->assertOk()
            ->assertSee('Galeri Dokumentasi')->assertSee('Link YouTube')->assertSee('Total unggahan sekali simpan maksimal 120 MB')
            ->assertSee('name="photos[]"', false)->assertSee('name="videos[]"', false)->assertSee('name="youtube_links[]"', false);
    }

    public function test_create_event_with_photos_video_and_youtube(): void
    {
        $this->actingAs($this->org)->post(route('organizer.events.store'), $this->payload([
            'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')],
            'videos' => [UploadedFile::fake()->create('c.mp4', 2000, 'video/mp4')],
            'youtube_links' => ['https://youtu.be/dQw4w9WgXcQ', '', 'https://www.youtube.com/watch?v=9bZkp7q19f0'],
            'photo_caption' => 'Suasana acara',
        ]))->assertSessionHasNoErrors()->assertRedirect(route('organizer.events.index'));

        $event = Event::firstOrFail();
        $photos = $event->photos()->orderBy('sort_order')->get();
        $this->assertSame(['image', 'image', 'video', 'youtube', 'youtube'], $photos->pluck('type')->all());
        $this->assertSame([1, 2, 3, 4, 5], $photos->pluck('sort_order')->map(fn ($v) => (int) $v)->all());
        $this->assertSame('Suasana acara', $photos[0]->caption);
        $this->assertSame('dQw4w9WgXcQ', $photos[3]->youtube_id);
        Storage::disk('public')->assertExists($photos[0]->path);
        Storage::disk('public')->assertExists($photos[2]->path);
        $this->assertStringStartsWith('event-videos/'.$event->id, $photos[2]->path);
    }

    public function test_invalid_youtube_rejected_and_input_kept(): void
    {
        $this->actingAs($this->org)->from(route('organizer.events.create'))->post(route('organizer.events.store'), $this->payload([
            'youtube_links' => ['https://vimeo.com/123'],
        ]))->assertRedirect(route('organizer.events.create'))->assertSessionHasErrors('youtube_links.0')->assertSessionHasInput('title', 'Aksi Galeri');
        $this->assertSame(0, Event::count());
    }

    public function test_oversized_media_rejected(): void
    {
        $this->actingAs($this->org)->post(route('organizer.events.store'), $this->payload([
            'photos' => [UploadedFile::fake()->image('besar.jpg')->size(10241)],
        ]))->assertSessionHasErrors(['photos.0' => 'Ukuran setiap foto maksimal 10 MB.']);
        $this->actingAs($this->org)->post(route('organizer.events.store'), $this->payload([
            'videos' => [UploadedFile::fake()->create('besar.mp4', 30721, 'video/mp4')],
        ]))->assertSessionHasErrors(['videos.0' => 'Ukuran setiap video maksimal 30 MB.']);
        $this->actingAs($this->org)->post(route('organizer.events.store'), $this->payload([
            'photos' => array_map(fn ($i) => UploadedFile::fake()->image("p$i.jpg"), range(1, 11)),
        ]))->assertSessionHasErrors('photos');
        $this->assertSame(0, Event::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_create_without_media_still_works(): void
    {
        $this->actingAs($this->org)->post(route('organizer.events.store'), $this->payload())->assertSessionHasNoErrors()->assertRedirect(route('organizer.events.index'));
        $this->assertSame(0, Event::firstOrFail()->photos()->count());
    }

    private function ownedEvent(): Event
    {
        return Event::factory()->create(['user_id' => $this->org->id, 'organization_id' => $this->org->organization_id, 'status' => 'published']);
    }

    private function updatePayload(Event $event, array $extra = []): array
    {
        return array_merge($event->only(['title', 'category_id', 'description', 'city', 'location', 'status']) + [
            'start_at' => $event->start_at->format('Y-m-d H:i'), 'end_at' => $event->end_at->format('Y-m-d H:i'), 'quota' => $event->quota,
        ], $extra);
    }

    public function test_edit_form_has_single_action_bar_and_queued_gallery(): void
    {
        $event = $this->ownedEvent();
        $event->photos()->create(['path' => 'event-photos/1/lama.jpg', 'caption' => 'Foto lama', 'sort_order' => 1]);
        $html = $this->actingAs($this->org)->get(route('organizer.events.edit', $event))->assertOk()
            ->assertSee('Galeri Dokumentasi')->assertSee('Tersimpan di galeri')->assertSee('Foto lama')
            ->assertSee('name="photos[]"', false)->assertSee('name="youtube_links[]"', false)
            ->assertDontSee('Unggah Foto')->assertDontSee('Tambahkan Video')->assertDontSee('name="youtube_url"', false)
            ->getContent();
        $this->assertSame(1, substr_count($html, '>Simpan</button>') + substr_count($html, ">\n    Simpan\n</button>"), 'Harus ada satu tombol Simpan');
        $this->assertLessThan(strrpos($html, 'Simpan'), strpos($html, 'Tersimpan di galeri'), 'Tombol Simpan harus di bawah galeri');
        $this->assertSame(1, substr_count($html, 'action="'.route('organizer.events.update', $event).'"'));
    }

    public function test_update_saves_fields_and_media_in_one_submit(): void
    {
        $event = $this->ownedEvent();
        $event->photos()->create(['path' => 'event-photos/1/lama.jpg', 'sort_order' => 1]);
        $this->actingAs($this->org)->put(route('organizer.events.update', $event), $this->updatePayload($event, [
            'title' => 'Judul Baru',
            'photos' => [UploadedFile::fake()->image('a.jpg')],
            'videos' => [UploadedFile::fake()->create('b.webm', 1000, 'video/webm')],
            'youtube_links' => ['https://youtu.be/dQw4w9WgXcQ'],
            'video_caption' => 'Cuplikan',
        ]))->assertSessionHasNoErrors()->assertRedirect(route('organizer.events.index'));

        $event->refresh();
        $this->assertSame('Judul Baru', $event->title);
        $items = $event->photos()->orderBy('sort_order')->get();
        $this->assertSame(['image', 'image', 'video', 'youtube'], $items->pluck('type')->all());
        $this->assertSame([1, 2, 3, 4], $items->pluck('sort_order')->map(fn ($v) => (int) $v)->all());
        $this->assertSame('Cuplikan', $items[2]->caption);
        Storage::disk('public')->assertExists($items[1]->path);
    }

    public function test_update_without_media_still_works(): void
    {
        $event = $this->ownedEvent();
        $this->actingAs($this->org)->put(route('organizer.events.update', $event), $this->updatePayload($event, ['title' => 'Tanpa Media']))
            ->assertSessionHasNoErrors()->assertRedirect(route('organizer.events.index'));
        $this->assertSame('Tanpa Media', $event->fresh()->title);
        $this->assertSame(0, $event->photos()->count());
    }

    public function test_update_limit_counts_existing_items(): void
    {
        $event = $this->ownedEvent();
        foreach (range(1, 29) as $i) {
            $event->photos()->create(['type' => 'youtube', 'youtube_id' => 'dQw4w9WgXcQ', 'sort_order' => $i]);
        }
        $this->actingAs($this->org)->put(route('organizer.events.update', $event), $this->updatePayload($event, [
            'title' => 'Tidak Tersimpan',
            'youtube_links' => ['https://youtu.be/dQw4w9WgXcQ', 'https://youtu.be/9bZkp7q19f0'],
        ]))->assertSessionHasErrors(['gallery' => 'Galeri maksimal berisi 30 item. Sudah ada 29 item, sisa 1 slot.']);
        $this->assertSame(29, $event->photos()->count());
        $this->assertNotSame('Tidak Tersimpan', $event->fresh()->title);

        $this->actingAs($this->org)->put(route('organizer.events.update', $event), $this->updatePayload($event, [
            'youtube_links' => ['https://youtu.be/9bZkp7q19f0'],
        ]))->assertSessionHasNoErrors();
        $this->assertSame(30, $event->photos()->count());
    }

    public function test_delete_existing_item_still_works(): void
    {
        $event = $this->ownedEvent();
        $photo = $event->photos()->create(['path' => 'event-photos/1/lama.jpg', 'sort_order' => 1]);
        $this->actingAs($this->org)->delete(route('organizer.events.photos.destroy', [$event, $photo]))->assertRedirect();
        $this->assertDatabaseMissing('event_photos', ['id' => $photo->id]);
    }
}
