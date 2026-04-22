# WordPress Setup Guide

This guide walks through installing WordPress, activating the KPI Dashboard plugin, and connecting it to Laravel.

## 1. Start the Stack

```bash
./kpi.sh start
```

Wait until all containers are healthy (`./kpi.sh status`).

## 2. Run the WordPress Install Wizard

1. Open `http://localhost:8080/wp-admin` in your browser.
2. Choose a language and click **Continue**.
3. Fill in:
   - Site Title: anything (e.g. "KPI Dashboard")
   - Username: choose an admin username
   - Password: choose a strong password
   - Email: your email
4. Click **Install WordPress**.
5. Log in with the credentials you just set.

## 3. Activate the KPI Dashboard Plugin

The plugin is mounted from `wordpress/plugins/kpi-dashboard/` via Docker volume.

1. Go to **Plugins > Installed Plugins**.
2. Find **KPI Dashboard** and click **Activate**.

## 4. Generate a WordPress Application Password

Application Passwords are built into WordPress 5.6+. No plugin is needed.

1. Go to **Users > Profile** (or click your username in the top right).
2. Scroll down to the **Application Passwords** section.
3. Enter a name (e.g. "Laravel KPI") and click **Add New Application Password**.
4. Copy the generated password (format: `xxxx xxxx xxxx xxxx xxxx xxxx`). You will not see it again.

## 5. Configure the Plugin

1. Go to **Settings > KPI Dashboard**.
2. Set **Laravel API Base URL** to `http://localhost:8081` (or your production Laravel URL).
3. Click **Save Settings**.

## 6. Authenticate with Laravel (Token Exchange)

This step exchanges your WP Application Password for a Sanctum token. The Application Password is verified by Laravel against the WP REST API and is never stored.

1. Scroll down to the **Authenticate via WordPress Application Password** section.
2. Enter your WordPress **username** (pre-filled with your current login).
3. Paste the **Application Password** generated in step 4.
4. Click **Get Token from Laravel**.

On success you will see: "Sanctum token obtained and saved successfully." The token is now stored server-side and used for all KPI requests.

## 7. Add the KPI Dashboard to a Page

**Option A: Gutenberg block**

1. Create or edit a page.
2. Click the **+** block inserter and search for "KPI Dashboard".
3. Add the block. Save and view the page.

**Option B: Shortcode**

Add `[kpi_dashboard]` anywhere in a post or page.

## 8. Verify the Dashboard Loads

View the page as a logged-in user. You should see KPI cards with live data from Laravel, including sparkline charts. The dashboard auto-refreshes every 60 seconds.

If you see an error, check:
- Laravel is running (`./kpi.sh status`)
- The API URL is correct (no trailing slash)
- The token exchange completed successfully (re-run step 6 if needed)

## CORS Note

Laravel is configured to allow requests from `http://localhost:8080`. In production, update `config/cors.php` in the Laravel app to include your WordPress domain.

## Production Considerations

- Use HTTPS for both WordPress and Laravel.
- Generate a fresh Application Password in production and run the token exchange.
- Set `APP_ENV=production` and `APP_DEBUG=false` in the Laravel `.env`.
- Restrict the WP Application Password to the minimum required role.
