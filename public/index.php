<?php
require_once __DIR__ . '/includes/config.php';
$current      = 'home';
$page_title   = '$99 Limited-Time Headlight Restoration | ' . $SITE['name'];
$page_desc    = 'Special limited-time offer: mobile headlight restoration for $99, was $220. Brisbane mobile service with countdown.';
$page_scripts = ['countdown'];
$body_class   = 'landing-home';
include __DIR__ . '/includes/header.php';
?>
      <section class="offer-landing-hero" id="home" aria-labelledby="hero-title">
        <div class="hero-video-bg" aria-hidden="true">
          <video class="hero-video" autoplay muted loop playsinline preload="auto" disablepictureinpicture>
            <source src="<?= asset('assets/video/headlights-hero-loop.mp4') ?>" type="video/mp4" />
          </video>
        </div>
        <script>
        (function () {
          var v = document.querySelector('.hero-video');
          if (!v) return;
          // Muted autoplay is allowed by every browser; force it and keep retrying.
          v.muted = true; v.defaultMuted = true; v.setAttribute('muted', '');
          var play = function () { var p = v.play(); if (p && p.catch) p.catch(function () {}); };
          play();
          window.addEventListener('load', play);
          document.addEventListener('visibilitychange', function () { if (!document.hidden) play(); });
          // Last-resort fallback if a browser still blocks it (e.g. iOS Low Power Mode).
          ['pointerdown', 'touchstart', 'click', 'scroll', 'keydown'].forEach(function (ev) {
            window.addEventListener(ev, play, { once: true, passive: true });
          });
        })();
        </script>
        <div class="hero-video-overlay" aria-hidden="true"></div>
        <div class="hero-ambient" aria-hidden="true"></div>

        <div class="container offer-landing-inner">
          <div class="offer-hero-copy">
            <span class="sale-pill reveal">Special Offer</span>
            <h1 id="hero-title" class="reveal">$<?= $OFFER['now'] ?> Headlight<br />Restoration</h1>
            <p class="hero-direct reveal">Save $<?= $OFFER['save'] ?> <span>Offer Ends Soon</span></p>

            <div class="offer-countdown-card reveal" aria-label="Special offer countdown">
              <p>Offer ends in</p>
              <div class="countdown landing-countdown" data-countdown data-target="<?= htmlspecialchars($OFFER['target']) ?>">
                <span><strong data-days>00</strong><small>Days</small></span>
                <span><strong data-hours>00</strong><small>Hours</small></span>
                <span><strong data-minutes>00</strong><small>Minutes</small></span>
                <span><strong data-seconds>00</strong><small>Seconds</small></span>
              </div>
            </div>

            <div class="offer-price reveal">
              <span class="was-price">$<?= $OFFER['was'] ?></span>
              <strong>$<?= $OFFER['now'] ?></strong>
              <span class="save-pill">Save $<?= $OFFER['save'] ?></span>
            </div>

            <a class="button button-primary offer-main-cta reveal" href="/book">
              <span>Claim Special Offer</span>
              <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6" /></svg>
            </a>
          </div>
        </div>
      </section>

      <section class="landing-section landing-reviews" aria-labelledby="reviews-title">
        <div class="container">
          <div class="landing-section-heading reveal">
            <p class="eyebrow">Reviews</p>
            <h2 id="reviews-title">Trusted By Brisbane Drivers</h2>
            <p class="reviews-rating"><span aria-hidden="true">★★★★★</span> <?= htmlspecialchars($REVIEWS['rating']) ?> from <?= htmlspecialchars($REVIEWS['count']) ?> happy drivers</p>
          </div>
        </div>
        <div class="reviews-marquee" aria-label="Customer reviews">
          <div class="reviews-track">
<?php for ($pass = 0; $pass < 2; $pass++): ?>
<?php foreach ($REVIEWS['items'] as $r): ?>
            <figure class="landing-review-card"<?= $pass === 1 ? ' aria-hidden="true"' : '' ?>>
              <span class="stars" aria-hidden="true">★★★★★</span>
              <blockquote><?= htmlspecialchars($r['text']) ?></blockquote>
              <figcaption><?= htmlspecialchars($r['name']) ?></figcaption>
            </figure>
