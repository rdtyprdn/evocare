<?php

namespace App\Support;

use App\Models\City;
use App\Models\Event;
use Illuminate\Support\Collection;

class CitySearch
{
    /**
     * Skor kecocokan (semakin kecil semakin baik), null = tidak cocok.
     * 0 = sama persis, 1 = awalan nama, 2 = awalan salah satu kata, 3 = mengandung, 4 = cocok pada wilayah/provinsi.
     */
    public static function score(string $q, string $name, string $context = ''): ?int
    {
        $q = mb_strtolower(trim($q));
        $n = mb_strtolower($name);
        if ($q === '') {
            return 0;
        }
        if ($n === $q) {
            return 0;
        }
        if (str_starts_with($n, $q)) {
            return 1;
        }
        foreach (preg_split('/[\s\-]+/', $n) as $word) {
            if (str_starts_with($word, $q)) {
                return 2;
            }
        }
        if (str_contains($n, $q)) {
            return 3;
        }
        if ($context !== '' && str_contains(mb_strtolower($context), $q)) {
            return 4;
        }

        return null;
    }

    /**
     * Urutan: skor kecocokan, lalu kota yang punya kegiatan aktif, lalu alfabetis.
     *
     * @return Collection<int, array{name:string, subtitle:string}>
     */
    public static function search(string $q, int $limit = 6): Collection
    {
        $cities = City::all()->map(fn (City $c) => ['name' => $c->name, 'subtitle' => $c->subtitle()]);

        // Sertakan kota dari kegiatan yang belum ada di daftar kota.
        $active = Event::published()->where('end_at', '>=', now())->distinct()->pluck('city')
            ->map(fn ($n) => mb_strtolower($n))->flip();

        $known = $cities->pluck('name')->map(fn ($n) => mb_strtolower($n))->all();
        Event::query()->distinct()->pluck('city')
            ->reject(fn ($n) => in_array(mb_strtolower($n), $known, true))
            ->each(fn ($n) => $cities->push(['name' => $n, 'subtitle' => 'Indonesia']));

        return $cities
            ->map(fn ($c) => $c + [
                'score' => self::score($q, $c['name'], $c['subtitle']),
                'idle' => $active->has(mb_strtolower($c['name'])) ? 0 : 1, // kota yang punya kegiatan aktif didahulukan
            ])
            ->reject(fn ($c) => $c['score'] === null)
            ->sort(fn ($a, $b) => [$a['score'], $a['idle'], mb_strtolower($a['name'])] <=> [$b['score'], $b['idle'], mb_strtolower($b['name'])])
            ->take($limit)
            ->map(fn ($c) => ['name' => $c['name'], 'subtitle' => $c['subtitle']])
            ->values();
    }
}
