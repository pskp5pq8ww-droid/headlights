<?php
/**
 * Central site configuration — business info, offer, packages, FAQs, areas.
 * Included by every page. No output here.
 */

// Cache-busting version for CSS/JS. Bump when you change assets.
const ASSET_VER = '13';

// ── Business info ────────────────────────────────────────────────────────────
$SITE = [
    'name'         => 'Shining Headlights Australia',
    'tagline'      => 'Mobile Headlight Restoration',
    'phone_label'  => '0400 000 000',
    'phone_href'   => 'tel:+61400000000',
    'email'        => 'hello@shiningheadlights.com.au',
    'region'       => 'Brisbane, Queensland',
    'canonical'    => 'https://shiningheadlights.com.au',
];

// ── EOFY offer ───────────────────────────────────────────────────────────────
// Countdown target — End of Financial Year (30 June), Brisbane time (UTC+10, no DST).
$EOFY = [
    'badge'    => 'EOFY · Limited Time Only',
    'title'    => 'EOFY Mobile Headlight Restoration Sale',
    'sub'      => 'We come to you anywhere in Brisbane.',
    'support'  => 'Crystal-clear headlights. Safer driving. Better looks. Professional restoration at your home, workplace or driveway.',
    'now'      => 99,
    'was'      => 149,
    'save'     => 50,
    'note'     => 'EOFY launch price. Limited time only.',
    'target'   => '2026-06-30T23:59:59+10:00',
];

// ── Packages ─────────────────────────────────────────────────────────────────
$PACKAGES = [
    [
        'name' => 'Basic Restore', 'price' => 'From $99', 'featured' => false,
        'bestFor' => 'For lightly cloudy headlights.',
        'inclusions' => ['Light oxidation removal', 'Headlight clarity restoration', 'Final clean finish'],
    ],
    [
        'name' => 'Crystal Restore', 'price' => 'From $149', 'featured' => true, 'badge' => 'Most Popular',
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
    'eofy'         => ['/eofy-offer',   'EOFY Offer'],
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

// Google Maps key loader. Keep real keys in Hostinger env vars, private env files, or _private/maps.php.
function maps_api_key(): string {
    $envKey = getenv('GOOGLE_MAPS_API_KEY') ?: getenv('NEXT_PUBLIC_GOOGLE_MAPS_API_KEY') ?: '';
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

    return '';
}

/**
 * Search paths for _private/maps.php — ordered from most-likely to least-likely.
 *
 * Hostinger shared-hosting layout (typical):
 *   /home/u[id]/                         ← user home  (dirname(__DIR__, 3) from includes/)
 *     htdocs/                            ← dirname(DOCUMENT_ROOT)
 *       orangered-rhinoceros-*.hostingersite.com/  ← DOCUMENT_ROOT = web root
 *         includes/                      ← __DIR__
 *
 * The _private/ folder must sit at the user-home level or one above the web root.
 */
function maps_php_candidates(): array {
    $docRoot  = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__), '/');
    $userHome = dirname(__DIR__, 3); // includes → web-root → htdocs → user-home

    return array_values(array_unique([
        $userHome          . '/_private/maps.php', // Hostinger user home (/home/u.../):      PRIMARY
        dirname($docRoot)  . '/_private/maps.php', // one above web root (/home/u.../htdocs): FALLBACK
        dirname(__DIR__, 2). '/_private/maps.php', // two levels above includes/:             LOCAL DEV
        dirname(__DIR__)   . '/_private/maps.php', // one level above includes/:              LAST RESORT
    ]));
}

function maps_env_file_candidates(): array {
    $docRoot     = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__), '/');
    $docParent   = dirname($docRoot);
    $userHome    = dirname(__DIR__, 3);
    $projectRoot = dirname(__DIR__, 2);
    return array_values(array_unique([
        $userHome    . '/.env',
        $userHome    . '/_private/.env',
        $userHome    . '/_private/env',
        $docParent   . '/.env',
        $docParent   . '/_private/.env',
        $docParent   . '/_private/env',
        $projectRoot . '/.env',
        $projectRoot . '/env',
        $projectRoot . '/_private/.env',
        $projectRoot . '/_private/env',
    ]));
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
