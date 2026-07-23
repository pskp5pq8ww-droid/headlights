<?php
/**
 * Central site configuration — business info, offer, packages, FAQs, areas.
 * Included by every page. No output here.
 */

// Cache-busting version for CSS/JS. Bump when you change assets.
const ASSET_VER = '40';
const DEFAULT_COUNTDOWN_TARGET = '2026-08-31T23:59:59+10:00';

// ── Business info ────────────────────────────────────────────────────────────
$SITE = [
    'name'         => 'Shining Headlights Australia',
    'tagline'      => 'Mobile Headlight Restoration',
    'phone_label'  => '0400 000 000',
    'phone_href'   => 'tel:+61400000000',
    'email'        => 'hello@shiningaus.com',
    'region'       => 'Brisbane, Queensland',
    'canonical'    => 'https://shiningaus.com',
];

// ── Special limited-time offer ───────────────────────────────────────────────
// Countdown target — centralized campaign end time, Brisbane time (UTC+10, no DST).
$OFFER = [
    'badge'    => 'Special Offer · Limited Time Only',
    'title'    => 'Special Limited-Time Headlight Restoration Offer',
    'sub'      => 'We come to you anywhere in Brisbane.',
    'support'  => 'Crystal-clear headlights. Safer driving. Better looks. Professional restoration at your home, workplace or driveway.',
    'now'      => 99,
    'was'      => 220,
    'save'     => 121,
    'note'     => 'Special limited-time price. Availability is limited.',
    'target'   => DEFAULT_COUNTDOWN_TARGET,
];

$SITE_SETTINGS = site_settings_read();
if (!empty($SITE_SETTINGS['countdownTarget'])) {
    $OFFER['target'] = $SITE_SETTINGS['countdownTarget'];
}

// ── Packages ─────────────────────────────────────────────────────────────────
$PACKAGES = [
    [
        'name' => 'Basic Restore', 'price' => 'From $99', 'featured' => false,
        'bestFor' => 'For lightly cloudy headlights.',
        'inclusions' => ['Light oxidation removal', 'Headlight clarity restoration', 'Final clean finish'],
    ],
    [
        'name' => 'Crystal Restore', 'price' => 'From $220', 'featured' => true, 'badge' => 'Most Popular',
        'bestFor' => 'For yellow, cloudy or heavily oxidised headlights.',
        'inclusions' => ['Multi-stage sanding process', 'Professional clarity restoration', 'Glossy clear finish', 'Basic UV protection'],
    ],
    [
        'name' => 'Premium Protection Restore', 'price' => 'From $199', 'featured' => false,
        'bestFor' => 'For the best finish and longer-lasting protection.',
        'inclusions' => ['Full headlight restoration', 'Deep oxidation removal', 'Crystal clear glossy finish', 'Premium UV protection', 'Final inspection'],
    ],
];

// ── Service area preview ─────────────────────────────────────────────────────
$AREAS = ['Brisbane CBD', 'Northside', 'Southside', 'Westside', 'Moreton Bay', 'Surrounding areas'];

