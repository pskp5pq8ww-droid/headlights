<?php
declare(strict_types=1);

/**
 * Lightweight, privacy-friendly website analytics for Shining Headlights.
 *
 * Design goals (optimised for Hostinger shared PHP, no database):
 *   - Non-blocking: the page sends a fire-and-forget beacon; rendering never waits.
 *   - Append-only storage: one JSONL line per event in a daily file
 *     (Storagehighlights/analytics/events-YYYY-MM-DD.jsonl) — no big-file rewrites.
 *   - Cheap reads: the dashboard only parses the days in the selected range.
 *   - Privacy: raw IPs are never stored (only a salted hash); bots are skipped.
 *   - Geo lookup runs at most once per session, and only as a safe, timed-out call.
 */

require_once __DIR__ . '/bookings.php';

const ANALYTICS_VISITOR_COOKIE = 'shv';
const ANALYTICS_SESSION_COOKIE = 'shs';
const ANALYTICS_SESSION_TTL = 1800;        // 30 min sliding session
const ANALYTICS_VISITOR_TTL = 31536000;    // 1 year

function analytics_dir(): string {
    $env = getenv('ANALYTICS_STORAGE_PATH');
    if ($env && trim($env) !== '') return rtrim($env, '/');
    return booking_storage_base() . '/analytics';
}

function analytics_ensure_dir(): void {
    $dir = analytics_dir();
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('Unable to create analytics directory: ' . $dir);
    }
    $htaccess = $dir . '/.htaccess';
    if (!is_file($htaccess)) {
        @file_put_contents($htaccess, "Deny from all\n", LOCK_EX);
        @chmod($htaccess, 0600);
    }
}

function analytics_file_for(string $date): string {
    return analytics_dir() . '/events-' . preg_replace('/[^0-9-]/', '', $date) . '.jsonl';
}

function analytics_is_bot(string $ua): bool {
    if ($ua === '') return true;
    return (bool)preg_match('/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|embedly|quora|pinterest|vkshare|whatsapp|telegram|headless|lighthouse|pingdom|uptime|monitor|curl|wget|python-requests|axios|go-http/i', $ua);
}

function analytics_device(string $ua): string {
    if (preg_match('/Mobi|Android|iPhone|iPod|Windows Phone/i', $ua)) return 'mobile';
    if (preg_match('/iPad|Tablet/i', $ua)) return 'tablet';
    return 'desktop';
}

