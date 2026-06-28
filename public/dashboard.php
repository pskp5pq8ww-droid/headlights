<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/includes/bookings.php';
require_once __DIR__ . '/includes/services.php';

$error = '';
$notice = '';
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $id = clean_text($_POST['id'] ?? '', 80);
        if ($action === 'create_service' || $action === 'update_service') {
            $services = read_services(false);
            $payload = service_payload_from_post($_POST);
            if ($action === 'create_service') {
                $service = validate_service_payload($payload);
                foreach ($services as $existing) {
                    if ($existing['slug'] === $service['slug']) throw new InvalidArgumentException('Slug must be unique.');
                }
                $services[] = $service;
            } else {
                if ($id === '') throw new InvalidArgumentException('Missing service id.');
                $updated = false;
                foreach ($services as &$existing) {
                    if ($existing['id'] !== $id) continue;
                    $candidate = validate_service_payload($payload, $existing);
                    foreach ($services as $other) {
                        if ($other['id'] !== $id && $other['slug'] === $candidate['slug']) throw new InvalidArgumentException('Slug must be unique.');
                    }
                    $existing = $candidate;
                    $updated = true;
                    break;
                }
                unset($existing);
                if (!$updated) throw new InvalidArgumentException('Service not found.');
            }
            write_services($services);
            header('Location: /dashboard?notice=' . urlencode($action === 'create_service' ? 'Service created.' : 'Service updated.') . '#services');
            exit;
        } elseif ($action === 'deactivate_service') {
            $services = read_services(false);
            foreach ($services as &$service) {
                if ($service['id'] !== $id) continue;
                $service['isActive'] = false;
                $service['updatedAt'] = booking_now();
                break;
            }
            unset($service);
            write_services($services);
            header('Location: /dashboard?notice=' . urlencode('Service deactivated.') . '#services');
            exit;
        } elseif ($id !== '') {
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
            } elseif ($action === 'delete_booking') {
                if (($_POST['confirm_delete'] ?? '') === 'yes') {
                    deleteBooking($id);
                }
            }
        }
        $redirect = '/dashboard';
        if (!empty($_POST['selectedDate'])) $redirect .= '?date=' . urlencode((string)$_POST['selectedDate']);
        if (!empty($_POST['detail'])) $redirect .= (str_contains($redirect, '?') ? '&' : '?') . 'id=' . urlencode((string)$_POST['detail']);
        $view = clean_text($_POST['view'] ?? '', 40);
        if ($view !== '' && in_array($view, ['overview', 'calendar', 'bookings', 'services', 'reports', 'detail'], true)) {
            $redirect .= '#' . $view;
        }
        header('Location: ' . $redirect);
        exit;
    }

    $bookings = readBookings();
    $services = read_services(false);
} catch (Throwable $e) {
    booking_log_error('Dashboard failed: ' . $e->getMessage());
    $bookings = [];
    $services = [];
    $error = 'Unable to load bookings. Please check server storage or permissions.';
}
$notice = clean_text($_GET['notice'] ?? '', 180);

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
$dateRange = clean_text($_GET['date_range'] ?? '', 40);
$dateFrom = clean_text($_GET['date_from'] ?? '', 40);
$dateTo = clean_text($_GET['date_to'] ?? '', 40);
$sort = clean_text($_GET['sort'] ?? 'newest', 40);