// ── Reviews ──────────────────────────────────────────────────────────────────
$REVIEWS = [
    'rating'  => '4.9',
    'count'   => '250+',
    'heading' => 'Trusted by Brisbane drivers',
    'items'   => [
        ['text' => 'Came to my driveway and had both headlights crystal clear in under an hour. Looks like a new car.', 'name' => 'James R., Chermside'],
        ['text' => 'So easy — booked online, they came to my work. Night driving is so much better now.', 'name' => 'Priya M., Sunnybank'],
        ['text' => 'Professional, on time and a fraction of the cost of new headlights. Highly recommend.', 'name' => 'Daniel K., Redcliffe'],
        ['text' => 'My old Hilux headlights were completely yellow. Now they look factory fresh. Great value.', 'name' => 'Mark T., Logan'],
        ['text' => 'Booked for my mum in Ipswich, they texted before arriving and were super tidy. Lovely team.', 'name' => 'Sarah W., Ipswich'],
        ['text' => 'Honestly thought I needed new headlights. Saved me hundreds and took 45 minutes in my carport.', 'name' => 'Nguyen P., Carindale'],
        ['text' => 'Did both cars at our place in North Lakes. Massive difference, especially driving at night.', 'name' => 'Emma L., North Lakes'],
        ['text' => 'On time, friendly and the finish is glossy and clear. Will be telling the neighbours.', 'name' => 'Brett H., Springfield Lakes'],
        ['text' => 'Came to my office car park in the CBD on a lunch break. Couldn\'t be easier. 5 stars.', 'name' => 'Olivia S., Brisbane CBD'],
        ['text' => 'The UV protection package was worth it. Still crystal clear months later. Highly recommend.', 'name' => 'Raj K., Mount Gravatt'],
        ['text' => 'Quick reply, fair price and a fantastic result on my Mazda. Wynnum locals, book these guys.', 'name' => 'Tahlia B., Wynnum'],
        ['text' => 'Restored the headlights on our caravan tow car before a trip. Professional and well priced.', 'name' => 'Greg M., Redland Bay'],
    ],
];

// ── Before / After real results ──────────────────────────────────────────────
$BEFORE_AFTER_RESULTS = [
    [
        'id' => 1,
        'before' => 'assets/before-after/before-1.jpg',
        'after' => 'assets/before-after/after-1.jpg',
        'title' => 'Headlight restoration result 1',
    ],
    [
        'id' => 2,
        'before' => 'assets/before-after/before-2.jpg',
        'after' => 'assets/before-after/after-2.jpg',
        'title' => 'Headlight restoration result 2',
    ],
];

$SERVICE_PROOFS = [
    [
        'image' => 'assets/before-after/after-1.jpg',
        'label' => 'Restored finish',
        'title' => 'Clear lens after mobile restoration',
    ],
    [
        'image' => 'assets/before-after/before-1.jpg',
        'label' => 'Before condition',
        'title' => 'Cloudy lens before restoration',
    ],
    [
        'image' => 'assets/before-after/after-2.jpg',
        'label' => 'Finished result',
        'title' => 'Glossy clarity restored on-site',
    ],
    [
        'image' => 'assets/before-after/before-2.jpg',
        'label' => 'Before condition',
        'title' => 'Yellow oxidised headlight before service',
    ],
    [
        'image' => 'assets/before-after/condition-before-3.jpg',
        'label' => 'Oxidation example',
        'title' => 'Heavy clouding we can assess on arrival',
    ],
];

// ── FAQs ─────────────────────────────────────────────────────────────────────
$FAQS = [
    ['q' => 'How long does it take?',              'a' => 'Most restorations take around 45–90 minutes depending on the condition of the headlights.'],
    ['q' => 'Do you come to my home?',             'a' => 'Yes. We come to your home, workplace or driveway anywhere in Brisbane and surrounding areas.'],
    ['q' => 'Is it safe for my headlights?',       'a' => 'Yes. We use a professional multi-stage process that removes only the oxidised surface layer and restores clarity, then protects the finish.'],
    ['q' => 'How long do results last?',           'a' => 'Results depend on headlight condition, weather exposure and aftercare. UV protection helps the finish last significantly longer.'],
    ['q' => 'Do I need to visit a workshop?',      'a' => 'No. The whole point of our service is that we come to you — no workshop visit needed.'],
    ['q' => 'What areas do you service?',          'a' => 'Brisbane CBD, Northside, Southside, Westside, Moreton Bay and surrounding areas. Gold Coast and Sunshine Coast by request (travel fees may apply).'],
    ['q' => 'How do I book?',                      'a' => 'Use the Book Now page, enter your address and details, and pick a time. We confirm your mobile booking by phone.'],
];

