<?php

use App\Models\Kpi;
use App\Models\KpiMetric;
use App\Models\User;
use Illuminate\Support\Carbon;

// Helper: create an authenticated user and return actingAs response helper
function actingAsUser(): \Tests\TestCase
{
    $user = User::factory()->create();
    return test()->actingAs($user, 'sanctum');
}

// ---------------------------------------------------------------------------
// GET /api/kpi/summary
// ---------------------------------------------------------------------------

it('returns 401 on summary without token', function () {
    $this->getJson('/api/kpi/summary')
        ->assertStatus(401);
});

it('returns 200 with correct shape on summary when authenticated', function () {
    $kpi = Kpi::factory()->create();
    KpiMetric::factory()->for($kpi)->create(['value' => 42.5]);

    actingAsUser()
        ->getJson('/api/kpi/summary')
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'name', 'slug', 'unit', 'latest_value'],
            ],
        ])
        ->assertJson(['success' => true]);
});

it('includes latest_value from most recent metric on summary', function () {
    $kpi = Kpi::factory()->create();

    KpiMetric::factory()->for($kpi)->create([
        'value'       => 10.0,
        'recorded_at' => now()->subDays(2),
    ]);
    KpiMetric::factory()->for($kpi)->create([
        'value'       => 99.9,
        'recorded_at' => now()->subDay(),
    ]);

    $response = actingAsUser()
        ->getJson('/api/kpi/summary')
        ->assertStatus(200);

    $data = $response->json('data');
    $match = collect($data)->firstWhere('id', $kpi->id);

    expect($match['latest_value'])->toBe(99.9);
});

// ---------------------------------------------------------------------------
// GET /api/kpi/{kpi}/history
// ---------------------------------------------------------------------------

it('returns correct time-series shape on history', function () {
    $kpi = Kpi::factory()->create();
    KpiMetric::factory()->for($kpi)->count(3)->create();

    actingAsUser()
        ->getJson("/api/kpi/{$kpi->id}/history")
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'kpi'     => ['id', 'name', 'slug', 'unit'],
                'metrics' => [
                    '*' => ['value', 'recorded_at'],
                ],
            ],
        ]);
});

it('respects limit param on history', function () {
    $kpi = Kpi::factory()->create();
    KpiMetric::factory()->for($kpi)->count(10)->create();

    $response = actingAsUser()
        ->getJson("/api/kpi/{$kpi->id}/history?limit=3")
        ->assertStatus(200);

    expect($response->json('data.metrics'))->toHaveCount(3);
});

it('respects order=asc param on history', function () {
    $kpi = Kpi::factory()->create();

    KpiMetric::factory()->for($kpi)->create([
        'value'       => 1.0,
        'recorded_at' => now()->subDays(3),
    ]);
    KpiMetric::factory()->for($kpi)->create([
        'value'       => 2.0,
        'recorded_at' => now()->subDays(2),
    ]);
    KpiMetric::factory()->for($kpi)->create([
        'value'       => 3.0,
        'recorded_at' => now()->subDay(),
    ]);

    $response = actingAsUser()
        ->getJson("/api/kpi/{$kpi->id}/history?order=asc")
        ->assertStatus(200);

    $values = collect($response->json('data.metrics'))->pluck('value')->toArray();

    expect($values)->toEqual([1.0, 2.0, 3.0]);
});

it('returns 404 for unknown KPI on history', function () {
    actingAsUser()
        ->getJson('/api/kpi/999999/history')
        ->assertStatus(404);
});

// ---------------------------------------------------------------------------
// POST /api/kpi/{kpi}/update
// ---------------------------------------------------------------------------

it('creates a new metric and returns 201 on update', function () {
    $kpi = Kpi::factory()->create();

    $response = actingAsUser()
        ->postJson("/api/kpi/{$kpi->id}/update", [
            'value'       => 55.25,
            'recorded_at' => now()->subHour()->toIso8601String(),
        ])
        ->assertStatus(201)
        ->assertJson(['success' => true]);

    expect($response->json('data.value'))->toBe(55.25)
        ->and($response->json('data.kpi_id'))->toBe($kpi->id);

    $this->assertDatabaseHas('kpi_metrics', [
        'kpi_id' => $kpi->id,
        'value'  => 55.25,
    ]);
});

it('defaults recorded_at to now when omitted on update', function () {
    $kpi = Kpi::factory()->create();

    Carbon::setTestNow('2026-01-15 12:00:00');

    actingAsUser()
        ->postJson("/api/kpi/{$kpi->id}/update", ['value' => 7.77])
        ->assertStatus(201);

    $this->assertDatabaseHas('kpi_metrics', [
        'kpi_id' => $kpi->id,
        'value'  => 7.77,
    ]);

    Carbon::setTestNow();
});

it('returns 422 when value is missing on update', function () {
    $kpi = Kpi::factory()->create();

    actingAsUser()
        ->postJson("/api/kpi/{$kpi->id}/update", [])
        ->assertStatus(422)
        ->assertJson(['success' => false]);
});

it('returns 422 when recorded_at is in the future on update', function () {
    $kpi = Kpi::factory()->create();

    actingAsUser()
        ->postJson("/api/kpi/{$kpi->id}/update", [
            'value'       => 10,
            'recorded_at' => now()->addDay()->toIso8601String(),
        ])
        ->assertStatus(422)
        ->assertJson(['success' => false]);
});
