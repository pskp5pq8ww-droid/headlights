<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/backups.php';

/*
 * Downloads are handled before any HTML output so we can stream the file.
 *   ?download=zip[&cats=bookings,uploads,users,analytics]  → full ZIP archive
 *   ?download=bookings                                      → combined bookings JSON
 */
$download = $_GET['download'] ?? '';
if ($download !== '') {
    try {
        if ($download === 'bookings') {
            backup_stream_string(
                backup_bookings_json(),
                'shining-bookings-' . backup_timestamp() . '.json',
                'application/json; charset=utf-8'
            );
        }

        if ($download === 'zip') {
            $cats = isset($_GET['cats']) ? array_filter(explode(',', (string)$_GET['cats'])) : BACKUP_CATEGORIES;
            $tmp = rtrim(sys_get_temp_dir(), '/') . '/shining-backup-' . bin2hex(random_bytes(6)) . '.zip';
            $included = backup_build_zip($tmp, $cats);
            if (!$included) {
                @unlink($tmp);
                throw new RuntimeException('There is no data to back up yet.');
            }
            // Name the file after what's inside it + the date, e.g.
            // shining-backup-full-20260628-143022.zip or shining-backup-users-...zip
            $scope = count($included) === count(BACKUP_CATEGORIES) ? 'full' : implode('-', $included);
            backup_stream_file($tmp, 'shining-backup-' . $scope . '-' . backup_timestamp() . '.zip', 'application/zip');
        }

        throw new RuntimeException('Unknown download type.');
    } catch (Throwable $e) {
        $_SESSION['backups_error'] = $e->getMessage();
        header('Location: /backups');
        exit;
    }
}

$error = $_SESSION['backups_error'] ?? '';
unset($_SESSION['backups_error']);

try {
    $overview = backup_overview();
} catch (Throwable $e) {
    $overview = [];
    $error = $error ?: 'Unable to read storage. Check server permissions.';
}

$totalFiles = array_sum(array_column($overview, 'files'));
$totalBytes = array_sum(array_column($overview, 'bytes'));
$zipOk = backup_zip_supported();

function h(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?><!doctype html>
<html lang="en-AU">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex,nofollow" />
  <title>Backups | Shining Headlights Australia</title>
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
        <a href="/bookings-map"><span>Map</span></a>
        <a href="/users"><span>Users</span></a>
        <a class="is-active" href="/backups"><span>Backups</span></a>
      </nav>
      <a class="admin-logout" href="/logout">Logout</a>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <h1>Backups</h1>
          <p>Download a snapshot of your important data. Keep a copy somewhere safe off the server.</p>
        </div>
      </header>

      <?php if ($error): ?><p class="admin-error" role="alert"><?= h($error) ?></p><?php endif; ?>

      <section class="panel">
        <div class="panel-head">
          <h2>What's stored on the server</h2>
          <span><?= (int)$totalFiles ?> files · <?= h(backup_format_bytes((int)$totalBytes)) ?></span>
        </div>
        <div class="booking-quick-grid">
          <?php foreach ($overview as $cat): ?>
            <div>
              <span class="field-label"><?= h($cat['label']) ?></span>
              <span class="field-value">
                <?= (int)$cat['files'] ?> file<?= (int)$cat['files'] === 1 ? '' : 's' ?>
                · <?= h(backup_format_bytes((int)$cat['bytes'])) ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="panel">
        <div class="panel-head"><h2>Download a backup</h2></div>

        <div class="detail-grid">
          <div class="detail-card">
            <h3>Bookings only (JSON)</h3>
            <p class="detail-muted">
              Every customer booking — including hidden/cancelled ones — in a single
              JSON file. Smallest and most important backup. Always works.
            </p>
            <a class="auth-submit detail-save" href="/backups?download=bookings">Download bookings JSON</a>
          </div>

          <div class="detail-card">
            <h3>Full backup (ZIP)</h3>
            <p class="detail-muted">
              Everything important — bookings, customer photos, admin accounts and
              website analytics — bundled into one ZIP archive.
            </p>
            <?php if ($zipOk): ?>
              <a class="auth-submit detail-save" href="/backups?download=zip">Download full backup</a>
            <?php else: ?>
              <p class="admin-error" role="alert">
                ZIP archives aren't available on this server. Use the bookings JSON above instead.
              </p>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($zipOk): ?>
        <p class="detail-muted" style="margin-top:1rem">
          Need just one part? Download a focused ZIP:
          <a href="/backups?download=zip&amp;cats=bookings,uploads">bookings + photos</a> ·
          <a href="/backups?download=zip&amp;cats=users">admin accounts</a> ·
          <a href="/backups?download=zip&amp;cats=analytics">analytics</a>
        </p>
        <?php endif; ?>
      </section>
    </main>
  </div>
</body>
</html>
