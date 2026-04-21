<?php

namespace Database\Seeders;

use App\Models\Kpi;
use App\Models\KpiMetric;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KpiSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the KPIs and their metrics.
     */
    public function run(): void
    {
        // KPI 1: Monthly Revenue
        $monthlyRevenue = Kpi::create([
            'name' => 'Monthly Revenue',
            'slug' => 'monthly-revenue',
            'unit' => '$',
            'description' => 'Total revenue for the current month',
        ]);

        for ($i = 0; $i < 10; $i++) {
            KpiMetric::create([
                'kpi_id' => $monthlyRevenue->id,
                'value' => 45000 + (rand(-5000, 5000)),
                'recorded_at' => Carbon::now()->subDays($i),
            ]);
        }

        // KPI 2: Active Users
        $activeUsers = Kpi::create([
            'name' => 'Active Users',
            'slug' => 'active-users',
            'unit' => 'users',
            'description' => 'Number of active users in the system',
        ]);

        for ($i = 0; $i < 10; $i++) {
            KpiMetric::create([
                'kpi_id' => $activeUsers->id,
                'value' => 2350 + (rand(-200, 300)),
                'recorded_at' => Carbon::now()->subDays($i),
            ]);
        }

        // KPI 3: Conversion Rate
        $conversionRate = Kpi::create([
            'name' => 'Conversion Rate',
            'slug' => 'conversion-rate',
            'unit' => '%',
            'description' => 'Percentage of visitors who convert to customers',
        ]);

        for ($i = 0; $i < 10; $i++) {
            KpiMetric::create([
                'kpi_id' => $conversionRate->id,
                'value' => 3.25 + ((rand(-50, 50)) / 100),
                'recorded_at' => Carbon::now()->subDays($i),
            ]);
        }
    }
}
