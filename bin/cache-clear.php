#!/usr/bin/env php
<?php

/**
 * Cache Clear Script
 * Clears all application caches
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Karyalay\Services\CacheService;

echo "Clearing application cache...\n";

try {
    $cacheService = new CacheService();
    
    // Clear all cache
    $cacheService->flush();
    
    // Clear file-based cache directory
    $cacheDir = __DIR__ . '/../storage/cache';
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        echo "✓ File cache cleared\n";
    }
    
    // Clear compiled views/templates if any
    $viewsDir = __DIR__ . '/../storage/views';
    if (is_dir($viewsDir)) {
        $files = glob($viewsDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        echo "✓ Compiled views cleared\n";
    }
    
    echo "✓ Cache cleared successfully\n";
    exit(0);
    
} catch (Exception $e) {
    echo "✗ Error clearing cache: " . $e->getMessage() . "\n";
    exit(1);
}
