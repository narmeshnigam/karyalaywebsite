#!/bin/bash

###############################################################################
# Database Backup Script
# Creates compressed backups of the database
###############################################################################

set -e

# Configuration
BACKUP_DIR="/var/www/production/backups/database"
DB_NAME="${DB_NAME:-karyalay_production}"
DB_USER="${DB_USER:-karyalay_prod}"
DB_PASS="${DB_PASS}"
RETENTION_DAYS=30

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Generate backup filename with timestamp
BACKUP_FILE="$BACKUP_DIR/db-backup-$(date +%Y%m%d-%H%M%S).sql.gz"

# Create backup
echo "Creating database backup..."
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$BACKUP_FILE"

# Check if backup was successful
if [ -f "$BACKUP_FILE" ]; then
    echo "✓ Backup created successfully: $BACKUP_FILE"
    
    # Get backup size
    SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
    echo "  Size: $SIZE"
    
    # Remove old backups
    echo "Cleaning up old backups (older than $RETENTION_DAYS days)..."
    find "$BACKUP_DIR" -name "db-backup-*.sql.gz" -type f -mtime +$RETENTION_DAYS -delete
    
    # Count remaining backups
    COUNT=$(find "$BACKUP_DIR" -name "db-backup-*.sql.gz" -type f | wc -l)
    echo "  Remaining backups: $COUNT"
    
    exit 0
else
    echo "✗ Backup failed!"
    exit 1
fi
