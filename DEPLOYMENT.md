# Deployment Guide

This document provides comprehensive instructions for deploying the SellerPortal System to staging and production environments.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Environment Setup](#environment-setup)
3. [CI/CD Pipeline](#cicd-pipeline)
4. [Manual Deployment](#manual-deployment)
5. [Docker Deployment](#docker-deployment)
6. [Database Migrations](#database-migrations)
7. [Rollback Procedures](#rollback-procedures)
8. [Monitoring and Logging](#monitoring-and-logging)
9. [Troubleshooting](#troubleshooting)

## Prerequisites

### System Requirements

- **PHP**: 8.0 or higher
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Database**: MySQL 8.0+ or MariaDB 10.5+
- **Composer**: 2.0+
- **Git**: 2.30+
- **SSL Certificate**: Required for production

### Server Requirements

**Staging Server:**
- 2 CPU cores
- 4GB RAM
- 50GB SSD storage
- Ubuntu 20.04 LTS or higher

**Production Server:**
- 4 CPU cores
- 8GB RAM
- 100GB SSD storage
- Ubuntu 20.04 LTS or higher
- Load balancer (optional but recommended)

## Environment Setup

### 1. Server Preparation

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y php8.0 php8.0-cli php8.0-fpm php8.0-mysql \
    php8.0-mbstring php8.0-xml php8.0-bcmath php8.0-zip \
    php8.0-gd php8.0-curl apache2 mysql-server git unzip

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Configure Apache
sudo a2enmod rewrite headers expires ssl
sudo systemctl restart apache2
```

### 2. Database Setup

```bash
# Create database and user
mysql -u root -p << EOF
CREATE DATABASE karyalay_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'karyalay_staging'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON karyalay_staging.* TO 'karyalay_staging'@'localhost';
FLUSH PRIVILEGES;
EOF
```

### 3. Application Setup

```bash
# Clone repository
cd /var/www
git clone https://github.com/your-org/karyalay-portal.git staging
cd staging

# Install dependencies
composer install --no-dev --optimize-autoloader

# Configure environment
cp .env.staging .env
nano .env  # Update with actual credentials

# Run migrations
php bin/migrate.php

# Set permissions
sudo chown -R www-data:www-data storage uploads
sudo chmod -R 755 storage uploads
```

### 4. SSL Certificate Setup

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache

# Obtain SSL certificate
sudo certbot --apache -d staging.karyalay.com

# Auto-renewal is configured automatically
```

## CI/CD Pipeline

### GitHub Actions Setup

The project includes a GitHub Actions workflow (`.github/workflows/ci-cd.yml`) that automatically:

1. Runs tests on every push and pull request
2. Performs security scans
3. Deploys to staging when pushing to `staging` branch
4. Deploys to production when pushing to `main` branch

### Required GitHub Secrets

Configure these secrets in your GitHub repository settings:

**Staging:**
- `STAGING_SSH_KEY`: Private SSH key for staging server
- `STAGING_HOST`: Staging server hostname
- `STAGING_USER`: SSH username
- `STAGING_PATH`: Deployment path on server

**Production:**
- `PRODUCTION_SSH_KEY`: Private SSH key for production server
- `PRODUCTION_HOST`: Production server hostname
- `PRODUCTION_USER`: SSH username
- `PRODUCTION_PATH`: Deployment path on server

### Triggering Deployments

**Staging Deployment:**
```bash
git checkout staging
git merge develop
git push origin staging
```

**Production Deployment:**
```bash
git checkout main
git merge staging
git push origin main
```

## Manual Deployment

### Staging Deployment

```bash
# Set environment variables
export STAGING_HOST="staging.karyalay.com"
export STAGING_USER="deploy"
export STAGING_PATH="/var/www/staging"
export STAGING_SSH_KEY="~/.ssh/staging_key"

# Run deployment script
./bin/deploy-staging.sh
```

### Production Deployment

```bash
# Set environment variables
export PRODUCTION_HOST="karyalay.com"
export PRODUCTION_USER="deploy"
export PRODUCTION_PATH="/var/www/production"
export PRODUCTION_SSH_KEY="~/.ssh/production_key"

# Run deployment script
./bin/deploy-production.sh
```

## Docker Deployment

### Development Environment

```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f

# Stop services
docker-compose down
```

### Production Environment

```bash
# Build production image
docker-compose -f docker-compose.production.yml build

# Start production services
docker-compose -f docker-compose.production.yml up -d

# View logs
docker-compose -f docker-compose.production.yml logs -f
```

### Docker Commands

```bash
# Access application container
docker exec -it karyalay-app bash

# Run migrations
docker exec karyalay-app php bin/migrate.php

# Clear cache
docker exec karyalay-app php bin/cache-clear.php

# View database
docker exec -it karyalay-db mysql -u karyalay -p
```

## Database Migrations

### Running Migrations

```bash
# Run all pending migrations
php bin/migrate.php

# Check migration status
php bin/migrate.php --status

# Rollback last migration
php bin/migrate.php --rollback
```

### Creating New Migrations

```bash
# Create new migration file
php bin/create-migration.php "add_new_column_to_users"

# Edit the generated file in database/migrations/
# Then run migrations
php bin/migrate.php
```

### Migration Best Practices

1. Always test migrations in staging first
2. Create backup before running migrations in production
3. Migrations should be reversible when possible
4. Never modify existing migration files after deployment
5. Use transactions for data migrations

## Rollback Procedures

### Automatic Rollback

The deployment scripts create automatic backups. To rollback:

```bash
# Rollback production to previous version
./bin/rollback-production.sh
```

### Manual Rollback

```bash
# SSH to server
ssh deploy@karyalay.com

# Navigate to application directory
cd /var/www/production

# List available backups
ls -lh backups/

# Restore specific backup
tar -xzf backups/backup-20240101-120000.tar.gz

# Run migrations if needed
php bin/migrate.php --rollback

# Clear cache
php bin/cache-clear.php

# Restart services
sudo systemctl reload apache2
```

## Monitoring and Logging

### Application Logs

```bash
# View application logs
tail -f storage/logs/app.log

# View error logs
tail -f storage/logs/error.log

# View Apache logs
sudo tail -f /var/log/apache2/error.log
sudo tail -f /var/log/apache2/access.log
```

### Health Checks

```bash
# Check application health
curl https://karyalay.com/health

# Check database connection
php bin/check-db.php

# Check disk space
df -h

# Check memory usage
free -h
```

### Performance Monitoring

- Set up New Relic or similar APM tool
- Configure Sentry for error tracking
- Use CloudWatch or similar for infrastructure monitoring
- Set up uptime monitoring (Pingdom, UptimeRobot)

## Troubleshooting

### Common Issues

**Issue: 500 Internal Server Error**
```bash
# Check PHP error logs
sudo tail -f /var/log/apache2/error.log

# Check file permissions
sudo chown -R www-data:www-data /var/www/production
sudo chmod -R 755 storage uploads

# Clear cache
php bin/cache-clear.php
```

**Issue: Database Connection Failed**
```bash
# Check database status
sudo systemctl status mysql

# Test connection
mysql -u karyalay_prod -p -h localhost karyalay_production

# Check .env configuration
cat .env | grep DB_
```

**Issue: Composer Dependencies**
```bash
# Clear Composer cache
composer clear-cache

# Reinstall dependencies
rm -rf vendor
composer install --no-dev --optimize-autoloader
```

**Issue: Permission Denied**
```bash
# Fix ownership
sudo chown -R www-data:www-data /var/www/production

# Fix permissions
find /var/www/production -type d -exec chmod 755 {} \;
find /var/www/production -type f -exec chmod 644 {} \;
chmod -R 755 storage uploads
```

### Emergency Contacts

- **DevOps Lead**: devops@karyalay.com
- **System Administrator**: sysadmin@karyalay.com
- **On-Call Engineer**: +1-XXX-XXX-XXXX

### Rollback Decision Tree

1. **Minor issues (UI bugs, non-critical features)**: Fix forward with hotfix
2. **Database issues**: Rollback immediately and investigate
3. **Payment gateway issues**: Rollback immediately
4. **Performance degradation**: Monitor for 15 minutes, rollback if not improving
5. **Security issues**: Rollback immediately and patch

## Post-Deployment Checklist

- [ ] Verify application is accessible
- [ ] Check all critical pages load correctly
- [ ] Test user registration and login
- [ ] Test payment flow (in test mode)
- [ ] Verify email notifications are working
- [ ] Check database migrations completed successfully
- [ ] Verify static assets are loading from CDN
- [ ] Check SSL certificate is valid
- [ ] Review error logs for any issues
- [ ] Update deployment documentation if needed
- [ ] Notify team of successful deployment

## Additional Resources

### Internal Documentation
- [Environment Variables Reference](ENVIRONMENT_VARIABLES.md) - Complete guide to all environment variables
- [Database Migrations Guide](DATABASE_MIGRATIONS.md) - Comprehensive migration documentation
- [Database Setup Guide](DATABASE.md) - Database configuration and setup
- [Deployment Quick Reference](DEPLOYMENT_QUICK_REFERENCE.md) - Quick commands and checklists
- [Deployment Setup Summary](DEPLOYMENT_SETUP_SUMMARY.md) - Overview of deployment infrastructure
- [CI/CD Documentation](.github/README.md) - GitHub Actions workflow details
- [Monitoring and Logging](MONITORING_AND_LOGGING.md) - Monitoring setup and configuration
- [Performance Optimization](PERFORMANCE_OPTIMIZATION.md) - Performance tuning guide
- [Security Audit](SECURITY_AUDIT.md) - Security best practices and audit results

### External Resources
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Docker Documentation](https://docs.docker.com/)
- [PHP Deployment Best Practices](https://www.php.net/manual/en/install.php)
- [MySQL Performance Tuning](https://dev.mysql.com/doc/refman/8.0/en/optimization.html)
- [Razorpay Integration Guide](https://razorpay.com/docs/payments/)
- [AWS S3 Documentation](https://docs.aws.amazon.com/s3/)
- [Sentry Error Tracking](https://docs.sentry.io/)

## Support

For deployment issues or questions, contact the DevOps team or create an issue in the repository.

**Contact Information:**
- DevOps Lead: devops@karyalay.com
- System Administrator: sysadmin@karyalay.com
- On-Call Engineer: +1-XXX-XXX-XXXX