$filtered = array_values(array_filter($bookings, function ($b) use ($search, $statusFilter, $packageFilter, $dateFilter, $dateRange, $dateFrom, $dateTo, $now) {
    if ($statusFilter !== '' && ($b['status'] ?? '') !== $statusFilter) return false;
    if ($packageFilter !== '' && ($b['packageSelected'] ?? '') !== $packageFilter) return false;
    $preferred = (string)($b['preferredDate'] ?? '');
    if ($dateFilter !== '' && $preferred !== $dateFilter) return false;
    if ($dateRange === 'today' && $preferred !== $now->format('Y-m-d')) return false;
    if ($dateRange === 'week') {
        $from = $now->modify('monday this week')->format('Y-m-d');
        $to = $now->modify('sunday this week')->format('Y-m-d');
        if ($preferred === '' || $preferred < $from || $preferred > $to) return false;
    }
    if ($dateRange === 'month' && !str_starts_with($preferred, $now->format('Y-m'))) return false;
    if ($dateRange === 'custom') {
        if ($dateFrom !== '' && ($preferred === '' || $preferred < $dateFrom)) return false;
        if ($dateTo !== '' && ($preferred === '' || $preferred > $dateTo)) return false;
    }
    if ($search !== '') {
        $haystack = strtolower(implode(' ', [
            $b['fullName'] ?? '', $b['phone'] ?? '', $b['email'] ?? '',
            $b['vehicleMakeModel'] ?? '', $b['addressOrSuburb'] ?? '',
            $b['formattedAddress'] ?? '', $b['addressSuburb'] ?? '', $b['addressPostcode'] ?? '',
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
function maps_link(array $b): string {
    if (!empty($b['addressLat']) && !empty($b['addressLng'])) return 'https://www.google.com/maps?q=' . rawurlencode($b['addressLat'] . ',' . $b['addressLng']);
    $address = $b['formattedAddress'] ?: $b['addressOrSuburb'] ?? '';
    return $address !== '' ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($address) : '';
}
function booking_price(array $b): float {
    $estimate = (float)($b['estimatedTotal'] ?? 0);
    return $estimate > 0 ? $estimate : package_price((string)($b['packageSelected'] ?? ''));
}
function admin_money(float $amount): string {
    return '$' . number_format($amount, fmod($amount, 1.0) === 0.0 ? 0 : 2);
}
function payment_label(array $b): string {
    $status = $b['paymentStatus'] ?? 'unpaid';
    return ucfirst($status);
}
function payment_badge_class(array $b): string {
    $status = strtolower((string)($b['paymentStatus'] ?? 'unpaid'));
    return 'pay-badge pay-' . preg_replace('/[^a-z]/', '', $status);
}
function status_options(string $current): string {
    $html = '';
    $statuses = ['new', 'contacted', 'confirmed', 'completed', 'cancelled'];
    if ($current !== '' && !in_array($current, $statuses, true)) $statuses[] = $current;
    foreach ($statuses as $status) {
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
function service_payload_from_post(array $post): array {
    return [
        'name' => $post['name'] ?? '',
        'slug' => $post['slug'] ?? '',
        'category' => $post['category'] ?? '',
        'shortDescription' => $post['shortDescription'] ?? '',
        'longDescription' => $post['longDescription'] ?? '',
        'inclusions' => $post['inclusions'] ?? '',
        'exclusions' => $post['exclusions'] ?? '',
        'priceSmall' => $post['priceSmall'] ?? 0,
        'priceMedium' => $post['priceMedium'] ?? 0,
        'priceLarge' => $post['priceLarge'] ?? 0,
        'priceSingle' => $post['priceSingle'] ?? 0,
        'priceExtraPair' => $post['priceExtraPair'] ?? 0,
        'estimatedTime' => $post['estimatedTime'] ?? '',
        'isAddOn' => !empty($post['isAddOn']),
        'isFeatured' => !empty($post['isFeatured']),
        'isActive' => !empty($post['isActive']),
        'sortOrder' => $post['sortOrder'] ?? 100,
        'imageKey' => $post['imageKey'] ?? '',
        'icon' => $post['icon'] ?? '',
        'termsNote' => $post['termsNote'] ?? '',
    ];
}
function service_lines(array $items): string {
    return implode("\n", array_map('strval', $items));
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
        <a class="is-active" href="#overview" data-admin-view-link="overview"><span>Dashboard</span></a>
        <a href="#calendar" data-admin-view-link="calendar"><span>Calendar</span></a>
        <a href="#bookings" data-admin-view-link="bookings"><span>Bookings</span></a>
        <a href="#services" data-admin-view-link="services"><span>Services</span></a>
        <a href="#reports" data-admin-view-link="reports"><span>Reports</span></a>
        <a href="/analytics"><span>Analytics</span></a>
        <a href="/bookings-map"><span>Map</span></a>
        <a href="/users"><span>Users</span></a>
        <a href="/backups"><span>Backups</span></a>
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
      <?php if ($notice): ?><p class="admin-notice" role="status"><?= h($notice) ?></p><?php endif; ?>

      <section class="admin-view is-active" id="overview" data-admin-view="overview" aria-label="Dashboard overview">
        <div class="stat-grid" aria-label="Dashboard metrics">
          <div class="stat-card"><span class="stat-num"><?= $stats['total'] ?></span><span class="stat-label">Total Bookings</span></div>
          <div class="stat-card"><span class="stat-num"><?= $stats['new'] ?></span><span class="stat-label">New Bookings</span></div>
          <div class="stat-card"><span class="stat-num"><?= $stats['confirmed'] ?></span><span class="stat-label">Confirmed Bookings</span></div>
          <div class="stat-card"><span class="stat-num"><?= $stats['completed'] ?></span><span class="stat-label">Completed Bookings</span></div>
          <div class="stat-card"><span class="stat-num"><?= $stats['cancelled'] ?></span><span class="stat-label">Cancelled Bookings</span></div>
          <div class="stat-card"><span class="stat-num"><?= $stats['today'] ?></span><span class="stat-label">Today's Bookings</span></div>
          <div class="stat-card"><span class="stat-num"><?= $stats['contacted'] ?></span><span class="stat-label">Contacted</span></div>
          <div class="stat-card highlight"><span class="stat-num">$<?= number_format((float)$stats['paidRevenue'], 2) ?></span><span class="stat-label">Paid Revenue (Square)</span></div>
          <div class="stat-card"><span class="stat-num"><?= $stats['paid'] ?></span><span class="stat-label">Paid Bookings</span></div>
          <div class="stat-card"><span class="stat-num">$<?= $stats['estimatedRevenue'] ?></span><span class="stat-label">Estimated Revenue</span></div>
          <div class="stat-card"><span class="stat-num"><?= $stats['pendingFollowUps'] ?></span><span class="stat-label">Pending Follow-ups</span></div>
          <div class="stat-card wide"><span class="stat-num small"><?= h($stats['mostSelectedPackage']) ?></span><span class="stat-label">Most Selected Package</span></div>
        </div>
      </section>

      <section class="admin-view" id="calendar" data-admin-view="calendar" aria-label="Calendar">
        <div class="dashboard-grid">
        <div class="panel">
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
                  <input type="hidden" name="view" value="calendar" />
                  <select name="status" onchange="this.form.submit()"><?= status_options($b['status']) ?></select>
                </form>
                <a class="up-action" href="/dashboard?date=<?= h($selectedDate) ?>&id=<?= h($b['id']) ?>#detail">View details</a>
              </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
        </div>
      </section>

      <section class="admin-view panel" id="bookings" data-admin-view="bookings" aria-label="Bookings">
        <div class="panel-head">
          <h2>Bookings</h2>
          <span><?= count($filtered) ?> shown</span>
        </div>
        <form class="admin-filters" method="get" action="/dashboard#bookings">
          <input type="search" name="search" value="<?= h($_GET['search'] ?? '') ?>" placeholder="Search name, phone, email, vehicle, suburb" />
          <select name="status"><option value="">All statuses</option><?= status_options($statusFilter) ?></select>
          <select name="package"><?= package_options($packageFilter) ?></select>
          <select name="date_range">
            <option value="">Any date</option>
            <option value="today"<?= $dateRange === 'today' ? ' selected' : '' ?>>Today</option>
            <option value="week"<?= $dateRange === 'week' ? ' selected' : '' ?>>This week</option>
            <option value="month"<?= $dateRange === 'month' ? ' selected' : '' ?>>This month</option>
            <option value="custom"<?= $dateRange === 'custom' ? ' selected' : '' ?>>Custom range</option>
          </select>
          <input type="date" name="filter_date" value="<?= h($dateFilter) ?>" />
          <input type="date" name="date_from" value="<?= h($dateFrom) ?>" aria-label="Date from" />
          <input type="date" name="date_to" value="<?= h($dateTo) ?>" aria-label="Date to" />
          <select name="sort">
            <option value="newest"<?= $sort === 'newest' ? ' selected' : '' ?>>Sort by newest</option>
            <option value="preferred_date"<?= $sort === 'preferred_date' ? ' selected' : '' ?>>Sort by preferred date</option>
          </select>
          <button type="submit">Filter</button>
        </form>

        <?php if (!$filtered): ?>
          <p class="empty">No bookings found.</p>
        <?php else: ?>
          <div class="booking-card-list">
            <?php foreach ($filtered as $b): $mapUrl = maps_link($b); $price = booking_price($b); ?>
              <article class="admin-booking-card" id="booking-<?= h($b['id']) ?>" data-booking-card>
                <button type="button" class="booking-card-summary" data-booking-toggle aria-expanded="false">
                  <span class="bc-name"><?= h($b['fullName'] ?: 'Unnamed customer') ?></span>
                  <span class="bc-when"><?= h(trim(($b['preferredDate'] ?? '') . ' · ' . ($b['preferredTimeWindow'] ?? ''), ' ·')) ?: 'No date' ?></span>
                  <span class="bc-amount"><?= $price > 0 ? h(admin_money((float)$price)) : 'Quote' ?></span>
                  <span class="badge <?= badge_class_admin($b['status']) ?>"><?= h(status_label($b['status'])) ?></span>
                  <span class="<?= payment_badge_class($b) ?>"><?= h(payment_label($b)) ?></span>
                  <svg class="bc-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="booking-card-body" hidden>
                <div class="booking-card-main">
                  <div class="booking-quick-grid">
                    <div>
                      <span class="field-label">Email</span>
                      <a class="field-value" href="mailto:<?= h($b['email']) ?>"><?= h($b['email'] ?: 'No email') ?></a>
                    </div>
                    <div>
                      <span class="field-label">Phone</span>
                      <a class="field-value" href="tel:<?= h($b['phone']) ?>"><?= h($b['phone'] ?: 'No phone') ?></a>
                    </div>
                    <div>
                      <span class="field-label">Location</span>
                      <span class="field-value"><?= h($b['addressSuburb'] ?: $b['addressOrSuburb']) ?></span>
                    </div>
                    <div>
                      <span class="field-label">Vehicle</span>
                      <span class="field-value"><?= h($b['vehicleMakeModel'] ?: 'Vehicle not supplied') ?></span>
                    </div>
                    <div>
                      <span class="field-label">Preferred</span>
                      <span class="field-value"><?= h(trim(($b['preferredDate'] ?? '') . ' ' . ($b['preferredTimeWindow'] ?? ''))) ?></span>
                    </div>
                    <div>
                      <span class="field-label">Price</span>
                      <span class="field-value"><?= $price > 0 ? h(admin_money((float)$price)) : 'Quote' ?></span>
                    </div>
                    <div>
                      <span class="field-label">Paid</span>
                      <span class="field-value"><?= ($b['paymentStatus'] ?? '') === 'paid' ? h(($b['currency'] ?: 'AUD') . ' $' . number_format((float)$b['amount'], 2)) : h(payment_label($b)) ?></span>
                    </div>
                  </div>
                </div>
                <div class="booking-card-actions">
                  <button type="button" class="mini-action" data-copy="<?= h($b['email']) ?>">Copy Email</button>
                  <button type="button" class="mini-action" data-copy="<?= h($b['phone']) ?>">Copy Phone</button>
                  <?php if (!empty($b['email'])): ?><a class="mini-action" href="mailto:<?= h($b['email']) ?>">Open Mail</a><?php endif; ?>
                  <?php if (!empty($b['phone'])): ?><a class="mini-action call-action" href="tel:<?= h($b['phone']) ?>">Call</a><?php endif; ?>
                  <?php if ($mapUrl !== ''): ?><a class="mini-action" href="<?= h($mapUrl) ?>" target="_blank" rel="noopener">Open Maps</a><?php endif; ?>
                  <a class="mini-action is-primary" href="/dashboard?date=<?= h($b['preferredDate'] ?: $selectedDate) ?>&id=<?= h($b['id']) ?>#detail">View Details</a>
                  <form method="post" class="quick-status-form">
                    <input type="hidden" name="action" value="quick_status" />
                    <input type="hidden" name="id" value="<?= h($b['id']) ?>" />
                    <input type="hidden" name="selectedDate" value="<?= h($selectedDate) ?>" />
                    <input type="hidden" name="view" value="bookings" />
                    <select name="status" onchange="this.form.submit()"><?= status_options($b['status']) ?></select>
                  </form>
                </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <section class="admin-view panel" id="services" data-admin-view="services" aria-label="Services">
        <div class="panel-head">
          <h2>Services</h2>
          <span><?= count($services) ?> stored</span>
        </div>

        <details class="service-admin-create">
          <summary>Create service</summary>
          <form method="post" class="service-admin-form">
            <input type="hidden" name="action" value="create_service" />
            <label>Name<input name="name" required /></label>
            <label>Slug<input name="slug" placeholder="quick-exterior-wash" /></label>
            <label>Category<input name="category" value="Add-on" /></label>
            <label>Short description<textarea name="shortDescription" rows="2"></textarea></label>
            <label>Long description<textarea name="longDescription" rows="3"></textarea></label>
            <div class="service-price-admin-grid">
              <label>Small<input name="priceSmall" type="number" min="0" step="0.01" value="0" required /></label>
              <label>Medium<input name="priceMedium" type="number" min="0" step="0.01" value="0" required /></label>
              <label>Large<input name="priceLarge" type="number" min="0" step="0.01" value="0" required /></label>
              <label>Single<input name="priceSingle" type="number" min="0" step="0.01" value="0" /></label>
              <label>Extra pair<input name="priceExtraPair" type="number" min="0" step="0.01" value="0" /></label>
              <label>Sort order<input name="sortOrder" type="number" value="120" /></label>
            </div>
            <label>Estimated time<input name="estimatedTime" /></label>
            <label>Inclusions <small>one per line</small><textarea name="inclusions" rows="4"></textarea></label>
            <label>Exclusions <small>one per line</small><textarea name="exclusions" rows="3"></textarea></label>
            <label>Terms note<textarea name="termsNote" rows="3"></textarea></label>
            <div class="service-admin-checks">
              <label><input type="checkbox" name="isActive" checked /> Active</label>
              <label><input type="checkbox" name="isFeatured" /> Featured</label>
              <label><input type="checkbox" name="isAddOn" checked /> Add-on</label>
            </div>
            <button class="auth-submit service-save-btn" type="submit">Create service</button>
          </form>
        </details>

        <div class="service-admin-list">
<?php foreach ($services as $service): ?>
          <details class="service-admin-item">
            <summary>
              <span><?= h($service['name']) ?></span>
              <small><?= h($service['slug']) ?> · <?= !empty($service['isActive']) ? 'Active' : 'Inactive' ?> · from $<?= h((string)service_from_price($service)) ?></small>
            </summary>
            <form method="post" class="service-admin-form">
              <input type="hidden" name="action" value="update_service" />
              <input type="hidden" name="id" value="<?= h($service['id']) ?>" />
              <label>Name<input name="name" value="<?= h($service['name']) ?>" required /></label>
              <label>Slug<input name="slug" value="<?= h($service['slug']) ?>" required /></label>
              <label>Category<input name="category" value="<?= h($service['category']) ?>" /></label>
              <label>Short description<textarea name="shortDescription" rows="2"><?= h($service['shortDescription']) ?></textarea></label>
              <label>Long description<textarea name="longDescription" rows="3"><?= h($service['longDescription']) ?></textarea></label>
              <div class="service-price-admin-grid">
                <label>Small<input name="priceSmall" type="number" min="0" step="0.01" value="<?= h($service['priceSmall']) ?>" required /></label>
                <label>Medium<input name="priceMedium" type="number" min="0" step="0.01" value="<?= h($service['priceMedium']) ?>" required /></label>
                <label>Large<input name="priceLarge" type="number" min="0" step="0.01" value="<?= h($service['priceLarge']) ?>" required /></label>
                <label>Single<input name="priceSingle" type="number" min="0" step="0.01" value="<?= h($service['priceSingle']) ?>" /></label>
                <label>Extra pair<input name="priceExtraPair" type="number" min="0" step="0.01" value="<?= h($service['priceExtraPair']) ?>" /></label>
                <label>Sort order<input name="sortOrder" type="number" value="<?= h($service['sortOrder']) ?>" /></label>
              </div>
              <label>Estimated time<input name="estimatedTime" value="<?= h($service['estimatedTime']) ?>" /></label>
              <label>Image key<input name="imageKey" value="<?= h($service['imageKey']) ?>" /></label>
              <label>Icon<input name="icon" value="<?= h($service['icon']) ?>" /></label>
              <label>Inclusions <small>one per line</small><textarea name="inclusions" rows="4"><?= h(service_lines($service['inclusions'])) ?></textarea></label>
              <label>Exclusions <small>one per line</small><textarea name="exclusions" rows="3"><?= h(service_lines($service['exclusions'])) ?></textarea></label>
              <label>Terms note<textarea name="termsNote" rows="3"><?= h($service['termsNote']) ?></textarea></label>
              <div class="service-admin-checks">
                <label><input type="checkbox" name="isActive" <?= !empty($service['isActive']) ? 'checked' : '' ?> /> Active</label>
                <label><input type="checkbox" name="isFeatured" <?= !empty($service['isFeatured']) ? 'checked' : '' ?> /> Featured</label>
                <label><input type="checkbox" name="isAddOn" <?= !empty($service['isAddOn']) ? 'checked' : '' ?> /> Add-on</label>
              </div>
              <div class="service-admin-actions">
                <button class="auth-submit service-save-btn" type="submit">Save service</button>
              </div>
            </form>
            <form method="post" class="service-deactivate-form" onsubmit="return confirm('Deactivate this service? It will be hidden from public booking but kept in the service store.');">
              <input type="hidden" name="action" value="deactivate_service" />
              <input type="hidden" name="id" value="<?= h($service['id']) ?>" />
              <button type="submit">Deactivate service</button>
            </form>
          </details>
<?php endforeach; ?>
        </div>
      </section>

      <section class="admin-view" id="reports" data-admin-view="reports" aria-label="Reports">
        <div class="dashboard-grid">
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
        </div>
      </section>

      <section class="admin-view panel" id="detail" data-admin-view="detail" aria-label="Booking details">
        <h2>Booking details</h2>
        <?php if (!$selectedBooking): ?>
          <p class="empty">Select a booking to view details.</p>
        <?php else: $b = $selectedBooking; $mapUrl = maps_link($b); $price = booking_price($b); ?>
          <form method="post" class="detail-grid">
            <input type="hidden" name="action" value="update_booking" />
            <input type="hidden" name="id" value="<?= h($b['id']) ?>" />
            <input type="hidden" name="selectedDate" value="<?= h($selectedDate) ?>" />
            <input type="hidden" name="detail" value="<?= h($b['id']) ?>" />
            <input type="hidden" name="view" value="detail" />
            <div class="detail-card">
              <h3>Customer Details</h3>
              <p><strong><?= h($b['fullName']) ?></strong></p>
              <p><a href="mailto:<?= h($b['email']) ?>"><?= h($b['email']) ?></a></p>
              <p><a href="tel:<?= h($b['phone']) ?>"><?= h($b['phone']) ?></a></p>
              <p>Preferred contact: <?= h($b['preferredContactMethod']) ?></p>
              <div class="detail-actions-row">
                <button type="button" class="mini-action" data-copy="<?= h($b['email']) ?>">Copy Email</button>
                <button type="button" class="mini-action" data-copy="<?= h($b['phone']) ?>">Copy Phone</button>
                <?php if (!empty($b['email'])): ?><a class="mini-action" href="mailto:<?= h($b['email']) ?>">Open Mail</a><?php endif; ?>
                <?php if (!empty($b['phone'])): ?><a class="mini-action call-action" href="tel:<?= h($b['phone']) ?>">Call</a><?php endif; ?>
              </div>
              <p><?= h($b['addressOrSuburb']) ?></p>
              <?php if (!empty($b['formattedAddress'])): ?><p><?= h($b['formattedAddress']) ?></p><?php endif; ?>
              <?php if (!empty($b['addressSuburb']) || !empty($b['addressPostcode']) || !empty($b['addressState']) || !empty($b['addressCountry'])): ?>
                <p><?= h(trim(($b['addressSuburb'] ?? '') . ' ' . ($b['addressPostcode'] ?? '') . ' ' . ($b['addressState'] ?? '') . ' ' . ($b['addressCountry'] ?? ''))) ?></p>
              <?php endif; ?>
              <?php if ($mapUrl !== ''): ?><p><a href="<?= h($mapUrl) ?>" target="_blank" rel="noopener">Open in Google Maps</a></p><?php endif; ?>
            </div>
            <div class="detail-card">
              <h3>Vehicle Details</h3>
              <p>Vehicle: <?= h($b['vehicleMakeModel'] ?: 'Not supplied') ?></p>
              <p>Number of headlights: <?= h($b['numberOfHeadlights']) ?></p>
              <p>Condition notes: <?= h($b['headlightCondition'] ?: 'Not supplied') ?></p>
              <p>Vehicle location type: <?= h($b['vehicleLocationType'] ?: 'Not supplied') ?></p>
              <p>Customer message: <?= h($b['message'] ?: 'No message') ?></p>
            </div>
            <div class="detail-card">
              <h3>Booking Details</h3>
              <label>Status<select name="status"><?= status_options($b['status']) ?></select></label>
              <label>Preferred date<input type="date" name="preferredDate" value="<?= h($b['preferredDate']) ?>" /></label>
              <label>Preferred time<input name="preferredTimeWindow" value="<?= h($b['preferredTimeWindow']) ?>" /></label>
              <p>Service selected: <?= h($b['packageSelected']) ?></p>
              <p>Promo selected: <?= str_contains($b['packageSelected'], 'EOFY') ? 'EOFY Sale' : 'None' ?></p>
              <p>Price: <?= $price > 0 ? h(admin_money((float)$price)) : 'Quote' ?></p>
<?php if (!empty($b['selectedServices']) && is_array($b['selectedServices'])): ?>
              <ul class="metric-list booking-service-snapshot">
<?php foreach ($b['selectedServices'] as $item): ?>
                <li><span><?= h($item['serviceName'] ?? 'Service') ?></span><strong><?= (float)($item['priceAtBooking'] ?? 0) > 0 ? h(admin_money((float)$item['priceAtBooking'])) : 'Quote' ?></strong></li>
<?php endforeach; ?>
              </ul>
<?php endif; ?>
              <p>Created: <?= h($b['createdAt']) ?></p>
              <p>Last updated: <?= h($b['updatedAt']) ?></p>
            </div>
            <div class="detail-card">
              <h3>Payment</h3>
              <p>Status: <span class="<?= payment_badge_class($b) ?>"><?= h(payment_label($b)) ?></span></p>
              <p>Amount: <?= ($b['paymentStatus'] ?? '') === 'paid' ? h(($b['currency'] ?: 'AUD') . ' $' . number_format((float)$b['amount'], 2)) : '—' ?></p>
              <p>Square Payment ID: <?= !empty($b['squarePaymentId']) ? '<span class="mono">' . h($b['squarePaymentId']) . '</span>' : '—' ?></p>
              <?php if (!empty($b['squareOrderId'])): ?><p>Square Order ID: <span class="mono"><?= h($b['squareOrderId']) ?></span></p><?php endif; ?>
              <?php if (!empty($b['cardBrand'])): ?><p>Card: <?= h($b['cardBrand'] . ' ****' . ($b['cardLast4'] ?? '')) ?></p><?php endif; ?>
              <?php if (!empty($b['paidAt'])): ?><p>Paid at: <?= h($b['paidAt']) ?></p><?php endif; ?>
              <?php if (!empty($b['squareReceiptUrl'])): ?><p><a href="<?= h($b['squareReceiptUrl']) ?>" target="_blank" rel="noopener">View Square receipt</a></p><?php endif; ?>
            </div>
            <div class="detail-card">
              <h3>Admin Notes</h3>
              <label>Admin notes<textarea name="adminNotes" rows="5"><?= h($b['adminNotes']) ?></textarea></label>
              <label class="check-row"><input type="checkbox" name="followUpRequired" <?= !empty($b['followUpRequired']) ? 'checked' : '' ?> /> Follow-up required</label>
              <label>Follow-up date<input type="date" name="followUpDate" value="<?= h($b['followUpDate']) ?>" /></label>
            </div>
            <div class="detail-card detail-card-wide">
              <h3>Booking Actions</h3>
              <div class="status-actions">
                <?php foreach (['new', 'contacted', 'confirmed', 'completed', 'cancelled'] as $status): ?>
                  <button class="mini-action" type="submit" name="status" value="<?= h($status) ?>">Mark as <?= h(status_label($status)) ?></button>
                <?php endforeach; ?>
              </div>
              <p class="detail-muted">Save keeps status, date, time, follow-up and internal notes persistent in the private JSON booking file.</p>
            </div>
            <button class="auth-submit detail-save" type="submit">Save booking</button>
          </form>
          <form method="post" class="delete-booking-form" onsubmit="return confirm('Delete this booking from the dashboard? The JSON file will be kept but hidden.');">
            <input type="hidden" name="action" value="delete_booking" />
            <input type="hidden" name="id" value="<?= h($b['id']) ?>" />
            <input type="hidden" name="selectedDate" value="<?= h($selectedDate) ?>" />
            <input type="hidden" name="view" value="detail" />
            <input type="hidden" name="confirm_delete" value="yes" />
            <button type="submit">Delete booking</button>
          </form>
        <?php endif; ?>
      </section>
    </main>
  </div>
  <script src="/js/dashboard.js?v=<?= ASSET_VER ?>"></script>
</body>
</html>
