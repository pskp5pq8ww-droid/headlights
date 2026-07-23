# Square Payments — Booking + Admin (Production)

This project is a **PHP site on Hostinger** (Apache + clean URLs). Payments use the
**Square Payments REST API via cURL** (no Composer/SDK needed) and the
**Square Web Payments SDK** in the browser. Production credentials live outside
`public_html`.

---

## A. Summary of changes

- Added a dedicated **Review** step followed by a **Payment** step to the booking wizard on `/book`.
- Card is collected with Square Web Payments SDK; the browser only ever sends a
  one-time `sourceId` token + booking data to the backend.
- New backend endpoint charges the card with Square and **only saves the booking
  as `paid` if the charge succeeds**. Price is validated server-side.
- Booking records gained payment fields (`paymentStatus`, `amount`, `currency`,
  `squarePaymentId`, `squareOrderId`, `squareReceiptUrl`, `cardBrand`, `cardLast4`, `paidAt`).
- `/thank-you` is now a minimalist **success screen** showing the confirmation + receipt.
- Admin dashboard shows **paid revenue**, **paid count**, a **payment badge** per booking,
  and a **Payment** card (Square IDs + receipt) in the booking detail.
- Quote-only services (and any case where Square is not configured) gracefully fall
  back to the original "request booking" flow — nothing breaks.
- The old simulated `pay.php` was retired (returns HTTP 410).

## B. Files modified

- `public/includes/bookings.php` — payment fields, `normalize_payment_status()`,
  `booking_amount_cents()`, `updateBookingPayment()`, paid revenue in stats, shared `send_booking_email()`.
- `public/includes/config.php` — asset version and shared campaign countdown target.
- `public/includes/footer.php` — loads `square-payment.js` on booking pages.
- `public/form.php` — removed duplicate `send_booking_email()` (now shared).
- `public/book.php` — review/payment UI + pricing/config passed to JS.
- `public/js/booking.js` — payment step, summary, Square submit + fallback.
- `public/thank-you.php` — payment-aware success screen.
- `public/dashboard.php` — payment stats, badges, payment detail card.
- `public/css/styles.css`, `public/css/admin.css` — payment + success styles.
- `public/pay.php` — retired (410).
- `.gitignore` — ignore `_private/square.php`.

## C. Files created

- `public/includes/square.php` — Square config + REST charge + payment storage/logging.
- `public/api/square-config.php` — public config for the browser (no secret token).
- `public/api/payments/square.php` — create-payment endpoint.
- `public/js/square-payment.js` — Web Payments SDK wrapper.
- `_private/square.example.php` — credentials template.
- `_private/square.php` — real production creds (gitignored; never commit).
- `.env.example` — environment reference.
- `docs/SQUARE_PAYMENTS.md` — this file.

## D. Production Credentials (Hostinger)

Primary method on this project = the private PHP file `/_private/square.php`.
On Hostinger it should live at `/home/u613502604/_private/square.php`, outside
`public_html`. Alternatively set these as real env vars in hPanel:

```
SQUARE_ENVIRONMENT=production
SQUARE_APPLICATION_ID=<your production Application ID>
SQUARE_ACCESS_TOKEN=<your production Access token — keep secret, never commit>
SQUARE_LOCATION_ID=<your production Location ID>
SQUARE_CURRENCY=AUD
```

The `SQUARE_ACCESS_TOKEN` is read **only** by the backend and never reaches the browser.

The public Web Payments SDK URL is derived from the `SQUARE_APPLICATION_ID`
prefix, so a production app id (`sq0idp-...`) always loads Square's production
SDK. This protects the booking form from a stale `SQUARE_ENVIRONMENT` value.

## E. Folders on Hostinger

Storage base defaults to `<project>/Storagehighlights` (or `BOOKING_STORAGE_PATH`).
Subfolders are **auto-created** on first use:

```
storage/bookings      storage/payments      storage/logs
storage/booking-uploads
```

`square-payments.log` is written under `storage/logs`. Each folder gets a
`Deny from all` `.htaccess` automatically and files are `chmod 0600`.

## F. SSH commands (only if you want explicit paths)

```bash
BASE=/home/u613502604/domains/TU_DOMINIO/storage
mkdir -p "$BASE"/{bookings,payments,logs,admin,backups,booking-uploads}
chmod 700 "$BASE" "$BASE"/payments "$BASE"/logs "$BASE"/admin
chmod 755 "$BASE"/bookings
# then set BOOKING_STORAGE_PATH=$BASE in hPanel env vars
```

(700 on payments/logs/admin keeps them private even if the web root moves.)

## G. Install dependencies

**None required** — no Composer, no npm runtime deps. The integration uses PHP
cURL (standard on Hostinger) and Square's CDN-hosted Web Payments SDK.

## H. Build / restart

Static PHP — nothing to build. Just upload the changed files. If you changed
`ASSET_VER`, browsers pick up new CSS/JS automatically. No process to restart.

## I. How to test in Production

1. Confirm `/api/square-config` returns `environment: production`, your production
   `applicationId`, and the production `locationId`.
2. Open `https://TU_DOMINIO/book`.
3. Fill steps 1–3 and pick a priced service.
4. Step 4 shows the summary + Square card field. Enter a real card for a small
   live test amount.
5. Click **Pay & Confirm Booking** → redirected to `/thank-you` success screen.
6. Confirm the booking appears in the Square Dashboard → Transactions.
7. Refund the test payment from the Square Dashboard if needed.

## J. Check a saved booking

- Admin dashboard `/dashboard` → booking shows a green **Paid** badge + amount.
- File: `storage/bookings/booking_<id>.json` → `"paymentStatus": "paid"`, `squarePaymentId`.
- Payment audit: `storage/payments/payment_*.json` (paid + failed attempts).
- Log: `storage/logs/square-payments.log`.

## K. Admin access

`https://TU_DOMINIO/admin` → log in (credentials from `ADMIN_USERNAME`/`ADMIN_PASSWORD`
or `/_private/admin.php`) → `/dashboard`. Session-based, bcrypt, `noindex`.

## L. Production checklist

1. `/_private/square.php` uses production `application_id`, `access_token`, and
   `location_id`.
2. Hostinger environment variables are either empty or set to the same production
   values. hPanel env vars override the private PHP file.
3. `/api/square-config` shows `environment: production`.
4. HTTPS is enforced.
```
