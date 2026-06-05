<?php
// Load Google Maps API key from central config (outside web root)
$mapsConfig = @include dirname(__DIR__) . '/config/maps.php';
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
