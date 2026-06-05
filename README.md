# Shining Headlights Australia

Premium mobile headlight restoration landing page for Brisbane, built as a static HTML/CSS/JS site with PHP booking/admin handlers for Hostinger.

## Current Project Type

This is a Hostinger-friendly static/PHP project.

It is not a Laravel app in production. The old Laravel scaffold has been archived in `_archive/old-laravel-scaffold/`.

## Main Structure

- `public/index.php` - production entry file, injects private Maps config
- `public/index.html` - landing page markup
- `public/form.php` - booking form handler
- `public/admin/` - booking admin viewer
- `public/css/styles.css` - site styles
- `public/js/config.js` - public business/content config
- `public/js/main.js` - frontend behavior
- `public/assets/` - images and SVG assets
- `_private/maps.example.php` - template for private Maps key config
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

## Private Google Maps Config

Do not put the real key in public JS or HTML.

1. Copy `_private/maps.example.php` to `_private/maps.php`.
2. Add your restricted key to `_private/maps.php`.
3. Keep `_private/maps.php` out of Git.

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
