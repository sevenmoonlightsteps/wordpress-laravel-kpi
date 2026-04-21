<?php

namespace Database\Factories;

use App\Models\Kpi;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Kpi>
 */
class KpiFactory extends Factory
{
    protected $model = Kpi::class;

    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'name'        => $name,
            'slug'        => Str::slug($name) . '-' . fake()->unique()->numberBetween(1, 9999),
            'unit'        => fake()->randomElement(['%', '$', 'count', 'ms', 'GB']),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