<?php endforeach; ?>
<?php endfor; ?>
          </div>
        </div>
      </section>

      <section class="landing-sms-banner reveal" aria-label="Mobile booking confirmation preview">
        <img src="<?= asset('assets/mobile-booking-banner.png') ?>" alt="Mobile headlight restoration booking confirmation message with before and after photos" loading="lazy" />
      </section>

      <section class="landing-section landing-benefits" aria-labelledby="benefits-title">
        <div class="container">
          <div class="landing-section-heading reveal">
            <p class="eyebrow">Benefits</p>
            <h2 id="benefits-title">Why Drivers Restore Their Headlights</h2>
          </div>
          <div class="landing-card-grid">
            <article class="landing-feature-card reveal">
              <span class="feature-icon"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m12 3 1.7 5.8L20 10.5l-6.3 1.7L12 18l-1.7-5.8L4 10.5l6.3-1.7L12 3Z"/></svg></span>
              <h3>Crystal Clear Visibility</h3>
              <p>Cleaner light output for night driving.</p>
            </article>
            <article class="landing-feature-card reveal">
              <span class="feature-icon"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 3 20 6v6c0 5-3.4 8.3-8 9-4.6-.7-8-4-8-9V6l8-3Z"/></svg></span>
              <h3>Safer Night Driving</h3>
              <p>Restore confidence after dark.</p>
            </article>
            <article class="landing-feature-card reveal">
              <span class="feature-icon"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M7 17h10M6 17l1-7h10l1 7M5 17h14l1.5 3h-17L5 17Zm5-10h4"/></svg></span>
              <h3>Instant Appearance Upgrade</h3>
              <p>Make the front of your car look newer.</p>
            </article>
          </div>
        </div>
      </section>

      <section class="landing-section landing-product" id="our-product" aria-labelledby="product-title">
        <div class="container">
          <div class="landing-section-heading reveal">
            <p class="eyebrow">Our Product</p>
            <h2 id="product-title">The Polymer Behind The Shine</h2>
            <p>Every restoration is finished with our professional Headlight Restoration Polymer Liquid — a clear, UV-stable coating made for polycarbonate lenses. It bonds to the freshly prepped surface, seals out oxidation and cures to a glass-clear finish that restores light output and helps keep your headlights clear for up to 12+ months. <span class="product-note">Results vary.</span></p>
          </div>

          <ul class="product-spec-row reveal" aria-label="Polymer highlights">
            <li>Polycarbonate lens clarity</li>
            <li>UV-protective finish</li>
            <li>Up to 12+ months protection</li>
          </ul>

          <div class="product-video-frame reveal">
            <iframe
              src="https://player.vimeo.com/video/1206162717?background=1&amp;autoplay=1&amp;loop=1&amp;muted=1&amp;controls=0&amp;title=0&amp;byline=0&amp;portrait=0&amp;autopause=0&amp;dnt=1"
              allow="autoplay; fullscreen; picture-in-picture"
              referrerpolicy="strict-origin-when-cross-origin"
              title="Shining Headlights restoration polymer product video"
              tabindex="-1"
              aria-hidden="true"
              loading="lazy"></iframe>
          </div>
        </div>
      </section>

      <section class="landing-section landing-before-after" id="before-after" aria-labelledby="before-after-title">
        <div class="container">
          <div class="landing-section-heading reveal">
            <p class="eyebrow">Before / After</p>
            <h2 id="before-after-title">Cloudy To Clear In One Visit</h2>
          </div>
          <article class="premium-comparison reveal">
            <div class="real-result-pair landing-real-pair">
              <figure>
                <img src="<?= asset($BEFORE_AFTER_RESULTS[0]['before']) ?>" alt="Cloudy oxidised headlight before restoration" loading="lazy" />
                <figcaption>Before</figcaption>
              </figure>
              <figure>
                <img src="<?= asset($BEFORE_AFTER_RESULTS[0]['after']) ?>" alt="Clear restored headlight after restoration" loading="lazy" />
                <figcaption>After</figcaption>
              </figure>
            </div>
          </article>
        </div>
      </section>

      <section class="landing-section service-proof-section" aria-labelledby="proof-title">
        <div class="container">
          <div class="landing-section-heading reveal">
            <p class="eyebrow">Proofs of service</p>
            <h2 id="proof-title">Real Mobile Results Around Brisbane</h2>
            <p>Actual customer vehicle photos showing cloudy headlights, restored clarity and service conditions.</p>
          </div>
          <div class="service-proof-grid">
<?php foreach ($SERVICE_PROOFS as $proof): ?>
            <figure class="service-proof-card reveal">
              <img src="<?= asset($proof['image']) ?>" alt="<?= htmlspecialchars($proof['title']) ?>" loading="lazy" />
              <figcaption>
                <span><?= htmlspecialchars($proof['label']) ?></span>
                <strong><?= htmlspecialchars($proof['title']) ?></strong>
              </figcaption>
            </figure>
<?php endforeach; ?>
          </div>
        </div>
      </section>

      <section class="landing-final-cta" aria-labelledby="final-title">
        <div class="container final-offer-panel reveal">
          <p class="eyebrow">Limited time</p>
          <h2 id="final-title">Still Driving With Cloudy Headlights?</h2>
          <p>Restore Them Today For Just $<?= $OFFER['now'] ?></p>
          <a class="button button-primary offer-main-cta" href="/book">
            <span>Claim Special Offer</span>
            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6" /></svg>
          </a>
        </div>
      </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
