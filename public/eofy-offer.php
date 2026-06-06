<?php
require_once __DIR__ . '/includes/config.php';
$current      = 'eofy';
$page_title   = 'EOFY Offer | ' . $SITE['name'];
$page_desc    = 'Full details on our EOFY mobile headlight restoration sale — from $99, was $149. Limited time only.';
$page_scripts = ['countdown'];
include __DIR__ . '/includes/header.php';
?>
      <section class="section section-dark page-hero" aria-labelledby="eofy-title">
        <div class="hero-bg" aria-hidden="true"></div>
        <div class="container narrow center">
          <div class="hero-brand-lockup center-brand-lockup reveal">
            <img src="<?= asset('assets/shining-headlights-isotype.svg') ?>" alt="" width="58" height="68" aria-hidden="true" />
            <span>
              <strong><?= htmlspecialchars($SITE['name']) ?></strong>
              <small><?= htmlspecialchars($SITE['tagline']) ?></small>
            </span>
          </div>
          <span class="eofy-badge"><?= $EOFY['badge'] ?></span>
          <h1 id="eofy-title" class="reveal"><?= htmlspecialchars($EOFY['title']) ?></h1>
          <p class="hero-lede reveal"><?= htmlspecialchars($EOFY['support']) ?></p>

          <div class="promo-hero-card center-card reveal">
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
            <a class="button button-primary promo-cta" href="/book"><span>Claim EOFY Offer</span>
              <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6" /></svg>
            </a>
          </div>
        </div>
      </section>

      <section class="section section-white">
        <div class="container narrow">
          <div class="section-heading reveal"><p class="eyebrow">What's included</p><h2>Your EOFY restoration covers</h2></div>
          <div class="card-grid two-col">
            <article class="info-card reveal"><h3>Full multi-stage restoration</h3><p>Oxidation removal, clarity restoration and a glossy clear finish.</p></article>
            <article class="info-card reveal"><h3>UV protection coating</h3><p>Helps your results last longer against the Queensland sun.</p></article>
            <article class="info-card reveal"><h3>Mobile service included</h3><p>We come to your home, workplace or driveway across Brisbane.</p></article>
            <article class="info-card reveal"><h3>No workshop visit</h3><p>No drop-off, no waiting room — book a time and we handle the rest.</p></article>
          </div>
          <p class="pricing-note reveal">Final price may vary depending on headlight condition, vehicle type and location. Travel fees may apply for outer areas.</p>
        </div>
      </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
