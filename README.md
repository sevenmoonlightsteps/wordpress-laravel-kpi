# WordPress + Laravel KPI Dashboard

A monorepo integrating WordPress (front-end, content layer) with Laravel (back-end API, auth) to deliver a live KPI dashboard. WordPress renders the dashboard using a Gutenberg block or shortcode; Laravel handles authentication via Sanctum, serves KPI data, and pulls WordPress content through the WP REST API when needed.

## Architecture

```
+------------------------------------------+
|          WordPress  :8080                 |
|  - Public pages, blog, landing           |
|  - KPI Dashboard block / shortcode       |
|  - Proxies KPI requests server-side      |
|  WP REST API at /wp-json/wp/v2/...       |
+-------------------+----------------------+
                    |  HTTP REST
+-------------------v----------------------+
|          Laravel  :8081                  |
|  - Sanctum token auth                   |
|  - POST /api/auth/wp-token  (exchange)  |
|  - GET  /api/kpi/summary                |
|  - GET  /api/kpi/{id}/history           |
|  - POST /api/kpi/{id}/update            |
|  - WordPressService (pulls WP content)  |
+------------------------------------------+
```

**Auth flow:** WordPress admin generates a WP Application Password, enters it in the plugin settings, and clicks "Get Token from Laravel". The plugin POSTs the credentials to `POST /api/auth/wp-token`; Laravel verifies them against the WP REST API and returns a Sanctum token. The token is stored server-side in WP options and attached to every KPI request. It is never exposed to the browser.

## Repository Structure

```
wordpress-laravel-kpi/
├── wordpress/
│   ├── plugins/kpi-dashboard/   ← custom plugin (block + shortcode + auth)
│   └── themes/                  ← custom or child theme
├── laravel/
│   ├── app/Http/Controllers/    ← KpiController, AuthController
│   ├── app/Services/            ← WordPressService
│   ├── database/migrations/
│   ├── database/seeders/        ← KpiSeeder (sample data)
│   └── tests/Feature/           ← Pest feature tests
├── docker/
│   ├── nginx/                   ← wordpress.conf, laravel.conf
│   ├── laravel/                 ← Dockerfile
│   └── mysql/                   ← init.sh (creates both DBs)
├── docs/
│   ├── wordpress-setup.md
│   ├── laravel-setup.md
│   └── api.md
├── docker-compose.yml
├── .env.example                 ← root Docker env vars
└── kpi.sh                       ← start / stop / status helper
```

## Prerequisites

- Docker and Docker Compose v2
- PHP 8.3+ and Composer (only needed to run tests outside Docker)
- Git

## Quick Start

```bash
# 1. Clone the repo
git clone https://github.com/<you>/wordpress-laravel-kpi.git
cd wordpress-laravel-kpi

# 2. Copy and configure the root env file
cp .env.example .env
# Generate a Laravel app key and set it as LARAVEL_APP_KEY:
#   docker run --rm php:8.3-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"

# 3. Start the stack
./kpi.sh start

# 4. Run Laravel migrations and seed sample KPI data
docker compose exec laravel php artisan migrate --seed

# 5. Complete WordPress setup
#    Open http://localhost:8080/wp-admin and run the WP install wizard.
#    Activate the KPI Dashboard plugin (Plugins > Installed Plugins).
#    Go to Settings > KPI Dashboard:
#      - Laravel API Base URL: http://localhost:8081
#      - Create a WP Application Password on your profile page.
#      - Enter your username + Application Password and click "Get Token from Laravel".

# WordPress -> http://localhost:8080
# Laravel   -> http://localhost:8081/api
```

See [docs/wordpress-setup.md](docs/wordpress-setup.md), [docs/laravel-setup.md](docs/laravel-setup.md), and [docs/api.md](docs/api.md) for full details.

## Running Tests

```bash
cd laravel
composer install
php artisan test
```

## Stack Management

```bash
./kpi.sh start    # start all containers
./kpi.sh stop     # stop all containers
./kpi.sh status   # show container status
```
