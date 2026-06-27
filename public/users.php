<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/users.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $notice = '';
    $error = '';
    try {
        if ($action === 'save_user') {
            $username = clean_text($_POST['username'] ?? '', 60);
            $password = (string)($_POST['password'] ?? '');
            $role = clean_text($_POST['role'] ?? 'admin', 20) ?: 'admin';
            if ($username === '') throw new RuntimeException('Username is required.');
            if ($password !== '' && strlen($password) < 6) throw new RuntimeException('Password must be at least 6 characters.');
            upsertUser($username, $password, $role);
            $notice = 'User "' . $username . '" saved.';
        } elseif ($action === 'delete_user') {
            $username = clean_text($_POST['username'] ?? '', 60);
            if (!deleteUser($username)) throw new RuntimeException('Could not delete (cannot remove the last account).');
            $notice = 'User "' . $username . '" deleted.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
    $_SESSION['users_notice'] = $notice;
    $_SESSION['users_error'] = $error;
    header('Location: /users');
    exit;
}

$notice = $_SESSION['users_notice'] ?? '';
$error = $_SESSION['users_error'] ?? '';
unset($_SESSION['users_notice'], $_SESSION['users_error']);

try {
    $users = readUsers();
} catch (Throwable $e) {
    $users = [];
    $error = $error ?: 'Unable to load users. Check server storage permissions.';
}
$currentUser = $_SESSION['admin_user'] ?? '';

function h(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?><!doctype html>
<html lang="en-AU">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex,nofollow" />
  <title>Users | Shining Headlights Australia</title>
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
        <a class="is-active" href="/users"><span>Users</span></a>
      </nav>
      <a class="admin-logout" href="/logout">Logout</a>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div>
          <h1>Users</h1>
          <p>Admin accounts. Passwords are stored encrypted (bcrypt) on the server.</p>
        </div>
      </header>

      <?php if ($notice): ?><p class="admin-notice" role="status"><?= h($notice) ?></p><?php endif; ?>
      <?php if ($error): ?><p class="admin-error" role="alert"><?= h($error) ?></p><?php endif; ?>

      <section class="panel">
        <div class="panel-head"><h2>Accounts</h2><span><?= count($users) ?> total</span></div>
        <?php if (!$users): ?>
          <p class="empty">No users found.</p>
        <?php else: ?>
          <div class="booking-card-list">
            <?php foreach ($users as $u): ?>
              <article class="admin-booking-card">
                <div class="booking-card-main">
                  <div class="booking-card-title">
                    <h3><?= h($u['username'] ?? '') ?><?= ($u['username'] ?? '') === $currentUser ? ' <small>(you)</small>' : '' ?></h3>
                    <span class="badge badge-confirmed"><?= h($u['role'] ?? 'admin') ?></span>
                  </div>
                  <div class="booking-quick-grid">
                    <div><span class="field-label">Created</span><span class="field-value"><?= h($u['createdAt'] ?? '—') ?></span></div>
                    <div><span class="field-label">Updated</span><span class="field-value"><?= h($u['updatedAt'] ?? '—') ?></span></div>
                  </div>
                </div>
                <div class="booking-card-actions">
                  <?php if (count($users) > 1): ?>
                  <form method="post" onsubmit="return confirm('Delete user <?= h($u['username'] ?? '') ?>?');">
                    <input type="hidden" name="action" value="delete_user" />
                    <input type="hidden" name="username" value="<?= h($u['username'] ?? '') ?>" />
                    <button type="submit" class="mini-action">Delete</button>
                  </form>
                  <?php endif; ?>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <section class="panel">
        <div class="panel-head"><h2>Add or update a user</h2></div>
        <form method="post" class="detail-grid">
          <input type="hidden" name="action" value="save_user" />
          <div class="detail-card">
            <h3>Account</h3>
            <label>Username<input name="username" required autocomplete="off" /></label>
            <label>Password <small>(min 6 chars; leave blank to keep current when updating)</small>
              <input name="password" type="password" autocomplete="new-password" />
            </label>
            <label>Role
              <select name="role">
                <option value="admin">admin</option>
                <option value="staff">staff</option>
              </select>
            </label>
          </div>
          <button class="auth-submit detail-save" type="submit">Save user</button>
        </form>
        <p class="detail-muted">To change your own password, enter your username with a new password and click Save.</p>
      </section>
    </main>
  </div>
</body>
</html>
