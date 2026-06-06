<?php
/**
 * Minimal session-based admin authentication.
 *
 * Credentials are read from a private file outside the web root:
 *   /home/uXXX/_private/admin.php   (NOT inside public_html, NOT in git)
 * which returns: ['username' => '...', 'password_hash' => '<bcrypt hash>']
 *
 * If that file is missing, a default account is used so the dashboard is
 * testable immediately:  username "admin"  /  password "eofy2026".
 * CHANGE THIS for production — see docs/DEPLOYMENT.md.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function admin_credentials(): array {
    $file = dirname($_SERVER['DOCUMENT_ROOT'] ?? __DIR__) . '/_private/admin.php';
    if (is_file($file)) {
        $c = include $file;
        if (is_array($c) && !empty($c['username']) && !empty($c['password_hash'])) {
            return $c;
        }
    }
    // Fallback default (hashed at runtime — still bcrypt, never plain-text compared)
    return [
        'username'      => 'admin',
        'password_hash' => password_hash('eofy2026', PASSWORD_BCRYPT),
    ];
}

function admin_attempt(string $username, string $password): bool {
    $c = admin_credentials();
    $userOk = hash_equals($c['username'], $username);
    $passOk = password_verify($password, $c['password_hash']);
    if ($userOk && $passOk) {
        session_regenerate_id(true);
        $_SESSION['admin_ok']    = true;
        $_SESSION['admin_user']  = $username;
        $_SESSION['admin_since'] = time();
        return true;
    }
    return false;
}

function admin_logged_in(): bool {
    return !empty($_SESSION['admin_ok']);
}

function admin_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function require_admin(): void {
    if (!admin_logged_in()) {
        header('Location: /admin');
        exit;
    }
}
