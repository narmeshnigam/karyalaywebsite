# Deployment Configuration

This directory contains deployment-related scripts and configuration files.

## Files

### backup-database.sh
Automated database backup script that creates compressed SQL dumps.

**Usage:**
```bash
./deployment/backup-database.sh
```

**Configuration:**
- `BACKUP_DIR`: Directory where backups are stored
- `DB_NAME`: Database name
- `DB_USER`: Database user
- `DB_PASS`: Database password
- `RETENTION_DAYS`: Number of days to keep backups (default: 30)

### crontab.example
Example cron job configuration for automated tasks.

**Installation:**
```bash
# Copy to system cron directory
sudo cp deployment/crontab.example /etc/cron.d/karyalay-portal

# Or add to user crontab
crontab -e
# Then paste the contents of crontab.example
```

## Automated Tasks

### Monitoring
- **Frequency**: Every 5 minutes
- **Purpose**: Check application health, SSL, disk space, memory
- **Script**: `bin/monitor.sh`

### Subscription Expiration
- **Frequency**: Daily at 2 AM
- **Purpose**: Check and update expired subscriptions
- **Script**: `bin/check-expirations.php`

### Database Backup
- **Frequency**: Daily at 3 AM
- **Purpose**: Create compressed database backup
- **Script**: `deployment/backup-database.sh`

### Cache Cleanup
- **Frequency**: Daily at 4 AM
- **Purpose**: Remove cache files older than 7 days
- **Command**: `find /var/www/production/storage/cache -type f -mtime +7 -delete`

### Log Cleanup
- **Frequency**: Weekly on Sunday at 5 AM
- **Purpose**: Remove log files older than 30 days
- **Command**: `find /var/www/production/storage/logs -type f -mtime +30 -delete`

### SSL Certificate Renewal
- **Frequency**: Daily at 6 AM
- **Purpose**: Automatically renew SSL certificates
- **Command**: `certbot renew --quiet --post-hook "systemctl reload apache2"`

### Performance Reports
- **Frequency**: Weekly on Monday at 1 AM
- **Purpose**: Generate performance metrics report
- **Script**: `bin/generate-performance-report.php`

### Backup Cleanup
- **Frequency**: Weekly on Sunday at 6 AM
- **Purpose**: Remove backups older than 30 days
- **Command**: `find /var/www/production/backups -type f -mtime +30 -delete`

### Weekly Summary
- **Frequency**: Weekly on Monday at 9 AM
- **Purpose**: Send weekly summary email to admins
- **Script**: `bin/send-weekly-summary.php`

## Manual Backup

### Create Database Backup
```bash
./deployment/backup-database.sh
```

### Restore Database Backup
```bash
# List available backups
ls -lh /var/www/production/backups/database/

# Restore specific backup
gunzip < /var/www/production/backups/database/db-backup-20240101-030000.sql.gz | mysql -u karyalay_prod -p karyalay_production
```

### Create Full Application Backup
```bash
cd /var/www/production
tar -czf backups/full-backup-$(date +%Y%m%d-%H%M%S).tar.gz \
    --exclude='storage/cache/*' \
    --exclude='vendor' \
    --exclude='node_modules' \
    .
```

## Monitoring Setup

### Install Monitoring Script
```bash
# Make script executable
chmod +x bin/monitor.sh

# Test monitoring
./bin/monitor.sh

# Add to crontab for automated monitoring
crontab -e
# Add: */5 * * * * /var/www/production/bin/monitor.sh >> /var/log/karyalay-monitor.log 2>&1
```

### Configure Alerts
Edit `bin/monitor.sh` and set:
- `APP_URL`: Your application URL
- `ALERT_EMAIL`: Email address for alerts

### View Monitoring Logs
```bash
tail -f /var/log/karyalay-monitor.log
```

## Backup Strategy

### Backup Types

1. **Database Backups**
   - Frequency: Daily
   - Retention: 30 days
   - Location: `/var/www/production/backups/database/`

2. **Application Backups**
   - Frequency: Before each deployment
   - Retention: 10 backups
   - Location: `/var/www/production/backups/`

3. **Off-site Backups** (Recommended)
   - Frequency: Weekly
   - Location: AWS S3, Google Cloud Storage, or similar
   - Retention: 90 days

### Backup Verification

Test backups regularly:
```bash
# Create test database
mysql -u root -p -e "CREATE DATABASE karyalay_test"

# Restore backup to test database
gunzip < backups/database/db-backup-latest.sql.gz | mysql -u root -p karyalay_test

# Verify data
mysql -u root -p karyalay_test -e "SHOW TABLES; SELECT COUNT(*) FROM users;"

# Drop test database
mysql -u root -p -e "DROP DATABASE karyalay_test"
```

## Disaster Recovery

### Recovery Time Objective (RTO)
Target: 1 hour

### Recovery Point Objective (RPO)
Target: 24 hours (daily backups)

### Recovery Procedure

1. **Provision new server** (if needed)
2. **Install dependencies**
   ```bash
   sudo apt update && sudo apt install -y php8.0 mysql-server apache2
   ```

3. **Restore application files**
   ```bash
   cd /var/www
   tar -xzf backup-location/full-backup-latest.tar.gz
   ```

4. **Restore database**
   ```bash
   gunzip < backup-location/db-backup-latest.sql.gz | mysql -u root -p karyalay_production
   ```

5. **Configure environment**
   ```bash
   cp .env.production .env
   # Update .env with correct credentials
   ```

6. **Set permissions**
   ```bash
   sudo chown -R www-data:www-data /var/www/production
   chmod -R 755 storage uploads
   ```

7. **Start services**
   ```bash
   sudo systemctl start apache2 mysql
   ```

8. **Verify application**
   ```bash
   curl https://karyalay.com/health.php
   ```

## Security Considerations

1. **Backup Encryption**: Consider encrypting backups containing sensitive data
2. **Access Control**: Restrict access to backup files
3. **Off-site Storage**: Store backups in a different location/region
4. **Backup Testing**: Regularly test backup restoration
5. **Monitoring**: Monitor backup job success/failure

## Troubleshooting

### Cron Jobs Not Running

```bash
# Check cron service status
sudo systemctl status cron

# Check cron logs
sudo tail -f /var/log/syslog | grep CRON

# Verify crontab
crontab -l
```

### Backup Script Fails

```bash
# Check disk space
df -h

# Check database credentials
mysql -u karyalay_prod -p -e "SELECT 1"

# Check permissions
ls -la /var/www/production/backups/
```

### Monitoring Alerts Not Sending

```bash
# Check mail command
which mail

# Install mail if needed
sudo apt install mailutils

# Test email
echo "Test" | mail -s "Test" admin@karyalay.com
```

## Additional Resources

- [Main Deployment Guide](../DEPLOYMENT.md)
- [Quick Reference](../DEPLOYMENT_QUICK_REFERENCE.md)
- [CI/CD Documentation](../.github/README.md)
