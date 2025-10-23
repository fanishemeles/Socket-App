<!-- // path: README.md -->
# SOCKET — Ethiopian EV Charging Finder

SOCKET is a PHP 8.3 + MySQL web application that helps Ethiopian EV drivers locate, filter, and subscribe to premium insights for charging stations across Addis Ababa and the wider country. The app ships with a responsive Bootstrap 5 UI, Leaflet-powered maps, and Chapa sandbox payment flows ready for Plesk hosting.

## Features

- Ethiopian phone registration and login supporting `+251`, `09`, and `07` prefixes.
- Automatic five-day free trial, followed by 30-day subscriptions paid through the Chapa sandbox API.
- Leaflet + OpenStreetMap map views with connector and status filters, near-me discovery, and responsive design.
- Admin console for CRUD management of charging stations.
- Progressive Web App manifest and service worker for offline-first caching.

## Project structure

```
app/
  config.php       # Environment loader and configuration helpers
  db.php           # PDO connection helper
  auth.php         # Session, authentication, and subscription utilities
  helpers.php      # Shared utilities (responses, phone normalization, etc.)
  chapa.php        # Chapa payment initialization + verification stubs
public/
  index.php        # Landing page with CTA
  login.php        # Phone/password login form
  register.php     # Registration with 5-day trial provisioning
  logout.php       # Session termination
  dashboard.php    # Authenticated dashboard, subscription status, Leaflet map
  map.php          # Public map-only view
  admin/stations.php               # Admin station management
  api/stations.php                  # JSON feed for station data
  api/payments/init.php             # Authenticated payment initiation endpoint
  api/payments/webhook.php          # Chapa webhook handler
  assets/style.css                  # CAFU-inspired dark theme overrides
  manifest.json                     # PWA manifest
  service-worker.js                 # Offline cache logic
sql/
  schema.sql       # Database schema, seed admin, and sample stations
```

## Requirements

- PHP 8.3 (compatible with Plesk Apache + FPM environments)
- MySQL 5.7+ or MariaDB 10+
- cURL enabled for live Chapa integration (falls back to sandbox stub if keys missing)

## Local configuration (.env)

Create a `.env` file at the project root (same level as the `app` and `public` folders) with the following keys. Values here are examples—replace them with your own credentials.

```
BASE_URL=https://example.com
DB_HOST=localhost
DB_NAME=socket
DB_USER=socket_user
DB_PASS=super_secure_password
CHAPA_PUBLIC=YOUR_CHAPA_PUBLIC_KEY
CHAPA_SECRET=YOUR_CHAPA_SECRET_KEY
APP_TIMEZONE=Africa/Addis_Ababa
FREE_TRIAL_DAYS=5
SUBSCRIPTION_DAYS=30
SUBSCRIPTION_ETB=150
```

If `.env` is missing, sensible defaults are used (`localhost`, empty password, etc.).

## Database setup

1. Create a new MySQL database and user in Plesk (or via CLI).
2. Import the schema and seeds:
   ```bash
   mysql -u <user> -p <database> < sql/schema.sql
   ```
   This creates the required tables, seeds an administrator (`+251900000000` / `Admin123`), and inserts example stations in Addis Ababa.
3. Update your `.env` file with the database credentials and revisit the site to confirm connectivity.

## Deploying on Plesk

1. Upload the repository contents into your domain’s `httpdocs/` directory. Ensure the `public/` folder is accessible via the web root or configure a hosting setting to point to `public/` if available.
2. Confirm PHP 8.3 is selected in Hosting Settings and enable `allow_url_fopen`/`curl` if you plan to connect to Chapa.
3. Create the `.env` file described earlier directly in `httpdocs/`.
4. Import `sql/schema.sql` into your MySQL database using Plesk’s phpMyAdmin or the CLI.
5. Visit `/public/register.php` to create user accounts. The seeded admin can log in via `/public/login.php`.
6. Test the Chapa sandbox flow: click **Upgrade with Chapa** on the dashboard, which will redirect to the checkout URL returned by the sandbox (or a fallback link if API keys are placeholders).

## Admin access

- Login at `/public/login.php` using the seeded administrator credentials or any account whose `role` column is `admin`.
- Manage stations at `/public/admin/stations.php`.

## Chapa sandbox notes

- When real sandbox keys are provided, the app posts to `https://api.chapa.co/v1/transaction/initialize` and awaits the checkout URL.
- Without valid keys, the helper returns a fallback checkout link pointing back to the dashboard so you can continue testing without live calls.
- Configure your webhook endpoint in the Chapa dashboard to `https://your-domain.com/public/api/payments/webhook.php` for automatic subscription extensions.

## Progressive Web App

- `manifest.json` supplies app metadata and colors for install prompts.
- `service-worker.js` precaches the core interface for basic offline support (Bootstrap CDN requests are cached on first visit).

## Development tips

- Run `php -S localhost:8000 -t public` from the project root to spin up a built-in PHP server.
- Use `php -l public/*.php` (and similar globs) for linting during development.

## License

This project is provided for demonstration purposes. Adapt authentication, payment handling, and station data integrations before using in production.
