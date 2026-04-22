<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

// ---------------------------------------------------------------------------
// POST /api/auth/wp-token
// ---------------------------------------------------------------------------

it('returns 422 when required fields are missing', function () {
    $this->postJson('/api/auth/wp-token', [])
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['errors' => ['wp_site_url', 'wp_username', 'wp_app_password']]);
});

it('returns 422 when wp_site_url is not a valid URL', function () {
    $this->postJson('/api/auth/wp-token', [
        'wp_site_url'     => 'not-a-url',
        'wp_username'     => 'admin',
        'wp_app_password' => 'xxxx xxxx xxxx xxxx xxxx xxxx',
    ])->assertStatus(422)
      ->assertJsonPath('success', false);
});

it('returns 401 when WordPress rejects the credentials', function () {
    Http::fake([
        '*/wp-json/wp/v2/users/me' => Http::response(
            ['code' => 'invalid_username'],
            401
        ),
    ]);

    $this->postJson('/api/auth/wp-token', [
        'wp_site_url'     => 'http://wordpress.local',
        'wp_username'     => 'wronguser',
        'wp_app_password' => 'bad password',
    ])->assertStatus(401)
      ->assertJsonPath('success', false);
});

it('returns 502 when WordPress site is unreachable', function () {
    Http::fake([
        '*/wp-json/wp/v2/users/me' => Http::response([], 500),
    ]);

    $this->postJson('/api/auth/wp-token', [
        'wp_site_url'     => 'http://unreachable.local',
        'wp_username'     => 'admin',
        'wp_app_password' => 'xxxx xxxx',
    ])->assertStatus(502)
      ->assertJsonPath('success', false);
});

it('issues a sanctum token when WordPress credentials are valid', function () {
    Http::fake([
        '*/wp-json/wp/v2/users/me' => Http::response([
            'id'   => 1,
            'name' => 'Admin User',
        ], 200),
    ]);

    $response = $this->postJson('/api/auth/wp-token', [
        'wp_site_url'     => 'http://wordpress.local',
        'wp_username'     => 'admin',
        'wp_app_password' => 'xxxx xxxx xxxx xxxx xxxx xxxx',
    ]);

    $response->assertStatus(201)
             ->assertJsonPath('success', true)
             ->assertJsonStructure(['data' => ['token']]);

    $token = $response->json('data.token');
    expect($token)->toBeString()->not->toBeEmpty();
});

it('creates a new laravel user on first exchange', function () {
    Http::fake([
        '*/wp-json/wp/v2/users/me' => Http::response(['id' => 42, 'name' => 'Jane'], 200),
    ]);

    expect(User::where('email', 'jane@wordpress.local')->exists())->toBeFalse();

    $this->postJson('/api/auth/wp-token', [
        'wp_site_url'     => 'http://wordpress.local',
        'wp_username'     => 'jane',
        'wp_app_password' => 'xxxx xxxx xxxx',
    ])->assertStatus(201);

    expect(User::where('email', 'jane@wordpress.local')->exists())->toBeTrue();
});

it('reuses the existing laravel user on subsequent exchanges', function () {
    Http::fake([
        '*/wp-json/wp/v2/users/me' => Http::response(['id' => 1, 'name' => 'Admin User'], 200),
    ]);

    $payload = [
        'wp_site_url'     => 'http://wordpress.local',
        'wp_username'     => 'admin',
        'wp_app_password' => 'xxxx xxxx xxxx xxxx xxxx xxxx',
    ];

    $this->postJson('/api/auth/wp-token', $payload)->assertStatus(201);
    $this->postJson('/api/auth/wp-token', $payload)->assertStatus(201);

    expect(User::where('email', 'admin@wordpress.local')->count())->toBe(1);
});

it('revokes the previous wp-exchange token on re-authentication', function () {
    Http::fake([
        '*/wp-json/wp/v2/users/me' => Http::response(['id' => 1, 'name' => 'Admin User'], 200),
    ]);

    $payload = [
        'wp_site_url'     => 'http://wordpress.local',
        'wp_username'     => 'admin',
        'wp_app_password' => 'xxxx xxxx xxxx xxxx xxxx xxxx',
    ];

    $first  = $this->postJson('/api/auth/wp-token', $payload)->json('data.token');
    $second = $this->postJson('/api/auth/wp-token', $payload)->json('data.token');

    expect($first)->not->toBe($second);

    $user = User::where('email', 'admin@wordpress.local')->first();
    expect($user->tokens()->where('name', 'wp-exchange')->count())->toBe(1);
});

it('issued token authenticates against protected kpi routes', function () {
    Http::fake([
        '*/wp-json/wp/v2/users/me' => Http::response(['id' => 1, 'name' => 'Admin User'], 200),
    ]);

    $token = $this->postJson('/api/auth/wp-token', [
        'wp_site_url'     => 'http://wordpress.local',
        'wp_username'     => 'admin',
        'wp_app_password' => 'xxxx xxxx xxxx xxxx xxxx xxxx',
    ])->json('data.token');

    $this->withToken($token)
         ->getJson('/api/kpi/summary')
         ->assertStatus(200)
         ->assertJsonPath('success', true);
});
