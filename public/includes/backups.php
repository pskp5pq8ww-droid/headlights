<?php
declare(strict_types=1);

/**
 * Admin backups — package the important business data into a downloadable file.
 *
 * "Important data" means everything that can't be regenerated from code:
 *   - bookings        (Storagehighlights/bookings/booking_*.json)  → customer records
 *   - booking uploads (Storagehighlights/booking-uploads/)          → customer photos
 *   - admin users     (Storagehighlights/admin/users.json)          → login accounts
 *   - analytics       (Storagehighlights/analytics/events-*.jsonl)  → visitor stats
 *
 * Nothing here writes to storage — backups are read-only snapshots streamed to
 * the admin's browser. Two formats are offered:
 *   - a single combined bookings JSON (always works, no extensions needed)
 *   - a full ZIP of all selected categories (needs the ZipArchive extension)
 */

require_once __DIR__ . '/bookings.php';
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/analytics.php';

const BACKUP_CATEGORIES = ['bookings', 'uploads', 'users', 'analytics'];

/** Human label for each backup category. */
function backup_category_label(string $category): string {
    return [
        'bookings' => 'Bookings (customer records)',
        'uploads' => 'Booking photos',
        'users' => 'Admin accounts',
        'analytics' => 'Website analytics',
    ][$category] ?? $category;
}

/** Absolute filesystem path that holds a given category's files. */
function backup_category_path(string $category): string {
    switch ($category) {
        case 'bookings': return booking_dir();
        case 'uploads': return booking_upload_dir();
        case 'users': return users_file();
        case 'analytics': return analytics_dir();
    }
    return '';
}

/** Recursively total the byte size and file count under a path. */
function backup_path_stats(string $path): array {
    if ($path === '' || !file_exists($path)) return ['files' => 0, 'bytes' => 0];
    if (is_file($path)) return ['files' => 1, 'bytes' => (int)@filesize($path)];

    $files = 0;
    $bytes = 0;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($it as $file) {
        if ($file->isFile() && $file->getFilename() !== '.htaccess') {
            $files++;
            $bytes += (int)$file->getSize();
        }
    }
    return ['files' => $files, 'bytes' => $bytes];
}

/** Per-category counts + sizes for the Backups page summary. */
function backup_overview(): array {
    $overview = [];
    foreach (BACKUP_CATEGORIES as $category) {
        $overview[$category] = backup_path_stats(backup_category_path($category)) + [
            'label' => backup_category_label($category),
            'available' => file_exists(backup_category_path($category)),
        ];
    }
    return $overview;
}

/** Pretty file size, e.g. "1.4 MB". */
function backup_format_bytes(int $bytes): string {
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = (int)floor(log($bytes, 1024));
    $i = max(0, min($i, count($units) - 1));
    return round($bytes / (1024 ** $i), $i === 0 ? 0 : 1) . ' ' . $units[$i];
}

function backup_timestamp(): string {
    return (new DateTimeImmutable('now', new DateTimeZone('Australia/Brisbane')))->format('Ymd-His');
}

/** True only when the server can build a ZIP archive. */
function backup_zip_supported(): bool {
    return class_exists('ZipArchive');
}

/** Combined snapshot of every booking (including soft-deleted) as one JSON string. */
function backup_bookings_json(): string {
    $bookings = readBookings(true);
    // Drop the transient _file marker added by the reader.
    foreach ($bookings as &$b) unset($b['_file']);
    unset($b);

    return (string)json_encode([
        'type' => 'shining-headlights-bookings-backup',
        'generatedAt' => booking_now(),
        'count' => count($bookings),
        'bookings' => $bookings,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/** Add a file or whole directory tree to an open ZipArchive under a base folder. */
function backup_zip_add_path(ZipArchive $zip, string $source, string $localBase): void {
    if (!file_exists($source)) return;

    if (is_file($source)) {
        $zip->addFile($source, $localBase . '/' . basename($source));
        return;
    }

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $item) {
        $relative = substr($item->getPathname(), strlen($source) + 1);
        $relative = str_replace('\\', '/', $relative);
        if ($item->getFilename() === '.htaccess') continue; // server-only guard file
        if ($item->isDir()) {
            $zip->addEmptyDir($localBase . '/' . $relative);
        } elseif ($item->isFile()) {
            $zip->addFile($item->getPathname(), $localBase . '/' . $relative);
        }
    }
}

/**
 * Build a ZIP of the requested categories at $destPath.
 * Returns the list of categories actually included (those that had data).
 */
function backup_build_zip(string $destPath, array $categories): array {
    if (!backup_zip_supported()) {
        throw new RuntimeException('ZIP archives are not supported on this server.');
    }
    $categories = array_values(array_intersect($categories, BACKUP_CATEGORIES));
    if (!$categories) throw new InvalidArgumentException('No backup categories selected.');

    $zip = new ZipArchive();
    if ($zip->open($destPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Unable to create the backup archive.');
    }

    $stamp = backup_timestamp();
    $root = 'shining-backup-' . $stamp;
    $included = [];

    foreach ($categories as $category) {
        $path = backup_category_path($category);
        $stats = backup_path_stats($path);
        if ($stats['files'] === 0) continue;
        backup_zip_add_path($zip, $path, $root . '/' . $category);
        $included[] = $category;
    }

    // Always drop in a combined bookings JSON for easy reading/restoring,
    // plus a small manifest describing the snapshot.
    if (in_array('bookings', $categories, true)) {
        $zip->addFromString($root . '/bookings-combined-' . $stamp . '.json', backup_bookings_json());
    }
    $zip->addFromString($root . '/manifest.json', (string)json_encode([
        'type' => 'shining-headlights-backup',
        'generatedAt' => booking_now(),
        'categories' => $included,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $zip->close();
    return $included;
}

/** Stream a string to the browser as a download, then stop. */
function backup_stream_string(string $content, string $filename, string $mime): void {
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($content));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    echo $content;
    exit;
}

/** Stream a file to the browser as a download, delete it, then stop. */
function backup_stream_file(string $path, string $filename, string $mime, bool $deleteAfter = true): void {
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . (string)filesize($path));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    readfile($path);
    if ($deleteAfter) @unlink($path);
    exit;
}
