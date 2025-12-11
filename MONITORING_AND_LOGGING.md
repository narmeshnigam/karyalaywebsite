# Monitoring and Logging Guide

This document describes the monitoring and logging infrastructure for the SellerPortal System.

## Overview

The system includes comprehensive monitoring and logging capabilities:

- **Structured Logging**: JSON-formatted logs with context
- **Error Tracking**: Integration with Sentry or custom error tracking
- **Performance Monitoring**: Track slow queries and requests
- **Alerting**: Multi-channel alerts for critical issues
- **Health Checks**: Automated system health monitoring

## Components

### 1. Logger Service

The `LoggerService` provides structured logging with PSR-3 compatible log levels.

**Usage:**

```php
use Karyalay\Services\LoggerService;

$logger = LoggerService::getInstance();

// Log messages at different levels
$logger->info('User logged in', ['user_id' => 123]);
$logger->warning('Slow query detected', ['duration_ms' => 1500]);
$logger->error('Payment failed', ['order_id' => 456]);

// Log exceptions
try {
    // ... code
} catch (Exception $e) {
    $logger->logException($e, ['context' => 'payment processing']);
}
```

**Log Levels:**
- `emergency`: System is unusable
- `alert`: Action must be taken immediately
- `critical`: Critical conditions
- `error`: Error conditions
- `warning`: Warning conditions
- `notice`: Normal but significant condition
- `info`: Informational messages
- `debug`: Debug-level messages

**Log Files:**
- `storage/logs/{environment}-{date}.log`: All logs
- `storage/logs/errors-{date}.log`: Errors and above only

### 2. Error Tracking Service

The `ErrorTrackingService` integrates with Sentry for error tracking and monitoring.

**Configuration:**

```bash
# .env
ERROR_TRACKING_ENABLED=true
SENTRY_DSN=https://your-sentry-dsn@sentry.io/project-id
APP_VERSION=1.0.0
```

**Usage:**

```php
use Karyalay\Services\ErrorTrackingService;

$errorTracker = ErrorTrackingService::getInstance();

// Capture exceptions
try {
    // ... code
} catch (Exception $e) {
    $errorTracker->captureException($e, ['context' => 'additional info']);
}

// Capture messages
$errorTracker->captureMessage('Something went wrong', 'error', ['details' => '...']);

// Set user context
$errorTracker->setUser([
    'id' => $userId,
    'email' => $userEmail,
    'name' => $userName,
]);

// Add breadcrumbs for debugging
$errorTracker->addBreadcrumb('User clicked checkout', 'user.action', ['cart_total' => 99.99]);
```

**Installing Sentry SDK (Optional):**

```bash
composer require sentry/sentry
```

If Sentry SDK is not installed, errors will be logged to `storage/logs/error-tracking.log`.

### 3. Performance Monitoring Service

The `PerformanceMonitoringService` tracks application performance metrics.

**Usage:**

```php
use Karyalay\Services\PerformanceMonitoringService;

$perfMonitor = PerformanceMonitoringService::getInstance();

// Track operations
$perfMonitor->startTimer('database.query');
// ... perform operation
$duration = $perfMonitor->stopTimer('database.query');

// Track database queries
$perfMonitor->trackQuery($sql, $duration, $params);

// Get performance summary
$summary = $perfMonitor->getSummary();
```

**Helper Function:**

```php
// Track performance of a callback
$result = track_performance('expensive_operation', function() {
    // ... code
    return $result;
});
```

**Performance Logs:**
- `storage/logs/performance-{date}.log`: Performance metrics

**Thresholds:**
- Slow Query: 1000ms (1 second)
- Slow Request: 2000ms (2 seconds)
- Memory Warning: 128MB

### 4. Alert Service

The `AlertService` sends alerts through multiple channels.

**Configuration:**

```bash
# .env
ALERTS_ENABLED=true
ALERT_EMAIL=admin@karyalay.com,ops@karyalay.com
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
PAGERDUTY_INTEGRATION_KEY=your-pagerduty-key
```

**Usage:**

```php
use Karyalay\Services\AlertService;

$alertService = AlertService::getInstance();

// Send alerts at different severity levels
$alertService->info('Deployment completed', 'Version 1.2.0 deployed successfully');
$alertService->warning('High memory usage', 'Memory usage at 85%', ['memory_mb' => 850]);
$alertService->error('Payment gateway timeout', 'Razorpay API not responding');
$alertService->critical('Database connection failed', 'Cannot connect to primary database');
```

**Alert Channels:**
- **Email**: Sends to configured email addresses
- **Slack**: Posts to Slack channel via webhook
- **PagerDuty**: Creates incidents in PagerDuty

**Rate Limiting:**
Alerts are rate-limited to prevent spam. Same alert title won't be sent more than once per 5 minutes.

### 5. Global Error Handler

The global error handler is automatically loaded and integrates all monitoring services.

**Included in:**
- `includes/error_handler.php`

**Features:**
- Catches all PHP errors and exceptions
- Logs to structured logs
- Sends to error tracking service
- Sends alerts for critical errors
- Displays user-friendly error pages in production

**To enable:**

Add to your bootstrap file (e.g., `config/bootstrap.php`):

