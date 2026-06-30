<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/analytics.php';

$allowedRanges = [7, 30, 90];
$days = (int)($_GET['days'] ?? 30);
if (!in_array($days, $allowedRanges, true)) $days = 30;

$error = '';
try {
    $events = analytics_read_range($days);
    $bookings = readBookings();
    $m = analytics_aggregate($events, $bookings, $days);
} catch (Throwable $e) {
    $error = 'Unable to load analytics. Please check server storage or permissions.';
    $m = analytics_aggregate([], [], $days);
}

function h(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function visit_time(mixed $v): string {
    if (trim((string)$v) === '') return '—';
    try {
        $dt = new DateTimeImmutable((string)$v);
        return $dt->setTimezone(new DateTimeZone('Australia/Brisbane'))->format('d M, g:i A');
    } catch (Throwable) {
        return (string)$v;
    }
}
$maxViews = 0;
foreach ($m['series'] as $pt) { if ($pt['views'] > $maxViews) $maxViews = $pt['views']; }
$totalDevices = array_sum($m['devices']) ?: 1;

function bar_list(array $items, string $unit = ''): string {
    if (!$items) return '<p class="empty">No data yet.</p>';
    $max = max($items) ?: 1;
    $html = '<ul class="bar-list">';
    foreach ($items as $label => $count) {
        $pct = (int)round($count / $max * 100);
        $html .= '<li><span class="bar-label">' . h($label) . '</span>'
            . '<span class="bar-track"><span class="bar-fill" style="width:' . $pct . '%"></span></span>'
            . '<span class="bar-count">' . h((string)$count) . h($unit) . '</span></li>';
    }
    return $html . '</ul>';
}
?><!doctype html>
<html lang="en-AU">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex,nofollow" />
  <title>Analytics | Shining Headlights Australia</title>
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
        <a class="is-active" href="/analytics"><span>Analytics</span></a>
        <a href="/bookings-map"><span>Map</span></a>
        <a href="/users"><span>Users</span></a>
        <a href="/backups"><span>Backups</span></a>
      </nav>
      <a class="admin-logout" href="/logout">Logout</a>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <h1>Analytics</h1>
          <p>Traffic, conversion and locations · last <?= $days ?> days</p>
        </div>
        <div class="range-tabs">
          <?php foreach ($allowedRanges as $r): ?>
            <a href="/analytics?days=<?= $r ?>" class="<?= $r === $days ? 'is-active' : '' ?>"><?= $r ?>d</a>
          <?php endforeach; ?>
        </div>
      </header>

      <?php if ($error): ?><p class="admin-error" role="alert"><?= h($error) ?></p><?php endif; ?>

      <section class="stat-grid" aria-label="Traffic metrics">
        <div class="stat-card"><span class="stat-num"><?= number_format($m['views']) ?></span><span class="stat-label">Page Views</span></div>
        <div class="stat-card"><span class="stat-num"><?= number_format($m['uniqueVisitors']) ?></span><span class="stat-label">Unique Visitors</span></div>
        <div class="stat-card"><span class="stat-num"><?= number_format($m['sessions']) ?></span><span class="stat-label">Sessions</span></div>
        <div class="stat-card"><span class="stat-num"><?= number_format($m['visitorsToday']) ?></span><span class="stat-label">Visitors Today</span></div>
        <div class="stat-card"><span class="stat-num"><?= number_format($m['uniqueIpHashes'] ?? 0) ?></span><span class="stat-label">IP Signals</span></div>
        <div class="stat-card"><span class="stat-num small"><?= h($m['peakHour'] ?: '—') ?></span><span class="stat-label">Peak Hour <small><?= (int)($m['peakHourViews'] ?? 0) ?> views</small></span></div>
        <div class="stat-card highlight"><span class="stat-num"><?= h((string)$m['bookingConversion']) ?>%</span><span class="stat-label">Visitor → Booking</span></div>
        <div class="stat-card"><span class="stat-num"><?= h((string)$m['paidConversion']) ?>%</span><span class="stat-label">Booking → Paid</span></div>
        <div class="stat-card"><span class="stat-num"><?= number_format($m['bookings']) ?></span><span class="stat-label">Bookings</span></div>
        <div class="stat-card"><span class="stat-num">$<?= number_format($m['revenue'], 2) ?></span><span class="stat-label">Paid Revenue</span></div>
      </section>

      <section class="panel">
        <div class="panel-head"><h2>Visits over time</h2><span><?= $days ?> days</span></div>
        <?php if ($m['views'] === 0): ?>
          <p class="empty">No visits recorded yet. Data starts collecting once this is deployed.</p>
        <?php else: ?>
          <div class="chart-bars" role="img" aria-label="Daily page views">
            <?php foreach ($m['series'] as $pt): $hpct = $maxViews > 0 ? max(2, (int)round($pt['views'] / $maxViews * 100)) : 2; ?>
              <span class="chart-bar" title="<?= h($pt['date']) ?>: <?= (int)$pt['views'] ?> views">
                <span class="chart-bar-fill" style="height:<?= $hpct ?>%"></span>
              </span>
            <?php endforeach; ?>
          </div>
          <div class="chart-axis"><span><?= h($m['series'][0]['date'] ?? '') ?></span><span><?= h(end($m['series'])['date'] ?? '') ?></span></div>
        <?php endif; ?>
      </section>

      <section class="dashboard-grid">
        <div class="panel">
          <h2>Top pages</h2>
          <?= bar_list($m['topPages']) ?>
        </div>
        <div class="panel">
          <h2>Traffic sources</h2>
          <?= bar_list($m['topReferrers']) ?>
        </div>
      </section>

      <section class="dashboard-grid">
        <div class="panel">
          <h2>Devices</h2>
          <ul class="bar-list">
            <?php foreach ($m['devices'] as $dev => $count): $pct = (int)round($count / $totalDevices * 100); ?>
              <li><span class="bar-label"><?= h(ucfirst($dev)) ?></span>
                <span class="bar-track"><span class="bar-fill" style="width:<?= $pct ?>%"></span></span>
                <span class="bar-count"><?= h((string)$pct) ?>%</span></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="panel">
          <h2>Customer locations <small>(from bookings)</small></h2>
          <?= bar_list($m['customerSuburbs']) ?>
        </div>
      </section>

      <section class="dashboard-grid">
        <div class="panel">
          <h2>Peak hours</h2>
          <?= bar_list($m['hourlyPeaks'] ?? [], ' views') ?>
        </div>
        <div class="panel">
          <h2>Visitor IP signals <small>(anonymous)</small></h2>
          <?= bar_list($m['topIpHashes'] ?? [], ' views') ?>
        </div>
      </section>

      <section class="panel">
        <div class="panel-head"><h2>Recent visits</h2><span>Latest 25</span></div>
        <?php if (empty($m['recentVisits'])): ?>
          <p class="empty">No visits recorded yet.</p>
        <?php else: ?>
          <div class="visit-table-wrap">
            <table class="visit-table">
              <thead>
                <tr>
                  <th>Time</th>
                  <th>Visitor</th>
                  <th>IP signal</th>
                  <th>Page</th>
                  <th>Device</th>
                  <th>Source</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($m['recentVisits'] as $visit): ?>
                  <tr>
                    <td><?= h(visit_time($visit['time'] ?? '')) ?></td>
                    <td><span class="mono"><?= h(substr((string)($visit['visitor'] ?? ''), 0, 8) ?: '—') ?></span><?php if (!empty($visit['newVisitor'])): ?> <span class="visit-pill">new</span><?php endif; ?></td>
                    <td><span class="mono"><?= h(((string)($visit['ipHash'] ?? '')) !== '' ? $visit['ipHash'] : '—') ?></span></td>
                    <td><?= h($visit['page'] ?? '/') ?></td>
                    <td><?= h(ucfirst((string)($visit['device'] ?? ''))) ?></td>
                    <td><?= h($visit['referrer'] ?? 'direct') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>

      <section class="dashboard-grid">
        <div class="panel">
          <h2>Visitor countries</h2>
          <?= bar_list($m['countries']) ?>
        </div>
        <div class="panel">
          <h2>Visitor regions / cities</h2>
          <?= bar_list($m['regions'] ?: $m['cities']) ?>
        </div>
      </section>

      <p class="detail-muted">Visitors are counted with a privacy-friendly cookie; raw IPs are never stored, only a salted anonymous signal. Bots are excluded. Geo is looked up once per session.</p>
    </main>
  </div>
</body>
</html>
