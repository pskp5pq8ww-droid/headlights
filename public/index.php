<?php
// Load Google Maps API key from a private config file outside the public web root.
// Copy _private/maps.example.php to _private/maps.php on the server and set the key there.
$mapsConfig = @include dirname(__DIR__) . '/_private/maps.php';
$mapsKey    = is_array($mapsConfig) ? ($mapsConfig['google_maps_api_key'] ?? '') : '';

header('Content-Type: text/html; charset=UTF-8');

// Read the static HTML and inject the key as a <meta> tag so JS can read it
// without the key being hardcoded in any static file.
$html = file_get_contents(__DIR__ . '/index.html');
$html = str_replace(
    '<meta name="theme-color"',
    '<meta name="gmap-key" content="' . htmlspecialchars($mapsKey, ENT_QUOTES, 'UTF-8') . '" />' . "\n    " . '<meta name="theme-color"',
    $html
);
echo $html;
