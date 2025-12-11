#!/bin/bash

###############################################################################
# Production Deployment Script
# This script deploys the application to the production environment
# IMPORTANT: This should only be run after thorough testing in staging
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PRODUCTION_HOST="${PRODUCTION_HOST:-karyalay.com}"
PRODUCTION_USER="${PRODUCTION_USER:-deploy}"
PRODUCTION_PATH="${PRODUCTION_PATH:-/var/www/production}"
BACKUP_DIR="${PRODUCTION_PATH}/backups"

echo -e "${RED}========================================${NC}"
echo -e "${RED}SellerPortal - PRODUCTION Deployment${NC}"
echo -e "${RED}========================================${NC}"

# Confirmation prompt
echo -e "${YELLOW}WARNING: You are about to deploy to PRODUCTION${NC}"
echo -e "${YELLOW}Have you tested this release in staging? (yes/no)${NC}"
read -r CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo -e "${RED}Deployment cancelled${NC}"
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
    echo "Maintenance mode enabled"
ENDSSH

# Create backup
echo -e "${YELLOW}Creating backup...${NC}"
ssh -i "$PRODUCTION_SSH_KEY" "$PRODUCTION_USER@$PRODUCTION_HOST" << 'ENDSSH'
    cd $PRODUCTION_PATH
    BACKUP_NAME="backup-$(date +%Y%m%d-%H%M%S).tar.gz"
    tar -czf "$BACKUP_DIR/$BACKUP_NAME" \
        --exclude='storage/cache/*' \
        --exclude='vendor' \
        --exclude='node_modules' \
        .
    echo "Backup created: $BACKUP_NAME"
    
    # Keep only last 10 backups
    cd $BACKUP_DIR
    ls -t | tail -n +11 | xargs -r rm
ENDSSH

# Build and prepare deployment package
echo -e "${YELLOW}Building deployment package...${NC}"
composer install --no-dev --optimize-autoloader --no-interaction
composer dump-autoload --optimize --classmap-authoritative

# Run final tests
echo -e "${YELLOW}Running final tests...${NC}"
composer test:unit || {
    echo -e "${RED}Tests failed! Aborting deployment.${NC}"
    exit 1
}

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

# Upload to production server
echo -e "${YELLOW}Uploading to production server...${NC}"
scp -i "$PRODUCTION_SSH_KEY" deploy.tar.gz "$PRODUCTION_USER@$PRODUCTION_HOST:$PRODUCTION_PATH/"

# Extract and configure on server
echo -e "${YELLOW}Extracting and configuring...${NC}"
ssh -i "$PRODUCTION_SSH_KEY" "$PRODUCTION_USER@$PRODUCTION_HOST" << 'ENDSSH'
    cd $PRODUCTION_PATH
    
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
    
    # Disable maintenance mode
    rm -f maintenance.flag
    echo "Maintenance mode disabled"
ENDSSH

# Clean up local files
rm deploy.tar.gz

# Run smoke tests
echo -e "${YELLOW}Running smoke tests...${NC}"
sleep 5  # Wait for services to stabilize

RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "https://$PRODUCTION_HOST")
if [ "$RESPONSE" = "200" ]; then
    echo -e "${GREEN}✓ Production deployment successful!${NC}"
    echo -e "${GREEN}URL: https://$PRODUCTION_HOST${NC}"
    
    # Create deployment tag
    TAG="v$(date +%Y%m%d-%H%M%S)"
    git tag -a "$TAG" -m "Production deployment"
    git push origin "$TAG"
    echo -e "${GREEN}Created deployment tag: $TAG${NC}"
else
    echo -e "${RED}✗ Deployment may have issues. HTTP Status: $RESPONSE${NC}"
    echo -e "${YELLOW}Consider rolling back if issues persist${NC}"
    exit 1
fi

# Send notification (placeholder)
echo -e "${BLUE}Sending deployment notification...${NC}"
# Add your notification logic here (Slack, Discord, Email, etc.)

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Production Deployment Complete${NC}"
echo -e "${GREEN}========================================${NC}"
echo -e "${BLUE}Monitor the application for any issues${NC}"
echo -e "${BLUE}Rollback command: ./bin/rollback-production.sh${NC}"
