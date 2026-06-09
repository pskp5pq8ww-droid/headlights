<?php
require_once __DIR__ . '/includes/config.php';
$current      = '';
$page_title   = 'Thank You | ' . $SITE['name'];
$page_desc    = 'Your mobile headlight restoration booking request has been received.';
$body_class   = 'thank-you-page booking-page';
include __DIR__ . '/includes/header.php';
?>
      <section class="section section-light thank-you-section" aria-labelledby="thank-you-title">
        <div class="container thank-you-shell">
          <div class="thank-you-panel">
            <span class="eofy-badge">Booking request received</span>
            <h1 id="thank-you-title">Thank you! Your booking request has been received.</h1>
            <p class="thank-you-lede">We'll contact you shortly to confirm your appointment and service details.</p>
            <p>Our mobile headlight restoration team will review your request and get in touch as soon as possible.</p>
            <div class="hero-actions">
              <a class="button button-primary" href="/">Back to Home</a>
              <a class="button button-secondary" href="/contact">Contact Us</a>
            </div>
          </div>
        </div>
      </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
