<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/includes/bookings.php';

$error = '';
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $id = clean_text($_POST['id'] ?? '', 80);
        if ($id !== '') {
            if ($action === 'update_booking') {
                updateBooking($id, [
                    'status' => $_POST['status'] ?? '',
                    'adminNotes' => $_POST['adminNotes'] ?? '',
                    'followUpRequired' => !empty($_POST['followUpRequired']),
                    'followUpDate' => $_POST['followUpDate'] ?? '',
                    'preferredDate' => $_POST['preferredDate'] ?? '',
                    'preferredTimeWindow' => $_POST['preferredTimeWindow'] ?? '',
                ]);
            } elseif ($action === 'quick_status') {
                updateBooking($id, ['status' => $_POST['status'] ?? '']);
            }
        }
        $redirect = '/dashboard';
        if (!empty($_POST['selectedDate'])) $redirect .= '?date=' . urlencode((string)$_POST['selectedDate']);
        if (!empty($_POST['detail'])) $redirect .= (str_contains($redirect, '?') ? '&' : '?') . 'id=' . urlencode((string)$_POST['detail']);
        header('Location: ' . $redirect);
        exit;
    }

    $bookings = readBookings();
} catch (Throwable $e) {
    booking_log_error('Dashboard failed: ' . $e->getMessage());
    $bookings = [];
    $error = 'Unable to load bookings. Please check server storage or permissions.';
}

$stats = getBookingStats($bookings);
$tz = new DateTimeZone('Australia/Brisbane');
$now = new DateTimeImmutable('now', $tz);
$selectedDate = clean_text($_GET['date'] ?? $now->format('Y-m-d'), 40);
$selectedId = clean_text($_GET['id'] ?? '', 80);
$selectedBooking = $selectedId ? getBookingById($selectedId) : null;

$search = strtolower(clean_text($_GET['search'] ?? '', 120));
$statusFilter = clean_text($_GET['status'] ?? '', 40);
$packageFilter = clean_text($_GET['package'] ?? '', 120);
$dateFilter = clean_text($_GET['filter_date'] ?? '', 40);
$sort = clean_text($_GET['sort'] ?? 'newest', 40);

$filtered = array_values(array_filter($bookings, function ($b) use ($search, $statusFilter, $packageFilter, $dateFilter) {
    if ($statusFilter !== '' && ($b['status'] ?? '') !== $statusFilter) return false;
    if ($packageFilter !== '' && ($b['packageSelected'] ?? '') !== $packageFilter) return false;
    if ($dateFilter !== '' && ($b['preferredDate'] ?? '') !== $dateFilter) return false;
    if ($search !== '') {
        $haystack = strtolower(implode(' ', [
            $b['fullName'] ?? '', $b['phone'] ?? '', $b['email'] ?? '',
            $b['vehicleMakeModel'] ?? '', $b['addressOrSuburb'] ?? '',
        ]));
        if (!str_contains($haystack, $search)) return false;
    }
    return true;
}));

usort($filtered, function ($a, $b) use ($sort) {
    if ($sort === 'preferred_date') return strcmp(($a['preferredDate'] ?? '') . ($a['preferredTimeWindow'] ?? ''), ($b['preferredDate'] ?? '') . ($b['preferredTimeWindow'] ?? ''));
    return strcmp((string)($b['createdAt'] ?? ''), (string)($a['createdAt'] ?? ''));
});

$dailyBookings = getBookingsByDate($selectedDate);
$monthCursor = DateTimeImmutable::createFromFormat('!Y-m-d', substr($selectedDate, 0, 7) . '-01', $tz) ?: $now->modify('first day of this month');
$monthStart = $monthCursor->modify('first day of this month');
$monthEnd = $monthCursor->modify('last day of this month');
$firstWeekday = (int)$monthStart->format('N');
$daysInMonth = (int)$monthEnd->format('j');
$countsByDate = [];
foreach ($bookings as $b) {
    $d = $b['preferredDate'] ?? '';
    if ($d !== '') $countsByDate[$d] = ($countsByDate[$d] ?? 0) + 1;
}

