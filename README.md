# Shining Headlights Australia Landing Page

Premium static landing page for a Brisbane mobile headlight restoration service.

## Run Locally

Open `index.html` directly in a browser, or run a local static server:

```bash
python3 -m http.server 4173
```

Then visit `http://localhost:4173`.

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
