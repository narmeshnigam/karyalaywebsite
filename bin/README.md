# Bin Scripts

This directory contains utility scripts for managing and monitoring the SellerPortal System.

## Available Scripts

### monitor.sh

Automated health monitoring script that checks system health and sends alerts.

**Usage:**
```bash
./bin/monitor.sh
```

**Checks:**
- HTTP response (200 OK)
- Health endpoint status
- SSL certificate expiration
- Disk space usage
- Memory usage

**Configuration:**
Set environment variables before running:
```bash
export APP_URL="https://karyalay.com"
export ALERT_EMAIL="admin@karyalay.com"
./bin/monitor.sh
```

**Automated Monitoring:**
Add to crontab for regular checks:
```bash
# Check every 5 minutes
*/5 * * * * /path/to/bin/monitor.sh >> /var/log/karyalay-monitor.log 2>&1
```

### check-db.php

Database connectivity and health check script.

**Usage:**
```bash
php bin/check-db.php
```

### cache-clear.php

Clear application cache.

**Usage:**
```bash
php bin/cache-clear.php
```

### deploy-staging.sh

Deploy to staging environment.

**Usage:**
```bash
./bin/deploy-staging.sh
```

### deploy-production.sh

Deploy to production environment.

**Usage:**
```bash
./bin/deploy-production.sh
```

### rollback-production.sh

Rollback production deployment.

**Usage:**
```bash
./bin/rollback-production.sh
```

## Monitoring Setup

### 1. Make Scripts Executable

```bash
chmod +x bin/*.sh
```

### 2. Configure Environment

Create a monitoring configuration file:

```bash
# /etc/karyalay/monitor.conf
APP_URL="https://karyalay.com"
ALERT_EMAIL="admin@karyalay.com,ops@karyalay.com"
```

### 3. Set Up Cron Jobs

```bash
# Edit crontab
crontab -e

# Add monitoring jobs
*/5 * * * * /path/to/bin/monitor.sh
0 2 * * * /path/to/bin/cache-clear.php
```

### 4. Configure Alerts

The monitoring script can send alerts via:
- Email (requires `mail` command)
- Slack (set `SLACK_WEBHOOK_URL`)
- PagerDuty (set `PAGERDUTY_INTEGRATION_KEY`)

### 5. Log Files

Monitoring logs are written to:
- `/var/log/karyalay-monitor.log` (or custom location)
- `storage/logs/` (application logs)

## Troubleshooting

### Script Permission Denied

```bash
chmod +x bin/monitor.sh
```

### Mail Command Not Found

Install mail utility:
```bash
# Ubuntu/Debian
sudo apt-get install mailutils

# CentOS/RHEL
sudo yum install mailx
```

### SSL Check Fails

Ensure OpenSSL is installed:
```bash
openssl version
```

### Monitoring Not Running

Check crontab:
```bash
crontab -l
```

Check cron logs:
```bash
grep CRON /var/log/syslog
```

## Best Practices

1. **Regular Monitoring**: Run health checks every 5 minutes
2. **Alert Routing**: Send critical alerts to on-call team
3. **Log Rotation**: Set up log rotation to prevent disk space issues
4. **Test Alerts**: Regularly test alert delivery
5. **Document Incidents**: Keep a log of monitoring alerts and resolutions

## Support

For issues with monitoring scripts:
1. Check script logs
2. Verify environment variables
3. Test scripts manually
4. Review system logs
5. Contact system administrator

