<?php
require_once __DIR__ . '/includes/config.php';
$current      = 'home';
$page_title   = 'EOFY Mobile Headlight Restoration Sale | ' . $SITE['name'];
$page_desc    = 'EOFY Sale: mobile headlight restoration from $99 (was $149). We come to you anywhere in Brisbane. Limited time only.';
$page_scripts = ['countdown'];
include __DIR__ . '/includes/header.php';
?>
      <!-- ═══ EOFY HERO ═══ -->
      <section class="hero section-dark eofy-hero" id="home">
        <div class="hero-bg" aria-hidden="true"></div>
        <div class="container eofy-hero-grid">
          <div class="hero-copy">
            <div class="hero-brand-lockup reveal">
              <img src="<?= asset('assets/shining-headlights-isotype.svg') ?>" alt="" width="58" height="68" aria-hidden="true" />
              <span>
                <strong><?= htmlspecialchars($SITE['name']) ?></strong>
                <small><?= htmlspecialchars($SITE['tagline']) ?></small>
              </span>
            </div>
            <span class="eofy-badge"><?= $EOFY['badge'] ?></span>
            <h1 class="reveal"><?= htmlspecialchars($EOFY['title']) ?></h1>
            <p class="hero-lede reveal"><?= htmlspecialchars($EOFY['sub']) ?></p>
            <p class="hero-support reveal"><?= htmlspecialchars($EOFY['support']) ?></p>
            <div class="hero-actions reveal">
              <a class="button button-primary" href="/book">
                <span>Claim EOFY Offer</span>
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6" /></svg>
              </a>
              <a class="button button-secondary" href="/before-after"><span>See Before &amp; After</span></a>
            </div>
            <p class="trust-line reveal">Serving Brisbane &amp; surrounding areas</p>
          </div>

          <div class="eofy-hero-side">
            <figure class="hero-media reveal" aria-label="Before and after restored car headlight comparison">
              <img src="<?= asset('assets/hero-headlight-before-after.png') ?>"
                   alt="A car headlight split between cloudy yellow oxidation and clear restored finish"
                   width="1536" height="1024" fetchpriority="high" />
              <figcaption><span>Before</span><span>After</span></figcaption>
            </figure>

            <!-- EOFY promo card -->
            <div class="promo-hero-card reveal">
              <span class="promo-badge">EOFY Launch Price</span>
              <div class="promo-price-row">
                <span class="promo-was">Was $<?= $EOFY['was'] ?></span>
                <span class="promo-now">$<?= $EOFY['now'] ?></span>
                <span class="promo-save">Save $<?= $EOFY['save'] ?></span>
              </div>
              <p class="promo-note"><?= htmlspecialchars($EOFY['note'] ?? '') ?></p>

              <div class="countdown" data-countdown data-target="<?= htmlspecialchars($EOFY['target']) ?>" aria-label="EOFY offer countdown">
                <span><strong data-days>00</strong><small>Days</small></span>
                <span><strong data-hours>00</strong><small>Hrs</small></span>
                <span><strong data-minutes>00</strong><small>Min</small></span>
                <span><strong data-seconds>00</strong><small>Sec</small></span>
              </div>

              <a class="button button-primary promo-cta" href="/book">
                <span>Claim EOFY Offer</span>
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6" /></svg>
              </a>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══ BENEFIT CARDS ═══ -->
      <section class="section section-light benefits" aria-label="Why choose us">
        <div class="container value-row four-benefits">
          <div class="value-chip reveal">
            <span class="icon-badge"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M8 17h8M7 17l1-7h8l1 7M6 17h12l1.5 3h-15L6 17Zm4-10h4"/></svg></span>
            <span><strong>We come to you</strong>Home, workplace or driveway</span>
          </div>
          <div class="value-chip reveal">
            <span class="icon-badge"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 6v6l4 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg></span>
            <span><strong>Save time</strong>No workshop visit</span>
          </div>
          <div class="value-chip reveal">
            <span class="icon-badge"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m12 3 1.5 5.5L19 10l-5.5 1.5L12 17l-1.5-5.5L5 10l5.5-1.5L12 3Z"/></svg></span>
            <span><strong>Crystal clear results</strong>Like-new clarity</span>
          </div>
          <div class="value-chip reveal">
            <span class="icon-badge"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M3 12h5l2-7 4 14 2-7h5"/></svg></span>
            <span><strong>Better night visibility</strong>Safer driving at night</span>
          </div>
        </div>
      </section>

      <!-- ═══ SOCIAL PROOF ═══ -->
      <section class="section section-white reviews" aria-labelledby="reviews-title">
        <div class="container">
          <div class="section-heading reveal">
            <p class="eyebrow">Reviews</p>
            <h2 id="reviews-title"><?= htmlspecialchars($REVIEWS['heading']) ?></h2>
            <p class="review-rating">
              <span class="stars" aria-hidden="true">★★★★★</span>
              <strong><?= htmlspecialchars($REVIEWS['rating']) ?>/5</strong> from <?= htmlspecialchars($REVIEWS['count']) ?> reviews
            </p>
          </div>
          <div class="review-grid">
