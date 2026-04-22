# Laravel Setup Guide

This guide covers environment configuration, database migrations, seeding, and running tests for the Laravel back-end.

## Directory

All Laravel files live in `laravel/` within the monorepo root.

## 1. Environment Configuration

### Docker (recommended)

The `docker-compose.yml` injects environment variables from the root `.env` file. Copy and edit it:

```bash
cp .env.example .env
```

Key values to set:

| Variable | Description |
|---|---|
| `DB_ROOT_PASSWORD` | MySQL root password |
| `DB_USER` | MySQL app user |
| `DB_PASSWORD` | MySQL app user password |
| `DB_WORDPRESS_NAME` | WordPress database name |
| `DB_LARAVEL_NAME` | Laravel database name |
| `LARAVEL_APP_KEY` | Laravel app key (see below) |

**Generate a Laravel app key:**

```bash
docker run --rm php:8.3-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

Paste the output as the value of `LARAVEL_APP_KEY` in `.env`.

### Local (without Docker)

```bash
cd laravel
cp .env.example .env
php artisan key:generate
```

Edit `laravel/.env` to point `DB_*` variables at your local MySQL instance or use SQLite:

```
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite
```

## 2. Install Dependencies

```bash
# Inside the container
docker compose exec laravel composer install

# Or locally
cd laravel && composer install
```

## 3. Run Migrations

```bash
# Docker
docker compose exec laravel php artisan migrate

# Local
cd laravel && php artisan migrate
```

### Migrations included

| Migration | Creates |
|---|---|
| `create_users_table` | `users`, `password_reset_tokens`, `sessions` |
| `create_cache_table` | `cache`, `cache_locks` |
| `create_jobs_table` | `jobs`, `job_batches`, `failed_jobs` |
| `create_kpis_table` | `kpis` (id, name, slug, unit, description) |
| `create_kpi_metrics_table` | `kpi_metrics` (id, kpi_id, value, recorded_at) |
| `create_personal_access_tokens_table` | `personal_access_tokens` (Sanctum) |

## 4. Seed Sample Data

```bash
# Docker
docker compose exec laravel php artisan migrate --seed

# Local
cd laravel && php artisan db:seed
```

The `KpiSeeder` creates three KPIs with 10 historical data points each:

| KPI | Unit | Range |
|---|---|---|
| Monthly Revenue | $ | ~40,000-50,000 |
| Active Users | users | ~2,150-2,650 |
| Conversion Rate | % | ~2.75-3.75 |

## 5. Running Tests

The test suite uses Pest and an in-memory SQLite database. No external services are needed (WordPress HTTP calls are faked with `Http::fake()`).

```bash
cd laravel
php artisan test
```

To run a specific test file:

```bash
php artisan test tests/Feature/AuthTest.php
php artisan test tests/Feature/KpiApiTest.php
php artisan test tests/Feature/WordPressServiceTest.php
```

## 6. Key Configuration Files

### `config/cors.php`

Laravel is configured to accept requests from WordPress. In production, update the `allowed_origins` array:

```php
'allowed_origins' => ['https://your-wordpress-domain.com'],
```

### `config/sanctum.php`

Sanctum is used for API token authentication. Tokens are created via the token exchange endpoint and stored in the `personal_access_tokens` table.

### `routes/api.php`

```
POST /api/auth/wp-token          public, rate-limited 5/min
GET  /api/kpi/summary            requires auth:sanctum
GET  /api/kpi/{kpi}/history      requires auth:sanctum
POST /api/kpi/{kpi}/update       requires auth:sanctum, throttle 60/min
```

## 7. Useful Artisan Commands

```bash
# List all routes
php artisan route:list

# Clear all caches
php artisan optimize:clear

# Run migrations fresh with seeding
php artisan migrate:fresh --seed

# Check Sanctum tokens in the database
php artisan tinker --execute="App\Models\User::with('tokens')->get()->toJson(pretty: true)"
```

## 8. Production Checklist

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Run `php artisan config:cache` and `php artisan route:cache`
- [ ] Use MySQL (not SQLite) with a dedicated app user
- [ ] Set `allowed_origins` in `config/cors.php` to your WordPress domain
- [ ] Configure a queue worker if using `QUEUE_CONNECTION=database`
- [ ] Set up log rotation for `storage/logs/`
