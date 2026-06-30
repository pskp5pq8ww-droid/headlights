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
          <a href="/terms">Terms &amp; Conditions</a>
          <a href="/contact">Contact</a>
        </div>
      </div>
      <div class="container footer-note">
        <p>Results may vary depending on headlight condition, age, previous damage and exposure. UV protection is recommended for longer-lasting results.</p>
        <p>&copy; <span data-year><?= date('Y') ?></span> <?= htmlspecialchars($SITE['name']) ?>.</p>
        <p class="footer-admin"><a href="/admin">Staff / Admin login</a></p>
      </div>
    </footer>

    <a class="mobile-sticky-cta" href="/book" aria-label="Claim the EOFY mobile headlight restoration offer">Claim EOFY Offer</a>

    <div class="support-widget" data-support-widget>
      <button class="support-fab" type="button" aria-expanded="false" aria-controls="supportPanel" data-support-toggle>
        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8Z"/></svg>
        <span>Help</span>
      </button>
      <section class="support-panel" id="supportPanel" aria-label="Send us a message" hidden>
        <div class="support-panel-head">
          <div>
            <strong>Send a message</strong>
            <small>Questions, complaints or claims</small>
          </div>
          <button class="support-close" type="button" aria-label="Close message form" data-support-close>
            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
          </button>
        </div>
        <form class="support-form" data-support-form>
          <input type="hidden" name="page" value="" data-support-page />
          <label>Type
            <select name="category">
              <option value="question">Question</option>
              <option value="complaint">Complaint</option>
              <option value="claim">Claim</option>
              <option value="booking_help">Booking help</option>
              <option value="other">Other</option>
            </select>
          </label>
          <label>Name
            <input name="name" autocomplete="name" />
          </label>
          <label>Email
            <input name="email" type="email" autocomplete="email" placeholder="Email" />
          </label>
          <label>Phone
            <input name="phone" autocomplete="tel" placeholder="Phone" />
          </label>
          <label>Subject
            <input name="subject" maxlength="160" />
          </label>
          <label>Message
            <textarea name="message" rows="4" required></textarea>
          </label>
          <button class="support-submit" type="submit">Send ticket</button>
          <p class="support-status" data-support-status role="status" aria-live="polite"></p>
        </form>
      </section>
    </div>

    <script src="<?= asset('vendor/gsap/gsap.min.js') ?>"></script>
    <script src="<?= asset('js/gsapAnimations.js') ?>"></script>
    <script src="<?= asset('js/site.js') ?>"></script>
    <script src="<?= asset('js/support-widget.js') ?>"></script>
<?php if (in_array('countdown', $page_scripts, true)): ?>
    <script src="<?= asset('js/countdown.js') ?>"></script>
<?php endif; ?>
<?php if (in_array('booking', $page_scripts, true)): ?>
    <script src="<?= asset('js/config.js') ?>"></script>
<?php if (in_array('square', $page_scripts, true)): ?>
    <script src="<?= asset('js/square-payment.js') ?>"></script>
<?php endif; ?>
    <script src="<?= asset('js/booking.js') ?>"></script>
<?php $mapsKey = !empty($page_maps) ? maps_api_key() : ''; ?>
<?php if ($mapsKey !== ''): ?>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?= rawurlencode($mapsKey) ?>&libraries=places&callback=initGoogleBookingAutocomplete"></script>
<?php endif; ?>
<?php endif; ?>

    <script>
    /* Non-blocking analytics beacon — fire and forget, never delays the page. */
    (function () {
      try {
        var payload = JSON.stringify({ p: location.pathname, r: document.referrer });
        if (navigator.sendBeacon) {
          navigator.sendBeacon("/api/track", new Blob([payload], { type: "application/json" }));
        } else {
          fetch("/api/track", { method: "POST", body: payload, headers: { "Content-Type": "application/json" }, keepalive: true });
        }
      } catch (e) {}
    })();
    </script>
  </body>
</html>
