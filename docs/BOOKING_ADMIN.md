# Booking and Admin System

## Scope

The booking flow uses manual address entry. Google address autocomplete and map selection are intentionally disabled.

The system stores bookings in JSON files instead of a database, so it is compatible with a simple Hostinger PHP/static deployment.

## Public Booking Flow

- Page: `/book`
- Form endpoint: `/form`
- Public API endpoint: `/api/bookings`
- Required fields: name, phone, email, service address or suburb, vehicle, preferred date, preferred time window and package.
- Optional fields: headlight condition, vehicle location type, number of headlights, preferred contact method, message and photos.

Successful submissions create one file per booking:

```text
Storagehighlights/bookings/booking_bk_YYYYMMDDHHMMSS_xxxxxxxxxxxx.json
```

## Storage

Default private storage path:

```text
Storagehighlights/
```

The app resolves it one level above the public document root. On Hostinger this should normally be:

```text
/home/YOUR_USER/Storagehighlights/
```

Override it with:

```text
BOOKING_STORAGE_PATH=/home/YOUR_USER/Storagehighlights
```

The app creates:

```text
Storagehighlights/bookings/
Storagehighlights/booking-uploads/
Storagehighlights/logs/
```

Keep this folder outside `public_html`.

## Admin

- Login: `/admin`
- Dashboard: `/dashboard`
- API: `/api/admin/bookings`
- API by ID: `/api/admin/bookings/{booking_id}`
- Metrics API: `/api/admin/metrics`

Admin credentials must be configured before login works.

Environment variable option:

```text
ADMIN_USERNAME=your-admin-user
ADMIN_PASSWORD=your-strong-password
ADMIN_EMAIL=bookings@yourdomain.com
```

Private file option:

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

## Dashboard Features

- Metrics: today's bookings, new leads, confirmed jobs, completed jobs, estimated revenue, follow-ups, weekly/monthly counts and most selected package.
- Calendar: monthly day view with booking counts.
- Daily list: bookings for selected date.
- Booking table: search, status filter, package filter, date filter and sorting.
- Booking detail: customer info, status, preferred date/time, admin notes and follow-up fields.

## Backups

Back up this folder regularly:

```text
Storagehighlights/
```

To restore, upload the backed up `Storagehighlights/` folder back outside `public_html` with the same folder structure.

Invalid booking JSON files are not deleted. They are renamed with a `.broken-YYYYMMDDHHMMSS` suffix and an error is written to:

```text
Storagehighlights/logs/errors.log
```

## Known Limits

- No payment is charged in this flow.
- Email depends on Hostinger's PHP `mail()` configuration.
- Uploaded photos are stored privately and listed in booking data, but they are not publicly served from the dashboard.
- This is a file-based system, so very high booking volume should eventually move to a database.
