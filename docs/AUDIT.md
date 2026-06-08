# Project Audit

## Diagnosis

The project was unstable because it mixed two deployment models:

- Laravel-style files: `app/`, `routes/`, `resources/`, `bootstrap/`, `composer.json`.
- Static/PHP files: `index.html`, `form.php`, `public/assets`, `public/css`, `public/js`.

Hostinger was sometimes serving the wrong entry file. If `index.html` loaded directly, Google Maps could not receive the private API key because the key injection happens through `index.php`. If the old Laravel `public/index.php` loaded, it tried to require `vendor/autoload.php`, which causes a blank page when Laravel dependencies are not installed.

## Reference Project Pattern

The `Documents/king barber` project uses a simpler static layout:

- top-level HTML pages
- separate `css/` files
- separate `js/` files
- assets in one predictable folder
- no mixed framework routing for the landing page
- a small local preview server

This project now follows the same stability principle while keeping Shining Headlights branding and functionality.

## Preserved Features

- landing page
- mobile menu
- countdown
- before/after visuals
- booking wizard
- manual service address or suburb field
- PHP booking endpoint
- JSON booking storage outside the public web root
- email notification
- secured admin booking dashboard
- admin JSON APIs

## Archived Files

Old Laravel/scaffold and duplicate root static files were moved into `_archive/` instead of being deleted.

## Current Booking/Admin Notes

- Google address autocomplete and map preview are part of the booking flow when a Maps key is configured.
- Bookings are stored as individual JSON files in `Storagehighlights/bookings`.
- Admin credentials are required through environment variables or `_private/admin.php`.
- See `docs/BOOKING_ADMIN.md` for deployment, backup and testing details.
