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
- Admin panel: `public/admin/`

If Hostinger cannot point the domain directly to `/public`, upload the whole repository and keep the root `.htaccess`; it rewrites traffic into `/public`.

## Google Maps Key

Keep the real Google Maps key outside the public web root.

1. Copy `_private/maps.example.php` to `_private/maps.php`.
2. Paste the restricted Google Maps key into `_private/maps.php`.
3. In Google Cloud Console, restrict the key by domain and APIs.
4. Required APIs: Maps JavaScript API and Places API.

Do not commit `_private/maps.php` to a public repository.

## GitHub FTP Deploy

The workflow deploys only `public/` to `/public_html/`.

GitHub secrets needed:

- `FTP_HOST`
- `FTP_USERNAME`
- `FTP_PASSWORD`

When using FTP deploy, manually create `_private/maps.php` on Hostinger one level above the public document root, or use Hostinger File Manager to upload it outside `public_html`.

## Manual Upload

Upload the contents of `public/` into `public_html`.

Also create this private file outside `public_html`:

```text
_private/maps.php
```

Use `_private/maps.example.php` as the template.

## Live Update Checklist

- Clear Hostinger cache if enabled.
- Open the site in a private browser window.
- Confirm page source contains `<meta name="gmap-key"`.
- Confirm `css/styles.css` and `js/main.js` are loading from the latest upload.
- Test booking form submission.
- Test `/admin/`.
