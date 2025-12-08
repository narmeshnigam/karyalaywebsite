#!/usr/bin/env php
<?php

/**
 * Subscription Expiration Cron Job
 * 
 * This script should be run periodically (e.g., daily) to check for and expire
 * subscriptions that have passed their end date.
 * 
 * Usage:
 *   php bin/expire-subscriptions.php
 * 
 * Cron example (run daily at 1:00 AM):
 *   0 1 * * * /usr/bin/php /path/to/project/bin/expire-subscriptions.php >> /path/to/logs/expiration.log 2>&1
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Karyalay\Services\ExpirationService;

// Load environment variables from .env if it exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!getenv($name)) {
            putenv("$name=$value");
        }
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Starting subscription expiration job...\n";

try {
    $expirationService = new ExpirationService();
    
    // Process all expired subscriptions
    $result = $expirationService->processExpiredSubscriptions();
    
    if (isset($result['error'])) {
        echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $result['error'] . "\n";
        exit(1);
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Processed {$result['count']} expired subscription(s)\n";
    
    if ($result['count'] > 0) {
        echo "[" . date('Y-m-d H:i:s') . "] Expired subscription IDs: " . implode(', ', $result['subscription_ids']) . "\n";
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Subscription expiration job completed successfully\n";
    exit(0);
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
