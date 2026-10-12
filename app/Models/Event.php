<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    public const STATUSES = ['draft' => 'Draf', 'published' => 'Dipublikasikan', 'cancelled' => 'Dibatalkan'];

    protected $fillable = [
        'organization_id', 'category_id', 'user_id', 'title', 'slug', 'description',
        'city', 'location', 'start_at', 'end_at', 'quota', 'image_url', 'status',
    ];

    protected function casts(): array
    {
        return ['start_at' => 'datetime', 'end_at' => 'datetime', 'quota' => 'integer'];
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
            ->when($f['from'] ?? null, fn ($q, $d) => $q->where('start_at', '>=', \Carbon\Carbon::parse($d)->startOfDay()))
            ->when($f['to'] ?? null, fn ($q, $d) => $q->where('start_at', '<=', \Carbon\Carbon::parse($d)->endOfDay()));
    }
}
