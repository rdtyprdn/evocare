<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventPhoto;
use App\Services\EventMediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EventPhotoController extends Controller
{
    public const MAX_ITEMS = EventMediaService::MAX_ITEMS;

    public function __construct(private EventMediaService $media) {}

    public function store(Request $request, Event $event)
    {
        Gate::authorize('managePhotos', $event);

        $kind = $request->input('kind', 'photo');
        $request->validate(['caption' => ['nullable', 'string', 'max:150']], ['caption.max' => 'Keterangan maksimal 150 karakter.']);
        $caption = $request->filled('caption') ? trim($request->input('caption')) : null;
        $back = fn () => redirect()->to(route('organizer.events.edit', $event).'#galeri');

        if ($kind === 'youtube') {
            $request->validate(['youtube_url' => ['required', 'string', 'max:300']], ['youtube_url.required' => 'Tempel tautan video YouTube terlebih dahulu.']);
            $id = EventPhoto::parseYoutubeId($request->input('youtube_url'));
            if (! $id) {
                return $back()->withInput()->withErrors(['youtube_url' => 'Tautan YouTube tidak valid. Gunakan tautan seperti youtube.com/watch?v=..., youtu.be/... atau youtube.com/shorts/....']);
            }
            if ($event->photos()->count() + 1 > self::MAX_ITEMS) {
                return $back()->withErrors(['youtube_url' => 'Galeri maksimal berisi '.self::MAX_ITEMS.' item.']);
            }
            $this->media->addMany($event, [['type' => 'youtube', 'youtube_id' => $id, 'caption' => $caption]]);

            return $back()->with('success', 'Video YouTube berhasil ditambahkan ke galeri.');
        }

        if ($kind === 'video') {
            $request->validate([
                'videos' => ['required', 'array', 'min:1', 'max:3'],
                'videos.*' => EventMediaService::videoRules(),
                'captions' => ['nullable', 'array'],
                'captions.*' => ['nullable', 'string', 'max:150'],
            ], [
                'videos.required' => 'Pilih minimal satu video.',
                'videos.max' => 'Maksimal 3 video sekali unggah.',
                'videos.*.mimetypes' => 'Format video harus MP4, WEBM, atau MOV.',
                'videos.*.max' => 'Ukuran setiap video maksimal 30 MB.',
            ]);
            $files = $request->file('videos');
            $field = 'videos';
            $type = 'video';
            $label = 'video';
        } else {
            $request->validate([
                'photos' => ['required', 'array', 'min:1', 'max:10'],
                'photos.*' => EventMediaService::photoRules(),
                'captions' => ['nullable', 'array'],
                'captions.*' => ['nullable', 'string', 'max:150'],
            ], [
                'photos.required' => 'Pilih minimal satu foto.',
                'photos.max' => 'Maksimal 10 foto sekali unggah.',
                'photos.*.image' => 'Setiap file harus berupa gambar.',
                'photos.*.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
                'photos.*.max' => 'Ukuran setiap foto maksimal 10 MB.',
            ]);
            $files = $request->file('photos');
            $field = 'photos';
            $type = 'image';
            $label = 'foto';
        }

        if ($event->photos()->count() + count($files) > self::MAX_ITEMS) {
            return $back()->withErrors([$field => 'Galeri maksimal berisi '.self::MAX_ITEMS.' item.']);
        }

        $caps = array_values((array) $request->input('captions', []));
        $items = [];
        foreach (array_values($files) as $i => $f) {
            $c = $caps[$i] ?? $caption;
            $c = is_string($c) ? trim($c) : null;
            $items[] = ['type' => $type, 'file' => $f, 'caption' => ($c === '' || $c === null) ? null : $c];
        }
        $this->media->addMany($event, $items);

        return $back()->with('success', count($files).' '.$label.' dokumentasi berhasil diunggah.');
    }

    public function destroy(Event $event, EventPhoto $photo)
    {
        Gate::authorize('managePhotos', $event);
        $photo->delete();

        return redirect()->to(route('organizer.events.edit', $event).'#galeri')->with('success', 'Item galeri berhasil dihapus.');
    }
}
