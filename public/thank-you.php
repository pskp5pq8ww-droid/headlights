<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/bookings.php';

$current      = '';
$page_title   = 'Booking Confirmed | ' . $SITE['name'];
$page_desc    = 'Your mobile headlight restoration booking has been received.';
$body_class   = 'thank-you-page booking-page';

$bookingId = clean_text($_GET['booking'] ?? '', 80);
$booking = $bookingId !== '' ? getBookingById($bookingId) : null;
$isPaid = $booking && ($booking['paymentStatus'] ?? '') === 'paid';

/*
 * Meta Pixel conversion — fired below once the booking is confirmed.
 *   - paid bookings  → Purchase (real amount paid)
 *   - quote/requests → Lead (estimated package price)
 * We also pass Advanced Matching data (email, phone, name, location). Meta
 * normalises + SHA-256 hashes these in the browser before sending, which
 * dramatically improves attribution and audience matching.
 */
$FB_PIXEL_ID = '1026998226936698';
$fbEvent = null;
if ($booking) {
    $pkg = $booking['packageSelected'] ?: 'Not sure / Quote';
    $qty = max(1, (int)($booking['numberOfHeadlights'] ?? 1));
    $price = $isPaid ? (float)$booking['amount'] : (float)package_price($pkg);
    $currency = $booking['currency'] ?: 'AUD';

    // ── Advanced Matching (hashed client-side by Meta) ──────────────────────
    $nameParts = array_values(array_filter(preg_split('/\s+/', trim((string)$booking['fullName'])) ?: []));
    $phoneDigits = preg_replace('/\D+/', '', (string)$booking['phone']);
    if (strlen($phoneDigits) === 10 && str_starts_with($phoneDigits, '0')) {
        $phoneDigits = '61' . substr($phoneDigits, 1); // AU mobile → E.164 country code
    }
    $country = strtolower(trim((string)$booking['addressCountry']));
    if ($country === '' || str_contains($country, 'austral')) $country = 'au';
    else $country = substr($country, 0, 2);

    $fbUser = array_filter([
        'em' => strtolower(trim((string)$booking['email'])),
        'ph' => $phoneDigits,
        'fn' => strtolower($nameParts[0] ?? ''),
        'ln' => strtolower(count($nameParts) > 1 ? end($nameParts) : ''),
        'ct' => strtolower(preg_replace('/[^a-z ]/i', '', (string)($booking['addressSuburb'] ?: $booking['addressOrSuburb']))),
        'st' => strtolower((string)$booking['addressState']),
        'zp' => preg_replace('/\s+/', '', (string)$booking['addressPostcode']),
        'country' => $country,
        'external_id' => (string)$booking['id'],
    ], fn($v) => $v !== '' && $v !== null);

    // ── Event payload (rich custom data) ────────────────────────────────────
    $fbEvent = [
        'name' => $isPaid ? 'Purchase' : 'Lead',
        'params' => array_filter([
            'value' => round($price, 2),
            'currency' => $currency,
            'content_name' => $pkg,
            'content_category' => 'Headlight Restoration',
            'content_type' => 'product',
            'content_ids' => [$pkg],
            'contents' => [['id' => $pkg, 'quantity' => $qty, 'item_price' => round($price, 2)]],
            'num_items' => $qty,
            'order_id' => (string)$booking['id'],
            'status' => $isPaid ? 'paid' : 'requested',
        ], fn($v) => $v !== '' && $v !== null),
        // eventID lets Meta de-duplicate against a future server-side (CAPI)
        // event and against page refreshes — keep it stable per booking.
        'eventID' => (string)$booking['id'],
    ];
}

include __DIR__ . '/includes/header.php';
?>
      <section class="section section-light thank-you-section" aria-labelledby="thank-you-title">
        <div class="container thank-you-shell">
          <div class="thank-you-panel success-card">
            <div class="success-check" aria-hidden="true">
              <svg viewBox="0 0 52 52"><circle cx="26" cy="26" r="24" fill="none"/><path fill="none" d="M14 27l8 8 16-16"/></svg>
            </div>

            <?php if ($isPaid): ?>
              <span class="offer-badge">Payment successful</span>
              <h1 id="thank-you-title">Booking confirmed</h1>
              <p class="thank-you-lede">Your payment was successful and your appointment has been saved.</p>

              <dl class="success-details">
                <div><dt>Name</dt><dd><?= htmlspecialchars($booking['fullName']) ?></dd></div>
                <div><dt>Service</dt><dd><?= htmlspecialchars($booking['packageSelected']) ?></dd></div>
                <div><dt>Date</dt><dd><?= htmlspecialchars($booking['preferredDate'] ?: 'To confirm') ?></dd></div>
                <div><dt>Time</dt><dd><?= htmlspecialchars($booking['preferredTimeWindow'] ?: 'To confirm') ?></dd></div>
                <div><dt>Total paid</dt><dd><?= htmlspecialchars(($booking['currency'] ?: 'AUD')) ?> $<?= number_format((float)$booking['amount'], 2) ?></dd></div>
                <div><dt>Booking ID</dt><dd class="mono"><?= htmlspecialchars($booking['id']) ?></dd></div>
              </dl>

              <div class="hero-actions">
                <a class="button button-primary" href="/">Back to Home</a>
                <?php if (!empty($booking['squareReceiptUrl'])): ?>
                  <a class="button button-secondary" href="<?= htmlspecialchars($booking['squareReceiptUrl']) ?>" target="_blank" rel="noopener">View Receipt</a>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <span class="offer-badge">Booking request received</span>
              <h1 id="thank-you-title">Thank you! Your booking request has been received.</h1>
              <p class="thank-you-lede">We'll contact you shortly to confirm your appointment and service details.</p>
              <?php if ($booking): ?>
                <dl class="success-details">
                  <div><dt>Service</dt><dd><?= htmlspecialchars($booking['packageSelected']) ?></dd></div>
                  <div><dt>Preferred</dt><dd><?= htmlspecialchars(trim(($booking['preferredDate'] ?? '') . ' ' . ($booking['preferredTimeWindow'] ?? ''))) ?></dd></div>
                  <div><dt>Booking ID</dt><dd class="mono"><?= htmlspecialchars($booking['id']) ?></dd></div>
                </dl>
              <?php endif; ?>
              <div class="hero-actions">
                <a class="button button-primary" href="/">Back to Home</a>
                <a class="button button-secondary" href="/contact">Contact Us</a>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </section>
<?php if ($fbEvent): ?>
      <!-- Meta Pixel conversion event -->
      <script>
        // Re-init with Advanced Matching now that we have the customer's details.
        fbq('init', <?= json_encode($FB_PIXEL_ID, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, <?= json_encode($fbUser, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>);
        fbq(
          'track',
          <?= json_encode($fbEvent['name'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
          <?= json_encode($fbEvent['params'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>,
          { eventID: <?= json_encode($fbEvent['eventID'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?> }
        );
      </script>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
