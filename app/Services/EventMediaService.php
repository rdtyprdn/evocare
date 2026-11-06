<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventPhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Menyimpan media galeri (foto, video, YouTube) ke event_photos.
 * Dipakai oleh form Buat Kegiatan dan pengelolaan galeri di Ubah Kegiatan.
 */
class EventMediaService
{
    public const MAX_ITEMS = 30;

    public const MAX_PHOTOS_PER_UPLOAD = 10;

    public const MAX_VIDEOS_PER_UPLOAD = 3;

    public const PHOTO_MAX_KB = 10240;

    public const VIDEO_MAX_KB = 30720;

    public static function photoRules(): array
    {
        return ['image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::PHOTO_MAX_KB];
    }

    public static function videoRules(): array
    {
        return ['file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:'.self::VIDEO_MAX_KB];
    }

    public static function messages(): array
    {
        return [
            'photos.max' => 'Maksimal '.self::MAX_PHOTOS_PER_UPLOAD.' foto sekali unggah.',
            'photos.*.image' => 'Setiap file harus berupa gambar.',
            'photos.*.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'photos.*.max' => 'Ukuran setiap foto maksimal 10 MB.',
            'photos.*.uploaded' => 'Foto gagal diunggah. Ukuran setiap foto maksimal 10 MB.',
            'videos.max' => 'Maksimal '.self::MAX_VIDEOS_PER_UPLOAD.' video sekali unggah.',
            'videos.*.mimetypes' => 'Format video harus MP4, WEBM, atau MOV.',
            'videos.*.max' => 'Ukuran setiap video maksimal 30 MB.',
            'videos.*.uploaded' => 'Video gagal diunggah. Ukuran setiap video maksimal 30 MB.',
        ];
    }

    public function remainingSlots(Event $event): int
    {
        return max(0, self::MAX_ITEMS - $event->photos()->count());
    }

    /**
     * Simpan sekumpulan item. Setiap item: ['type' => image|video, 'file' => UploadedFile]
     * atau ['type' => 'youtube', 'youtube_id' => '...'], plus 'caption' opsional.
     * Atomik: jika gagal, baris DB dibatalkan dan file yang sudah tersimpan dihapus.
     */
    public function addMany(Event $event, array $items): int
    {
        if ($items === []) {
            return 0;
        }
        $stored = [];
        try {
            DB::transaction(function () use ($event, $items, &$stored) {
                $order = (int) $event->photos()->max('sort_order');
                foreach ($items as $item) {
                    $data = ['type' => $item['type'], 'caption' => $item['caption'] ?? null, 'sort_order' => ++$order];
                    if (isset($item['file']) && $item['file'] instanceof UploadedFile) {
                        $dir = ($item['type'] === 'video' ? 'event-videos/' : 'event-photos/').$event->id;
                        $path = $item['file']->store($dir, 'public');
                        if (! $path) {
                            throw new \RuntimeException('Gagal menyimpan file.');
                        }
                        $stored[] = $path;
                        $data['path'] = $path;
                    } else {
                        $data['youtube_id'] = $item['youtube_id'];
                    }
                    $event->photos()->create($data);
                }
            });
        } catch (Throwable $e) {
            foreach ($stored as $path) {
                Storage::disk('public')->delete($path);
            }
            throw $e;
        }

        return count($items);
    }

    public static function youtubeId(?string $url): ?string
    {
        return EventPhoto::parseYoutubeId($url);
    }

    /**
     * Perbarui keterangan item galeri yang sudah tersimpan.
     *
     * @param  array<int|string, mixed>  $captions  keyed by EventPhoto id
     */
    public function updateCaptions(Event $event, array $captions): int
    {
        $updated = 0;
        foreach ($captions as $id => $caption) {
            if (! is_numeric($id)) {
                continue;
            }
            $photo = $event->photos()->whereKey((int) $id)->first();
            if (! $photo) {
                continue;
            }
            $value = is_string($caption) ? trim($caption) : '';
            $value = $value === '' ? null : mb_substr($value, 0, 150);
            if ($photo->caption === $value) {
                continue;
            }
            $photo->update(['caption' => $value]);
            $updated++;
        }

        return $updated;
    }
}
