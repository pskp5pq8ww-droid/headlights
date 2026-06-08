<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/config.php';
admin_no_cache();

if (admin_logged_in()) {
    header('Location: /dashboard');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = (string)($_POST['password'] ?? '');
    if ($u === '' || $p === '') {
        $error = 'Please enter your username and password.';
    } elseif (admin_attempt($u, $p)) {
        header('Location: /dashboard');
        exit;
    } else {
        usleep(600000); // slow brute force
        $error = 'Incorrect username or password.';
    }
}
?><!doctype html>
<html lang="en-AU">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex,nofollow" />
  <title>Admin Login | Shining Headlights Australia</title>
  <link rel="stylesheet" href="/css/admin.css?v=<?= ASSET_VER ?>" />
</head>
<body class="admin-auth">
  <main class="auth-card">
    <a class="auth-brand" href="/">
      <img src="/assets/shining-headlights-isotype.svg" alt="" width="46" height="54" />
      <span><strong>Shining Headlights</strong><small>Admin Access</small></span>
    </a>

    <h1>Sign in</h1>
    <p class="auth-secure">Secure access only. Authorised staff.</p>

    <?php if ($error): ?><p class="auth-error" role="alert"><?= htmlspecialchars($error) ?></p><?php endif; ?>

    <form method="POST" autocomplete="off">
      <label>Username
        <input type="text" name="username" autofocus required />
      </label>
      <label>Password
        <span class="pw-wrap">
          <input type="password" name="password" id="adminPassword" required />
          <button type="button" class="pw-toggle" id="pwToggle" aria-label="Show password">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
          </button>
        </span>
      </label>
      <button type="submit" class="auth-submit">Sign in</button>
    </form>

    <a class="auth-back" href="/">&larr; Back to website</a>
  </main>
  <script src="/js/admin.js?v=<?= ASSET_VER ?>"></script>
</body>
</html>
