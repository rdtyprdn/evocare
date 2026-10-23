<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    use HasFactory;

    public const STATUSES = ['draft' => 'Draf', 'published' => 'Dipublikasikan', 'cancelled' => 'Dibatalkan'];

    public const EXECUTION_STATES = [
        'upcoming' => 'Akan Datang',
        'ongoing' => 'Sedang Berlangsung',
        'completed' => 'Selesai',
    ];

    protected $fillable = [
        'organization_id', 'category_id', 'user_id', 'title', 'slug', 'description',
        'city', 'location', 'start_at', 'end_at', 'quota', 'image_url', 'image_path', 'status',
        'report_summary', 'report_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'report_updated_at' => 'datetime',
            'quota' => 'integer',
        ];
    }

    /** Gambar unggahan diprioritaskan, lalu URL, null jika tidak ada. */
    public function imageSrc(): ?string
    {
        if ($this->image_path) {
            return asset('storage/'.$this->image_path);
        }

        return $this->image_url ?: null;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function confirmedRegistrations()
    {
        return $this->hasMany(Registration::class)->where('status', 'confirmed');
    }

    public function remainingQuota(): int
    {
        $used = $this->confirmed_registrations_count ?? $this->confirmedRegistrations()->count();

        return max(0, $this->quota - $used);
    }

    public function occupancyPercentage(): int
    {
        $used = $this->confirmed_registrations_count ?? $this->confirmedRegistrations()->count();

        return (int) min(100, round(($used / max(1, $this->quota)) * 100));
    }

    public function executionState(): string
    {
        $now = now();
        if ($this->end_at && $this->end_at->isPast()) {
            return 'completed';
        }
        if ($this->start_at && $this->end_at && $now->between($this->start_at, $this->end_at)) {
            return 'ongoing';
        }

        return 'upcoming';
    }

    public function executionStateLabel(): string
    {
        return self::EXECUTION_STATES[$this->executionState()] ?? 'Akan Datang';
    }

    public function reportSummary(): ?string
    {
        if (array_key_exists('report_summary', $this->attributes) && $this->attributes['report_summary'] !== null) {
            return $this->attributes['report_summary'];
        }
        $path = "reports/event-{$this->id}.json";
        if ($this->id && Storage::disk('local')->exists($path)) {
            $data = json_decode(Storage::disk('local')->get($path), true);

            return $data['report_summary'] ?? null;
        }

        return null;
    }

    public function reportUpdatedAt(): ?Carbon
    {
        if ($this->report_updated_at instanceof Carbon) {
            return $this->report_updated_at;
        }
        $path = "reports/event-{$this->id}.json";
        if ($this->id && Storage::disk('local')->exists($path)) {
            $data = json_decode(Storage::disk('local')->get($path), true);

            return ! empty($data['report_updated_at']) ? Carbon::parse($data['report_updated_at']) : null;
        }

        return null;
    }

    public function saveReport(?string $summary): void
    {
        $clean = $summary !== null && trim($summary) !== '' ? trim($summary) : null;
        $timestamp = $clean !== null ? now() : null;

        if (Schema::hasColumn('events', 'report_summary')) {
            $this->forceFill([
                'report_summary' => $clean,
                'report_updated_at' => $timestamp,
            ])->save();

            return;
        }

        $path = "reports/event-{$this->id}.json";
        if ($clean === null) {
            Storage::disk('local')->delete($path);
        } else {
            Storage::disk('local')->put($path, json_encode([
                'report_summary' => $clean,
                'report_updated_at' => $timestamp?->toIso8601String(),
            ]));
        }
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published');
    }

    /** Pencarian & filter: q, category (slug), city, from, to */
    public function scopeFilter(Builder $q, array $f): Builder
    {
        return $q
            ->when($f['q'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$s}%")
                ->orWhere('description', 'like', "%{$s}%")
                ->orWhere('location', 'like', "%{$s}%")))
            ->when($f['category'] ?? null, fn ($q, $c) => $q->whereHas('category', fn ($w) => $w->where('slug', $c)))
            ->when($f['city'] ?? null, fn ($q, $c) => $q->where('city', $c))
            ->when($f['from'] ?? null, fn ($q, $d) => $q->where('start_at', '>=', Carbon::parse($d)->startOfDay()))
            ->when($f['to'] ?? null, fn ($q, $d) => $q->where('start_at', '<=', Carbon::parse($d)->endOfDay()));
    }
}
