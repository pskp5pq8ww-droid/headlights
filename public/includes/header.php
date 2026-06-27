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
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_desc) ?>" />
    <meta name="theme-color" content="#071827" />
    <link rel="canonical" href="<?= htmlspecialchars($SITE['canonical']) ?>/" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet" />
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
        <a class="button button-primary drawer-cta" href="/book">Claim EOFY Offer</a>
      </aside>
<?php endif; ?>
    </header>

    <main id="main">