// ── Navigation ───────────────────────────────────────────────────────────────
$NAV = [
    'home'         => ['/',             'Home'],
    'offer'        => ['/special-offer', 'Special Offer'],
    'services'     => ['/services',     'Services'],
    'before-after' => ['/before-after', 'Before &amp; After'],
    'pricing'      => ['/pricing',      'Pricing'],
    'faq'          => ['/faq',          'FAQ'],
    'book'         => ['/book',         'Book Now'],
    'contact'      => ['/contact',      'Contact'],
];

// Helper: versioned asset URL
function asset(string $path): string {
    return '/' . ltrim($path, '/') . '?v=' . ASSET_VER;
}

function site_settings_storage_base(): string {
    $env = getenv('BOOKING_STORAGE_PATH');
    if (is_string($env) && trim($env) !== '') return rtrim(trim($env), '/');
    return dirname($_SERVER['DOCUMENT_ROOT'] ?? __DIR__) . '/Storagehighlights';
}

function site_settings_path(): string {
    return site_settings_storage_base() . '/site-settings.json';
}

function site_settings_defaults(): array {
    return [
        'countdownTarget' => DEFAULT_COUNTDOWN_TARGET,
        'updatedAt' => '',
    ];
}

function site_settings_ensure_storage(): void {
    $base = site_settings_storage_base();
    if (!is_dir($base) && !mkdir($base, 0700, true) && !is_dir($base)) {
        throw new RuntimeException('Unable to create settings storage directory.');
    }
    $htaccess = $base . '/.htaccess';
    if (!is_file($htaccess)) {
        @file_put_contents($htaccess, "Deny from all\n", LOCK_EX);
        @chmod($htaccess, 0600);
    }
}

function site_settings_normalize_target(string $value): string {
    $value = trim($value);
    if ($value === '') return '';
    try {
        $tz = new DateTimeZone('Australia/Brisbane');
        $date = new DateTimeImmutable($value, $tz);
        return $date->setTimezone($tz)->format(DateTimeInterface::ATOM);
    } catch (Throwable) {
        return '';
    }
}

function site_settings_effective_countdown_target(string $value): string {
    $target = site_settings_normalize_target($value);
    if ($target === '') return DEFAULT_COUNTDOWN_TARGET;
    try {
        $tz = new DateTimeZone('Australia/Brisbane');
        $targetDate = new DateTimeImmutable($target);
        $defaultDate = new DateTimeImmutable(DEFAULT_COUNTDOWN_TARGET, $tz);
        $now = new DateTimeImmutable('now', $tz);
        if ($targetDate <= $now && $defaultDate > $now) return DEFAULT_COUNTDOWN_TARGET;
    } catch (Throwable) {
        return DEFAULT_COUNTDOWN_TARGET;
    }
    return $target;
}

function site_settings_read(): array {
    $defaults = site_settings_defaults();
    $path = site_settings_path();
    if (!is_file($path)) return $defaults;
    $data = json_decode((string)@file_get_contents($path), true);
    if (!is_array($data)) return $defaults;
    $target = site_settings_effective_countdown_target((string)($data['countdownTarget'] ?? ''));
    return [
        'countdownTarget' => $target !== '' ? $target : $defaults['countdownTarget'],
        'updatedAt' => trim((string)($data['updatedAt'] ?? '')),
    ];
}

function site_settings_write(array $settings): void {
    site_settings_ensure_storage();
    $current = site_settings_read();
    $target = site_settings_normalize_target((string)($settings['countdownTarget'] ?? ''));
    if ($target === '') throw new InvalidArgumentException('Please choose a valid countdown end date and time.');
    $next = [
        'countdownTarget' => $target,
        'updatedAt' => (new DateTimeImmutable('now', new DateTimeZone('Australia/Brisbane')))->format(DateTimeInterface::ATOM),
    ] + $current;
    $path = site_settings_path();
    $tmp = $path . '.tmp.' . bin2hex(random_bytes(4));
    $json = json_encode($next, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) throw new RuntimeException('Unable to encode settings JSON.');
    if (file_put_contents($tmp, $json, LOCK_EX) === false) throw new RuntimeException('Unable to write settings file.');
    @chmod($tmp, 0600);
    if (!rename($tmp, $path)) {
        @unlink($tmp);
        throw new RuntimeException('Unable to publish settings file.');
    }
    @chmod($path, 0600);
}

