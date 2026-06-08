# Hostinger Deployment

## Project Type

This project is a static HTML/CSS/JS landing page with PHP handlers for booking/admin.
It is not a Laravel deployment.

## Recommended Hostinger Settings

- Hosting type: PHP / static website
- Document root: `public`
- Build command: none
- Package manager: none required
- Output directory: `public`
- Entry file: `public/index.php`
- Booking endpoint: `public/form.php`
- Public booking API: `/api/bookings`
- Admin login: `/admin`
- Admin dashboard: `/dashboard`

If Hostinger cannot point the domain directly to `/public`, upload the whole repository and keep the root `.htaccess`; it rewrites traffic into `/public`.

## Private Storage

Bookings are stored as JSON files. No database migration is needed.

Default Hostinger path:

```text
/home/YOUR_USER/Storagehighlights/
```

The app creates:

```text
Storagehighlights/bookings/
Storagehighlights/booking-uploads/
Storagehighlights/logs/
```

Recommended permissions:

```text
Storagehighlights directories: 700
booking JSON files: 600
uploaded photos: 600
```

If Hostinger uses a different private location, set:

```text
BOOKING_STORAGE_PATH=/home/YOUR_USER/Storagehighlights
```

Keep this folder outside `public_html`.

## Google Maps Autocomplete

The booking page can load Google Maps autocomplete and a map preview.

Required Google Cloud APIs:

```text
Maps JavaScript API
Places API
Geocoding API
```

Preferred option: create this private PHP config file outside `public_html`, at the same level as `public_html`:

```text
private/maps.php
```

Example:

```php
<?php
return [
  'google_maps_api_key' => 'your-browser-key',
];
```

Do not put this file inside `public_html` and do not commit it to GitHub.

Environment variable fallback:

```text
GOOGLE_MAPS_API_KEY=your-browser-key
NEXT_PUBLIC_GOOGLE_MAPS_API_KEY=your-browser-key
```

`NEXT_PUBLIC_GOOGLE_MAPS_API_KEY` is visible to browser JavaScript, so use it only as a fallback for frontend-style deployments. This PHP deployment should prefer `private/maps.php` or `GOOGLE_MAPS_API_KEY`.

Legacy fallback: `_private/maps.php` outside `public_html` is still supported.

Restrict the key by website referrer before going live.

## Admin Credentials

Preferred option: configure environment variables in Hostinger:

```text
ADMIN_USERNAME=your-admin-user
ADMIN_PASSWORD=your-strong-password
ADMIN_EMAIL=bookings@yourdomain.com
```

Alternative option: create this private file outside `public_html`:

```text
_private/admin.php
```

Example:

```php
<?php
return [
  'username' => 'your-admin-user',
  'password_hash' => password_hash('your-strong-password', PASSWORD_BCRYPT),
];
```

Do not commit `_private/admin.php`.

## GitHub FTP Deploy

The workflow deploys only `public/` to `/public_html/`.

GitHub secrets needed:

- `FTP_HOST`
- `FTP_USERNAME`
- `FTP_PASSWORD`

When using FTP deploy, manually create `Storagehighlights/`, `private/maps.php` and `_private/admin.php` one level above the public document root, or use Hostinger File Manager to create them outside `public_html`.

## Manual Upload

Upload the contents of `public/` into `public_html`.

Also create these outside `public_html`:

```text
Storagehighlights/
private/maps.php
_private/admin.php
```

## Live Update Checklist

- Clear Hostinger cache if enabled.
- Open the site in a private browser window.
- Confirm `css/styles.css`, `css/admin.css`, `js/site.js` and `js/booking.js` are loading from the latest upload.
- Test booking form submission.
- Confirm a JSON file appears in `Storagehighlights/bookings`.
- Test `/admin` login.
- Test `/dashboard` booking status and notes updates.
