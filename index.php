<?php
/**
 * Root Index - Redirect to Public Directory
 * This file redirects all requests to the public directory
 */

// Get the current request URI
$requestUri = $_SERVER['REQUEST_URI'];

// If accessing assets, uploads, or storage directly, allow it
if (preg_match('#^/karyalayportal/(assets|uploads|storage)/#', $requestUri)) {
    // Let Apache handle it naturally
    http_response_code(404);
    exit('File not found');
}

// Redirect to public directory
$publicPath = '/karyalayportal/public' . (isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '');
header('Location: ' . $publicPath);
exit;