// Google Maps key loader. Keep real keys outside public_html, ideally in private/maps.php.
function maps_api_key(): string {
    $envKey = maps_env_value('GOOGLE_MAPS_API_KEY') ?: maps_env_value('NEXT_PUBLIC_GOOGLE_MAPS_API_KEY');
    if ($envKey !== '') return trim($envKey);

    foreach (maps_env_file_candidates() as $envFile) {
        $fileKey = maps_key_from_env_file($envFile);
        if ($fileKey !== '') return $fileKey;
    }

    foreach (maps_php_candidates() as $candidate) {
        if (is_file($candidate)) {
            $config = include $candidate;
            if (is_array($config) && !empty($config['google_maps_api_key'])) {
                return trim((string)$config['google_maps_api_key']);
            }
        }
    }

    return maps_restricted_browser_key_fallback();
}

function maps_restricted_browser_key_fallback(): string {
    return 'AIzaSyAUMH2keW0YBPKUaQlUwuZGJ_LeRcuAcAM';
}

function maps_env_value(string $name): string {
    $value = getenv($name);
    if (is_string($value) && trim($value) !== '') return trim($value);
    if (isset($_ENV[$name]) && trim((string)$_ENV[$name]) !== '') return trim((string)$_ENV[$name]);
    if (isset($_SERVER[$name]) && trim((string)$_SERVER[$name]) !== '') return trim((string)$_SERVER[$name]);
    return '';
}

function maps_php_candidates(): array {
    $roots = maps_private_roots();
    $paths = ['/home/u613502604/private/maps.php'];

    foreach ($roots as $root) {
        $paths[] = $root . '/private/maps.php';
    }
    foreach ($roots as $root) {
        $paths[] = $root . '/_private/maps.php';
    }

    return array_values(array_unique($paths));
}

function maps_env_file_candidates(): array {
    $paths = [];
    foreach (maps_private_roots() as $root) {
        $paths[] = $root . '/.env';
        $paths[] = $root . '/env';
        $paths[] = $root . '/private/.env';
        $paths[] = $root . '/private/env';
        $paths[] = $root . '/_private/.env';
        $paths[] = $root . '/_private/env';
    }

    return array_values(array_unique($paths));
}

/**
 * Candidate private roots, from closest to the public web root up to the account home.
 * This covers Hostinger layouts like /home/{user}/domains/site/public_html
 * with the config file at /home/{user}/private/maps.php.
 */
function maps_private_roots(): array {
    $roots = [];
    $docRoot = maps_normalize_path($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__));
    $includeRoot = maps_normalize_path(dirname(__DIR__));

    foreach ([dirname($docRoot), dirname($includeRoot), getenv('HOME') ?: ''] as $start) {
        foreach (maps_path_ancestors($start, 8) as $ancestor) {
            $roots[] = $ancestor;
        }
    }

    return array_values(array_unique(array_filter($roots)));
}

function maps_path_ancestors(string $path, int $limit): array {
    $path = maps_normalize_path($path);
    if ($path === '') return [];

    $ancestors = [$path];
    for ($i = 0; $i < $limit; $i++) {
        $parent = dirname($path);
        if ($parent === $path || $parent === '.' || $parent === '') break;
        $ancestors[] = $parent;
        $path = $parent;
    }

    return $ancestors;
}

function maps_normalize_path(string $path): string {
    return rtrim($path, "/\\");
}

function maps_key_from_env_file(string $file): string {
    if (!is_file($file) || !is_readable($file)) return '';
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) return '';

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$name, $value] = array_map('trim', explode('=', $line, 2));
        if (!in_array($name, ['GOOGLE_MAPS_API_KEY', 'NEXT_PUBLIC_GOOGLE_MAPS_API_KEY'], true)) continue;
        return trim($value, " \t\n\r\0\x0B\"'");
    }

    return '';
}
