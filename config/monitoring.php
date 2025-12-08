<?php

/**
 * Monitoring and Logging Configuration
 */

return [
    // Logging
    'logging' => [
        'enabled' => getenv('LOGGING_ENABLED') !== 'false',
        'level' => getenv('LOG_LEVEL') ?: 'info',
        'path' => __DIR__ . '/../storage/logs',
    ],
    
    // Error Tracking (Sentry)
    'error_tracking' => [
        'enabled' => getenv('ERROR_TRACKING_ENABLED') === 'true',
        'dsn' => getenv('SENTRY_DSN') ?: '',
        'environment' => getenv('APP_ENV') ?: 'development',
        'release' => getenv('APP_VERSION') ?: 'unknown',
        'sample_rate' => [
            'production' => 0.1,
            'staging' => 0.5,
            'development' => 1.0,
        ],
    ],
    
    // Performance Monitoring
    'performance' => [
        'enabled' => getenv('PERFORMANCE_MONITORING_ENABLED') !== 'false',
        'thresholds' => [
            'slow_query_ms' => 1000,
            'slow_request_ms' => 2000,
            'memory_warning_mb' => 128,
        ],
    ],
    
    // Alerts
    'alerts' => [
        'enabled' => getenv('ALERTS_ENABLED') === 'true',
        'rate_limit_window' => 300, // 5 minutes
        
        'channels' => [
            'email' => [
                'enabled' => !empty(getenv('ALERT_EMAIL')),
                'recipients' => array_filter(explode(',', getenv('ALERT_EMAIL') ?: '')),
            ],
            
            'slack' => [
                'enabled' => !empty(getenv('SLACK_WEBHOOK_URL')),
                'webhook_url' => getenv('SLACK_WEBHOOK_URL') ?: '',
            ],
            
            'pagerduty' => [
                'enabled' => !empty(getenv('PAGERDUTY_INTEGRATION_KEY')),
                'integration_key' => getenv('PAGERDUTY_INTEGRATION_KEY') ?: '',
            ],
        ],
    ],
    
    // Health Check
    'health_check' => [
        'enabled' => true,
        'endpoint' => '/health.php',
        'checks' => [
            'database' => true,
            'storage' => true,
            'uploads' => true,
            'php_version' => true,
            'php_extensions' => true,
        ],
    ],
];

