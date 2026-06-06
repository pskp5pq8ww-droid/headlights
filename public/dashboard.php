<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/includes/config.php';

const STATUSES = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];

$bookingsDir = (dirname($_SERVER['DOCUMENT_ROOT'] ?? __DIR__)) . '/Storagehighlights/bookings';

// ── Handle status update (Post/Redirect/Get) ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_status') {
    $file   = basename($_POST['file'] ?? '');
    $status = $_POST['status'] ?? '';
    if ($file && in_array($status, STATUSES, true) && preg_match('/^booking_[\w-]+\.json$/', $file)) {
        $path = $bookingsDir . '/' . $file;
        if (is_file($path)) {
            $data = json_decode(file_get_contents($path), true);
            if (is_array($data)) {
                $data['status'] = $status;
                file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
            }
        }
    }
    header('Location: /dashboard');
    exit;
}

// ── Load bookings ─────────────────────────────────────────────────────────────
$bookings = [];
if (is_dir($bookingsDir)) {
    foreach (glob($bookingsDir . '/booking_*.json') as $f) {
        $d = json_decode(file_get_contents($f), true);
        if (!is_array($d)) continue;
        $d['_file']  = basename($f);
        $d['status'] = $d['status'] ?? 'Pending';
        $bookings[] = $d;
    }
}

// ── Brisbane "today" and stats ───────────────────────────────────────────────
$bne       = new DateTimeZone('Australia/Brisbane');
$now       = new DateTime('now', $bne);
$todayStr  = $now->format('Y-m-d');
$weekStart = (clone $now)->modify('monday this week')->format('Y-m-d');
$weekEnd   = (clone $now)->modify('sunday this week')->format('Y-m-d');

$stats = ['today' => 0, 'week' => 0, 'pending' => 0, 'completed' => 0, 'total' => count($bookings)];
foreach ($bookings as $b) {
    $d = $b['date'] ?? '';
    if ($d === $todayStr) $stats['today']++;
    if ($d >= $weekStart && $d <= $weekEnd && $d !== '') $stats['week']++;
    if (($b['status'] ?? '') === 'Pending')   $stats['pending']++;
    if (($b['status'] ?? '') === 'Completed') $stats['completed']++;
}

// Upcoming (appointment date >= today), soonest first
$upcoming = array_filter($bookings, fn($b) => ($b['date'] ?? '') >= $todayStr && ($b['date'] ?? '') !== '');
usort($upcoming, fn($a, $b) => strcmp($a['date'] ?? '', $b['date'] ?? ''));
$upcoming = array_slice($upcoming, 0, 8);

// Recent (newest submissions first)
usort($bookings, fn($a, $b) => strcmp($b['received_at'] ?? '', $a['received_at'] ?? ''));
$recent = array_slice($bookings, 0, 15);

function badge_class(string $s): string {
    return 'badge-' . strtolower($s);
}
function statusSelect(array $b): string {
    $file = htmlspecialchars($b['_file'] ?? '', ENT_QUOTES);
    $cur  = $b['status'] ?? 'Pending';
    $opts = '';
    foreach (STATUSES as $s) {
        $sel = $s === $cur ? ' selected' : '';
        $opts .= "<option{$sel}>" . htmlspecialchars($s) . "</option>";
    }
    return '<form method="POST" class="status-form">'
         . '<input type="hidden" name="action" value="set_status">'
         . '<input type="hidden" name="file" value="' . $file . '">'
         . '<select name="status" onchange="this.form.submit()">' . $opts . '</select></form>';
}
?><!doctype html>
<html lang="en-AU">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex,nofollow" />
  <title>Dashboard | Shining Headlights Australia</title>
  <link rel="stylesheet" href="/css/admin.css?v=<?= ASSET_VER ?>" />
