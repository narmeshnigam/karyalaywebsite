#!/bin/bash

###############################################################################
# Production Rollback Script
# This script rolls back the production deployment to the previous backup
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
PRODUCTION_HOST="${PRODUCTION_HOST:-karyalay.com}"
PRODUCTION_USER="${PRODUCTION_USER:-deploy}"
PRODUCTION_PATH="${PRODUCTION_PATH:-/var/www/production}"
BACKUP_DIR="${PRODUCTION_PATH}/backups"

echo -e "${RED}========================================${NC}"
echo -e "${RED}SellerPortal - PRODUCTION ROLLBACK${NC}"
echo -e "${RED}========================================${NC}"

# Confirmation prompt
echo -e "${YELLOW}WARNING: You are about to rollback PRODUCTION${NC}"
echo -e "${YELLOW}This will restore the previous backup. Continue? (yes/no)${NC}"
read -r CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo -e "${RED}Rollback cancelled${NC}"
    exit 1
fi

# Check if SSH key is configured
if [ -z "$PRODUCTION_SSH_KEY" ]; then
    echo -e "${RED}Error: PRODUCTION_SSH_KEY environment variable not set${NC}"
    exit 1
fi

# Enable maintenance mode
echo -e "${YELLOW}Enabling maintenance mode...${NC}"
ssh -i "$PRODUCTION_SSH_KEY" "$PRODUCTION_USER@$PRODUCTION_HOST" << 'ENDSSH'
    cd $PRODUCTION_PATH
    touch maintenance.flag
ENDSSH

# List available backups and restore the latest
echo -e "${YELLOW}Rolling back to previous backup...${NC}"
ssh -i "$PRODUCTION_SSH_KEY" "$PRODUCTION_USER@$PRODUCTION_HOST" << 'ENDSSH'
    cd $BACKUP_DIR
    
    # Get the latest backup
    LATEST_BACKUP=$(ls -t | head -n 1)
    
    if [ -z "$LATEST_BACKUP" ]; then
        echo "No backups found!"
        exit 1
    fi
    
    echo "Restoring backup: $LATEST_BACKUP"
    
    # Extract backup
    cd $PRODUCTION_PATH
    tar -xzf "$BACKUP_DIR/$LATEST_BACKUP"
    
    # Clear cache
    php bin/cache-clear.php
    
    # Set permissions
    chmod -R 755 storage/cache
    chmod -R 755 uploads
    chown -R www-data:www-data storage
    chown -R www-data:www-data uploads
    
    # Restart services
    sudo systemctl reload apache2
    
    # Disable maintenance mode
    rm -f maintenance.flag
    
    echo "Rollback complete"
ENDSSH

# Run smoke tests
echo -e "${YELLOW}Running smoke tests...${NC}"
sleep 5

RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "https://$PRODUCTION_HOST")
if [ "$RESPONSE" = "200" ]; then
    echo -e "${GREEN}✓ Rollback successful!${NC}"
    echo -e "${GREEN}URL: https://$PRODUCTION_HOST${NC}"
else
    echo -e "${RED}✗ Rollback may have issues. HTTP Status: $RESPONSE${NC}"
    exit 1
fi

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Rollback Complete${NC}"
echo -e "${GREEN}========================================${NC}"
