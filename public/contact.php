<?php
require_once __DIR__ . '/includes/config.php';
$current    = 'contact';
$page_title = 'Contact | ' . $SITE['name'];
$page_desc  = 'Contact Shining Headlights Australia — mobile headlight restoration across Brisbane.';
include __DIR__ . '/includes/header.php';
?>
      <section class="section section-dark page-hero">
        <div class="hero-bg" aria-hidden="true"></div>
        <div class="container narrow center">
          <p class="eyebrow">Contact</p>
          <h1 class="reveal">Get in touch</h1>
          <p class="hero-lede reveal">We confirm every mobile booking by phone. Call us or book online and we'll come to you.</p>
        </div>
      </section>

      <section class="section section-white">
        <div class="container split-layout">
          <div class="section-copy reveal">
            <div class="contact-card">
              <span>Phone / WhatsApp / SMS</span>
              <strong><?= htmlspecialchars($SITE['name']) ?></strong>
              <a href="<?= htmlspecialchars($SITE['phone_href']) ?>">Call <?= htmlspecialchars($SITE['phone_label']) ?></a>
              <a href="mailto:<?= htmlspecialchars($SITE['email']) ?>"><?= htmlspecialchars($SITE['email']) ?></a>
            </div>
            <p class="section-note"><?= htmlspecialchars($SITE['region']) ?> — mobile service only. We come to your home, workplace or driveway.</p>
            <div class="hero-actions"><a class="button button-primary" href="/book"><span>Claim EOFY Offer</span></a></div>
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
<?php include __DIR__ . '/includes/footer.php'; ?>
