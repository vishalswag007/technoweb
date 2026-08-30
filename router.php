<?php
/**
 * Vishal Web Studio - Built-in Server Dynamic Router
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$filePath = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $uri);

// If request is for a PHP script that exists, execute it
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    if ($ext === 'php') {
        require $filePath;
        exit;
    }

    if ($ext === 'css') {
        header('Content-Type: text/css');
        readfile($filePath);
        exit;
    }

    if ($ext === 'js') {
        header('Content-Type: application/javascript');
        readfile($filePath);
        exit;
    }

    if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg', 'gif', 'ico'])) {
        $mime = ($ext === 'svg') ? 'image/svg+xml' : (($ext === 'ico') ? 'image/x-icon' : 'image/' . $ext);
        header('Content-Type: ' . $mime);
        readfile($filePath);
        exit;
    }

    if ($ext === 'woff2' || $ext === 'woff' || $ext === 'ttf') {
        header('Content-Type: font/' . $ext);
        readfile($filePath);
        exit;
    }

    return false;
}

// If directory requested, check for index.php inside it
if ($uri !== '/' && is_dir($filePath) && file_exists($filePath . DIRECTORY_SEPARATOR . 'index.php')) {
    require $filePath . DIRECTORY_SEPARATOR . 'index.php';
    exit;
}

// Default fallback to root index.php
require __DIR__ . '/index.php';
