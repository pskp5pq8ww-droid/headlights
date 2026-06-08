<?php
/**
 * Copy this file to:  /home/uXXX/_private/admin.php  (beside maps.php, OUTSIDE public_html)
 * Generate a password hash on the Hostinger terminal:
 *   php -r "echo password_hash('YOUR_NEW_PASSWORD', PASSWORD_BCRYPT);"
 * Paste the result as 'password_hash' below.
 * For a quick private setup, you can use 'password' instead of 'password_hash',
 * but keep this file outside public_html and out of Git.
 */
return [
    'username'      => 'admin',
    'password_hash' => '$2y$12$REPLACE_WITH_A_REAL_BCRYPT_HASH_FROM_THE_COMMAND_ABOVE',
    // 'password' => 'CHANGE_THIS_PASSWORD',
];
