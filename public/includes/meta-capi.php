<?php
declare(strict_types=1);

/**
 * Meta Conversions API (CAPI) — server-to-server conversion events.
 *
 * Companion to the browser Meta Pixel. The pixel can be blocked by ad-blockers
 * and iOS; CAPI sends the same conversion straight from our server so paid
 * bookings are never lost. Both carry the SAME event_id (the booking id), so
 * Meta de-duplicates them and counts each conversion only once.
 *
 * Credentials are read (in order) from:
 *   1. Environment variables  (META_ACCESS_TOKEN, META_PIXEL_ID, ...)
 *   2. /home/uXXX/_private/meta.php   (PHP file OUTSIDE public_html, NOT in git)
 *
 * The access token is ONLY ever used here in the backend, never sent to the
 * browser. Personal data (email, phone, name, location) is SHA-256 hashed
 * before it leaves the server, exactly as Meta requires.
 *
 * Design: this never throws and never blocks the booking — on any failure it
 * just logs to Storagehighlights/logs/meta-capi.log and returns false.
 */

require_once __DIR__ . '/bookings.php'; // storage paths, package_price(), booking_now()

const META_GRAPH_VERSION = 'v21.0';

// ── Configuration loading (mirrors the Square integration) ───────────────────
function meta_private_config(): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = [];
    $candidates = [
        dirname($_SERVER['DOCUMENT_ROOT'] ?? __DIR__) . '/_private/meta.php',
        '/home/u613502604/_private/meta.php',
        dirname(__DIR__, 2) . '/_private/meta.php',
    ];
    foreach (array_unique($candidates) as $file) {
        if (is_file($file)) {
            $config = include $file;
            if (is_array($config)) { $cache = $config; break; }
        }
    }
    return $cache;
}

function meta_setting(string $envName, string $fileKey, string $default = ''): string {
    $env = getenv($envName);
    if (is_string($env) && trim($env) !== '') return trim($env);
    if (isset($_ENV[$envName]) && trim((string)$_ENV[$envName]) !== '') return trim((string)$_ENV[$envName]);
    $cfg = meta_private_config();
    if (!empty($cfg[$fileKey])) return trim((string)$cfg[$fileKey]);
    return $default;
}

function meta_pixel_id(): string        { return meta_setting('META_PIXEL_ID', 'pixel_id', '1026998226936698'); }
function meta_access_token(): string    { return meta_setting('META_ACCESS_TOKEN', 'access_token'); }
function meta_test_event_code(): string { return meta_setting('META_TEST_EVENT_CODE', 'test_event_code'); }

/** True only when we have a token + pixel id + cURL. */
function meta_capi_configured(): bool {
    return meta_pixel_id() !== '' && meta_access_token() !== '' && function_exists('curl_init');
}

// ── Helpers ──────────────────────────────────────────────────────────────────
/** Normalise + SHA-256 hash a value the way Meta expects (lowercase, trimmed). */
function meta_hash(string $value): string {
    $value = trim(strtolower($value));
    return $value === '' ? '' : hash('sha256', $value);
}

function meta_log(string $message): void {
    try {
        ensureBookingStorageExists();
        @file_put_contents(
            booking_log_dir() . '/meta-capi.log',
            '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n",
            FILE_APPEND | LOCK_EX
        );
    } catch (Throwable) {
        error_log('[meta-capi] ' . $message);
    }
}

