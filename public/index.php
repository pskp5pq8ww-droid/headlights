<?php
require_once __DIR__ . '/includes/config.php';
$current      = 'home';
$page_title   = '$99 EOFY Headlight Restoration | ' . $SITE['name'];
$page_desc    = 'EOFY Sale: mobile headlight restoration for $99, was $149. Limited time offer with countdown. Brisbane mobile service.';
$page_scripts = ['countdown'];
$body_class   = 'landing-home';
include __DIR__ . '/includes/header.php';
?>
      <section class="eofy-landing-hero" id="home" aria-labelledby="hero-title">
        <div class="hero-ambient" aria-hidden="true"></div>
        <figure class="hero-product-light reveal" aria-hidden="true">
          <img src="<?= asset('assets/hero-headlight-before-after.png') ?>" alt="" width="1536" height="1024" fetchpriority="high" />
        </figure>

        <div class="container eofy-landing-inner">
          <div class="eofy-hero-copy">
            <span class="sale-pill reveal">EOFY Sale</span>
            <h1 id="hero-title" class="reveal">$<?= $EOFY['now'] ?> Headlight<br />Restoration</h1>
            <p class="hero-direct reveal">Save $<?= $EOFY['save'] ?> <span>Offer Ends Soon</span></p>

            <div class="eofy-countdown-card reveal" aria-label="EOFY offer countdown">
              <p>Offer ends in</p>
              <div class="countdown landing-countdown" data-countdown data-mode="daily-brisbane">
                <span><strong data-days>00</strong><small>Days</small></span>
                <span><strong data-hours>00</strong><small>Hours</small></span>
                <span><strong data-minutes>00</strong><small>Minutes</small></span>
                <span><strong data-seconds>00</strong><small>Seconds</small></span>
              </div>
            </div>

            <div class="eofy-price reveal">
              <span class="was-price">$<?= $EOFY['was'] ?></span>
              <strong>$<?= $EOFY['now'] ?></strong>
              <span class="save-pill">Save $<?= $EOFY['save'] ?></span>
            </div>

            <a class="button button-primary eofy-main-cta reveal" href="/book">
              <span>Claim EOFY Offer</span>
              <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6" /></svg>
            </a>
          </div>
        </div>
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

      <section class="landing-section landing-reviews" aria-labelledby="reviews-title">
        <div class="container">
          <div class="landing-section-heading reveal">
            <p class="eyebrow">Reviews</p>
            <h2 id="reviews-title">Trusted By Brisbane Drivers</h2>
          </div>
          <div class="landing-review-grid">
<?php foreach (array_slice($REVIEWS['items'], 0, 3) as $r): ?>
            <figure class="landing-review-card reveal">
              <span class="stars" aria-hidden="true">★★★★★</span>
              <blockquote><?= htmlspecialchars($r['text']) ?></blockquote>
              <figcaption><?= htmlspecialchars($r['name']) ?></figcaption>
            </figure>
<?php endforeach; ?>
          </div>
        </div>
      </section>

      <section class="landing-final-cta" aria-labelledby="final-title">
        <div class="container final-eofy-panel reveal">
          <p class="eyebrow">Limited time</p>
          <h2 id="final-title">Still Driving With Cloudy Headlights?</h2>
          <p>Restore Them Today For Just $<?= $EOFY['now'] ?></p>
          <a class="button button-primary eofy-main-cta" href="/book">
            <span>Claim EOFY Offer</span>
            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6" /></svg>
          </a>
        </div>
      </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