function analytics_client_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        $val = $_SERVER[$key] ?? '';
        if ($val !== '') {
            $ip = trim(explode(',', $val)[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '';
}

/** Salted, truncated hash of the IP — enough to de-dupe, impossible to reverse. */
function analytics_ip_hash(string $ip): string {
    if ($ip === '') return '';
    $salt = getenv('ANALYTICS_SALT') ?: 'shining-analytics-v1';
    return substr(hash('sha256', $salt . '|' . $ip), 0, 16);
}

function analytics_referrer_host(string $referrer): string {
    if ($referrer === '') return 'direct';
    $host = parse_url($referrer, PHP_URL_HOST);
    if (!$host) return 'direct';
    $host = preg_replace('/^www\./', '', strtolower($host));
    $self = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $self = preg_replace('/^www\./', '', $self);
    if ($host === $self) return 'internal';
    return $host;
}

/** One-shot, fail-safe geo lookup (only called for new sessions). */
function analytics_geo_lookup(string $ip): array {
    $empty = ['cc' => '', 'country' => '', 'region' => '', 'city' => ''];
    if ($ip === '' || !function_exists('curl_init')) return $empty;
    // Skip private / reserved ranges.
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return $empty;

    $ch = curl_init('http://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,country,countryCode,regionName,city');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT_MS => 1200,
        CURLOPT_CONNECTTIMEOUT_MS => 800,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    if (!$res) return $empty;
    $data = json_decode((string)$res, true);
    if (!is_array($data) || ($data['status'] ?? '') !== 'success') return $empty;
    return [
        'cc' => (string)($data['countryCode'] ?? ''),
        'country' => (string)($data['country'] ?? ''),
        'region' => (string)($data['regionName'] ?? ''),
        'city' => (string)($data['city'] ?? ''),
    ];
}

/**
 * Record one page view from a tracking beacon. Manages the visitor/session
 * cookies, runs geo only on a fresh session, and appends a JSONL line.
 */
function analytics_record(array $payload): void {
    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (analytics_is_bot($ua)) return;

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    $vid = (string)($_COOKIE[ANALYTICS_VISITOR_COOKIE] ?? '');
    $isNewVisitor = $vid === '' || !preg_match('/^[a-f0-9]{20}$/', $vid);
    if ($isNewVisitor) $vid = bin2hex(random_bytes(10));

    $sid = (string)($_COOKIE[ANALYTICS_SESSION_COOKIE] ?? '');
    $isNewSession = $sid === '' || !preg_match('/^[a-f0-9]{16}$/', $sid);
    if ($isNewSession) $sid = bin2hex(random_bytes(8));

    // Refresh cookies (sliding session window).
    $cookieOpts = ['path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax'];
    @setcookie(ANALYTICS_VISITOR_COOKIE, $vid, ['expires' => time() + ANALYTICS_VISITOR_TTL] + $cookieOpts);
    @setcookie(ANALYTICS_SESSION_COOKIE, $sid, ['expires' => time() + ANALYTICS_SESSION_TTL] + $cookieOpts);

    $path = '/' . ltrim(clean_text($payload['p'] ?? '/', 200), '/');
    $path = preg_replace('/[^\x20-\x7E]/', '', $path) ?: '/';

    $event = [
        't' => booking_now(),
        'p' => $path,
        'ref' => analytics_referrer_host((string)($payload['r'] ?? '')),
        'vid' => $vid,
        'sid' => $sid,
        'nv' => $isNewVisitor,
        'ns' => $isNewSession,
        'dev' => analytics_device($ua),
        'iph' => analytics_ip_hash(analytics_client_ip()),
    ];

    if ($isNewSession) {
        $geo = analytics_geo_lookup(analytics_client_ip());
        $event += $geo;
    }

    try {
        analytics_ensure_dir();
        $line = json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($line !== false) {
            @file_put_contents(analytics_file_for(date('Y-m-d')), $line . "\n", FILE_APPEND | LOCK_EX);
        }
    } catch (Throwable $e) {
        error_log('[analytics] ' . $e->getMessage());
    }
}

/** Read raw events for the last N days (inclusive of today). */
function analytics_read_range(int $days): array {
    analytics_ensure_dir();
    $events = [];
    $tz = new DateTimeZone('Australia/Brisbane');
    $cursor = new DateTimeImmutable('now', $tz);
    for ($i = 0; $i < max(1, $days); $i++) {
        $file = analytics_file_for($cursor->format('Y-m-d'));
        $cursor = $cursor->modify('-1 day');
        if (!is_file($file)) continue;
        $fh = @fopen($file, 'r');
        if (!$fh) continue;
        while (($line = fgets($fh)) !== false) {
            $line = trim($line);
            if ($line === '') continue;
            $row = json_decode($line, true);
            if (is_array($row)) $events[] = $row;
        }
        fclose($fh);
    }
    return $events;
}

/** Aggregate events (+ bookings) into the metrics the dashboard renders. */
function analytics_aggregate(array $events, array $bookings, int $days): array {
    $tz = new DateTimeZone('Australia/Brisbane');
    $today = (new DateTimeImmutable('now', $tz))->format('Y-m-d');

    $views = 0;
    $visitors = [];
    $sessions = [];
    $pages = [];
    $referrers = [];
    $devices = ['desktop' => 0, 'mobile' => 0, 'tablet' => 0];
    $countries = [];
    $regions = [];
    $cities = [];
    $byDay = [];
    $viewsToday = 0;
    $visitorsToday = [];

    foreach ($events as $e) {
        $views++;
        $day = substr((string)($e['t'] ?? ''), 0, 10);
        if ($day !== '') $byDay[$day] = ($byDay[$day] ?? 0) + 1;
        if (!empty($e['vid'])) $visitors[$e['vid']] = true;
        if (!empty($e['sid'])) $sessions[$e['sid']] = true;
        $pages[$e['p'] ?? '/'] = ($pages[$e['p'] ?? '/'] ?? 0) + 1;
        $ref = $e['ref'] ?? 'direct';
        $referrers[$ref] = ($referrers[$ref] ?? 0) + 1;
        $dev = $e['dev'] ?? 'desktop';
        if (isset($devices[$dev])) $devices[$dev]++;
        if (!empty($e['country'])) $countries[$e['country']] = ($countries[$e['country']] ?? 0) + 1;
        if (!empty($e['region'])) $regions[$e['region']] = ($regions[$e['region']] ?? 0) + 1;
        if (!empty($e['city'])) $cities[$e['city']] = ($cities[$e['city']] ?? 0) + 1;
        if ($day === $today) {
            $viewsToday++;
            if (!empty($e['vid'])) $visitorsToday[$e['vid']] = true;
        }
    }

    // Bookings within the same window (by createdAt).
    $cutoff = (new DateTimeImmutable('now', $tz))->modify('-' . max(1, $days) . ' days')->format('Y-m-d');
    $bookingCount = 0;
    $paidCount = 0;
    $revenue = 0.0;
    $suburbs = [];
    foreach ($bookings as $b) {
        $created = substr((string)($b['createdAt'] ?? ''), 0, 10);
        if ($created === '' || $created < $cutoff) continue;
        $bookingCount++;
        if (($b['paymentStatus'] ?? '') === 'paid') {
            $paidCount++;
            $revenue += (float)($b['amount'] ?? 0);
        }
        $area = trim((string)($b['addressSuburb'] ?: ($b['addressOrSuburb'] ?? '')));
        if ($area !== '') $suburbs[$area] = ($suburbs[$area] ?? 0) + 1;
    }

    $uniqueVisitors = count($visitors);
    arsort($pages); arsort($referrers); arsort($countries); arsort($regions); arsort($cities); arsort($suburbs);

    // Build an ordered day series for the chart.
    $series = [];
    $cursor = (new DateTimeImmutable('now', $tz))->modify('-' . (max(1, $days) - 1) . ' days');
    for ($i = 0; $i < max(1, $days); $i++) {
        $d = $cursor->format('Y-m-d');
        $series[] = ['date' => $d, 'views' => $byDay[$d] ?? 0];
        $cursor = $cursor->modify('+1 day');
    }

    return [
        'views' => $views,
        'uniqueVisitors' => $uniqueVisitors,
        'sessions' => count($sessions),
        'viewsToday' => $viewsToday,
        'visitorsToday' => count($visitorsToday),
        'bookings' => $bookingCount,
        'paid' => $paidCount,
        'revenue' => $revenue,
        'bookingConversion' => $uniqueVisitors > 0 ? round($bookingCount / $uniqueVisitors * 100, 1) : 0.0,
        'paidConversion' => $bookingCount > 0 ? round($paidCount / $bookingCount * 100, 1) : 0.0,
        'topPages' => array_slice($pages, 0, 8, true),
        'topReferrers' => array_slice($referrers, 0, 8, true),
        'devices' => $devices,
        'countries' => array_slice($countries, 0, 8, true),
        'regions' => array_slice($regions, 0, 8, true),
        'cities' => array_slice($cities, 0, 8, true),
        'customerSuburbs' => array_slice($suburbs, 0, 8, true),
        'series' => $series,
    ];
}
