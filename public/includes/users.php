<?php
declare(strict_types=1);

/**
 * Admin users store — bcrypt-hashed passwords saved in private server storage.
 *
 * Location: <storage base>/admin/users.json  (default Storagehighlights/admin),
 * or ADMIN_STORAGE_PATH if set. The folder is auto-created with `Deny from all`
 * and files are chmod 0600 — same protection as the bookings store.
 *
 * Passwords are NEVER stored in plain text: only the bcrypt hash is saved.
 * On first use the store is seeded with a default admin so login works without
 * uploading any file to the server. CHANGE THE DEFAULT PASSWORD after first login
 * from the Users page (/users).
 */

require_once __DIR__ . '/bookings.php'; // storage base + booking_now()

// Default seed used only once, to create the first admin if the store is empty.
const DEFAULT_ADMIN_USERNAME = 'admin';
const DEFAULT_ADMIN_PASSWORD = 'admin2026'; // ← change via /users after first login

function users_dir(): string {
    $env = getenv('ADMIN_STORAGE_PATH');
    if ($env && trim($env) !== '') return rtrim($env, '/');
    return booking_storage_base() . '/admin';
}

function users_file(): string {
    return users_dir() . '/users.json';
}

function users_ensure_dir(): void {
    $dir = users_dir();
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('Unable to create admin users directory: ' . $dir);
    }
    $htaccess = $dir . '/.htaccess';
    if (!is_file($htaccess)) {
        @file_put_contents($htaccess, "Deny from all\n", LOCK_EX);
        @chmod($htaccess, 0600);
    }
}

function writeUsers(array $users): void {
    users_ensure_dir();
    $path = users_file();
    $tmp = $path . '.tmp.' . bin2hex(random_bytes(4));
    $json = json_encode(array_values($users), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) throw new RuntimeException('Unable to encode users JSON');
    if (file_put_contents($tmp, $json, LOCK_EX) === false) throw new RuntimeException('Unable to write users temp file');
    @chmod($tmp, 0600);
    if (!rename($tmp, $path)) {
        @unlink($tmp);
        throw new RuntimeException('Unable to publish users file');
    }
    @chmod($path, 0600);
}

function seedDefaultAdmin(): void {
    if (is_file(users_file())) return;
    $now = booking_now();
    writeUsers([[
        'username' => DEFAULT_ADMIN_USERNAME,
        'password_hash' => password_hash(DEFAULT_ADMIN_PASSWORD, PASSWORD_BCRYPT),
        'role' => 'admin',
        'createdAt' => $now,
        'updatedAt' => $now,
    ]]);
}

function readUsers(): array {
    users_ensure_dir();
    if (!is_file(users_file())) seedDefaultAdmin();
    $data = json_decode((string)@file_get_contents(users_file()), true);
    return is_array($data) ? $data : [];
}

function findUser(string $username): ?array {
    foreach (readUsers() as $u) {
        if (isset($u['username']) && hash_equals((string)$u['username'], $username)) return $u;
    }
    return null;
}

/** True if the username + password match a stored bcrypt hash. */
function verifyUserCredentials(string $username, string $password): bool {
    $u = findUser($username);
    if (!$u || empty($u['password_hash'])) return false;
    return password_verify($password, (string)$u['password_hash']);
}

/** Create a new user or update an existing one (empty password = keep current). */
function upsertUser(string $username, string $password = '', string $role = 'admin'): void {
    $username = trim($username);
    if ($username === '') throw new InvalidArgumentException('Username is required.');
    $role = $role !== '' ? $role : 'admin';
    $users = readUsers();
    $now = booking_now();
    $found = false;
    foreach ($users as &$u) {
        if (hash_equals((string)($u['username'] ?? ''), $username)) {
            if ($password !== '') $u['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
            $u['role'] = $role;
            $u['updatedAt'] = $now;
            $found = true;
            break;
        }
    }
    unset($u);
    if (!$found) {
        if ($password === '') throw new InvalidArgumentException('A password is required to create a new user.');
        $users[] = [
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role' => $role,
            'createdAt' => $now,
            'updatedAt' => $now,
        ];
    }
    writeUsers($users);
}

/** Delete a user; refuses to remove the last remaining account. */
function deleteUser(string $username): bool {
    $users = readUsers();
    if (count($users) <= 1) return false;
    $remaining = array_values(array_filter(
        $users,
        fn($u) => !hash_equals((string)($u['username'] ?? ''), $username)
    ));
    if (count($remaining) === count($users)) return false; // not found
    writeUsers($remaining);
    return true;
}