</head>
<body class="admin-app">
  <div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
      <a class="admin-logo" href="/dashboard">
        <img src="/assets/shining-headlights-isotype.svg" alt="" width="34" height="40" />
        <span>Shining Admin</span>
      </a>
      <nav class="admin-nav">
        <a class="is-active" href="/dashboard"><span>Dashboard</span></a>
        <a href="/dashboard#recent"><span>Bookings</span></a>
        <a href="/dashboard#upcoming"><span>Calendar</span></a>
        <a href="/dashboard#recent"><span>Customers</span></a>
        <a href="/services"><span>Services</span></a>
        <a href="/before-after"><span>Reviews</span></a>
        <a href="/admin"><span>Settings</span></a>
      </nav>
      <a class="admin-logout" href="/logout">Logout</a>
    </aside>

    <!-- Main -->
    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <h1>Dashboard</h1>
          <p>Welcome back, <?= htmlspecialchars($_SESSION['admin_user'] ?? 'admin') ?>.</p>
        </div>
        <div class="bne-clock" aria-label="Brisbane time"
             data-brisbane
             data-init-time="<?= $now->format('g:i:s A') ?>"
             data-init-date="<?= $now->format('l, j F Y') ?>">
          <span class="bne-time" data-bne-time><?= $now->format('g:i:s A') ?></span>
          <span class="bne-date" data-bne-date><?= $now->format('l, j F Y') ?></span>
          <span class="bne-label">Brisbane Time</span>
        </div>
      </header>

      <!-- Summary cards -->
      <section class="stat-grid">
        <div class="stat-card"><span class="stat-num"><?= $stats['today'] ?></span><span class="stat-label">Today's bookings</span></div>
        <div class="stat-card"><span class="stat-num"><?= $stats['week'] ?></span><span class="stat-label">This week</span></div>
        <div class="stat-card"><span class="stat-num"><?= $stats['pending'] ?></span><span class="stat-label">Pending</span></div>
        <div class="stat-card"><span class="stat-num"><?= $stats['completed'] ?></span><span class="stat-label">Completed</span></div>
        <div class="stat-card highlight"><span class="stat-num"><?= $stats['total'] ?></span><span class="stat-label">Total bookings</span></div>
      </section>

      <!-- Upcoming -->
      <section class="panel" id="upcoming">
        <h2>Upcoming bookings</h2>
        <?php if (!$upcoming): ?>
          <p class="empty">No upcoming bookings.</p>
        <?php else: ?>
          <ul class="upcoming-list">
            <?php foreach ($upcoming as $b): ?>
            <li>
              <span class="up-date"><?= htmlspecialchars($b['date'] ?? '') ?><small><?= htmlspecialchars($b['time'] ?? '') ?></small></span>
              <span class="up-name"><?= htmlspecialchars($b['name'] ?? '') ?><small><?= htmlspecialchars($b['suburb'] ?? '') ?></small></span>
              <span class="up-service"><?= htmlspecialchars($b['package'] ?? '') ?></span>
              <span class="badge <?= badge_class($b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span>
              <a class="up-action" href="tel:<?= htmlspecialchars($b['phone'] ?? '') ?>">Call</a>
            </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </section>

      <!-- Recent table -->
      <section class="panel" id="recent">
        <h2>Recent bookings</h2>
        <?php if (!$recent): ?>
          <p class="empty">No bookings yet. New submissions from the booking form will appear here.</p>
        <?php else: ?>
          <div class="table-wrap">
          <table class="bookings-table">
            <thead><tr>
              <th>Customer</th><th>Contact</th><th>Address / Suburb</th><th>Date</th><th>Time</th><th>Status</th><th>Notes</th>
            </tr></thead>
            <tbody>
            <?php foreach ($recent as $b): ?>
              <tr>
                <td><?= htmlspecialchars($b['name'] ?? '') ?><small><?= htmlspecialchars($b['vehicle'] ?? '') ?></small></td>
                <td><a href="tel:<?= htmlspecialchars($b['phone'] ?? '') ?>"><?= htmlspecialchars($b['phone'] ?? '') ?></a><small><?= htmlspecialchars($b['email'] ?? '') ?></small></td>
                <td><?= htmlspecialchars($b['full_address'] ?: ($b['suburb'] ?? '')) ?></td>
                <td><?= htmlspecialchars($b['date'] ?? '') ?></td>
                <td><?= htmlspecialchars($b['time'] ?? '') ?></td>
                <td><span class="badge <?= badge_class($b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span><?= statusSelect($b) ?></td>
                <td class="notes-cell"><?= htmlspecialchars($b['message'] ?? '') ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          </div>
        <?php endif; ?>
      </section>
    </main>
  </div>
  <script src="/js/dashboard.js?v=<?= ASSET_VER ?>"></script>
</body>
</html>
