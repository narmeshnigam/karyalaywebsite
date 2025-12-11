# Deployment Process Documentation

This document provides a comprehensive overview of the deployment process for the SellerPortal System, including step-by-step procedures, checklists, and best practices.

## Table of Contents

1. [Deployment Overview](#deployment-overview)
2. [Environment Setup](#environment-setup)
3. [Deployment Workflows](#deployment-workflows)
4. [Database Migration Process](#database-migration-process)
5. [Rollback Procedures](#rollback-procedures)
6. [Monitoring and Verification](#monitoring-and-verification)
7. [Emergency Procedures](#emergency-procedures)
8. [Documentation Index](#documentation-index)

---

## Deployment Overview

### Deployment Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Development Workflow                      │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  Feature Branch → Pull Request → Code Review → Merge        │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    Develop Branch                            │
│  • Automated tests run                                       │
│  • Code quality checks                                       │
│  • Security scans                                            │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    Staging Branch                            │
│  • Automatic deployment to staging                           │
│  • Integration tests                                         │
│  • Manual QA testing                                         │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    Main Branch                               │
│  • Deployment approval required                              │
│  • Automatic deployment to production                        │
│  • Post-deployment verification                              │
└─────────────────────────────────────────────────────────────┘
```

### Deployment Methods

1. **Automated Deployment (CI/CD)**
   - Triggered by Git push to staging/main branches
   - Runs tests and security scans
   - Deploys automatically on success
   - Recommended for most deployments

2. **Manual Deployment**
   - Uses deployment scripts
   - Provides more control
   - Required for emergency fixes
   - Used when CI/CD is unavailable

3. **Docker Deployment**
   - Containerized application
   - Consistent across environments
   - Easy rollback
   - Recommended for new deployments

---

## Environment Setup

### Prerequisites

Before deploying, ensure you have:

1. **Access Credentials**
   - SSH keys for staging and production servers
   - GitHub repository access
   - Database credentials
   - Payment gateway credentials
   - Email service credentials

2. **Required Tools**
   - Git 2.30+
   - PHP 8.0+
   - Composer 2.0+
   - MySQL client
   - SSH client
   - Docker (for containerized deployment)

3. **Configuration Files**
   - `.env.staging` configured
   - `.env.production` configured
   - GitHub secrets configured
   - SSL certificates installed

### Environment Configuration

#### 1. Configure Environment Variables

See [ENVIRONMENT_VARIABLES.md](ENVIRONMENT_VARIABLES.md) for complete reference.

**Key Variables to Configure:**

```bash
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://karyalay.com

# Database
DB_HOST=prod-db.karyalay.com
DB_NAME=karyalay_production
DB_USER=karyalay_prod_user
DB_PASS=secure_password

# Email
MAIL_HOST=smtp.sendgrid.net
MAIL_USERNAME=apikey
MAIL_PASSWORD=your_api_key

# Payment Gateway
RAZORPAY_KEY_ID=rzp_live_xxxxx
RAZORPAY_KEY_SECRET=live_secret
RAZORPAY_WEBHOOK_SECRET=webhook_secret

# Monitoring
ERROR_TRACKING_ENABLED=true
SENTRY_DSN=https://xxx@sentry.io/xxx
ALERTS_ENABLED=true
ALERT_EMAIL=alerts@karyalay.com
```

#### 2. Configure GitHub Secrets

In GitHub repository settings → Secrets and variables → Actions:

**Staging:**
- `STAGING_SSH_KEY`
- `STAGING_HOST`
- `STAGING_USER`
- `STAGING_PATH`

**Production:**
- `PRODUCTION_SSH_KEY`
- `PRODUCTION_HOST`
- `PRODUCTION_USER`
- `PRODUCTION_PATH`

#### 3. Server Preparation

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install dependencies
sudo apt install -y php8.0 php8.0-cli php8.0-fpm php8.0-mysql \
    php8.0-mbstring php8.0-xml php8.0-bcmath php8.0-zip \
    php8.0-gd php8.0-curl apache2 mysql-server git unzip

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Configure web server
sudo a2enmod rewrite headers expires ssl
sudo systemctl restart apache2

# Set up SSL
sudo certbot --apache -d karyalay.com
```

---

## Deployment Workflows

### Workflow 1: Automated Deployment to Staging

**Trigger:** Push to `staging` branch

**Process:**
1. Developer merges feature to `develop` branch
2. Developer merges `develop` to `staging` branch
3. Push triggers GitHub Actions workflow
4. Workflow runs tests and security scans
5. On success, deploys to staging server
6. Sends notification to team

**Commands:**
```bash
# Merge to staging
git checkout staging
git merge develop
git push origin staging

# Monitor deployment
# Check GitHub Actions tab in repository
```

**Verification:**
```bash
# Check staging site
curl https://staging.karyalay.com/health.php

# Check deployment logs
ssh deploy@staging.karyalay.com 'tail -f /var/www/staging/storage/logs/app.log'
```

### Workflow 2: Automated Deployment to Production

**Trigger:** Push to `main` branch (requires approval)

**Process:**
1. Staging thoroughly tested
2. Create pull request from `staging` to `main`
3. Code review and approval
4. Merge to `main` branch
5. Push triggers GitHub Actions workflow
6. Workflow requires manual approval
7. On approval, deploys to production
8. Runs post-deployment verification
9. Sends notification to team

**Commands:**
```bash
# Create PR from staging to main
# (Done via GitHub UI)

# After approval and merge
git checkout main
git pull origin main

# Monitor deployment
# Check GitHub Actions tab in repository
```

**Verification:**
```bash
# Check production site
curl https://karyalay.com/health.php

# Check deployment logs
ssh deploy@karyalay.com 'tail -f /var/www/production/storage/logs/app.log'

# Run smoke tests
./bin/smoke-tests.sh production
```

### Workflow 3: Manual Deployment to Staging

**When to Use:**
- CI/CD is unavailable
- Testing deployment scripts
- Emergency fixes

**Process:**

```bash
# 1. Set environment variables
export STAGING_SSH_KEY="~/.ssh/staging_key"
export STAGING_HOST="staging.karyalay.com"
export STAGING_USER="deploy"
export STAGING_PATH="/var/www/staging"

# 2. Run deployment script
./bin/deploy-staging.sh

# 3. Verify deployment
curl https://staging.karyalay.com/health.php
```

### Workflow 4: Manual Deployment to Production

**When to Use:**
- CI/CD is unavailable
- Emergency hotfixes
- Scheduled maintenance deployments

**Process:**

```bash
# 1. Create backup
ssh deploy@karyalay.com 'cd /var/www/production && ./deployment/backup-database.sh'

# 2. Set environment variables
export PRODUCTION_SSH_KEY="~/.ssh/production_key"
export PRODUCTION_HOST="karyalay.com"
export PRODUCTION_USER="deploy"
export PRODUCTION_PATH="/var/www/production"

# 3. Enable maintenance mode (if needed)
ssh deploy@karyalay.com 'touch /var/www/production/maintenance.flag'

# 4. Run deployment script
./bin/deploy-production.sh

# 5. Disable maintenance mode
ssh deploy@karyalay.com 'rm /var/www/production/maintenance.flag'

# 6. Verify deployment
curl https://karyalay.com/health.php

# 7. Monitor for issues
ssh deploy@karyalay.com 'tail -f /var/www/production/storage/logs/app.log'
```

### Workflow 5: Docker Deployment

**Development:**
```bash
# Start services
docker-compose up -d

# View logs
docker-compose logs -f app

# Run migrations
docker exec karyalay-app php bin/migrate.php

# Stop services
docker-compose down
```

**Production:**
```bash
# Build production image
docker-compose -f docker-compose.production.yml build

# Start services
docker-compose -f docker-compose.production.yml up -d

# Run migrations
docker exec karyalay-app php bin/migrate.php

# View logs
docker-compose -f docker-compose.production.yml logs -f
```

---

## Database Migration Process

See [DATABASE_MIGRATIONS.md](DATABASE_MIGRATIONS.md) for comprehensive guide.

### Pre-Migration Checklist

- [ ] Migration tested in development
- [ ] Migration tested in staging
- [ ] Database backup created
- [ ] Rollback plan prepared
- [ ] Downtime window scheduled (if needed)
- [ ] Team notified

### Migration Steps

#### 1. Create Backup

```bash
# SSH to server
ssh deploy@karyalay.com

# Create backup
cd /var/www/production
./deployment/backup-database.sh

# Verify backup
ls -lh backups/database/
```

#### 2. Check Migration Status

```bash
# Check pending migrations
php bin/migrate.php --status
```

#### 3. Run Migrations

```bash
# Run all pending migrations
php bin/migrate.php

# Verify success
echo $?  # Should output 0
```

#### 4. Verify Database Changes

```bash
# Check new tables/columns
mysql -u karyalay_prod -p karyalay_production -e "DESCRIBE new_table;"

# Run application health check
curl https://karyalay.com/health.php
```

### Migration Best Practices

1. **Always backup before migrations**
2. **Test in staging first**
3. **Run during low-traffic periods**
4. **Monitor application after migration**
5. **Keep rollback plan ready**
6. **Document any manual steps**

---

## Rollback Procedures

### When to Rollback

Rollback immediately if:
- Critical functionality is broken
- Database errors occur
- Payment processing fails
- Security vulnerability introduced
- Performance severely degraded

### Rollback Decision Matrix

| Issue Severity | Action | Timeline |
|----------------|--------|----------|
| Critical (site down, payments failing) | Rollback immediately | < 5 minutes |
| High (major features broken) | Rollback if no quick fix | < 15 minutes |
| Medium (minor features broken) | Fix forward or rollback | < 30 minutes |
| Low (UI issues, non-critical bugs) | Fix forward | Next deployment |

### Automatic Rollback

```bash
# Rollback production to previous version
./bin/rollback-production.sh
```

This script:
1. Stops application
2. Restores previous code version
3. Restores database backup (if needed)
4. Clears cache
5. Restarts application
6. Verifies rollback

### Manual Rollback

#### 1. Rollback Application Code

```bash
# SSH to server
ssh deploy@karyalay.com

# Navigate to application
cd /var/www/production

# List available backups
ls -lh backups/

# Restore specific backup
tar -xzf backups/backup-20240115-120000.tar.gz

# Clear cache
php bin/cache-clear.php

# Restart web server
sudo systemctl reload apache2
```

#### 2. Rollback Database

```bash
# List database backups
ls -lh backups/database/

# Restore database
gunzip < backups/database/db-backup-20240115-120000.sql.gz | \
    mysql -u karyalay_prod -p karyalay_production

# Verify restoration
mysql -u karyalay_prod -p karyalay_production -e "SELECT COUNT(*) FROM users;"
```

#### 3. Verify Rollback

```bash
# Check application health
curl https://karyalay.com/health.php

# Check application logs
tail -f storage/logs/app.log

# Test critical functionality
# - User login
# - Payment processing
# - Subscription management
```

---

## Monitoring and Verification

### Post-Deployment Verification

#### Automated Checks

```bash
# Run smoke tests
./bin/smoke-tests.sh production

# Check health endpoint
curl https://karyalay.com/health.php

# Expected response:
# {
#   "status": "healthy",
#   "database": "connected",
#   "storage": "writable",
#   "version": "1.2.0"
# }
```

#### Manual Checks

1. **Homepage Loads**
   - Visit https://karyalay.com
   - Verify no errors
   - Check page loads in < 3 seconds

2. **User Authentication**
   - Test login with valid credentials
   - Test logout
   - Verify session management

3. **Payment Flow**
   - Test plan selection
   - Test checkout process
   - Verify payment gateway integration

4. **Admin Panel**
   - Login to admin panel
   - Check dashboard loads
   - Verify data displays correctly

5. **Customer Portal**
   - Login as customer
   - Check subscription details
   - Verify port information

### Monitoring Dashboards

1. **Application Health**
   - URL: https://karyalay.com/admin/monitoring
   - Metrics: Response time, error rate, uptime

2. **Error Tracking (Sentry)**
   - URL: https://sentry.io/organizations/karyalay/
   - Monitor: New errors, error trends

3. **Server Monitoring**
   - CPU usage
   - Memory usage
   - Disk space
   - Network traffic

### Log Monitoring

```bash
# Application logs
ssh deploy@karyalay.com 'tail -f /var/www/production/storage/logs/app.log'

# Error logs
ssh deploy@karyalay.com 'tail -f /var/www/production/storage/logs/error.log'

# Apache logs
ssh deploy@karyalay.com 'sudo tail -f /var/log/apache2/error.log'

# MySQL logs
ssh deploy@karyalay.com 'sudo tail -f /var/log/mysql/error.log'
```

---

## Emergency Procedures

### Scenario 1: Application Down

**Symptoms:**
- Site returns 500 error
- Health check fails
- Users cannot access site

**Response:**
```bash
# 1. Check server status
ssh deploy@karyalay.com 'sudo systemctl status apache2'

# 2. Check application logs
ssh deploy@karyalay.com 'tail -100 /var/www/production/storage/logs/error.log'

# 3. If recent deployment, rollback
./bin/rollback-production.sh

# 4. If not deployment-related, restart services
ssh deploy@karyalay.com 'sudo systemctl restart apache2 mysql'

# 5. Verify recovery
curl https://karyalay.com/health.php

# 6. Notify team
# Send alert to team@karyalay.com
```

### Scenario 2: Database Connection Failed

**Symptoms:**
- Database connection errors
- Queries timing out
- Health check shows database disconnected

**Response:**
```bash
# 1. Check MySQL status
ssh deploy@karyalay.com 'sudo systemctl status mysql'

# 2. Check database connections
ssh deploy@karyalay.com 'mysql -u karyalay_prod -p -e "SHOW PROCESSLIST;"'

# 3. Check disk space
ssh deploy@karyalay.com 'df -h'

# 4. Restart MySQL if needed
ssh deploy@karyalay.com 'sudo systemctl restart mysql'

# 5. Verify recovery
ssh deploy@karyalay.com 'cd /var/www/production && php bin/check-db.php'
```

### Scenario 3: Payment Gateway Issues

**Symptoms:**
- Payment processing fails
- Webhook errors
- Orders stuck in pending

**Response:**
```bash
# 1. Check payment gateway status
# Visit Razorpay status page

# 2. Check webhook configuration
# Verify webhook URL in Razorpay dashboard

# 3. Check environment variables
ssh deploy@karyalay.com 'cd /var/www/production && grep RAZORPAY .env'

# 4. Check payment logs
ssh deploy@karyalay.com 'tail -100 /var/www/production/storage/logs/payment.log'

# 5. If critical, enable maintenance mode
ssh deploy@karyalay.com 'touch /var/www/production/maintenance.flag'

# 6. Contact payment gateway support
# Email: support@razorpay.com
```

### Scenario 4: High Traffic / Performance Issues

**Symptoms:**
- Slow page loads
- Timeouts
- High server load

**Response:**
```bash
# 1. Check server resources
ssh deploy@karyalay.com 'top'
ssh deploy@karyalay.com 'free -h'
ssh deploy@karyalay.com 'df -h'

# 2. Check database performance
ssh deploy@karyalay.com 'mysql -u karyalay_prod -p -e "SHOW PROCESSLIST;"'

# 3. Enable caching
ssh deploy@karyalay.com 'cd /var/www/production && php bin/enable-cache.php'

# 4. Clear old cache
ssh deploy@karyalay.com 'cd /var/www/production && php bin/cache-clear.php'

# 5. Scale horizontally if possible
# Add more application servers behind load balancer

# 6. Contact hosting provider
# Request resource upgrade if needed
```

---

## Documentation Index

### Core Documentation

1. **[DEPLOYMENT.md](DEPLOYMENT.md)**
   - Comprehensive deployment guide
   - Server setup instructions
   - CI/CD configuration
   - Troubleshooting

2. **[ENVIRONMENT_VARIABLES.md](ENVIRONMENT_VARIABLES.md)**
   - Complete environment variable reference
   - Configuration examples
   - Security best practices

3. **[DATABASE_MIGRATIONS.md](DATABASE_MIGRATIONS.md)**
   - Migration system overview
   - Creating new migrations
   - Rollback procedures
   - Best practices

4. **[DATABASE.md](DATABASE.md)**
   - Database setup guide
   - Schema documentation
   - Connection configuration
   - Seeding data

### Quick References

5. **[DEPLOYMENT_QUICK_REFERENCE.md](DEPLOYMENT_QUICK_REFERENCE.md)**
   - Quick commands
   - Checklists
   - Common issues

6. **[DEPLOYMENT_SETUP_SUMMARY.md](DEPLOYMENT_SETUP_SUMMARY.md)**
   - Infrastructure overview
   - What was implemented
   - Next steps

### Additional Documentation

7. **[MONITORING_AND_LOGGING.md](MONITORING_AND_LOGGING.md)**
   - Monitoring setup
   - Log management
   - Alert configuration

8. **[PERFORMANCE_OPTIMIZATION.md](PERFORMANCE_OPTIMIZATION.md)**
   - Performance tuning
   - Caching strategies
   - Database optimization

9. **[SECURITY_AUDIT.md](SECURITY_AUDIT.md)**
   - Security best practices
   - Audit results
   - Recommendations

10. **[.github/README.md](.github/README.md)**
    - CI/CD workflow details
    - GitHub Actions configuration

---

## Support and Contacts

### Team Contacts

- **DevOps Lead**: devops@karyalay.com
- **System Administrator**: sysadmin@karyalay.com
- **On-Call Engineer**: +1-XXX-XXX-XXXX
- **Development Team**: dev@karyalay.com

### External Support

- **Hosting Provider**: support@hostingprovider.com
- **Payment Gateway**: support@razorpay.com
- **Email Service**: support@sendgrid.com
- **Error Tracking**: support@sentry.io

### Emergency Escalation

1. **Level 1**: On-call engineer (immediate response)
2. **Level 2**: DevOps lead (15 minutes)
3. **Level 3**: CTO (30 minutes)

---

## Conclusion

This deployment process documentation provides a comprehensive guide for deploying the SellerPortal System. Always follow the checklists, verify each step, and maintain clear communication with the team during deployments.

For questions or issues not covered in this documentation, contact the DevOps team at devops@karyalay.com.

**Remember:**
- Test thoroughly before production
- Always create backups
- Monitor after deployment
- Document any issues or changes
- Keep team informed
