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

          <div class="real-results-grid">
<?php foreach ($BEFORE_AFTER_RESULTS as $result): ?>
            <article class="real-result-card reveal">
              <h2><?= htmlspecialchars($result['title']) ?></h2>
              <div class="real-result-pair">
                <figure>
                  <img src="<?= asset($result['before']) ?>" alt="Cloudy oxidised headlight before restoration" loading="lazy" />
                  <figcaption>Before</figcaption>
                </figure>
                <figure>
                  <img src="<?= asset($result['after']) ?>" alt="Clear restored headlight after restoration" loading="lazy" />
                  <figcaption>After</figcaption>
                </figure>
              </div>
            </article>
<?php endforeach; ?>
          </div>

          <div class="section-cta reveal">
            <p class="cta-support">Ready to restore your headlights at home?</p>
            <a class="button button-primary" href="/book"><span>Book Mobile Service</span>
              <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6" /></svg></a>
          </div>
        </div>
      </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
