# Environment Variables Reference

This document provides a comprehensive reference for all environment variables used in the SellerPortal System.

## Table of Contents

1. [Application Configuration](#application-configuration)
2. [Database Configuration](#database-configuration)
3. [Email Configuration](#email-configuration)
4. [Payment Gateway Configuration](#payment-gateway-configuration)
5. [File Storage Configuration](#file-storage-configuration)
6. [CDN Configuration](#cdn-configuration)
7. [Monitoring and Logging](#monitoring-and-logging)
8. [Error Tracking](#error-tracking)
9. [Performance Monitoring](#performance-monitoring)
10. [Alerts Configuration](#alerts-configuration)
11. [Environment-Specific Examples](#environment-specific-examples)

---

## Application Configuration

### APP_ENV
- **Description**: Application environment
- **Type**: String
- **Values**: `development`, `staging`, `production`
- **Default**: `development`
- **Required**: Yes
- **Example**: `APP_ENV=production`

### APP_DEBUG
- **Description**: Enable debug mode (shows detailed errors)
- **Type**: Boolean
- **Values**: `true`, `false`
- **Default**: `true`
- **Required**: Yes
- **Security**: Must be `false` in production
- **Example**: `APP_DEBUG=false`

### APP_URL
- **Description**: Base URL of the application
- **Type**: URL
- **Default**: `http://localhost`
- **Required**: Yes
- **Example**: `APP_URL=https://karyalay.com`
- **Notes**: Used for generating absolute URLs, email links, and redirects

### APP_VERSION
- **Description**: Application version for release tracking
- **Type**: String (Semantic Versioning)
- **Default**: `1.0.0`
- **Required**: No
- **Example**: `APP_VERSION=1.2.3`
- **Notes**: Used in monitoring and error tracking

---

## Database Configuration

### DB_HOST
- **Description**: Database server hostname or IP address
- **Type**: String
- **Default**: `localhost`
- **Required**: Yes
- **Example**: `DB_HOST=db.karyalay.com`

### DB_PORT
- **Description**: Database server port
- **Type**: Integer
- **Default**: `3306`
- **Required**: No
- **Example**: `DB_PORT=3306`

### DB_NAME
- **Description**: Database name
- **Type**: String
- **Default**: `karyalay_portal`
- **Required**: Yes
- **Example**: `DB_NAME=karyalay_production`

### DB_USER
- **Description**: Database username
- **Type**: String
- **Default**: `root`
- **Required**: Yes
- **Example**: `DB_USER=karyalay_prod_user`

### DB_PASS
- **Description**: Database password
- **Type**: String
- **Default**: Empty
- **Required**: Yes (in production)
- **Example**: `DB_PASS=secure_password_here`
- **Security**: Use strong passwords in production

### DB_UNIX_SOCKET
- **Description**: Unix socket path for database connection (alternative to host/port)
- **Type**: String
- **Default**: Empty
- **Required**: No
- **Example**: `DB_UNIX_SOCKET=/var/run/mysqld/mysqld.sock`
- **Notes**: Used when connecting via Unix socket instead of TCP

### DB_PERSISTENT
- **Description**: Enable persistent database connections
- **Type**: Boolean
- **Values**: `true`, `false`
- **Default**: `true`
- **Required**: No
- **Example**: `DB_PERSISTENT=true`
- **Notes**: Improves performance by reusing connections

---

## Email Configuration

### MAIL_HOST
- **Description**: SMTP server hostname
- **Type**: String
- **Default**: `smtp.mailtrap.io`
- **Required**: Yes
- **Example**: `MAIL_HOST=smtp.gmail.com`

### MAIL_PORT
- **Description**: SMTP server port
- **Type**: Integer
- **Default**: `2525`
- **Required**: Yes
- **Common Values**:
  - `25` - Standard SMTP (unencrypted)
  - `587` - SMTP with STARTTLS
  - `465` - SMTP with SSL/TLS
  - `2525` - Alternative port (Mailtrap)
- **Example**: `MAIL_PORT=587`

### MAIL_USERNAME
- **Description**: SMTP authentication username
- **Type**: String
- **Default**: Empty
- **Required**: Yes (if SMTP requires auth)
- **Example**: `MAIL_USERNAME=noreply@karyalay.com`

### MAIL_PASSWORD
- **Description**: SMTP authentication password
- **Type**: String
- **Default**: Empty
- **Required**: Yes (if SMTP requires auth)
- **Example**: `MAIL_PASSWORD=smtp_password_here`
- **Security**: Keep secure, use app-specific passwords when possible

### MAIL_FROM_ADDRESS
- **Description**: Default "from" email address
- **Type**: Email
- **Default**: `noreply@karyalay.com`
- **Required**: Yes
- **Example**: `MAIL_FROM_ADDRESS=noreply@karyalay.com`

### MAIL_FROM_NAME
- **Description**: Default "from" name
- **Type**: String
- **Default**: `SellerPortal`
- **Required**: Yes
- **Example**: `MAIL_FROM_NAME="SellerPortal"`

### ADMIN_EMAIL
- **Description**: Admin email for system notifications
- **Type**: Email
- **Default**: `admin@karyalay.com`
- **Required**: Yes
- **Example**: `ADMIN_EMAIL=admin@karyalay.com`
- **Notes**: Receives alerts, contact form submissions, and system notifications

---

## Payment Gateway Configuration

### RAZORPAY_KEY_ID
- **Description**: Razorpay API key ID
- **Type**: String
- **Default**: Empty
- **Required**: Yes (for payment processing)
- **Example**: `RAZORPAY_KEY_ID=rzp_test_1234567890`
- **Notes**: Use test keys for development, live keys for production

### RAZORPAY_KEY_SECRET
- **Description**: Razorpay API key secret
- **Type**: String
- **Default**: Empty
- **Required**: Yes (for payment processing)
- **Example**: `RAZORPAY_KEY_SECRET=secret_key_here`
- **Security**: Keep this secret secure, never commit to version control

### RAZORPAY_WEBHOOK_SECRET
- **Description**: Razorpay webhook signature secret
- **Type**: String
- **Default**: Empty
- **Required**: Yes (for webhook verification)
- **Example**: `RAZORPAY_WEBHOOK_SECRET=webhook_secret_here`
- **Security**: Used to verify webhook authenticity
- **Notes**: Configure webhook URL in Razorpay dashboard

---

## File Storage Configuration

### STORAGE_DRIVER
- **Description**: File storage driver
- **Type**: String
- **Values**: `local`, `s3`
- **Default**: `local`
- **Required**: Yes
- **Example**: `STORAGE_DRIVER=s3`

### AWS_ACCESS_KEY_ID
- **Description**: AWS access key ID (for S3 storage)
- **Type**: String
- **Default**: Empty
- **Required**: Yes (if using S3)
- **Example**: `AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE`

### AWS_SECRET_ACCESS_KEY
- **Description**: AWS secret access key (for S3 storage)
- **Type**: String
- **Default**: Empty
- **Required**: Yes (if using S3)
- **Example**: `AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY`
- **Security**: Keep this secret secure

### AWS_DEFAULT_REGION
- **Description**: AWS region for S3 bucket
- **Type**: String
- **Default**: `us-east-1`
- **Required**: Yes (if using S3)
- **Example**: `AWS_DEFAULT_REGION=us-west-2`

### AWS_BUCKET
- **Description**: S3 bucket name
- **Type**: String
- **Default**: Empty
- **Required**: Yes (if using S3)
- **Example**: `AWS_BUCKET=karyalay-uploads`

---

## CDN Configuration

### CDN_ENABLED
- **Description**: Enable CDN for static assets
- **Type**: Boolean
- **Values**: `true`, `false`
- **Default**: `false`
- **Required**: No
- **Example**: `CDN_ENABLED=true`

### CDN_BASE_URL
- **Description**: Base URL for CDN
- **Type**: URL
- **Default**: Empty
- **Required**: Yes (if CDN enabled)
- **Example**: `CDN_BASE_URL=https://cdn.karyalay.com`

### CLOUDFRONT_DISTRIBUTION_ID
- **Description**: AWS CloudFront distribution ID
- **Type**: String
- **Default**: Empty
- **Required**: No (if using CloudFront)
- **Example**: `CLOUDFRONT_DISTRIBUTION_ID=E1234567890ABC`
- **Notes**: Used for cache invalidation

### CLOUDFRONT_DOMAIN
- **Description**: CloudFront distribution domain
- **Type**: String
- **Default**: Empty
- **Required**: No (if using CloudFront)
- **Example**: `CLOUDFRONT_DOMAIN=d111111abcdef8.cloudfront.net`

### CLOUDFLARE_ZONE_ID
- **Description**: Cloudflare zone ID
- **Type**: String
- **Default**: Empty
- **Required**: No (if using Cloudflare)
- **Example**: `CLOUDFLARE_ZONE_ID=023e105f4ecef8ad9ca31a8372d0c353`
- **Notes**: Used for cache purging

### CLOUDFLARE_DOMAIN
- **Description**: Domain managed by Cloudflare
- **Type**: String
- **Default**: Empty
- **Required**: No (if using Cloudflare)
- **Example**: `CLOUDFLARE_DOMAIN=karyalay.com`

### CUSTOM_CDN_DOMAIN
- **Description**: Custom CDN domain (for other CDN providers)
- **Type**: String
- **Default**: Empty
- **Required**: No
- **Example**: `CUSTOM_CDN_DOMAIN=cdn.karyalay.com`

---

## Monitoring and Logging

### LOGGING_ENABLED
- **Description**: Enable application logging
- **Type**: Boolean
- **Values**: `true`, `false`
- **Default**: `true`
- **Required**: No
- **Example**: `LOGGING_ENABLED=true`

### LOG_LEVEL
- **Description**: Minimum log level to record
- **Type**: String
- **Values**: `debug`, `info`, `warning`, `error`, `critical`
- **Default**: `info`
- **Required**: No
- **Example**: `LOG_LEVEL=warning`
- **Notes**: 
  - `debug` - Detailed debug information
  - `info` - Informational messages
  - `warning` - Warning messages
  - `error` - Error messages
  - `critical` - Critical conditions

---

## Error Tracking

### ERROR_TRACKING_ENABLED
- **Description**: Enable error tracking service (Sentry)
- **Type**: Boolean
- **Values**: `true`, `false`
- **Default**: `false`
- **Required**: No
- **Example**: `ERROR_TRACKING_ENABLED=true`

### SENTRY_DSN
- **Description**: Sentry Data Source Name
- **Type**: URL
- **Default**: Empty
- **Required**: Yes (if error tracking enabled)
- **Example**: `SENTRY_DSN=https://examplePublicKey@o0.ingest.sentry.io/0`
- **Notes**: Get this from your Sentry project settings

---

## Performance Monitoring

### PERFORMANCE_MONITORING_ENABLED
- **Description**: Enable performance monitoring
- **Type**: Boolean
- **Values**: `true`, `false`
- **Default**: `true`
- **Required**: No
- **Example**: `PERFORMANCE_MONITORING_ENABLED=true`
- **Notes**: Tracks page load times, database query performance, etc.

---

## Alerts Configuration

### ALERTS_ENABLED
- **Description**: Enable system alerts
- **Type**: Boolean
- **Values**: `true`, `false`
- **Default**: `false`
- **Required**: No
- **Example**: `ALERTS_ENABLED=true`

### ALERT_EMAIL
- **Description**: Email address for system alerts
- **Type**: Email
- **Default**: `admin@karyalay.com`
- **Required**: Yes (if alerts enabled)
- **Example**: `ALERT_EMAIL=alerts@karyalay.com`

### SLACK_WEBHOOK_URL
- **Description**: Slack webhook URL for alerts
- **Type**: URL
- **Default**: Empty
- **Required**: No
- **Example**: `SLACK_WEBHOOK_URL=https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXX`
- **Notes**: Configure in Slack workspace settings

### PAGERDUTY_INTEGRATION_KEY
- **Description**: PagerDuty integration key for critical alerts
- **Type**: String
- **Default**: Empty
- **Required**: No
- **Example**: `PAGERDUTY_INTEGRATION_KEY=abc123def456ghi789`
- **Notes**: Used for on-call incident management

---

## Environment-Specific Examples

### Development Environment (.env)

```bash
# Application
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost
APP_VERSION=1.0.0

# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=karyalay_portal
DB_USER=root
DB_PASS=
DB_PERSISTENT=true

# Email (Mailtrap for testing)
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_FROM_ADDRESS=noreply@karyalay.local
MAIL_FROM_NAME="SellerPortal Dev"
ADMIN_EMAIL=admin@karyalay.local

# Payment Gateway (Test Mode)
RAZORPAY_KEY_ID=rzp_test_1234567890
RAZORPAY_KEY_SECRET=test_secret_key
RAZORPAY_WEBHOOK_SECRET=test_webhook_secret

# File Storage
STORAGE_DRIVER=local

# CDN
CDN_ENABLED=false

# Monitoring
LOGGING_ENABLED=true
LOG_LEVEL=debug
ERROR_TRACKING_ENABLED=false
PERFORMANCE_MONITORING_ENABLED=true
ALERTS_ENABLED=false
```

### Staging Environment (.env.staging)

```bash
# Application
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://staging.karyalay.com
APP_VERSION=1.2.0

# Database
DB_HOST=staging-db.karyalay.com
DB_PORT=3306
DB_NAME=karyalay_staging
DB_USER=karyalay_staging_user
DB_PASS=staging_secure_password
DB_PERSISTENT=true

# Email
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.your_sendgrid_api_key
MAIL_FROM_ADDRESS=noreply@staging.karyalay.com
MAIL_FROM_NAME="SellerPortal Staging"
ADMIN_EMAIL=admin@karyalay.com

# Payment Gateway (Test Mode)
RAZORPAY_KEY_ID=rzp_test_1234567890
RAZORPAY_KEY_SECRET=test_secret_key
RAZORPAY_WEBHOOK_SECRET=test_webhook_secret

# File Storage
STORAGE_DRIVER=s3
AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE
AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=karyalay-staging-uploads

# CDN
CDN_ENABLED=true
CDN_BASE_URL=https://cdn-staging.karyalay.com
CLOUDFRONT_DISTRIBUTION_ID=E1234567890ABC
CLOUDFRONT_DOMAIN=d111111abcdef8.cloudfront.net

# Monitoring
LOGGING_ENABLED=true
LOG_LEVEL=info
ERROR_TRACKING_ENABLED=true
SENTRY_DSN=https://examplePublicKey@o0.ingest.sentry.io/0
PERFORMANCE_MONITORING_ENABLED=true
ALERTS_ENABLED=true
ALERT_EMAIL=devops@karyalay.com
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXX
```

### Production Environment (.env.production)

```bash
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://karyalay.com
APP_VERSION=1.2.0

# Database
DB_HOST=prod-db.karyalay.com
DB_PORT=3306
DB_NAME=karyalay_production
DB_USER=karyalay_prod_user
DB_PASS=production_very_secure_password
DB_PERSISTENT=true

# Email
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.your_production_sendgrid_api_key
MAIL_FROM_ADDRESS=noreply@karyalay.com
MAIL_FROM_NAME="SellerPortal"
ADMIN_EMAIL=admin@karyalay.com

# Payment Gateway (Live Mode)
RAZORPAY_KEY_ID=rzp_live_1234567890
RAZORPAY_KEY_SECRET=live_secret_key
RAZORPAY_WEBHOOK_SECRET=live_webhook_secret

# File Storage
STORAGE_DRIVER=s3
AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE
AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=karyalay-production-uploads

# CDN
CDN_ENABLED=true
CDN_BASE_URL=https://cdn.karyalay.com
CLOUDFRONT_DISTRIBUTION_ID=E9876543210XYZ
CLOUDFRONT_DOMAIN=d222222abcdef8.cloudfront.net

# Monitoring
LOGGING_ENABLED=true
LOG_LEVEL=warning
ERROR_TRACKING_ENABLED=true
SENTRY_DSN=https://productionPublicKey@o0.ingest.sentry.io/0
PERFORMANCE_MONITORING_ENABLED=true
ALERTS_ENABLED=true
ALERT_EMAIL=alerts@karyalay.com
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/T11111111/B11111111/YYYYYYYYYYYY
PAGERDUTY_INTEGRATION_KEY=abc123def456ghi789
```

---

## Security Best Practices

### 1. Never Commit .env Files
- Add `.env` to `.gitignore`
- Use `.env.example` as a template
- Document all required variables

### 2. Use Strong Passwords
- Database passwords: 20+ characters, mixed case, numbers, symbols
- API keys: Use provider-generated keys
- Rotate credentials regularly

### 3. Separate Credentials by Environment
- Use different credentials for dev, staging, production
- Never use production credentials in development
- Use test mode for payment gateways in non-production

### 4. Secure Storage
- Store production credentials in secure vault (AWS Secrets Manager, HashiCorp Vault)
- Limit access to production credentials
- Use CI/CD secrets for deployment

### 5. Regular Audits
- Review environment variables quarterly
- Remove unused variables
- Update documentation when adding new variables

---

## Troubleshooting

### Application Can't Connect to Database

**Check:**
1. `DB_HOST`, `DB_PORT`, `DB_NAME` are correct
2. `DB_USER` has proper permissions
3. `DB_PASS` is correct
4. Database server is running
5. Firewall allows connection

### Emails Not Sending

**Check:**
1. `MAIL_HOST`, `MAIL_PORT` are correct
2. `MAIL_USERNAME`, `MAIL_PASSWORD` are valid
3. SMTP server allows connections
4. Firewall allows outbound SMTP
5. Check spam folder

### Payment Gateway Errors

**Check:**
1. Using correct keys for environment (test vs live)
2. `RAZORPAY_KEY_ID` and `RAZORPAY_KEY_SECRET` are valid
3. Webhook URL configured in Razorpay dashboard
4. `RAZORPAY_WEBHOOK_SECRET` matches dashboard

### CDN Not Working

**Check:**
1. `CDN_ENABLED=true`
2. `CDN_BASE_URL` is correct
3. CDN distribution is deployed
4. DNS records point to CDN
5. Cache invalidation if needed

---

## Additional Resources

- [PHP Environment Variables](https://www.php.net/manual/en/function.getenv.php)
- [Razorpay Documentation](https://razorpay.com/docs/)
- [AWS S3 Documentation](https://docs.aws.amazon.com/s3/)
- [Sentry Documentation](https://docs.sentry.io/)
- [SendGrid Documentation](https://docs.sendgrid.com/)

---

## Support

For questions about environment configuration:
- Email: devops@karyalay.com
- Documentation: [DEPLOYMENT.md](DEPLOYMENT.md)
- Setup Guide: [SETUP.md](SETUP.md)