```php
require_once __DIR__ . '/../includes/error_handler.php';
```

## Health Checks

### Health Check Endpoint

**URL:** `/health.php`

**Response:**

```json
{
  "status": "healthy",
  "timestamp": "2024-01-15T10:30:00+00:00",
  "checks": {
    "database": {
      "status": "healthy",
      "message": "Database connection successful"
    },
    "storage": {
      "status": "healthy",
      "message": "Storage directory is writable"
    },
    "uploads": {
      "status": "healthy",
      "message": "Uploads directory is writable"
    },
    "php_version": {
      "status": "healthy",
      "message": "PHP version 8.1.0 meets requirements",
      "version": "8.1.0"
    },
    "php_extensions": {
      "status": "healthy",
      "message": "All required PHP extensions are loaded"
    }
  }
}
```

**Status Codes:**
- `200`: System is healthy
- `503`: System is unhealthy

### Monitoring Script

**Location:** `bin/monitor.sh`

**Usage:**

```bash
# Run manually
./bin/monitor.sh

# Add to crontab for automated monitoring
*/5 * * * * /path/to/bin/monitor.sh
```

**Checks:**
- HTTP response
- Health endpoint
- SSL certificate expiration
- Disk space
- Memory usage

## Admin Monitoring Dashboard

**URL:** `/admin/monitoring.php`

**Features:**
- System health overview
- Recent errors display
- Performance metrics
- Log file management
- Download log files
- Clear old logs

**Access:** Requires admin authentication

## Configuration

### Environment Variables

```bash
# Logging
LOGGING_ENABLED=true
LOG_LEVEL=info

# Error Tracking
ERROR_TRACKING_ENABLED=true
SENTRY_DSN=https://your-dsn@sentry.io/project

# Performance Monitoring
PERFORMANCE_MONITORING_ENABLED=true

# Alerts
ALERTS_ENABLED=true
ALERT_EMAIL=admin@karyalay.com
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/...
PAGERDUTY_INTEGRATION_KEY=your-key

# Application Version
APP_VERSION=1.0.0
```

### Configuration File

**Location:** `config/monitoring.php`

Contains detailed configuration for all monitoring components.

## Best Practices

### 1. Log Appropriately

```php
// ✅ Good: Structured logging with context
$logger->info('Order created', [
    'order_id' => $orderId,
    'customer_id' => $customerId,
    'amount' => $amount,
]);

// ❌ Bad: Unstructured logging
$logger->info("Order $orderId created for customer $customerId");
```

### 2. Don't Log Sensitive Data

```php
// ❌ Bad: Logging passwords
$logger->info('User login', ['password' => $password]);

// ✅ Good: Omit sensitive data
$logger->info('User login', ['user_id' => $userId]);
```

### 3. Use Appropriate Log Levels

- `debug`: Development debugging only
- `info`: Normal operations (user actions, system events)
- `warning`: Unexpected but handled situations
- `error`: Errors that need attention
- `critical`: Critical failures requiring immediate action

### 4. Add Context

```php
// ✅ Good: Rich context
$logger->error('Payment failed', [
    'order_id' => $orderId,
    'payment_gateway' => 'razorpay',
    'error_code' => $errorCode,
    'amount' => $amount,
]);
```

### 5. Track Performance

```php
// Track database queries
$perfMonitor->startTimer('database.query');
$result = $db->query($sql);
$perfMonitor->stopTimer('database.query');

// Track external API calls
$perfMonitor->startTimer('api.razorpay');
$response = $razorpay->createOrder($data);
$perfMonitor->stopTimer('api.razorpay');
```

## Troubleshooting

### Logs Not Being Written

1. Check directory permissions:
   ```bash
   chmod 755 storage/logs
   ```

2. Check `LOGGING_ENABLED` environment variable

3. Check disk space

### Error Tracking Not Working

1. Verify Sentry DSN is correct
2. Check `ERROR_TRACKING_ENABLED=true`
3. Install Sentry SDK: `composer require sentry/sentry`
4. Check network connectivity to Sentry

### Alerts Not Sending

1. Verify `ALERTS_ENABLED=true`
2. Check email configuration (SMTP settings)
3. Verify webhook URLs are correct
4. Check alert rate limiting (5-minute window)

### High Log Volume

1. Adjust `LOG_LEVEL` to reduce verbosity:
   ```bash
   LOG_LEVEL=warning  # Only warnings and above
   ```

2. Set up log rotation:
   ```bash
   # Add to crontab
   0 0 * * * find /path/to/storage/logs -name "*.log" -mtime +30 -delete
   ```

3. Use the admin dashboard to clear old logs

## Monitoring Checklist

- [ ] Configure environment variables
- [ ] Set up Sentry account (optional)
- [ ] Configure alert channels (email, Slack, PagerDuty)
- [ ] Add monitoring script to crontab
- [ ] Set up log rotation
- [ ] Test health check endpoint
- [ ] Test error tracking
- [ ] Test alert delivery
- [ ] Review logs regularly
- [ ] Monitor performance metrics

## Support

For issues or questions about monitoring and logging:
- Check logs in `storage/logs/`
- Review admin monitoring dashboard
- Contact system administrator

