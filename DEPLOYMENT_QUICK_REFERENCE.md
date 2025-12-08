# Deployment Quick Reference

Quick commands and checklists for common deployment tasks.

## Quick Commands

### Local Development

```bash
# Start Docker environment
docker-compose up -d

# Stop Docker environment
docker-compose down

# View logs
docker-compose logs -f app

# Run migrations
docker exec karyalay-app php bin/migrate.php

# Clear cache
docker exec karyalay-app php bin/cache-clear.php

# Run tests
docker exec karyalay-app composer test
```

### Staging Deployment

```bash
# Deploy to staging (manual)
export STAGING_SSH_KEY="~/.ssh/staging_key"
export STAGING_HOST="staging.karyalay.com"
export STAGING_USER="deploy"
export STAGING_PATH="/var/www/staging"
./bin/deploy-staging.sh

# Deploy to staging (via Git)
git checkout staging
git merge develop
git push origin staging  # Triggers automatic deployment
```

### Production Deployment

```bash
# Deploy to production (manual)
export PRODUCTION_SSH_KEY="~/.ssh/production_key"
export PRODUCTION_HOST="karyalay.com"
export PRODUCTION_USER="deploy"
export PRODUCTION_PATH="/var/www/production"
./bin/deploy-production.sh

# Deploy to production (via Git)
git checkout main
git merge staging
git push origin main  # Triggers automatic deployment (requires approval)
```

### Rollback

```bash
# Rollback production
export PRODUCTION_SSH_KEY="~/.ssh/production_key"
export PRODUCTION_HOST="karyalay.com"
export PRODUCTION_USER="deploy"
export PRODUCTION_PATH="/var/www/production"
./bin/rollback-production.sh
```

### Health Checks

```bash
# Check application health
curl https://karyalay.com/health.php

# Check database connection
ssh deploy@karyalay.com 'cd /var/www/production && php bin/check-db.php'

# Check disk space
ssh deploy@karyalay.com 'df -h'

# Check memory
ssh deploy@karyalay.com 'free -h'
```

### Logs

```bash
# View application logs
ssh deploy@karyalay.com 'tail -f /var/www/production/storage/logs/app.log'

# View Apache error logs
ssh deploy@karyalay.com 'sudo tail -f /var/log/apache2/error.log'

# View Apache access logs
ssh deploy@karyalay.com 'sudo tail -f /var/log/apache2/access.log'

# View MySQL logs
ssh deploy@karyalay.com 'sudo tail -f /var/log/mysql/error.log'
```

## Pre-Deployment Checklist

### Before Staging Deployment

- [ ] All tests pass locally
- [ ] Code reviewed and approved
- [ ] Database migrations tested locally
- [ ] Environment variables updated in `.env.staging`
- [ ] Dependencies updated in `composer.json`
- [ ] CHANGELOG.md updated

### Before Production Deployment

- [ ] Tested thoroughly in staging
- [ ] All tests pass in CI/CD
- [ ] Security scan passed
- [ ] Database backup created
- [ ] Rollback plan prepared
- [ ] Team notified of deployment window
- [ ] Monitoring alerts configured
- [ ] Environment variables updated in `.env.production`
- [ ] CDN cache invalidation plan ready (if needed)
- [ ] Customer communication prepared (if downtime expected)

## Post-Deployment Checklist

### After Staging Deployment

- [ ] Application accessible at staging URL
- [ ] Login functionality works
- [ ] Database migrations applied successfully
- [ ] No errors in logs
- [ ] Key features tested manually

### After Production Deployment

- [ ] Application accessible at production URL
- [ ] SSL certificate valid
- [ ] Login functionality works
- [ ] Payment gateway working (test transaction)
- [ ] Email notifications working
- [ ] Database migrations applied successfully
- [ ] No errors in logs
- [ ] CDN serving static assets correctly
- [ ] Performance metrics normal
- [ ] Monitoring alerts working
- [ ] Team notified of successful deployment
- [ ] Deployment documented

