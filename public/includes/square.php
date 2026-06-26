<?php
declare(strict_types=1);

/**
 * Square payments integration for Shining Headlights Australia.
 *
 * Uses the Square Payments REST API directly via cURL — no Composer / SDK
 * required, so it runs on standard Hostinger shared PHP hosting.
 *
 * Credentials are read (in order) from:
 *   1. Environment variables (set in Hostinger → Advanced → Environment variables)
 *   2. /home/uXXX/_private/square.php  (a PHP file OUTSIDE public_html, NOT in git)
 *
 * The SQUARE_ACCESS_TOKEN is ONLY ever used here in the backend. The browser
 * only ever receives square_public_config() (application id + location id).
 *
 * ─────────────────────────────────────────────────────────────────────────
 * HOW TO GO LIVE  (Sandbox → Production)
 * ─────────────────────────────────────────────────────────────────────────
 *   Set SQUARE_ENVIRONMENT=production and replace the application id, access
 *   token and location id with your PRODUCTION values. Nothing else changes:
 *   square_api_base() and the Web Payments SDK URL switch automatically.
 */

require_once __DIR__ . '/bookings.php';

const SQUARE_API_VERSION = '2025-01-23';

// ── Configuration loading ───────────────────────────────────────────────────
function square_env_value(string $name): string {
    $value = getenv($name);
    if (is_string($value) && trim($value) !== '') return trim($value);
    if (isset($_ENV[$name]) && trim((string)$_ENV[$name]) !== '') return trim((string)$_ENV[$name]);
    if (isset($_SERVER[$name]) && trim((string)$_SERVER[$name]) !== '') return trim((string)$_SERVER[$name]);
    return '';
}

function square_private_config(): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = [];
    $candidates = [
        dirname($_SERVER['DOCUMENT_ROOT'] ?? __DIR__) . '/_private/square.php',
        '/home/u613502604/_private/square.php',
        dirname(__DIR__, 2) . '/_private/square.php',
    ];
    foreach (array_unique($candidates) as $file) {
        if (is_file($file)) {
            $config = include $file;
            if (is_array($config)) { $cache = $config; break; }
        }
    }
    return $cache;
}

function square_setting(string $envName, string $fileKey, string $default = ''): string {
    $env = square_env_value($envName);
    if ($env !== '') return $env;
    $file = square_private_config();
    if (!empty($file[$fileKey])) return trim((string)$file[$fileKey]);
    return $default;
}

function square_environment(): string {
    $env = strtolower(square_setting('SQUARE_ENVIRONMENT', 'environment', 'sandbox'));
    return $env === 'production' ? 'production' : 'sandbox';
}

function square_application_id(): string { return square_setting('SQUARE_APPLICATION_ID', 'application_id'); }
function square_access_token(): string   { return square_setting('SQUARE_ACCESS_TOKEN', 'access_token'); }
function square_location_id(): string    { return square_setting('SQUARE_LOCATION_ID', 'location_id'); }
function square_currency(): string       { return strtoupper(square_setting('SQUARE_CURRENCY', 'currency', 'AUD')); }

function square_api_base(): string {
    return square_environment() === 'production'
        ? 'https://connect.squareup.com'
        : 'https://connect.squareupsandbox.com';
}

/** URL of the Web Payments SDK that matches the current environment. */
function square_web_sdk_url(): string {
    return square_environment() === 'production'
        ? 'https://web.squarecdn.com/v1/square.js'
        : 'https://sandbox.web.squarecdn.com/v1/square.js';
}

function square_is_configured(): bool {
    return square_application_id() !== '' && square_access_token() !== '' && square_location_id() !== '';
}

/** Config safe to expose to the browser — NEVER includes the access token. */
function square_public_config(): array {
    return [
        'applicationId' => square_application_id(),
        'locationId'    => square_location_id(),
        'environment'   => square_environment(),
        'currency'      => square_currency(),
        'sdkUrl'        => square_web_sdk_url(),
        'configured'    => square_application_id() !== '' && square_location_id() !== '',
    ];
}

// ── Payment storage + logging ────────────────────────────────────────────────
function payment_dir(): string {
    $env = getenv('PAYMENTS_STORAGE_PATH');
    if ($env && trim($env) !== '') return rtrim($env, '/');
    return booking_storage_base() . '/payments';
}

