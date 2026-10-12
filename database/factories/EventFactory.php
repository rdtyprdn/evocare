<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EventFactory extends Factory
{
    public function definition(): array
    {
        $title = 'Aksi '.fake()->unique()->sentence(3);
        $start = now()->addDays(fake()->numberBetween(3, 30))->setTime(8, 0);

        return [
            'organization_id' => Organization::factory(),
            'category_id' => Category::factory(),
            'user_id' => User::factory()->state(['role' => 'organizer']),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'description' => fake()->paragraph(),
            'city' => 'Bandung',
            'location' => 'Balai Warga',
            'start_at' => $start,
            'end_at' => (clone $start)->addHours(4),
            'quota' => 10,
            'status' => 'published',
        ];
    }
}
