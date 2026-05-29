<?php
// Used by: php -S localhost:8000 -t public/ router.php
// Routes all non-file requests through index.php

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve real static files directly (css, js, images etc.)
$file = __DIR__ . '/public' . $path;
if (is_file($file)) {
    return false;
}

// Everything else → front controller
require __DIR__ . '/public/index.php';
