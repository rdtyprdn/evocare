<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventImageTest extends TestCase
{
    use RefreshDatabase;

    private User $org;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->org = User::factory()->create(['role' => 'organizer']);
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'title' => 'Aksi Bergambar', 'category_id' => Category::factory()->create()->id, 'description' => 'Deskripsi.',
            'city' => 'Denpasar', 'location' => 'Pantai Sanur', 'start_at' => now()->addDays(3)->format('Y-m-d H:i'),
            'end_at' => now()->addDays(3)->addHours(2)->format('Y-m-d H:i'), 'quota' => 10, 'status' => 'published',
        ], $extra);
    }

    private function updatePayload(Event $event, array $extra = []): array
    {
        return array_merge($event->only(['title', 'category_id', 'description', 'city', 'location', 'status', 'image_url']) + [
            'start_at' => $event->start_at->format('Y-m-d H:i'), 'end_at' => $event->end_at->format('Y-m-d H:i'), 'quota' => $event->quota,
        ], $extra);
    }

    public function test_form_is_multipart_with_image_field(): void
    {
        $this->actingAs($this->org)->get(route('organizer.events.create'))
            ->assertOk()->assertSee('enctype="multipart/form-data"', false)->assertSee('Gambar Kegiatan')->assertSee('name="image"', false);
    }

    public function test_organizer_can_upload_image_on_create(): void
    {
        $this->actingAs($this->org)->post(route('organizer.events.store'), $this->payload(['image' => UploadedFile::fake()->image('a.jpg', 800, 400)]))
            ->assertSessionHasNoErrors()->assertRedirect();

        $event = Event::firstOrFail();
        $this->assertStringStartsWith('events/', $event->image_path);
        Storage::disk('public')->assertExists($event->image_path);
        $this->get(route('events.show', $event))->assertSee('storage/'.$event->image_path);
    }

    public function test_uploaded_image_takes_priority_over_url(): void
    {
        $this->actingAs($this->org)->post(route('organizer.events.store'), $this->payload([
            'image_url' => 'https://contoh.test/gambar.jpg', 'image' => UploadedFile::fake()->image('a.png'),
        ]));
        $event = Event::firstOrFail();
        $this->assertSame(asset('storage/'.$event->image_path), $event->imageSrc());
        $this->get('/')->assertSee('storage/'.$event->image_path)->assertDontSee('https://contoh.test/gambar.jpg');
    }

    public function test_replace_deletes_old_file(): void
    {
        $this->actingAs($this->org)->post(route('organizer.events.store'), $this->payload(['image' => UploadedFile::fake()->image('a.jpg')]));
        $event = Event::firstOrFail();
        $old = $event->image_path;

        $this->actingAs($this->org)->put(route('organizer.events.update', $event), $this->updatePayload($event, ['image' => UploadedFile::fake()->image('b.webp')]))
            ->assertSessionHasNoErrors();
        $new = $event->fresh()->image_path;

        $this->assertNotSame($old, $new);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($new);
    }

    public function test_remove_image_deletes_file_and_falls_back_to_url(): void
    {
        $this->actingAs($this->org)->post(route('organizer.events.store'), $this->payload([
            'image_url' => 'https://contoh.test/cadangan.jpg', 'image' => UploadedFile::fake()->image('a.jpg'),
        ]));
        $event = Event::firstOrFail();
        $path = $event->image_path;

        $this->actingAs($this->org)->put(route('organizer.events.update', $event), $this->updatePayload($event, ['remove_image' => '1']));
        $this->assertNull($event->fresh()->image_path);
        Storage::disk('public')->assertMissing($path);
        $this->assertSame('https://contoh.test/cadangan.jpg', $event->fresh()->imageSrc());
    }

    public function test_deleting_event_deletes_image(): void
    {
        $this->actingAs($this->org)->post(route('organizer.events.store'), $this->payload(['image' => UploadedFile::fake()->image('a.jpg')]));
        $event = Event::firstOrFail();
        $path = $event->image_path;

        $this->actingAs($this->org)->delete(route('organizer.events.destroy', $event));
        Storage::disk('public')->assertMissing($path);
    }

    public function test_image_validation(): void
    {
        $this->actingAs($this->org)->post(route('organizer.events.store'), $this->payload(['image' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')]))
            ->assertSessionHasErrors('image');
        $this->actingAs($this->org)->post(route('organizer.events.store'), $this->payload(['image' => UploadedFile::fake()->image('big.jpg')->size(3000)]))
            ->assertSessionHasErrors('image');
        $this->assertSame(0, Event::count());
    }

    public function test_post_too_large_on_event_form_redirects_back_with_message(): void
    {
        $this->actingAs($this->org)->call('POST', route('organizer.events.store'), [], [], [], [
            'CONTENT_LENGTH' => 19 * 1024 * 1024, 'HTTP_REFERER' => route('organizer.events.create'),
        ])->assertRedirect(route('organizer.events.create').'?upload_error=too_large');

        $this->actingAs($this->org)->get(route('organizer.events.create').'?upload_error=too_large')
            ->assertSee('Ukuran file terlalu besar. Maksimal 2 MB.');
    }

    public function test_placeholder_shown_without_image(): void
    {
        Event::factory()->create(['title' => 'Tanpa Gambar', 'image_url' => null]);
        $this->get('/')->assertSee('Belum ada gambar')->assertDontSee('picsum.photos');
    }
}
