<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EventGalleryVideoTest extends TestCase
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

    private function send(array $data, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->owner)->post(route('organizer.events.photos.store', $this->event), $data);
    }

    public function test_owner_can_upload_video(): void
    {
        $this->send(['kind' => 'video', 'caption' => 'Cuplikan acara', 'videos' => [UploadedFile::fake()->create('acara.mp4', 4000, 'video/mp4')]])
            ->assertSessionHasNoErrors()->assertRedirect();

        $item = EventPhoto::firstOrFail();
        $this->assertSame('video', $item->type);
        $this->assertStringStartsWith('event-videos/', $item->path);
        Storage::disk('public')->assertExists($item->path);
    }

    public function test_webm_is_accepted_and_exactly_5mb_passes(): void
    {
        $this->send(['kind' => 'video', 'videos' => [UploadedFile::fake()->create('a.webm', 30720, 'video/webm')]])->assertSessionHasNoErrors();
        $this->assertSame(1, EventPhoto::where('type', 'video')->count());
    }

    public function test_video_over_5mb_is_rejected(): void
    {
        $this->send(['kind' => 'video', 'videos' => [UploadedFile::fake()->create('besar.mp4', 30721, 'video/mp4')]])
            ->assertSessionHasErrors(['videos.0' => 'Ukuran setiap video maksimal 30 MB.']);
        $this->assertSame(0, EventPhoto::count());
    }

    public function test_invalid_video_mime_is_rejected(): void
    {
        $this->send(['kind' => 'video', 'videos' => [UploadedFile::fake()->create('virus.exe', 100, 'application/x-msdownload')]])
            ->assertSessionHasErrors('videos.0');
        $this->send(['kind' => 'video', 'videos' => [UploadedFile::fake()->image('foto.jpg')]])->assertSessionHasErrors('videos.0');
        $this->assertSame(0, EventPhoto::count());
    }

    public static function youtubeLinks(): array
    {
        return [
            'watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            'watch dengan parameter' => ['https://youtube.com/watch?feature=share&v=dQw4w9WgXcQ&t=30s'],
            'mobile' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ'],
            'youtu.be' => ['https://youtu.be/dQw4w9WgXcQ?si=abc'],
            'shorts' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ'],
            'embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ'],
            'tanpa https' => ['youtube.com/watch?v=dQw4w9WgXcQ'],
        ];
    }

    #[DataProvider('youtubeLinks')]
    public function test_youtube_link_variants_are_parsed(string $url): void
    {
        $this->assertSame('dQw4w9WgXcQ', EventPhoto::parseYoutubeId($url));
    }

    public function test_owner_can_add_youtube_link(): void
    {
        $this->send(['kind' => 'youtube', 'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ', 'caption' => 'Rekap kegiatan'])
            ->assertSessionHasNoErrors()->assertRedirect();

        $item = EventPhoto::firstOrFail();
        $this->assertSame(['youtube', 'dQw4w9WgXcQ', null], [$item->type, $item->youtube_id, $item->path]);
    }

    public function test_invalid_youtube_link_is_rejected(): void
    {
        foreach (['https://vimeo.com/123456', 'https://www.youtube.com/watch?v=pendek', 'bukan tautan', 'https://evil.com/watch?v=dQw4w9WgXcQ'] as $url) {
            $this->send(['kind' => 'youtube', 'youtube_url' => $url])->assertSessionHasErrors('youtube_url');
        }
        $this->send(['kind' => 'youtube', 'youtube_url' => ''])->assertSessionHasErrors(['youtube_url' => 'Tempel tautan video YouTube terlebih dahulu.']);
        $this->assertSame(0, EventPhoto::count());
    }

    public function test_non_owner_is_forbidden(): void
    {
        $other = User::factory()->organizer()->create();
        $this->send(['kind' => 'youtube', 'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ'], $other)->assertForbidden();
        $this->send(['kind' => 'video', 'videos' => [UploadedFile::fake()->create('a.mp4', 100, 'video/mp4')]], $other)->assertForbidden();
        $this->assertSame(0, EventPhoto::count());
    }

    public function test_total_limit_is_30_items(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            $this->event->photos()->create(['type' => 'youtube', 'youtube_id' => 'dQw4w9WgXcQ', 'sort_order' => $i]);
        }
        $this->send(['kind' => 'youtube', 'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ'])->assertSessionHasErrors('youtube_url');
        $this->assertSame(30, EventPhoto::count());
    }

    public function test_existing_photos_default_to_image(): void
    {
        $p = $this->event->photos()->create(['path' => 'event-photos/lama.jpg', 'sort_order' => 1]);
        $this->assertSame('image', $p->fresh()->type);
    }

    public function test_public_page_renders_video_and_youtube(): void
    {
        $this->event->photos()->create(['type' => 'image', 'path' => 'event-photos/1/a.jpg', 'sort_order' => 1]);
        $this->event->photos()->create(['type' => 'video', 'path' => 'event-videos/1/b.mp4', 'caption' => 'Cuplikan', 'sort_order' => 2]);
        $this->event->photos()->create(['type' => 'youtube', 'youtube_id' => 'dQw4w9WgXcQ', 'sort_order' => 3]);

        $this->get(route('events.show', $this->event))->assertOk()
            ->assertSee('storage/event-videos/1/b.mp4')
            ->assertSee('https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg', false)
            ->assertSee('youtube-nocookie.com', false)->assertSee('embed', false)
            ->assertSee('playsinline', false)->assertSee('data-gallery-player', false)
            ->assertSee('Cuplikan');
    }

    public function test_deleting_video_removes_file(): void
    {
        $this->send(['kind' => 'video', 'videos' => [UploadedFile::fake()->create('a.mp4', 100, 'video/mp4')]]);
        $item = EventPhoto::firstOrFail();
        $this->actingAs($this->owner)->delete(route('organizer.events.photos.destroy', [$this->event, $item]))->assertRedirect();
        Storage::disk('public')->assertMissing($item->path);
        $this->assertModelMissing($item);
    }

    public function test_edit_page_shows_tabs(): void
    {
        $this->actingAs($this->owner)->get(route('organizer.events.edit', $this->event))->assertOk()
            ->assertSee('Link YouTube')->assertSee('Maks 30 MB per video, MP4 atau WEBM', false)
            ->assertSee('name="videos[]"', false)->assertSee('name="youtube_links[]"', false);
    }

    public function test_uploaded_video_uses_custom_player_without_native_controls(): void
    {
        $this->event->photos()->create(['type' => 'video', 'path' => 'event-videos/1/b.mp4', 'sort_order' => 1]);
        $html = $this->get(route('events.show', $this->event))->assertOk()
            ->assertSee('aria-label="Pemutar video"', false)->assertSee('controlsList="nodownload noremoteplayback', false)
            ->assertSee('disablePictureInPicture', false)->assertSee('draggable="false"', false)
            ->assertSee('Layar penuh')->assertSee('aria-label="Volume"', false)->assertSee('bg-orange-600', false)
            ->getContent();
        preg_match_all('/<video\b[^>]*>/', $html, $m);
        $this->assertNotEmpty($m[0]);
        foreach ($m[0] as $tag) {
            $this->assertDoesNotMatchRegularExpression('/\scontrols(\s|>|=)/', $tag);
        }
    }
}
