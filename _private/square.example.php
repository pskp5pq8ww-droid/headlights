<?php
/**
 * Copy this file to:  /home/u613502604/_private/square.php   (OUTSIDE public_html, NOT in git)
 * or set the equivalent environment variables in Hostinger.
 *
 * The access token is read ONLY by the backend and is never sent to the browser.
 *
 * Get these values from https://developer.squareup.com/apps → your app →
 * Production:
 *   - Application ID
 *   - Access token
 *   - Location ID
 */
return [
    'environment'    => 'production',
    'application_id' => 'REPLACE_WITH_PRODUCTION_APPLICATION_ID',
    'access_token'   => 'REPLACE_WITH_PRODUCTION_ACCESS_TOKEN',
    'location_id'    => 'REPLACE_WITH_PRODUCTION_LOCATION_ID',
    'currency'       => 'AUD',
];
