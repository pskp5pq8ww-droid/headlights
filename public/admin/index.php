<?php
ini_set('display_errors', 0);
error_reporting(0);
session_start();

// ── Config ────────────────────────────────────────────────────────────────────
// Change this password hash. Generate a new one with:
//   php -r "echo password_hash('YOUR_PASSWORD', PASSWORD_BCRYPT);"
define('ADMIN_HASH', '$2y$12$placeholderHashReplaceThisNow00000000000000000000000000u');

define('STORAGE_BASE', dirname(dirname($_SERVER['DOCUMENT_ROOT'])) . '/Storagehighlights');
define('BOOKINGS_DIR', STORAGE_BASE . '/bookings');

// ── Auth ──────────────────────────────────────────────────────────────────────
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$error = '';
if (isset($_POST['password'])) {
    if (password_verify($_POST['password'], ADMIN_HASH)) {
        $_SESSION['admin_ok'] = true;
    } else {
        $error = 'Incorrect password.';
        sleep(1); // slow brute-force
    }
}

$authed = !empty($_SESSION['admin_ok']);

// ── Load bookings ─────────────────────────────────────────────────────────────
$bookings = [];
if ($authed && is_dir(BOOKINGS_DIR)) {
    foreach (glob(BOOKINGS_DIR . '/booking_*.json') as $file) {
        $data = json_decode(file_get_contents($file), true);
        if ($data) $bookings[] = $data;
    }
    usort($bookings, fn($a, $b) => strcmp($b['received_at'], $a['received_at']));
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Bookings — Shining Headlights Admin</title>
<meta name="robots" content="noindex,nofollow" />
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:system-ui,sans-serif;background:#071827;color:#f4fcff;min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:40px 20px}
  .card{background:#0d2232;border:1px solid rgba(168,243,255,.16);border-radius:10px;padding:32px;width:100%;max-width:420px}
  h1{font-size:20px;margin-bottom:24px;color:#00d9ff}
  label{display:grid;gap:6px;font-size:14px;font-weight:700;color:#8fa3b2;margin-bottom:16px}
  input[type=password]{width:100%;padding:12px;border:1px solid rgba(143,163,178,.4);border-radius:8px;background:#071827;color:#f4fcff;font-size:15px;outline:none}
  input[type=password]:focus{border-color:#00d9ff}
  button{width:100%;padding:13px;border:none;border-radius:8px;background:#00d9ff;color:#03070b;font-weight:800;font-size:15px;cursor:pointer}
  .error{color:#f87171;font-size:14px;margin-top:12px}
  /* Table */
  .wrap{width:100%;max-width:1100px}
  .topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}
  .topbar h1{font-size:22px}
  .logout{padding:8px 16px;border:1px solid rgba(168,243,255,.3);border-radius:8px;background:transparent;color:#8fa3b2;cursor:pointer;font-size:13px}
  .count{font-size:13px;color:#8fa3b2}
  table{width:100%;border-collapse:collapse;font-size:13px}
  th{background:#0d2232;padding:10px 12px;text-align:left;color:#8fa3b2;font-weight:700;border-bottom:1px solid rgba(168,243,255,.12)}
  td{padding:10px 12px;border-bottom:1px solid rgba(168,243,255,.07);vertical-align:top;color:#f4fcff}
  tr:hover td{background:rgba(255,255,255,.03)}
  .pkg{display:inline-block;padding:2px 8px;border-radius:4px;background:rgba(0,217,255,.12);color:#00d9ff;font-size:11px;font-weight:700}
  .empty{text-align:center;padding:40px;color:#8fa3b2}
</style>
</head>
<body>
<?php if (!$authed): ?>
  <div class="card">
    <h1>Shining Headlights<br>Admin Access</h1>
    <form method="POST">
      <label>Password
        <input type="password" name="password" autofocus autocomplete="current-password" />
      </label>
      <button type="submit">Sign in</button>
      <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif ?>
    </form>
  </div>
<?php else: ?>
  <div class="wrap">
    <div class="topbar">
      <h1>Bookings</h1>
      <div style="display:flex;align-items:center;gap:16px">
        <span class="count"><?= count($bookings) ?> booking<?= count($bookings) !== 1 ? 's' : '' ?></span>
        <form method="POST" style="margin:0"><button class="logout" name="logout" value="1">Sign out</button></form>
      </div>
    </div>
    <?php if (!$bookings): ?>
      <p class="empty">No bookings yet.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Date</th><th>Name</th><th>Phone</th><th>Email</th>
            <th>Suburb</th><th>Vehicle</th><th>Appt</th><th>Package</th><th>Notes</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $b): ?>
          <tr>
            <td><?= htmlspecialchars(substr($b['received_at'] ?? '', 0, 16)) ?></td>
            <td><?= htmlspecialchars($b['name']    ?? '') ?></td>
            <td><?= htmlspecialchars($b['phone']   ?? '') ?></td>
            <td><?= htmlspecialchars($b['email']   ?? '') ?></td>
            <td><?= htmlspecialchars($b['suburb']  ?? '') ?></td>
            <td><?= htmlspecialchars($b['vehicle'] ?? '') ?></td>
            <td><?= htmlspecialchars(($b['date'] ?? '') . ' ' . ($b['time'] ?? '')) ?></td>
            <td><span class="pkg"><?= htmlspecialchars($b['package'] ?? '') ?></span></td>
            <td><?= htmlspecialchars($b['message'] ?? '') ?></td>
          </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    <?php endif ?>
  </div>
<?php endif ?>
</body>
</html>
