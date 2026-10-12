<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Komunitas '.fake()->unique()->word();

        return ['name' => $name, 'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)), 'type' => 'komunitas', 'city' => 'Bandung'];
    }
}
