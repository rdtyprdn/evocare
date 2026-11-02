<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class OrganizationType extends Model
{
    protected $fillable = ['slug', 'name'];

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'type', 'slug');
    }

    /** @return array<string, string> slug => nama */
    public static function options(): array
    {
        return static::orderBy('name')->pluck('name', 'slug')->all();
    }

    public static function labelFor(?string $slug): string
    {
        if (! $slug) {
            return '';
        }
        static $cache = [];

        return $cache[$slug] ??= (static::where('slug', $slug)->value('name') ?? Str::headline(str_replace(['-', '_'], ' ', $slug)));
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'jenis';
        $slug = $base;
        $i = 2;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
