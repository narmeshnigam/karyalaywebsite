#!/bin/bash

###############################################################################
# Staging Deployment Script
# This script deploys the application to the staging environment
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
STAGING_HOST="${STAGING_HOST:-staging.karyalay.com}"
STAGING_USER="${STAGING_USER:-deploy}"
STAGING_PATH="${STAGING_PATH:-/var/www/staging}"
BACKUP_DIR="${STAGING_PATH}/backups"

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}SellerPortal - Staging Deployment${NC}"
echo -e "${GREEN}========================================${NC}"

# Check if SSH key is configured
if [ -z "$STAGING_SSH_KEY" ]; then
    echo -e "${RED}Error: STAGING_SSH_KEY environment variable not set${NC}"
    exit 1
fi

# Create backup
echo -e "${YELLOW}Creating backup...${NC}"
ssh -i "$STAGING_SSH_KEY" "$STAGING_USER@$STAGING_HOST" << 'ENDSSH'
    cd $STAGING_PATH
    BACKUP_NAME="backup-$(date +%Y%m%d-%H%M%S).tar.gz"
    tar -czf "$BACKUP_DIR/$BACKUP_NAME" \
        --exclude='storage/cache/*' \
        --exclude='vendor' \
        --exclude='node_modules' \
        .
    echo "Backup created: $BACKUP_NAME"
    
    # Keep only last 5 backups
    cd $BACKUP_DIR
    ls -t | tail -n +6 | xargs -r rm
ENDSSH

# Build and prepare deployment package
echo -e "${YELLOW}Building deployment package...${NC}"
composer install --no-dev --optimize-autoloader --no-interaction
composer dump-autoload --optimize --classmap-authoritative

# Create deployment archive
echo -e "${YELLOW}Creating deployment archive...${NC}"
tar -czf deploy.tar.gz \
    --exclude='.git' \
    --exclude='.github' \
    --exclude='tests' \
    --exclude='node_modules' \
    --exclude='.env' \
    --exclude='storage/cache/*' \
    --exclude='*.log' \
    .

# Upload to staging server
echo -e "${YELLOW}Uploading to staging server...${NC}"
scp -i "$STAGING_SSH_KEY" deploy.tar.gz "$STAGING_USER@$STAGING_HOST:$STAGING_PATH/"

# Extract and configure on server
echo -e "${YELLOW}Extracting and configuring...${NC}"
ssh -i "$STAGING_SSH_KEY" "$STAGING_USER@$STAGING_HOST" << 'ENDSSH'
    cd $STAGING_PATH
    
    # Extract new files
    tar -xzf deploy.tar.gz
    rm deploy.tar.gz
    
    # Run database migrations
    php bin/migrate.php
    
    # Clear cache
    php bin/cache-clear.php
    
    # Set permissions
    chmod -R 755 storage/cache
    chmod -R 755 uploads
    chown -R www-data:www-data storage
    chown -R www-data:www-data uploads
    
    # Restart services
    sudo systemctl reload apache2
ENDSSH

# Clean up local files
rm deploy.tar.gz

# Run smoke tests
echo -e "${YELLOW}Running smoke tests...${NC}"
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "https://$STAGING_HOST")
if [ "$RESPONSE" = "200" ]; then
    echo -e "${GREEN}✓ Staging deployment successful!${NC}"
    echo -e "${GREEN}URL: https://$STAGING_HOST${NC}"
else
    echo -e "${RED}✗ Deployment may have issues. HTTP Status: $RESPONSE${NC}"
    exit 1
fi

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Deployment Complete${NC}"
echo -e "${GREEN}========================================${NC}"
