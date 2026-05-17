<?php
// Laravel PHP built-in server router. Routes requests to public/index.php
// Serves static files directly when they exist under /public.

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri)) {
    return false; // Let the built-in server serve the static file
}

require_once __DIR__ . '/public/index.php';