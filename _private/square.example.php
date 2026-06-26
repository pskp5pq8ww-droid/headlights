<?php
/**
 * Copy this file to:  /home/u613502604/_private/square.php   (OUTSIDE public_html, NOT in git)
 * or set the equivalent environment variables in Hostinger.
 *
 * The access token is read ONLY by the backend and is never sent to the browser.
 *
 * Get these values from https://developer.squareup.com/apps  →  your app:
 *   - Sandbox tab → Application ID  +  Sandbox Access token
 *   - Sandbox → Locations → Location ID
 *
 * ── GO LIVE ──
 *   Change 'environment' to 'production' and paste your PRODUCTION
 *   application_id / access_token / location_id.
 */
return [
    'environment'    => 'sandbox', // 'sandbox' | 'production'
    'application_id' => 'REPLACE_WITH_SANDBOX_APPLICATION_ID',
    'access_token'   => 'REPLACE_WITH_SANDBOX_ACCESS_TOKEN',
    'location_id'    => 'REPLACE_WITH_SANDBOX_LOCATION_ID',
    'currency'       => 'AUD',
];
