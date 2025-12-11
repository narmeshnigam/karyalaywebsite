# SellerPortal System

A comprehensive web application with public marketing website, customer portal, and admin panel.

## Requirements

- PHP 8.0 or higher
- MySQL/MariaDB
- Composer

## Installation

1. Clone the repository
2. Copy `.env.example` to `.env` and configure your environment variables
3. Install dependencies:
   ```bash
   php composer.phar install
   ```
4. Set up the database:
   ```bash
   # Create database
   mysql -u root -p -e "CREATE DATABASE karyalay_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   
   # Run migrations
   php bin/migrate.php
   
   # Seed sample data (optional, for development)
   php bin/seed.php
   ```

For detailed database setup instructions, see [DATABASE.md](DATABASE.md)

## Directory Structure

```
├── public/          # Web root - entry point
├── includes/        # Shared includes and utilities
├── classes/         # PHP classes (PSR-4 autoloaded)
├── templates/       # HTML templates
├── assets/          # Static assets (CSS, JS, images)
├── tests/           # Test files
│   ├── Unit/        # Unit tests
│   └── Property/    # Property-based tests
├── config/          # Configuration files
└── vendor/          # Composer dependencies
```

## Development

### Running Tests

```bash
# Run all tests
php composer.phar test

# Run unit tests only
php composer.phar test:unit

# Run property-based tests only
php composer.phar test:property
```

### Code Quality

```bash
# Check code style
php composer.phar cs:check

# Fix code style issues
php composer.phar cs:fix
```

### Local Development Server

```bash
php -S localhost:8000 -t public
```

Then visit http://localhost:8000 in your browser.

### Scheduled Tasks

The system includes scheduled tasks that should be run periodically:

#### Subscription Expiration Job

Checks for and expires subscriptions that have passed their end date.

```bash
# Run manually
php bin/expire-subscriptions.php

# Set up as a cron job (run daily at 1:00 AM)
0 1 * * * /usr/bin/php /path/to/project/bin/expire-subscriptions.php >> /path/to/logs/expiration.log 2>&1
```

## Testing Strategy

This project uses a dual testing approach:

- **Unit Tests**: Verify specific examples, edge cases, and error conditions
- **Property-Based Tests**: Verify universal properties across all inputs using Eris

All property-based tests run a minimum of 100 iterations and reference the design document properties.

## Deployment

### Quick Start

For local development with Docker:
```bash
docker-compose up -d
```

### Deployment Documentation

Complete deployment documentation is available:

#### Core Guides
- **[DEPLOYMENT_PROCESS.md](DEPLOYMENT_PROCESS.md)** - Complete deployment process overview
- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Comprehensive deployment guide with all procedures
- **[DEPLOYMENT_QUICK_REFERENCE.md](DEPLOYMENT_QUICK_REFERENCE.md)** - Quick commands and checklists
- **[DEPLOYMENT_SETUP_SUMMARY.md](DEPLOYMENT_SETUP_SUMMARY.md)** - Infrastructure overview

#### Configuration
- **[ENVIRONMENT_VARIABLES.md](ENVIRONMENT_VARIABLES.md)** - Complete environment variable reference
- **[DATABASE_MIGRATIONS.md](DATABASE_MIGRATIONS.md)** - Database migration guide
- **[DATABASE.md](DATABASE.md)** - Database setup and configuration

#### CI/CD and Monitoring
- **[.github/README.md](.github/README.md)** - GitHub Actions CI/CD pipeline
- **[MONITORING_AND_LOGGING.md](MONITORING_AND_LOGGING.md)** - Monitoring and logging setup
- **[deployment/README.md](deployment/README.md)** - Backup and cron job configuration

### Deployment Environments

- **Development**: Local Docker environment
- **Staging**: https://staging.karyalay.com
- **Production**: https://karyalay.com

### Deployment Methods

1. **Automated (Recommended)**: Push to `staging` or `main` branch triggers CI/CD
2. **Manual**: Use deployment scripts in `bin/` directory
3. **Docker**: Use `docker-compose.production.yml` for containerized deployment

### Quick Deployment Commands

```bash
# Deploy to staging (automated)
git checkout staging
git merge develop
git push origin staging

# Deploy to production (automated, requires approval)
git checkout main
git merge staging
git push origin main

# Manual deployment to production
export PRODUCTION_SSH_KEY="~/.ssh/production_key"
export PRODUCTION_HOST="karyalay.com"
export PRODUCTION_USER="deploy"
export PRODUCTION_PATH="/var/www/production"
./bin/deploy-production.sh

# Rollback production
./bin/rollback-production.sh
```

### Health Check

Check application health:
```bash
curl https://karyalay.com/health.php
```

Expected response:
```json
{
  "status": "healthy",
  "database": "connected",
  "storage": "writable",
  "version": "1.0.0"
}
```

## Monitoring

The system includes comprehensive monitoring and logging:
- **Structured Logging**: JSON-formatted logs with context
- **Error Tracking**: Sentry integration for error monitoring
- **Performance Monitoring**: Track slow queries and requests
- **Health Checks**: Automated system health monitoring every 5 minutes
- **Alerting**: Multi-channel alerts (Email, Slack, PagerDuty)
- **Admin Dashboard**: View logs, errors, and metrics at `/admin/monitoring.php`

See [MONITORING_AND_LOGGING.md](MONITORING_AND_LOGGING.md) for detailed documentation.

## Backup and Recovery

- **Database backups**: Daily at 3 AM
- **Application backups**: Before each deployment
- **Retention**: 30 days for database, 10 versions for application

See [deployment/README.md](deployment/README.md) for backup procedures.

## License

Proprietary
