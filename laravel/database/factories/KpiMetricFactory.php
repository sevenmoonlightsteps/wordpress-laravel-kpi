<?php

namespace Database\Factories;

use App\Models\Kpi;
use App\Models\KpiMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KpiMetric>
 */
class KpiMetricFactory extends Factory
{
    protected $model = KpiMetric::class;

    public function definition(): array
    {
        return [
            'kpi_id'      => Kpi::factory(),
            'value'       => fake()->randomFloat(4, 0, 10000),
            'recorded_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
