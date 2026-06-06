<?php
require_once __DIR__ . '/includes/config.php';
$current    = 'before-after';
$page_title = 'Before & After | ' . $SITE['name'];
$page_desc  = 'Real mobile headlight restoration results in Brisbane — from cloudy and yellow to clear and glossy.';
include __DIR__ . '/includes/header.php';
?>
      <section class="section section-dark before-after" id="before-after" aria-labelledby="ba-title">
        <div class="hero-bg" aria-hidden="true"></div>
        <div class="container">
          <div class="section-heading reveal">
            <div class="hero-brand-lockup center-brand-lockup">
              <img src="<?= asset('assets/shining-headlights-isotype.svg') ?>" alt="" width="58" height="68" aria-hidden="true" />
              <span>
                <strong><?= htmlspecialchars($SITE['name']) ?></strong>
                <small><?= htmlspecialchars($SITE['tagline']) ?></small>
              </span>
            </div>
            <p class="eyebrow">Before &amp; after</p>
            <h1 id="ba-title">Real results. Clear difference.</h1>
            <p>From cloudy and yellow to clean, clear and glossy.</p>
          </div>

          <div class="before-after-grid">
            <article class="comparison-card reveal">
              <div class="comparison-slider" style="--position: 52%">
                <img src="<?= asset('assets/result-before-1.svg') ?>" alt="Cloudy yellow headlight before restoration" loading="lazy" />
                <div class="after-layer"><img src="<?= asset('assets/result-after-1.svg') ?>" alt="Clear glossy headlight after restoration" loading="lazy" /></div>
                <span class="label before-label">Before</span><span class="label after-label">After</span>
                <span class="slider-line" aria-hidden="true"></span>
                <input type="range" min="5" max="95" value="52" aria-label="Before after comparison slider" />
              </div>
            </article>
            <article class="result-card reveal">
              <div class="result-pair">
                <figure><img src="<?= asset('assets/result-before-2.svg') ?>" alt="Oxidised headlight before restoration" loading="lazy" /><figcaption>Before</figcaption></figure>
                <figure><img src="<?= asset('assets/result-after-2.svg') ?>" alt="Restored clear headlight after service" loading="lazy" /><figcaption>After</figcaption></figure>
              </div>
            </article>
            <article class="result-card reveal">
              <div class="result-pair">
                <figure><img src="<?= asset('assets/result-before-3.svg') ?>" alt="Dull cloudy headlight before restoration" loading="lazy" /><figcaption>Before</figcaption></figure>
                <figure><img src="<?= asset('assets/result-after-3.svg') ?>" alt="Crystal clear headlight after restoration" loading="lazy" /><figcaption>After</figcaption></figure>
              </div>
            </article>
          </div>

          <div class="section-cta reveal"><a class="button button-primary" href="/book"><span>Book Your Transformation</span>
            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6" /></svg></a></div>
        </div>
      </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
