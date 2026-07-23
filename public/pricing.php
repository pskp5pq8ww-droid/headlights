<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/service-cards.php';
$current    = 'pricing';
$page_title = 'Pricing | ' . $SITE['name'];
$page_desc  = 'Mobile headlight restoration pricing in Brisbane — special limited-time offer from $99, standard packages and travel info.';
include __DIR__ . '/includes/header.php';
$services = read_services(true);
?>
      <section class="section section-dark page-hero">
        <div class="hero-bg" aria-hidden="true"></div>
        <div class="container narrow center">
          <p class="eyebrow">Pricing</p>
          <h1 class="reveal">Simple mobile restoration pricing</h1>
          <p class="hero-lede reveal">Special limited-time price from $<?= $OFFER['now'] ?> (was $<?= $OFFER['was'] ?>). Choose a package or send photos for a quick quote.</p>
        </div>
      </section>

      <section class="section section-light packages" id="packages">
        <div class="container">
          <div class="service-card-grid">
<?php render_service_cards($services); ?>
          </div>
          <p class="pricing-note reveal">All prices are in AUD and listed as from prices. Final pricing depends on vehicle size, condition, access, level of oxidation, dirt, stains, pet hair, sand and the amount of work required. Any extra cost will be confirmed before work begins.</p>
        </div>
      </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
