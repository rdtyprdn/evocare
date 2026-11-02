<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/** Item galeri dokumentasi: foto, video unggahan, atau video YouTube. */
class EventPhoto extends Model
{
    public const TYPES = ['image', 'video', 'youtube'];

    protected $fillable = ['event_id', 'type', 'path', 'youtube_id', 'caption', 'sort_order'];

    protected $attributes = ['type' => 'image'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    public function isYoutube(): bool
    {
        return $this->type === 'youtube';
    }

    /** URL file (foto/video) atau URL embed YouTube. */
    public function url(): string
    {
        if ($this->isYoutube()) {
            return 'https://www.youtube-nocookie.com/embed/'.$this->youtube_id.'?autoplay=1&rel=0&enablejsapi=1';
        }

        return asset('storage/'.$this->path);
    }

    /** Gambar pratinjau untuk thumbnail, null untuk video unggahan. */
    public function thumbnailUrl(): ?string
    {
        return match ($this->type) {
            'image' => $this->url(),
            'youtube' => 'https://img.youtube.com/vi/'.$this->youtube_id.'/hqdefault.jpg',
            default => null,
        };
    }

    public function typeLabel(): string
    {
        return ['image' => 'Foto', 'video' => 'Video', 'youtube' => 'YouTube'][$this->type] ?? 'Foto';
    }

    /** Ambil ID video dari tautan YouTube (watch, youtu.be, shorts, embed, live). */
    public static function parseYoutubeId(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }
        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }
        $parts = parse_url($url);
        $host = strtolower(preg_replace('/^(www\.|m\.|music\.)/', '', $parts['host'] ?? ''));
        $path = $parts['path'] ?? '';
        $id = null;

        if ($host === 'youtu.be') {
            $id = explode('/', ltrim($path, '/'))[0] ?? null;
        } elseif (in_array($host, ['youtube.com', 'youtube-nocookie.com'], true)) {
            if ($path === '/watch') {
                parse_str($parts['query'] ?? '', $q);
                $id = $q['v'] ?? null;
            } elseif (preg_match('#^/(shorts|embed|live|v)/([^/?]+)#', $path, $m)) {
                $id = $m[2];
            }
        }

        return is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/', $id) ? $id : null;
    }

    protected static function booted(): void
    {
        static::deleted(function (EventPhoto $item) {
            if ($item->path) {
                Storage::disk('public')->delete($item->path);
            }
        });
    }
}