## Emergency Procedures

### Application Down

1. Check health endpoint: `curl https://karyalay.com/health.php`
2. Check server status: `ssh deploy@karyalay.com 'sudo systemctl status apache2'`
3. Check logs for errors
4. If critical, rollback: `./bin/rollback-production.sh`
5. Notify team immediately

### Database Issues

1. Check database connection: `ssh deploy@karyalay.com 'cd /var/www/production && php bin/check-db.php'`
2. Check MySQL status: `ssh deploy@karyalay.com 'sudo systemctl status mysql'`
3. Review recent migrations
4. If critical, rollback database and application
5. Notify team immediately

### Payment Gateway Issues

1. Check payment gateway status page
2. Review payment logs
3. Test with small transaction
4. If critical, enable maintenance mode
5. Contact payment gateway support
6. Notify team and customers

### High Traffic / Performance Issues

1. Check server resources: CPU, memory, disk
2. Check database slow query log
3. Enable caching if not already enabled
4. Scale horizontally if possible
5. Contact hosting provider if needed

## Maintenance Mode

### Enable Maintenance Mode

```bash
ssh deploy@karyalay.com 'cd /var/www/production && touch maintenance.flag'
```

### Disable Maintenance Mode

```bash
ssh deploy@karyalay.com 'cd /var/www/production && rm maintenance.flag'
```

## Common Issues and Solutions

### Issue: Composer Dependencies Not Installing

```bash
# Clear Composer cache
composer clear-cache

# Remove vendor directory and reinstall
rm -rf vendor
composer install --no-dev --optimize-autoloader
```

### Issue: Permission Denied Errors

```bash
# Fix ownership
sudo chown -R www-data:www-data /var/www/production

# Fix permissions
find /var/www/production -type d -exec chmod 755 {} \;
find /var/www/production -type f -exec chmod 644 {} \;
chmod -R 755 storage uploads
```

### Issue: Database Migration Failed

```bash
# Check migration status
php bin/migrate.php --status

# Rollback last migration
php bin/migrate.php --rollback

# Re-run migrations
php bin/migrate.php
```

### Issue: Cache Not Clearing

```bash
# Clear all caches
php bin/cache-clear.php

# Manually clear file cache
rm -rf storage/cache/*

# Restart Apache
sudo systemctl restart apache2
```

## Useful SSH Commands

```bash
# Connect to staging
ssh deploy@staging.karyalay.com

# Connect to production
ssh deploy@karyalay.com

# Copy file to server
scp file.txt deploy@karyalay.com:/var/www/production/

# Copy file from server
scp deploy@karyalay.com:/var/www/production/file.txt ./

# Execute command on server
ssh deploy@karyalay.com 'command'

# Execute multiple commands
ssh deploy@karyalay.com 'cd /var/www/production && php bin/migrate.php && php bin/cache-clear.php'
```

## Monitoring URLs

- **Production**: https://karyalay.com
- **Staging**: https://staging.karyalay.com
- **Health Check**: https://karyalay.com/health.php
- **Admin Panel**: https://karyalay.com/admin
- **Customer Portal**: https://karyalay.com/app

## Contact Information

- **DevOps Lead**: devops@karyalay.com
- **System Administrator**: sysadmin@karyalay.com
- **On-Call Engineer**: +1-XXX-XXX-XXXX
- **Hosting Provider Support**: support@hostingprovider.com
- **Payment Gateway Support**: support@razorpay.com

## Additional Resources

- Full Deployment Guide: [DEPLOYMENT.md](DEPLOYMENT.md)
- CI/CD Documentation: [.github/README.md](.github/README.md)
- Database Documentation: [DATABASE.md](DATABASE.md)
- Security Audit: [SECURITY_AUDIT.md](SECURITY_AUDIT.md)
