<?php

/**
 * Global Error Handler
 * Integrates logging, error tracking, and alerting services
 */

use Karyalay\Services\LoggerService;
use Karyalay\Services\ErrorTrackingService;
use Karyalay\Services\AlertService;
use Karyalay\Services\PerformanceMonitoringService;

// Set error reporting based on environment
$environment = getenv('APP_ENV') ?: 'development';
if ($environment === 'production') {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

/**
 * Custom error handler
 */
set_error_handler(function ($severity, $message, $file, $line) {
    // Don't handle errors that are suppressed with @
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    $logger = LoggerService::getInstance();
    $errorTracker = ErrorTrackingService::getInstance();
    
    $context = [
        'severity' => $severity,
        'file' => $file,
        'line' => $line,
    ];
    
    // Map PHP error severity to log level
    $logLevel = 'error';
    if ($severity & (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR)) {
        $logLevel = 'error';
    } elseif ($severity & (E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING | E_USER_WARNING)) {
        $logLevel = 'warning';
    } elseif ($severity & (E_NOTICE | E_USER_NOTICE)) {
        $logLevel = 'notice';
    }
    
    // Log the error
    $logger->log($logLevel, $message, $context);
    
    // Send to error tracking for errors and warnings
    if ($severity & (E_ERROR | E_WARNING | E_USER_ERROR | E_USER_WARNING)) {
        $errorTracker->captureMessage($message, $logLevel, $context);
    }
    
    // Don't execute PHP internal error handler
    return true;
});

/**
 * Custom exception handler
 */
set_exception_handler(function (Throwable $exception) {
    $logger = LoggerService::getInstance();
    $errorTracker = ErrorTrackingService::getInstance();
    $alertService = AlertService::getInstance();
    
    // Log the exception
    $logger->logException($exception);
    
    // Send to error tracking
    $errorTracker->captureException($exception);
    
    // Send alert for critical exceptions
    $environment = getenv('APP_ENV') ?: 'development';
    if ($environment === 'production') {
        $alertService->critical(
            'Unhandled Exception',
            $exception->getMessage(),
            [
                'exception_class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]
        );
    }
    
    // Display user-friendly error page
    if ($environment === 'production') {
        http_response_code(500);
        include __DIR__ . '/../templates/error-500.php';
    } else {
        // In development, show detailed error
        echo '<h1>Exception</h1>';
        echo '<p><strong>Message:</strong> ' . htmlspecialchars($exception->getMessage()) . '</p>';
        echo '<p><strong>File:</strong> ' . htmlspecialchars($exception->getFile()) . ':' . $exception->getLine() . '</p>';
        echo '<pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>';
    }
    
    exit(1);
});

/**
 * Shutdown handler for fatal errors
 */
register_shutdown_function(function () {
    $error = error_get_last();
    
    if ($error !== null && $error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR)) {
        $logger = LoggerService::getInstance();
        $errorTracker = ErrorTrackingService::getInstance();
        $alertService = AlertService::getInstance();
        
        $message = "Fatal error: {$error['message']} in {$error['file']}:{$error['line']}";
        
        // Log fatal error
        $logger->critical($message, $error);
        
        // Send to error tracking
        $errorTracker->captureMessage($message, 'critical', $error);
        
        // Send alert
        $environment = getenv('APP_ENV') ?: 'development';
        if ($environment === 'production') {
            $alertService->critical('Fatal Error', $message, $error);
        }
    }
    
    // Track request performance
    $perfMonitor = PerformanceMonitoringService::getInstance();
    $perfMonitor->trackRequest();
    $perfMonitor->flush();
});

/**
 * Helper function to log messages
 */
function log_message(string $level, string $message, array $context = []): void
{
    $logger = LoggerService::getInstance();
    $logger->log($level, $message, $context);
}

/**
 * Helper function to track performance
 */
function track_performance(string $name, callable $callback)
{
    $perfMonitor = PerformanceMonitoringService::getInstance();
    $perfMonitor->startTimer($name);
    
    try {
        $result = $callback();
        return $result;
    } finally {
        $perfMonitor->stopTimer($name);
    }
}

