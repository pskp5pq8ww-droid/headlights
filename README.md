# Shining Headlights Australia

Premium mobile headlight restoration landing page for Brisbane, built as a static HTML/CSS/JS site with PHP booking/admin handlers for Hostinger.

## Current Project Type

This is a Hostinger-friendly static/PHP project.

It is not a Laravel app in production. The old Laravel scaffold has been archived in `_archive/old-laravel-scaffold/`.

## Main Structure

- `public/index.php` - production entry file
- `public/form.php` - booking form handler
- `public/book.php` - public booking wizard
- `public/dashboard.php` - secured booking admin dashboard
- `public/api/` - public/admin JSON endpoints
- `public/includes/bookings.php` - file-based booking storage helpers
- `public/css/styles.css` - site styles
- `public/css/admin.css` - admin styles
- `public/js/config.js` - public business/content config
- `public/js/site.js` - frontend behavior
- `public/js/booking.js` - booking wizard behavior
- `public/assets/` - images and SVG assets
- `_private/admin.php` - optional private admin credentials file, not committed
- `Storagehighlights/` - private booking JSON storage on Hostinger, outside `public_html`
- `_archive/` - old or duplicated files kept for safety
- `docs/` - deploy, audit and testing notes

## Run Locally

```bash
npm run dev
```

Then visit:

```text
http://localhost:8123
```

For a PHP-accurate local preview, serve the `public/` folder with PHP if available:

```bash
php -S localhost:8123 -t public
```

## Hostinger Deploy

Recommended:

- document root: `public`
- build command: none
- output directory: `public`
- entry file: `index.php`

If using GitHub FTP deploy, `.github/workflows/deploy.yml` uploads only `public/` to `/public_html/`.

Read [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) before going live.

## Booking and Admin

The booking flow uses manual service address or suburb entry. It does not require Google Maps or a database.

Before using `/admin`, configure admin credentials with `ADMIN_USERNAME` and `ADMIN_PASSWORD`, or create `_private/admin.php` outside `public_html`.

Read [docs/BOOKING_ADMIN.md](docs/BOOKING_ADMIN.md) for storage, backup, API and dashboard details.

## Edit Business Details

Update `public/js/config.js` for:

- packages and inclusions
- service areas
- FAQs
- phone number and email
- promotional offer copy
- booking endpoint

Replace visual assets in `public/assets/`.

## Testing

Use [docs/TESTING.md](docs/TESTING.md) after every deploy.