function ensurePaymentStorageExists(): void {
    ensureBookingStorageExists();
    $dir = payment_dir();
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('Unable to create payments directory: ' . $dir);
    }
    $htaccess = $dir . '/.htaccess';
    if (!is_file($htaccess)) {
        @file_put_contents($htaccess, "Deny from all\n", LOCK_EX);
        @chmod($htaccess, 0600);
    }
}

/** Append a payment event to the log. Never logs card data or the access token. */
function square_log(string $message): void {
    try {
        ensureBookingStorageExists();
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
        @file_put_contents(booking_log_dir() . '/square-payments.log', $line, FILE_APPEND | LOCK_EX);
    } catch (Throwable) {
        error_log('[square] ' . $message);
    }
}

/** Persist a payment attempt record (paid or failed) for the audit trail. */
function savePaymentRecord(array $record): array {
    try {
        ensurePaymentStorageExists();
        $record['id'] = (string)($record['id'] ?? ('pay_' . date('YmdHis') . '_' . bin2hex(random_bytes(5))));
        $record['savedAt'] = booking_now();
        $safe = preg_replace('/[^A-Za-z0-9_-]/', '', $record['id']);
        $path = payment_dir() . '/payment_' . $safe . '.json';
        $tmp  = $path . '.tmp.' . bin2hex(random_bytes(4));
        $json = json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json !== false && file_put_contents($tmp, $json, LOCK_EX) !== false) {
            @chmod($tmp, 0600);
            @rename($tmp, $path);
            @chmod($path, 0600);
        }
    } catch (Throwable $e) {
        square_log('Failed to save payment record: ' . $e->getMessage());
    }
    return $record;
}

// ── Square API call ──────────────────────────────────────────────────────────
/**
 * Charge a card via Square Payments API.
 *
 * @param string $sourceId       Payment token generated by the Web Payments SDK.
 * @param int    $amountCents    Amount in the smallest currency unit (validated server-side).
 * @param string $idempotencyKey Unique key per payment attempt.
 * @param array  $meta           Optional: note, email, referenceId.
 * @return array ['ok'=>bool, 'payment'=>array|null, 'error'=>string, 'code'=>string]
 */
function square_create_payment(string $sourceId, int $amountCents, string $idempotencyKey, array $meta = []): array {
    if (!square_is_configured()) {
        square_log('Charge attempted but Square is not configured.');
        return ['ok' => false, 'error' => 'Online payments are not available right now.', 'code' => 'NOT_CONFIGURED'];
    }
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'Payment system unavailable on this server.', 'code' => 'NO_CURL'];
    }

    $body = [
        'source_id'       => $sourceId,
        'idempotency_key' => $idempotencyKey,
        'amount_money'    => ['amount' => $amountCents, 'currency' => square_currency()],
        'location_id'     => square_location_id(),
        'autocomplete'    => true,
    ];
    if (!empty($meta['note']))        $body['note'] = mb_substr((string)$meta['note'], 0, 500);
    if (!empty($meta['email']))       $body['buyer_email_address'] = mb_substr((string)$meta['email'], 0, 255);
    if (!empty($meta['referenceId'])) $body['reference_id'] = mb_substr((string)$meta['referenceId'], 0, 40);

    $ch = curl_init(square_api_base() . '/v2/payments');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($body),
        CURLOPT_HTTPHEADER     => [
            'Square-Version: ' . SQUARE_API_VERSION,
            'Authorization: Bearer ' . square_access_token(),
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 12,
    ]);
    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        square_log('cURL transport error: ' . $curlErr);
        return ['ok' => false, 'error' => 'Could not reach the payment provider. Please try again.', 'code' => 'NETWORK'];
    }

    $data = json_decode((string)$response, true);
    if ($httpCode >= 200 && $httpCode < 300 && isset($data['payment'])) {
        return ['ok' => true, 'payment' => $data['payment'], 'error' => '', 'code' => 'OK'];
    }

    $detail = $data['errors'][0]['detail'] ?? 'Your payment could not be processed.';
    $code   = $data['errors'][0]['code']   ?? 'UNKNOWN';
    square_log('Square error HTTP ' . $httpCode . ' code=' . $code . ' detail=' . $detail);
    return ['ok' => false, 'error' => $detail, 'code' => $code, 'http' => $httpCode];
}
