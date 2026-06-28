<?php
/**
 * EXAMPLE Meta Conversions API config — copy this file to `meta.php` in the same
 * folder and paste your real Conversions API access token.
 *
 * `meta.php` is gitignored and MUST live OUTSIDE public_html on Hostinger, at:
 *   /home/u613502604/_private/meta.php
 *
 * Where to get the token:
 *   Meta Events Manager → your dataset/pixel → Settings →
 *   Conversions API → "Generate access token".
 *
 * test_event_code is OPTIONAL — set it while testing so events show up under
 * Events Manager → Test Events. REMOVE it (or leave blank) once you go live,
 * otherwise events only count as test traffic.
 */
return [
    'pixel_id'        => '1026998226936698',
    'access_token'    => 'PASTE_YOUR_CONVERSIONS_API_TOKEN_HERE',
    'test_event_code' => '', // e.g. 'TEST12345' while verifying; blank for production
];
