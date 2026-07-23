<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/service-cards.php';
$current    = 'services';
$page_title = 'Services | ' . $SITE['name'];
$page_desc  = 'Professional mobile headlight restoration in Brisbane — our process, benefits and what is included.';
include __DIR__ . '/includes/header.php';
$services = read_services(true);
?>
      <section class="section section-dark page-hero">
        <div class="hero-bg" aria-hidden="true"></div>
        <div class="container narrow center">
          <div class="hero-brand-lockup center-brand-lockup reveal">
            <img src="<?= asset('assets/shining-headlights-isotype.svg') ?>" alt="" width="58" height="68" aria-hidden="true" />
            <span>
              <strong><?= htmlspecialchars($SITE['name']) ?></strong>
              <small><?= htmlspecialchars($SITE['tagline']) ?></small>
            </span>
          </div>
          <p class="eyebrow">Services</p>
          <h1 class="reveal">Mobile headlight restoration</h1>
          <p class="hero-lede reveal">Restore, don't replace. We bring your headlights back to life at your location — for clarity, shine and safer night driving.</p>
          <div class="hero-actions reveal center"><a class="button button-primary" href="/book"><span>Claim Special Offer</span></a></div>
        </div>
      </section>

      <section class="section section-light service-catalog-section" aria-labelledby="services-catalog-title">
        <div class="container">
          <div class="section-heading reveal">
            <p class="eyebrow">Add extra shine to your booking</p>
            <h2 id="services-catalog-title">Mobile services and add-ons</h2>
            <p>Active services are loaded from the stored service catalog, so pricing updates from admin appear here automatically.</p>
          </div>
          <div class="service-card-grid">
<?php render_service_cards($services); ?>
          </div>
          <p class="pricing-note reveal">All prices are in AUD and listed as from prices. Final pricing depends on vehicle size, condition, access, level of oxidation, dirt, stains, pet hair, sand and the amount of work required. Any extra cost will be confirmed before work begins.</p>
        </div>
      </section>

      <section class="section section-white">
        <div class="container">
          <div class="section-heading reveal"><p class="eyebrow">The solution</p><h2>Why restoration beats replacement</h2></div>
          <div class="card-grid four-col">
            <article class="info-card reveal"><span class="icon-badge"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M8 17h8M7 17l1-7h8l1 7M6 17h12l1.5 3h-15L6 17Zm4-10h4"/></svg></span><h3>Mobile Service</h3><p>We come to your home, workplace or driveway.</p></article>
            <article class="info-card reveal"><span class="icon-badge"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m12 3 1.5 5.5L19 10l-5.5 1.5L12 17l-1.5-5.5L5 10l5.5-1.5L12 3Z"/></svg></span><h3>Crystal Clear Finish</h3><p>Bring back a clean, glossy headlight appearance.</p></article>
            <article class="info-card reveal"><span class="icon-badge"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M8 7h8M8 11h8M8 15h5M6 3h12a2 2 0 0 1 2 2v14l-4-2-4 2-4-2-4 2V5a2 2 0 0 1 2-2Z"/></svg></span><h3>Easy Booking</h3><p>Book online in minutes. No workshop visit needed.</p></article>
            <article class="info-card reveal"><span class="icon-badge"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M3 12h5l2-7 4 14 2-7h5"/></svg></span><h3>Better Night Confidence</h3><p>Clearer headlights help improve your driving visibility.</p></article>
          </div>
        </div>
      </section>

      <section class="section section-dark process" aria-labelledby="process-title">
        <div class="container split-layout">
          <div class="section-copy reveal">
            <p class="eyebrow">Professional process</p>
            <h2 id="process-title">A careful process for a clean result.</h2>
            <p>We carefully prepare each headlight by removing the damaged oxidised layer before restoring clarity and applying protection for a cleaner, glossier finish.</p>
            <a class="button button-secondary" href="/book"><span>Book your restoration</span></a>
          </div>
          <div class="process-list reveal">
            <span>Professional mobile setup</span>
            <span>Careful sanding preparation</span>
            <span>Clean restoration finish</span>
            <span>UV protection available</span>
            <span>Service done at your location</span>
          </div>
        </div>
      </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
