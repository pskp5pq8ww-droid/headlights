# Shining Headlights Australia Landing Page

Premium static landing page for a Brisbane mobile headlight restoration service.

## Run Locally

Open `index.html` directly in a browser, or run a local static server:

```bash
python3 -m http.server 4173
```

Then visit `http://localhost:4173`.

## Deploy on Hostinger

Upload the project files to the `public_html` folder in Hostinger File Manager or deploy them by FTP.

Required files and folders:

- `index.html`
- `.htaccess`
- `form.php`
- `assets/`
- `css/`
- `js/`

Do not upload:

- `.git/`
- `.github/`
- local notes or screenshots

After uploading, open your domain and test the booking form. The form posts to `form.php`, which uses PHP `mail()` on the Hostinger server.

Before going live, update these placeholders:

- `hello@shiningheadlights.com.au` in `form.php`
- `contact.email` and `contact.phoneDisplay` in `js/config.js`
- the canonical URL and structured data in `index.html`

For best email delivery, create the sender email address inside Hostinger first, then use that same address in the `From:` header in `form.php`.

## Edit Business Details

Update `js/config.js` for:

- Packages and inclusions
- Service areas
- FAQs
- Phone number and email
- Promotional offer copy
- Booking endpoint

Replace visual assets in `assets/`:

- `logo-clean.png` for the site logo
- `hero-headlight-before-after.png` for the main hero visual
- `result-before-*.svg` and `result-after-*.svg` for before/after examples

The booking form currently shows a success state locally. Add a real form endpoint in `contact.bookingEndpoint` when the booking backend is ready.
