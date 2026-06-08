<?php
$page_scripts = $page_scripts ?? [];
?>
    </main>

    <footer class="site-footer">
      <div class="container footer-grid">
        <div>
          <div class="footer-brand-lockup">
            <img src="<?= asset('assets/shining-headlights-isotype.svg') ?>" alt="" width="58" height="68" loading="lazy" aria-hidden="true" />
            <span>
              <strong><?= htmlspecialchars($SITE['name']) ?></strong>
              <small><?= htmlspecialchars($SITE['tagline']) ?></small>
            </span>
          </div>
          <p><?= htmlspecialchars($SITE['tagline']) ?></p>
          <p><?= htmlspecialchars($SITE['region']) ?></p>
        </div>
        <div>
          <h2>Contact</h2>
          <a href="<?= htmlspecialchars($SITE['phone_href']) ?>"><?= htmlspecialchars($SITE['phone_label']) ?></a>
          <a href="mailto:<?= htmlspecialchars($SITE['email']) ?>"><?= htmlspecialchars($SITE['email']) ?></a>
        </div>
        <div>
          <h2>Explore</h2>
          <a href="/eofy-offer">EOFY Offer</a>
          <a href="/services">Services</a>
          <a href="/pricing">Pricing</a>
          <a href="/faq">FAQ</a>
          <a href="/contact">Contact</a>
        </div>
      </div>
      <div class="container footer-note">
        <p>Results may vary depending on headlight condition, age, previous damage and exposure. UV protection is recommended for longer-lasting results.</p>
        <p>&copy; <span data-year><?= date('Y') ?></span> <?= htmlspecialchars($SITE['name']) ?>.</p>
      </div>
    </footer>

    <a class="mobile-sticky-cta" href="/book" aria-label="Claim the EOFY mobile headlight restoration offer">Claim EOFY Offer</a>

    <script src="<?= asset('vendor/gsap/gsap.min.js') ?>"></script>
    <script src="<?= asset('js/gsapAnimations.js') ?>"></script>
    <script src="<?= asset('js/site.js') ?>"></script>
<?php if (in_array('countdown', $page_scripts, true)): ?>
    <script src="<?= asset('js/countdown.js') ?>"></script>
<?php endif; ?>
<?php if (in_array('booking', $page_scripts, true)): ?>
    <script src="<?= asset('js/config.js') ?>"></script>
    <script src="<?= asset('js/booking.js') ?>"></script>
<?php $mapsKey = !empty($page_maps) ? maps_api_key() : ''; ?>
<?php if ($mapsKey !== ''): ?>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?= rawurlencode($mapsKey) ?>&libraries=places&callback=initGoogleBookingAutocomplete"></script>
<?php endif; ?>
<?php endif; ?>
  </body>
</html>
