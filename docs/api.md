# API Reference

Base URL: `http://localhost:8081` (local) or your production Laravel domain.

All KPI endpoints require a `Bearer` token in the `Authorization` header. Obtain a token via the token exchange endpoint below or through the WordPress plugin settings page.

---

## Authentication

### POST /api/auth/wp-token

Exchange a WordPress Application Password for a Laravel Sanctum token.

This endpoint is public (no token required). It is rate-limited to **5 requests per minute** per IP.

**Request**

```http
POST /api/auth/wp-token
Content-Type: application/json
Accept: application/json

{
  "wp_site_url":     "http://localhost:8080",
  "wp_username":     "admin",
  "wp_app_password": "xxxx xxxx xxxx xxxx xxxx xxxx"
}
```

| Field | Type | Required | Description |
|---|---|---|---|
| `wp_site_url` | string (URL) | yes | Full URL of the WordPress site |
| `wp_username` | string | yes | WordPress username |
| `wp_app_password` | string | yes | WP Application Password (with or without spaces) |

**Success response** `201 Created`

```json
{
  "success": true,
  "data": {
    "token": "1|abc123def456..."
  }
}
```

Use the `token` value as `Authorization: Bearer <token>` on all subsequent requests.

Re-authenticating revokes the previous token issued for this WordPress user and issues a fresh one.

**Error responses**

| Status | Reason |
|---|---|
| `422` | Missing or invalid request fields |
| `401` | WordPress rejected the credentials |
| `502` | Could not reach the WordPress site |

---

## KPI Endpoints

All endpoints below require:

```http
Authorization: Bearer <token>
Accept: application/json
```

---

### GET /api/kpi/summary

Returns all KPIs with their most recent metric value.

**Request**

```http
GET /api/kpi/summary
Authorization: Bearer <token>
```

**Success response** `200 OK`

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Monthly Revenue",
      "slug": "monthly-revenue",
      "unit": "$",
      "latest_value": 47312.50
    },
    {
      "id": 2,
      "name": "Active Users",
      "slug": "active-users",
      "unit": "users",
      "latest_value": 2418
    },
    {
      "id": 3,
      "name": "Conversion Rate",
      "slug": "conversion-rate",
      "unit": "%",
      "latest_value": 3.14
    }
  ]
}
```

`latest_value` is `null` if no metrics have been recorded for that KPI.

**Error responses**

| Status | Reason |
|---|---|
| `401` | Missing or invalid token |

---

### GET /api/kpi/{id}/history

Returns the time-series metric history for a single KPI.

**Request**

```http
GET /api/kpi/1/history?limit=30&order=desc
Authorization: Bearer <token>
```

**Query parameters**

| Parameter | Type | Default | Description |
|---|---|---|---|
| `limit` | integer (1-365) | `30` | Number of data points to return |
| `order` | `asc` or `desc` | `desc` | Sort order by `recorded_at` |

**Success response** `200 OK`

```json
{
  "success": true,
  "data": {
    "kpi": {
      "id": 1,
      "name": "Monthly Revenue",
      "slug": "monthly-revenue",
      "unit": "$"
    },
    "metrics": [
      { "value": 47312.50, "recorded_at": "2026-04-22T00:00:00+00:00" },
      { "value": 45980.00, "recorded_at": "2026-04-21T00:00:00+00:00" },
      { "value": 46750.25, "recorded_at": "2026-04-20T00:00:00+00:00" }
    ]
  }
}
```

**Error responses**

| Status | Reason |
|---|---|
| `401` | Missing or invalid token |
| `404` | KPI not found |
| `422` | Invalid query parameter value |

---

### POST /api/kpi/{id}/update

Record a new metric value for a KPI. Rate-limited to **60 requests per minute**.

This endpoint is intended for webhooks, cron jobs, or any system that pushes new KPI values into Laravel.

**Request**

```http
POST /api/kpi/1/update
Authorization: Bearer <token>
Content-Type: application/json

{
  "value": 48500.00,
  "recorded_at": "2026-04-22T12:00:00Z"
}
```

| Field | Type | Required | Description |
|---|---|---|---|
| `value` | numeric | yes | The metric value |
| `recorded_at` | ISO 8601 datetime | no | Defaults to `now()` if omitted; must not be in the future |

**Success response** `201 Created`

```json
{
  "success": true,
  "data": {
    "id": 42,
    "kpi_id": 1,
    "value": 48500.00,
    "recorded_at": "2026-04-22T12:00:00+00:00"
  }
}
```

**Error responses**

| Status | Reason |
|---|---|
| `401` | Missing or invalid token |
| `404` | KPI not found |
| `422` | `value` missing or not numeric; `recorded_at` is in the future |
| `429` | Rate limit exceeded |

---

## WordPress Proxy Endpoint

The KPI Dashboard plugin exposes a WP REST proxy that the Gutenberg block calls. This keeps the Sanctum token server-side.

### GET /wp-json/kpi-dashboard/v1/summary

Available on the **WordPress** server (`http://localhost:8080`). Requires the caller to be a logged-in WordPress user (cookie or Application Password auth).

Internally, WordPress fetches `GET /api/kpi/summary` from Laravel using the stored Sanctum token and returns the same payload.

```http
GET /wp-json/kpi-dashboard/v1/summary
Cookie: wordpress_logged_in_...
```

Response shape is identical to `GET /api/kpi/summary`.

---

## Data Models

### KPI

| Field | Type | Description |
|---|---|---|
| `id` | integer | Primary key |
| `name` | string | Human-readable name |
| `slug` | string | URL-safe identifier |
| `unit` | string | Display unit (e.g. `$`, `%`, `users`) |
| `description` | string (nullable) | Optional description |
| `created_at` | ISO 8601 | Creation timestamp |
| `updated_at` | ISO 8601 | Last update timestamp |

### KPI Metric

| Field | Type | Description |
|---|---|---|
| `id` | integer | Primary key |
| `kpi_id` | integer | Foreign key to `kpis` |
| `value` | decimal | The recorded value |
| `recorded_at` | ISO 8601 | When the value was recorded |
