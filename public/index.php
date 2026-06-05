<?php
// Static Hostinger fallback. If the server opens index.php first, serve the
// landing page instead of trying to boot Laravel without vendor dependencies.
header('Content-Type: text/html; charset=UTF-8');
readfile(__DIR__ . '/index.html');
