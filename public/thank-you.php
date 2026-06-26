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

include __DIR__ . '/includes/header.php';
?>
      <section class="section section-light thank-you-section" aria-labelledby="thank-you-title">
        <div class="container thank-you-shell">
          <div class="thank-you-panel success-card">
            <div class="success-check" aria-hidden="true">
              <svg viewBox="0 0 52 52"><circle cx="26" cy="26" r="24" fill="none"/><path fill="none" d="M14 27l8 8 16-16"/></svg>
            </div>

            <?php if ($isPaid): ?>
              <span class="eofy-badge">Payment successful</span>
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
              <span class="eofy-badge">Booking request received</span>
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
<?php include __DIR__ . '/includes/footer.php'; ?>
