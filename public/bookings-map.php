<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/bookings.php';

$error = '';
try {
    $bookings = readBookings();
} catch (Throwable $e) {
    $bookings = [];
    $error = 'Unable to load bookings. Please check server storage or permissions.';
}

// Build the client payload (admin-only page).
$points = [];
$withCoords = 0;
foreach ($bookings as $b) {
    $lat = is_numeric($b['addressLat'] ?? '') ? (float)$b['addressLat'] : null;
    $lng = is_numeric($b['addressLng'] ?? '') ? (float)$b['addressLng'] : null;
    if ($lat !== null && $lng !== null && $lat !== 0.0 && $lng !== 0.0) $withCoords++;
    else { $lat = null; $lng = null; }
    $points[] = [
        'id' => $b['id'],
        'name' => $b['fullName'] ?: 'Unnamed customer',
        'status' => $b['status'],
        'paymentStatus' => $b['paymentStatus'] ?? 'unpaid',
        'date' => trim(($b['preferredDate'] ?? '') . ' ' . ($b['preferredTimeWindow'] ?? '')),
        'address' => $b['formattedAddress'] ?: ($b['addressOrSuburb'] ?? ''),
        'package' => $b['packageSelected'] ?? '',
        'phone' => $b['phone'] ?? '',
        'price' => package_price($b['packageSelected'] ?? ''),
        'lat' => $lat,
        'lng' => $lng,
    ];
}

$mapsKey = maps_api_key();
function h(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function status_label(string $s): string { return ucwords(str_replace('_', ' ', $s)); }
function badge_class_admin(string $s): string { return 'badge-' . str_replace('_', '-', strtolower($s)); }
?><!doctype html>
<html lang="en-AU">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex,nofollow" />
  <title>Bookings Map | Shining Headlights Australia</title>
  <link rel="stylesheet" href="/css/admin.css?v=<?= ASSET_VER ?>" />
</head>
<body class="admin-app">
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <a class="admin-logo" href="/dashboard">
        <img src="/assets/shining-headlights-isotype.svg" alt="" width="34" height="40" />
        <span>Shining Admin</span>
      </a>
      <nav class="admin-nav">
        <a href="/dashboard"><span>Dashboard</span></a>
        <a href="/analytics"><span>Analytics</span></a>
        <a class="is-active" href="/bookings-map"><span>Map</span></a>
        <a href="/users"><span>Users</span></a>
      </nav>
      <a class="admin-logout" href="/logout">Logout</a>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <h1>Bookings Map</h1>
          <p><?= $withCoords ?> of <?= count($points) ?> bookings have a map location.</p>
        </div>
        <div class="map-legend" aria-hidden="true">
          <span><i style="background:#34c98f"></i>Paid</span>
          <span><i style="background:#00d9ff"></i>Confirmed</span>
          <span><i style="background:#f4b63f"></i>Contacted</span>
          <span><i style="background:#a8f3ff"></i>New</span>
          <span><i style="background:#f87171"></i>Cancelled</span>
        </div>
      </header>

      <?php if ($error): ?><p class="admin-error" role="alert"><?= h($error) ?></p><?php endif; ?>

      <div class="map-admin-layout">
        <div id="adminMap" class="admin-map"><?php if (!$mapsKey): ?><p class="empty" style="padding:24px">Google Maps key not configured.</p><?php endif; ?></div>

        <aside class="map-bookings-widget" aria-label="Incoming bookings">
          <div class="panel-head"><h2>Incoming bookings</h2><span><?= count($points) ?></span></div>
          <?php if (!$points): ?>
            <p class="empty">No bookings yet.</p>
          <?php else: ?>
            <ul class="map-booking-list">
              <?php foreach ($points as $p): $hasCoords = $p['lat'] !== null; ?>
                <li class="map-booking-item<?= $hasCoords ? '' : ' no-coords' ?>"<?= $hasCoords ? ' data-map-focus="' . h($p['id']) . '" tabindex="0" role="button"' : '' ?>>
                  <div class="mbi-top">
                    <strong><?= h($p['name']) ?></strong>
                    <span class="badge <?= badge_class_admin($p['status']) ?>"><?= h(status_label($p['status'])) ?></span>
                  </div>
                  <span class="mbi-line"><?= h($p['date'] ?: 'No date') ?><?= $p['price'] > 0 ? ' · $' . h((string)$p['price']) : '' ?></span>
                  <span class="mbi-line mbi-addr"><?= h($p['address'] ?: 'No address') ?></span>
                  <?php if (!$hasCoords): ?><span class="mbi-nopin">No map location</span><?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </aside>
      </div>
    </main>
  </div>

  <script>window.adminBookings = <?= json_encode($points, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
  <script src="/js/admin-map.js?v=<?= ASSET_VER ?>"></script>
  <?php if ($mapsKey): ?>
  <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?= rawurlencode($mapsKey) ?>&callback=initAdminMap"></script>
  <?php endif; ?>
</body>
</html>
