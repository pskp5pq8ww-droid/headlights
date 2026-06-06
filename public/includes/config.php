<?php
/**
 * Central site configuration — business info, offer, packages, FAQs, areas.
 * Included by every page. No output here.
 */

// Cache-busting version for CSS/JS. Bump when you change assets.
const ASSET_VER = '5';

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

// ── Google Maps key loader (private, outside web root) ───────────────────────
function maps_api_key(): string {
    $file = dirname($_SERVER['DOCUMENT_ROOT'] ?? __DIR__) . '/_private/maps.php';
    if (is_file($file)) {
        $c = include $file;
        if (is_array($c)) return (string)($c['google_maps_api_key'] ?? '');
    }
    return '';
}

// Helper: versioned asset URL
function asset(string $path): string {
    return '/' . ltrim($path, '/') . '?v=' . ASSET_VER;
}
