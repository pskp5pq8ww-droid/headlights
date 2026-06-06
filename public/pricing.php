<?php
require_once __DIR__ . '/includes/config.php';
$current    = 'pricing';
$page_title = 'Pricing | ' . $SITE['name'];
$page_desc  = 'Mobile headlight restoration pricing in Brisbane — EOFY offer from $99, standard packages and travel info.';
include __DIR__ . '/includes/header.php';
?>
      <section class="section section-dark page-hero">
        <div class="hero-bg" aria-hidden="true"></div>
        <div class="container narrow center">
          <p class="eyebrow">Pricing</p>
          <h1 class="reveal">Simple mobile restoration pricing</h1>
          <p class="hero-lede reveal">EOFY launch price from $<?= $EOFY['now'] ?> (was $<?= $EOFY['was'] ?>). Choose a package or send photos for a quick quote.</p>
        </div>
      </section>

      <section class="section section-light packages" id="packages">
        <div class="container">
          <div class="pricing-grid">
<?php foreach ($PACKAGES as $pkg): ?>
            <article class="pricing-card reveal<?= !empty($pkg['featured']) ? ' featured' : '' ?>">
<?php if (!empty($pkg['badge'])): ?>              <span class="badge"><?= htmlspecialchars($pkg['badge']) ?></span>
<?php endif; ?>
              <h3><?= htmlspecialchars($pkg['name']) ?></h3>
              <p class="best-for"><?= htmlspecialchars($pkg['bestFor']) ?></p>
              <p class="price"><?= htmlspecialchars($pkg['price']) ?></p>
              <ul>
<?php foreach ($pkg['inclusions'] as $inc): ?>                <li><?= htmlspecialchars($inc) ?></li>
<?php endforeach; ?>
              </ul>
              <a class="button <?= !empty($pkg['featured']) ? 'button-primary' : 'button-secondary' ?>" href="/book"><span>Book <?= htmlspecialchars($pkg['name']) ?></span></a>
            </article>
<?php endforeach; ?>
          </div>
          <p class="pricing-note reveal">Final price may vary depending on headlight condition, vehicle type and location. Travel fees may apply for outer areas (Gold Coast / Sunshine Coast by request).</p>
        </div>
      </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
