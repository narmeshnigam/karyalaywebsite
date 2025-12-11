<?php
/**
 * Root Index - Redirect to Public Directory
 * This file redirects all requests to the public directory
 */

// Get the current request URI
$requestUri = $_SERVER['REQUEST_URI'];

// Detect base path dynamically
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = dirname($scriptName);
if ($basePath === '/' || $basePath === '\\') {
    $basePath = '';
}

// If accessing assets, uploads, or storage directly, allow it
$pattern = '#^' . preg_quote($basePath, '#') . '/(assets|uploads|storage)/#';
if (preg_match($pattern, $requestUri)) {
    // Let Apache handle it naturally
    http_response_code(404);
    exit('File not found');
}

// Redirect to public directory
$publicPath = $basePath . '/public' . (isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '');
header('Location: ' . $publicPath);
exit;
