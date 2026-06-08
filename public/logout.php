<?php
require_once __DIR__ . '/includes/auth.php';
admin_logout();
admin_no_cache();
header('Location: /admin');
exit;
