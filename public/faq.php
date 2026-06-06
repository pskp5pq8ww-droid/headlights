<?php
require_once __DIR__ . '/includes/config.php';
$current    = 'faq';
$page_title = 'FAQ | ' . $SITE['name'];
$page_desc  = 'Frequently asked questions about mobile headlight restoration in Brisbane.';
include __DIR__ . '/includes/header.php';
?>
      <section class="section section-dark page-hero">
        <div class="hero-bg" aria-hidden="true"></div>
        <div class="container narrow center">
          <p class="eyebrow">FAQ</p>
          <h1 class="reveal">Quick answers before you book</h1>
        </div>
      </section>

      <section class="section section-white faq">
        <div class="container narrow">
          <div class="faq-list">
<?php foreach ($FAQS as $i => $f): ?>
            <article class="faq-item reveal<?= $i === 0 ? ' is-open' : '' ?>">
              <button class="faq-question" type="button" aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>">
                <span><?= htmlspecialchars($f['q']) ?></span>
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" /></svg>
              </button>
              <div class="faq-answer"><?= htmlspecialchars($f['a']) ?></div>
            </article>
<?php endforeach; ?>
          </div>
          <div class="section-cta reveal"><a class="button button-primary" href="/book"><span>Claim EOFY Offer</span></a></div>
        </div>
      </section>

      <script>
        document.querySelectorAll(".faq-question").forEach((btn) => {
          btn.addEventListener("click", () => {
            const item = btn.closest(".faq-item");
            const open = item.classList.toggle("is-open");
            btn.setAttribute("aria-expanded", String(open));
          });
        });
      </script>
<?php include __DIR__ . '/includes/footer.php'; ?>
