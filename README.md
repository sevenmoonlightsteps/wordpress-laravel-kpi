# WordPress + Laravel KPI Dashboard

This monorepo contains both the WordPress front-end and the Laravel back-end for a KPI Dashboard integration. WordPress serves public-facing pages and renders a KPI Dashboard block (shortcode/Gutenberg block) that fetches live data from the Laravel API. Laravel handles authentication via Sanctum, exposes a KPI API at `/api/kpi/*`, and pulls WordPress content through the WP REST API when needed. The two systems communicate over HTTP REST, keeping each layer independently deployable while sharing a single repository for coordinated development.

## Architecture

```
+------------------------------------------+
|          WordPress (Front-End)            |
|  - Public pages, blog, landing           |
|  - KPI Dashboard (shortcode/block)       |
|  - Fetches KPIs from Laravel API         |
|  REST API at /wp-json/wp/v2/...          |
+-------------------+----------------------+
                    | HTTP (REST)
+-------------------v----------------------+
|          Laravel (Back-End)              |
|  - Sanctum auth (tokens -> WordPress)   |
|  - KPI API: /api/kpi/*                  |
|  - Pulls WP content via WP REST API     |
|  - MySQL / SQLite (dev)                 |
+------------------------------------------+
```

## Repository Structure

```
wordpress-laravel-kpi/
├── wordpress/       <- WP theme + custom plugin
├── laravel/         <- Laravel application
├── docker/          <- Docker configuration files
├── docs/            <- Project documentation
└── README.md        <- This file
```

## Prerequisites

- PHP 8.2+
- Composer
- Node.js 20+ and npm
- Docker and Docker Compose
- MySQL 8+ (or SQLite for local dev)
- WP-CLI (optional but recommended)

## Quick Start

Placeholder. Setup instructions will be added in a future phase.

## Local Development

Placeholder. Local dev workflow will be documented once Docker Compose configuration is complete.