function h(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function status_label(string $status): string { return ucwords(str_replace('_', ' ', $status)); }
function badge_class_admin(string $status): string { return 'badge-' . str_replace('_', '-', strtolower($status)); }
function status_options(string $current): string {
    $html = '';
    foreach (BOOKING_STATUSES as $status) {
        $selected = $status === $current ? ' selected' : '';
        $html .= '<option value="' . h($status) . '"' . $selected . '>' . h(status_label($status)) . '</option>';
    }
    return $html;
}
function package_options(string $current = ''): string {
    $html = '<option value="">All packages</option>';
    foreach (array_keys(BOOKING_PACKAGE_PRICES) as $package) {
        $selected = $package === $current ? ' selected' : '';
        $html .= '<option value="' . h($package) . '"' . $selected . '>' . h($package) . '</option>';
    }
    return $html;
}
?><!doctype html>
<html lang="en-AU">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex,nofollow" />
  <title>Admin Dashboard | Shining Headlights Australia</title>
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
        <a class="is-active" href="/dashboard"><span>Dashboard</span></a>
        <a href="#calendar"><span>Calendar</span></a>
        <a href="#bookings"><span>Bookings</span></a>
        <a href="#metrics"><span>Reports</span></a>
      </nav>
      <a class="admin-logout" href="/logout">Logout</a>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <h1>Admin Dashboard</h1>
          <p>Bookings, calendar, metrics and follow-ups.</p>
        </div>
        <div class="bne-clock" data-brisbane>
          <span class="bne-time" data-bne-time><?= $now->format('g:i:s A') ?></span>
          <span class="bne-date" data-bne-date><?= $now->format('l, j F Y') ?></span>
          <span class="bne-label">Brisbane Time</span>
        </div>
      </header>

      <?php if ($error): ?><p class="admin-error" role="alert"><?= h($error) ?></p><?php endif; ?>

      <section class="stat-grid" id="metrics" aria-label="Dashboard metrics">
        <div class="stat-card"><span class="stat-num"><?= $stats['today'] ?></span><span class="stat-label">Today's Bookings</span></div>
        <div class="stat-card"><span class="stat-num"><?= $stats['new'] ?></span><span class="stat-label">New Leads</span></div>
        <div class="stat-card"><span class="stat-num"><?= $stats['confirmed'] ?></span><span class="stat-label">Confirmed Jobs</span></div>
        <div class="stat-card"><span class="stat-num"><?= $stats['completed'] ?></span><span class="stat-label">Completed Jobs</span></div>
        <div class="stat-card highlight"><span class="stat-num">$<?= $stats['estimatedRevenue'] ?></span><span class="stat-label">Estimated Revenue</span></div>
        <div class="stat-card"><span class="stat-num"><?= $stats['pendingFollowUps'] ?></span><span class="stat-label">Pending Follow-ups</span></div>
        <div class="stat-card"><span class="stat-num"><?= $stats['week'] ?></span><span class="stat-label">This Week's Bookings</span></div>
        <div class="stat-card"><span class="stat-num"><?= $stats['month'] ?></span><span class="stat-label">This Month's Bookings</span></div>
        <div class="stat-card"><span class="stat-num"><?= $stats['cancelled'] ?></span><span class="stat-label">Cancelled Bookings</span></div>
        <div class="stat-card wide"><span class="stat-num small"><?= h($stats['mostSelectedPackage']) ?></span><span class="stat-label">Most Selected Package</span></div>
      </section>

      <section class="dashboard-grid">
        <div class="panel" id="calendar">
          <div class="panel-head">
            <h2>Booking Calendar</h2>
            <span><?= h($monthStart->format('F Y')) ?></span>
          </div>
          <div class="calendar-grid calendar-weekdays">
            <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day): ?><span><?= $day ?></span><?php endforeach; ?>
          </div>
          <div class="calendar-grid">
            <?php for ($i = 1; $i < $firstWeekday; $i++): ?><span class="calendar-day is-empty"></span><?php endfor; ?>
            <?php for ($day = 1; $day <= $daysInMonth; $day++):
                $date = $monthStart->setDate((int)$monthStart->format('Y'), (int)$monthStart->format('m'), $day)->format('Y-m-d');
                $count = $countsByDate[$date] ?? 0;
            ?>
              <a class="calendar-day<?= $date === $selectedDate ? ' is-selected' : '' ?><?= $count ? ' has-bookings' : '' ?>" href="/dashboard?date=<?= h($date) ?>#calendar">
                <span><?= $day ?></span><?php if ($count): ?><strong><?= $count ?></strong><?php endif; ?>
              </a>
            <?php endfor; ?>
          </div>
        </div>

        <div class="panel">
          <div class="panel-head">
            <h2>Bookings for selected day</h2>
            <span><?= h($selectedDate) ?></span>
          </div>
          <?php if (!$dailyBookings): ?>
            <p class="empty">No bookings found.</p>
          <?php else: ?>
            <ul class="daily-list">
              <?php foreach ($dailyBookings as $b): ?>
              <li>
                <div><strong><?= h($b['preferredTimeWindow']) ?></strong><span><?= h($b['fullName']) ?> · <?= h($b['phone']) ?></span></div>
                <div><span><?= h($b['addressOrSuburb']) ?></span><small><?= h($b['vehicleMakeModel']) ?> · <?= h($b['packageSelected']) ?></small></div>
                <form method="post" class="inline-status">
                  <input type="hidden" name="action" value="quick_status" />
                  <input type="hidden" name="id" value="<?= h($b['id']) ?>" />
                  <input type="hidden" name="selectedDate" value="<?= h($selectedDate) ?>" />
                  <select name="status" onchange="this.form.submit()"><?= status_options($b['status']) ?></select>
                </form>
                <a class="up-action" href="/dashboard?date=<?= h($selectedDate) ?>&id=<?= h($b['id']) ?>#detail">View details</a>
              </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </section>

      <section class="panel" id="bookings">
        <div class="panel-head">
          <h2>Bookings</h2>
          <span><?= count($filtered) ?> shown</span>
        </div>
        <form class="admin-filters" method="get">
          <input type="search" name="search" value="<?= h($_GET['search'] ?? '') ?>" placeholder="Search name, phone, email, vehicle, suburb" />
          <select name="status"><option value="">All statuses</option><?= status_options($statusFilter) ?></select>
          <select name="package"><?= package_options($packageFilter) ?></select>
          <input type="date" name="filter_date" value="<?= h($dateFilter) ?>" />
          <select name="sort">
            <option value="newest"<?= $sort === 'newest' ? ' selected' : '' ?>>Sort by newest</option>
            <option value="preferred_date"<?= $sort === 'preferred_date' ? ' selected' : '' ?>>Sort by preferred date</option>
          </select>
          <button type="submit">Filter</button>
        </form>

        <?php if (!$filtered): ?>
          <p class="empty">No bookings found.</p>
        <?php else: ?>
          <div class="table-wrap">
            <table class="bookings-table">
              <thead><tr><th>Date</th><th>Time window</th><th>Customer</th><th>Phone</th><th>Location</th><th>Vehicle</th><th>Package</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
              <tbody>
              <?php foreach ($filtered as $b): ?>
                <tr>
                  <td><?= h($b['preferredDate']) ?></td>
                  <td><?= h($b['preferredTimeWindow']) ?></td>
                  <td><?= h($b['fullName']) ?><small><?= h($b['email']) ?></small></td>
                  <td><a href="tel:<?= h($b['phone']) ?>"><?= h($b['phone']) ?></a></td>
                  <td><?= h($b['addressOrSuburb']) ?></td>
                  <td><?= h($b['vehicleMakeModel']) ?></td>
                  <td><?= h($b['packageSelected']) ?></td>
                  <td><span class="badge <?= badge_class_admin($b['status']) ?>"><?= h(status_label($b['status'])) ?></span></td>
                  <td><?= h(substr($b['createdAt'], 0, 10)) ?></td>
                  <td><a class="up-action" href="/dashboard?date=<?= h($b['preferredDate'] ?: $selectedDate) ?>&id=<?= h($b['id']) ?>#detail">View details</a></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>

      <section class="dashboard-grid">
        <div class="panel">
          <h2>Package breakdown</h2>
          <?php if (!$stats['packageBreakdown']): ?><p class="empty">No bookings found.</p><?php else: ?>
            <ul class="metric-list"><?php foreach ($stats['packageBreakdown'] as $label => $count): ?><li><span><?= h($label) ?></span><strong><?= $count ?></strong></li><?php endforeach; ?></ul>
          <?php endif; ?>
        </div>
        <div class="panel">
          <h2>Top suburbs / locations</h2>
          <?php if (!$stats['topSuburbs']): ?><p class="empty">No bookings found.</p><?php else: ?>
            <ul class="metric-list"><?php foreach ($stats['topSuburbs'] as $label => $count): ?><li><span><?= h($label) ?></span><strong><?= $count ?></strong></li><?php endforeach; ?></ul>
          <?php endif; ?>
        </div>
      </section>

      <section class="panel" id="detail">
        <h2>Booking details</h2>
        <?php if (!$selectedBooking): ?>
          <p class="empty">Select a booking to view details.</p>
        <?php else: $b = $selectedBooking; ?>
          <form method="post" class="detail-grid">
            <input type="hidden" name="action" value="update_booking" />
            <input type="hidden" name="id" value="<?= h($b['id']) ?>" />
            <input type="hidden" name="selectedDate" value="<?= h($selectedDate) ?>" />
            <input type="hidden" name="detail" value="<?= h($b['id']) ?>" />
            <div class="detail-card">
              <h3>Customer</h3>
              <p><strong><?= h($b['fullName']) ?></strong></p>
              <p><a href="tel:<?= h($b['phone']) ?>"><?= h($b['phone']) ?></a></p>
              <p><a href="mailto:<?= h($b['email']) ?>"><?= h($b['email']) ?></a></p>
              <p><?= h($b['addressOrSuburb']) ?></p>
            </div>
            <div class="detail-card">
              <h3>Booking</h3>
              <label>Status<select name="status"><?= status_options($b['status']) ?></select></label>
              <label>Date<input type="date" name="preferredDate" value="<?= h($b['preferredDate']) ?>" /></label>
              <label>Time window<input name="preferredTimeWindow" value="<?= h($b['preferredTimeWindow']) ?>" /></label>
              <p><?= h($b['vehicleMakeModel']) ?> · <?= h($b['packageSelected']) ?></p>
            </div>
            <div class="detail-card">
              <h3>Internal notes</h3>
              <label>Admin notes<textarea name="adminNotes" rows="5"><?= h($b['adminNotes']) ?></textarea></label>
              <label class="check-row"><input type="checkbox" name="followUpRequired" <?= !empty($b['followUpRequired']) ? 'checked' : '' ?> /> Follow-up required</label>
              <label>Follow-up date<input type="date" name="followUpDate" value="<?= h($b['followUpDate']) ?>" /></label>
            </div>
            <div class="detail-card">
              <h3>Full information</h3>
              <p>Condition: <?= h($b['headlightCondition']) ?></p>
              <p>Location type: <?= h($b['vehicleLocationType']) ?></p>
              <p>Headlights: <?= h($b['numberOfHeadlights']) ?></p>
              <p>Preferred contact: <?= h($b['preferredContactMethod']) ?></p>
              <p>Message: <?= h($b['message']) ?></p>
              <p>Created: <?= h($b['createdAt']) ?></p>
              <p>Updated: <?= h($b['updatedAt']) ?></p>
            </div>
            <button class="auth-submit detail-save" type="submit">Save booking</button>
          </form>
        <?php endif; ?>
      </section>
    </main>
  </div>
  <script src="/js/dashboard.js?v=<?= ASSET_VER ?>"></script>
</body>
</html>