/** Best-effort real client IP (CAPI accepts this un-hashed to improve matching). */
function meta_client_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $k) {
        $v = $_SERVER[$k] ?? '';
        if ($v !== '') {
            $ip = trim(explode(',', (string)$v)[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '';
}

/** Absolute URL for the thank-you page the conversion happened on. */
function meta_source_url(string $path): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'shiningaus.com');
    return $scheme . '://' . $host . $path;
}

/**
 * Build the Advanced Matching user_data block from a booking.
 * Personal fields are SHA-256 hashed; IP/UA/fbp/fbc and external_id are sent
 * raw (Meta requires those un-hashed, and external_id must match the pixel).
 */
function meta_user_data_from_booking(array $booking): array {
    $nameParts = array_values(array_filter(preg_split('/\s+/', trim((string)($booking['fullName'] ?? ''))) ?: []));
    $phone = preg_replace('/\D+/', '', (string)($booking['phone'] ?? ''));
    if (strlen($phone) === 10 && str_starts_with($phone, '0')) $phone = '61' . substr($phone, 1); // AU → E.164
    $country = strtolower(trim((string)($booking['addressCountry'] ?? '')));
    if ($country === '' || str_contains($country, 'austral')) $country = 'au';
    else $country = substr($country, 0, 2);
    $city = strtolower((string)preg_replace('/[^a-z ]/i', '', (string)($booking['addressSuburb'] ?: ($booking['addressOrSuburb'] ?? ''))));
    $zip = (string)preg_replace('/\s+/', '', (string)($booking['addressPostcode'] ?? ''));

    $ud = [];
    $hashInto = function (string $key, string $raw) use (&$ud) {
        $h = meta_hash($raw);
        if ($h !== '') $ud[$key] = [$h];
    };
    $hashInto('em', (string)($booking['email'] ?? ''));
    $hashInto('ph', $phone);
    $hashInto('fn', $nameParts[0] ?? '');
    $hashInto('ln', count($nameParts) > 1 ? (string)end($nameParts) : '');
    $hashInto('ct', $city);
    $hashInto('st', (string)($booking['addressState'] ?? ''));
    $hashInto('zp', $zip);
    $hashInto('country', $country);

    // external_id stays raw so it matches the un-hashed pixel value.
    $bid = (string)($booking['id'] ?? '');
    if ($bid !== '') $ud['external_id'] = [$bid];

    // Un-hashed signals that sharpen attribution.
    $ip = meta_client_ip();
    if ($ip !== '') $ud['client_ip_address'] = $ip;
    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($ua !== '') $ud['client_user_agent'] = $ua;
    if (!empty($_COOKIE['_fbp'])) $ud['fbp'] = (string)$_COOKIE['_fbp'];
    if (!empty($_COOKIE['_fbc'])) $ud['fbc'] = (string)$_COOKIE['_fbc'];

    return $ud;
}

/** Build the custom_data block (value, package, etc.) for the event. */
function meta_custom_data_from_booking(array $booking, bool $isPaid): array {
    $pkg = $booking['packageSelected'] ?: 'Not sure / Quote';
    $qty = max(1, (int)($booking['numberOfHeadlights'] ?? 1));
    $price = $isPaid ? (float)($booking['amount'] ?? 0) : (float)package_price($pkg);

    return array_filter([
        'value' => round($price, 2),
        'currency' => $booking['currency'] ?: 'AUD',
        'content_name' => $pkg,
        'content_category' => 'Headlight Restoration',
        'content_type' => 'product',
        'content_ids' => [$pkg],
        'contents' => [['id' => $pkg, 'quantity' => $qty, 'item_price' => round($price, 2)]],
        'num_items' => $qty,
        'order_id' => (string)($booking['id'] ?? ''),
    ], fn($v) => $v !== '' && $v !== null);
}

/**
 * Send a conversion event for a booking to Meta's Conversions API.
 *
 * @param array  $booking   Normalised booking array.
 * @param string $eventName 'Purchase' (paid) or 'Lead' (request).
 * @param bool   $isPaid    Whether this booking was paid online.
 * @param string $sourceUrl Page the conversion happened on (event_source_url).
 * @return bool             True if Meta accepted the event.
 */
function meta_send_booking_event(array $booking, string $eventName, bool $isPaid, string $sourceUrl = ''): bool {
    if (!meta_capi_configured()) return false;

    try {
        $event = [
            'event_name'    => $eventName,
            'event_time'    => time(),
            'event_id'      => (string)($booking['id'] ?? ''), // matches the browser pixel → dedupe
            'action_source' => 'website',
            'user_data'     => meta_user_data_from_booking($booking),
            'custom_data'   => meta_custom_data_from_booking($booking, $isPaid),
        ];
        if ($sourceUrl !== '') $event['event_source_url'] = $sourceUrl;

        $payload = ['data' => [$event]];
        $testCode = meta_test_event_code();
        if ($testCode !== '') $payload['test_event_code'] = $testCode; // remove once live

        $url = 'https://graph.facebook.com/' . META_GRAPH_VERSION . '/'
             . rawurlencode(meta_pixel_id()) . '/events'
             . '?access_token=' . rawurlencode(meta_access_token());

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        $res  = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($res === false) {
            meta_log('Transport error sending ' . $eventName . ': ' . $err);
            return false;
        }
        if ($code < 200 || $code >= 300) {
            meta_log('HTTP ' . $code . ' for ' . $eventName . ' ' . ($booking['id'] ?? '?') . ' → ' . substr((string)$res, 0, 500));
            return false;
        }
        meta_log('Sent ' . $eventName . ' for booking ' . ($booking['id'] ?? '?') . ' (HTTP ' . $code . ')');
        return true;
    } catch (Throwable $e) {
        meta_log('Exception sending ' . $eventName . ': ' . $e->getMessage());
        return false;
    }
}