<?php foreach ($REVIEWS['items'] as $r): ?>
            <figure class="review-card reveal">
              <span class="stars" aria-hidden="true">★★★★★</span>
              <blockquote><?= htmlspecialchars($r['text']) ?></blockquote>
              <figcaption><?= htmlspecialchars($r['name']) ?></figcaption>
            </figure>
<?php endforeach; ?>
          </div>
          <div class="section-cta reveal"><a class="inline-link" href="/before-after">See more results</a></div>
        </div>
      </section>

      <!-- ═══ HOW IT WORKS ═══ -->
      <section class="section section-light how" id="how-it-works" aria-labelledby="how-title">
        <div class="container">
          <div class="section-heading reveal"><p class="eyebrow">How it works</p><h2 id="how-title">Three simple steps</h2></div>
          <div class="steps three-steps" aria-label="Service process">
            <article class="step reveal"><span>1</span><h3>Book online</h3><p>Choose your time and location in minutes.</p></article>
            <article class="step reveal"><span>2</span><h3>We come to you</h3><p>Home, workplace or driveway — wherever your car is.</p></article>
            <article class="step reveal"><span>3</span><h3>Drive safer</h3><p>Crystal-clear headlights and better night visibility.</p></article>
          </div>
        </div>
      </section>

      <!-- ═══ SERVICE AREA PREVIEW ═══ -->
      <section class="section section-white service-area" aria-labelledby="area-title">
        <div class="container split-layout">
          <div class="section-copy reveal">
            <p class="eyebrow">Service area</p>
            <h2 id="area-title">Mobile service across Brisbane</h2>
            <p>We service homes, workplaces and driveways across Brisbane and surrounding suburbs. Travel fees may apply for outer areas.</p>
            <a class="button button-secondary" href="/contact"><span>Check your area</span></a>
          </div>
          <div class="area-panel reveal">
            <div class="area-map" aria-hidden="true">
              <span class="map-pin main"></span><span class="map-pin north"></span>
              <span class="map-pin south"></span><span class="map-route"></span>
            </div>
            <div class="area-tags">
<?php foreach ($AREAS as $a): ?>              <span><?= htmlspecialchars($a) ?></span>
<?php endforeach; ?>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══ FINAL CTA BAND ═══ -->
      <section class="section section-dark final-cta" aria-labelledby="final-title">
        <div class="container final-band reveal">
          <div>
            <p class="eyebrow">Don't miss out</p>
            <h2 id="final-title">EOFY Savings Don't Last</h2>
            <p>Limited time offer. Limited spots each day. Book now to secure your EOFY price.</p>
          </div>
          <a class="button button-primary" href="/book"><span>Book Mobile Service Now</span>
            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6" /></svg>
          </a>
        </div>
      </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
