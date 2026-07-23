<?php
require_once __DIR__ . '/config.php';
$current      = $current      ?? '';
$page_title   = $page_title   ?? ($SITE['name'] . ' | ' . $SITE['tagline']);
$page_desc    = $page_desc    ?? 'Mobile headlight restoration in Brisbane. We come to you — home, workplace or driveway.';
$body_class   = trim((string)($body_class ?? ''));
$minimal_header = str_contains(' ' . $body_class . ' ', ' booking-page ');
?><!doctype html>
<html lang="en-AU">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- Meta Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '1026998226936698');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=1026998226936698&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->

    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_desc) ?>" />
    <meta name="theme-color" content="#071827" />
    <link rel="canonical" href="<?= htmlspecialchars($SITE['canonical']) ?>/" />
    <link rel="stylesheet" href="<?= asset('css/styles.css') ?>" />
  </head>
  <body<?= $body_class !== '' ? ' class="' . htmlspecialchars($body_class, ENT_QUOTES) . '"' : '' ?>>
    <a class="skip-link" href="#main">Skip to content</a>

    <header class="site-header<?= $minimal_header ? ' is-minimal' : '' ?>" data-header>
      <div class="container header-inner">
        <a class="brand" href="/" aria-label="<?= htmlspecialchars($SITE['name']) ?> home">
          <span class="brand-mark" aria-hidden="true">
            <img src="<?= asset('assets/shining-headlights-isotype.svg') ?>" alt="" width="42" height="50" />
          </span>
          <span class="brand-wordmark">
            <strong>SHINING</strong>
            <small><?= htmlspecialchars($SITE['tagline']) ?></small>
          </span>
        </a>

<?php if (!$minimal_header): ?>
        <button class="nav-toggle" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="siteDrawer" data-menu-toggle>
          <span class="nav-toggle-bars" aria-hidden="true"><span></span><span></span><span></span></span>
          <span class="nav-toggle-label">Menu</span>
        </button>
<?php endif; ?>
      </div>

<?php if (!$minimal_header): ?>
      <div class="drawer-backdrop" data-drawer-backdrop hidden></div>
      <aside class="site-drawer" id="siteDrawer" data-mobile-nav aria-hidden="true" aria-label="Site navigation">
        <nav class="site-drawer-nav">
<?php foreach ($NAV as $key => $item): ?>
          <a href="<?= $item[0] ?>"<?= $current === $key ? ' class="is-active"' : '' ?>><?= $item[1] ?></a>
<?php endforeach; ?>
        </nav>
        <a class="button button-primary drawer-cta" href="/book">Claim Special Offer</a>
      </aside>
<?php endif; ?>
    </header>

    <main id="main">
