<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Event;
use App\Support\CitySearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitySearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            ['Bandung', 'Kota Bandung', 'Jawa Barat'], ['Bandar Lampung', 'Kota Bandar Lampung', 'Lampung'],
            ['Banda Aceh', 'Kota Banda Aceh', 'Aceh'], ['Lembang', 'Kabupaten Bandung Barat', 'Jawa Barat'],
            ['Denpasar', 'Kota Denpasar', 'Bali'], ['Kuta', 'Kabupaten Badung', 'Bali'], ['Banjarmasin', 'Kota Banjarmasin', 'Kalimantan Selatan'],
        ] as [$name, $region, $province]) {
            City::create(compact('name', 'region', 'province'));
        }
    }

    public function test_score_tiers(): void
    {
        $this->assertSame(0, CitySearch::score('bandung', 'Bandung'));
        $this->assertSame(1, CitySearch::score('ban', 'Bandung'));
        $this->assertSame(2, CitySearch::score('lam', 'Bandar Lampung'));
        $this->assertSame(3, CitySearch::score('pasar', 'Denpasar'));
        $this->assertSame(4, CitySearch::score('bandung', 'Lembang', 'Kabupaten Bandung Barat'));
        $this->assertNull(CitySearch::score('xyz', 'Bandung'));
    }

    public function test_ban_without_events_is_alphabetical(): void
    {
        $names = CitySearch::search('ban')->pluck('name')->all();
        $this->assertSame(['Banda Aceh', 'Bandar Lampung', 'Bandung', 'Banjarmasin'], array_slice($names, 0, 4));
    }

    public function test_ban_puts_bandung_first_when_it_has_events(): void
    {
        Event::factory()->create(['city' => 'Bandung']);
        $names = CitySearch::search('ban')->pluck('name')->all();
        $this->assertSame(['Bandung', 'Banda Aceh', 'Bandar Lampung', 'Banjarmasin'], array_slice($names, 0, 4));
    }

    public function test_seeded_data_ban_gives_bandung_first(): void
    {
        City::query()->delete();
        $this->seed();
        $this->getJson('/api/cities?q=ban')->assertJsonPath('data.0.name', 'Bandung');
    }

    public function test_bandu_puts_bandung_first(): void
    {
        $this->assertSame('Bandung', CitySearch::search('BANDU')->first()['name']);
    }

    public function test_exact_match_beats_prefix_and_region_match_is_last(): void
    {
        $names = CitySearch::search('bandung')->pluck('name')->all();
        $this->assertSame(['Bandung', 'Lembang'], $names);
    }

    public function test_endpoint_returns_ranked_json_with_subtitle(): void
    {
        $this->getJson('/api/cities?q=Bandung')->assertOk()
            ->assertJsonPath('data.0.name', 'Bandung')
            ->assertJsonPath('data.0.subtitle', 'Kota Bandung, Jawa Barat, Indonesia');
    }

    public function test_endpoint_limits_results_and_handles_empty_query(): void
    {
        $this->getJson('/api/cities')->assertOk()->assertJsonCount(6, 'data');
        $this->getJson('/api/cities?q=zzzz')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_cities_from_events_are_included(): void
    {
        Event::factory()->create(['city' => 'Bukittinggi']);
        $this->getJson('/api/cities?q=bukit')->assertJsonPath('data.0.name', 'Bukittinggi')->assertJsonPath('data.0.subtitle', 'Indonesia');
    }

    public function test_home_has_autocomplete_and_city_filter_still_works(): void
    {
        Event::factory()->create(['title' => 'Aksi di Denpasar', 'city' => 'Denpasar']);
        Event::factory()->create(['title' => 'Aksi di Kuta', 'city' => 'Kuta']);

        $this->get('/?city=Denpasar')->assertOk()->assertSee('cityAutocomplete', false)
            ->assertSee('Aksi di Denpasar')->assertDontSee('Aksi di Kuta');
    }
}
