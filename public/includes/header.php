<?php
require_once __DIR__ . '/config.php';
$current      = $current      ?? '';
$page_title   = $page_title   ?? ($SITE['name'] . ' | ' . $SITE['tagline']);
$page_desc    = $page_desc    ?? 'Mobile headlight restoration in Brisbane. We come to you — home, workplace or driveway.';
$page_maps    = $page_maps    ?? false;
$body_class   = trim((string)($body_class ?? ''));
?><!doctype html>
<html lang="en-AU">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_desc) ?>" />
    <meta name="theme-color" content="#071827" />
    <link rel="canonical" href="<?= htmlspecialchars($SITE['canonical']) ?>/" />
<?php if ($page_maps): ?>
    <meta name="gmap-key" content="<?= htmlspecialchars(maps_api_key(), ENT_QUOTES) ?>" />
<?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="<?= asset('css/styles.css') ?>" />
  </head>
  <body<?= $body_class !== '' ? ' class="' . htmlspecialchars($body_class, ENT_QUOTES) . '"' : '' ?>>
    <a class="skip-link" href="#main">Skip to content</a>

    <header class="site-header" data-header>
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

        <nav class="desktop-nav" aria-label="Main navigation">
<?php foreach ($NAV as $key => $item): ?>
          <a href="<?= $item[0] ?>"<?= $current === $key ? ' class="is-active"' : '' ?>><?= $item[1] ?></a>
<?php endforeach; ?>
        </nav>

        <a class="admin-btn" href="/admin" aria-label="Admin login">
          <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-4 0-7 2-7 5v2h14v-2c0-3-3-5-7-5Z"/></svg>
          <span>Admin</span>
        </a>

        <button class="menu-toggle" type="button" aria-label="Open menu" aria-expanded="false" data-menu-toggle>
          <span></span><span></span><span></span>
        </button>
      </div>

      <nav class="mobile-nav" aria-label="Mobile navigation" data-mobile-nav>
<?php foreach ($NAV as $key => $item): ?>
        <a href="<?= $item[0] ?>"<?= $current === $key ? ' class="is-active"' : '' ?>><?= $item[1] ?></a>
<?php endforeach; ?>
        <a class="button button-primary" href="/book">Claim EOFY Offer</a>
        <a class="mobile-admin" href="/admin">Admin login</a>
      </nav>
    </header>

    <main id="main">
