<?php
/**
 * router.php — Router script for PHP built-in development server.
 *
 * Usage: php -S localhost:8000 -t public public/router.php
 *
 * This script handles routing for the built-in server:
 * - /api/* routes → run through Slim framework
 * - /assets/* and /favicon.ico → serve from dist/ directory with correct MIME types
 * - Everything else → serve dist/index.html for Vue Router
 */

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);

// API routes — let index.php handle them
if (str_starts_with($path, '/api/')) {
    require __DIR__ . '/index.php';
    return;
}

// Static assets from the Vue build — map /assets/* to /dist/assets/*
if (str_starts_with($path, '/assets/')) {
    $file = __DIR__ . '/dist' . $path;
    if (is_file($file)) {
        // Determine MIME type based on file extension
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $mimeTypes = [
            'js' => 'application/javascript',
            'css' => 'text/css',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
        ];
        $mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';
        
        header('Content-Type: ' . $mimeType);
        readfile($file);
        exit;
    }
}

// Favicon
if ($path === '/favicon.ico') {
    $file = __DIR__ . '/dist/favicon.ico';
    if (is_file($file)) {
        header('Content-Type: image/x-icon');
        readfile($file);
        exit;
    }
}

// All other routes → serve the Vue SPA entry point
$distIndex = __DIR__ . '/dist/index.html';
if (is_file($distIndex)) {
    header('Content-Type: text/html; charset=UTF-8');
    readfile($distIndex);
    exit;
}

// Fallback — let PHP handle it
return false;
