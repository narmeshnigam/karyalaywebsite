#!/bin/bash

###############################################################################
# Monitoring Setup Script
# Sets up monitoring infrastructure for SellerPortal System
###############################################################################

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo "========================================="
echo "SellerPortal - Monitoring Setup"
echo "========================================="
echo ""

# Check if running as root
if [ "$EUID" -eq 0 ]; then
    echo -e "${YELLOW}Warning: Running as root${NC}"
fi

# Get project root
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

echo "Project root: $PROJECT_ROOT"
echo ""

# Create logs directory
echo "Creating logs directory..."
mkdir -p storage/logs
chmod 755 storage/logs
echo -e "${GREEN}✓${NC} Logs directory created"

# Make monitoring script executable
echo "Making monitoring script executable..."
chmod +x bin/monitor.sh
echo -e "${GREEN}✓${NC} Monitoring script is executable"

# Check for required commands
echo ""
echo "Checking required commands..."

check_command() {
    if command -v "$1" &> /dev/null; then
        echo -e "${GREEN}✓${NC} $1 is installed"
        return 0
    else
        echo -e "${YELLOW}⚠${NC} $1 is not installed"
        return 1
    fi
}

check_command "curl"
check_command "php"
check_command "openssl"

if ! check_command "mail"; then
    echo -e "${YELLOW}  Install mailutils for email alerts: sudo apt-get install mailutils${NC}"
fi

# Check .env file
echo ""
if [ -f ".env" ]; then
    echo -e "${GREEN}✓${NC} .env file exists"
    
    # Check monitoring variables
    echo ""
    echo "Checking monitoring configuration..."
    
    check_env_var() {
        if grep -q "^$1=" .env; then
            value=$(grep "^$1=" .env | cut -d'=' -f2)
            if [ -n "$value" ]; then
                echo -e "${GREEN}✓${NC} $1 is configured"
            else
                echo -e "${YELLOW}⚠${NC} $1 is empty"
            fi
        else
            echo -e "${YELLOW}⚠${NC} $1 is not set"
        fi
    }
    
    check_env_var "LOGGING_ENABLED"
    check_env_var "ERROR_TRACKING_ENABLED"
    check_env_var "PERFORMANCE_MONITORING_ENABLED"
    check_env_var "ALERTS_ENABLED"
    check_env_var "ALERT_EMAIL"
else
    echo -e "${RED}✗${NC} .env file not found"
    echo "  Copy .env.example to .env and configure it"
fi

# Test health endpoint
echo ""
echo "Testing health endpoint..."
if [ -f "public/health.php" ]; then
    echo -e "${GREEN}✓${NC} Health endpoint exists"
    
    # Try to test it if PHP is available
    if command -v php &> /dev/null; then
        php -r "require 'public/health.php';" > /dev/null 2>&1 && \
            echo -e "${GREEN}✓${NC} Health endpoint is functional" || \
            echo -e "${YELLOW}⚠${NC} Health endpoint may have issues"
    fi
else
    echo -e "${RED}✗${NC} Health endpoint not found"
fi

# Suggest cron job
echo ""
echo "========================================="
echo "Next Steps"
echo "========================================="
echo ""
echo "1. Configure monitoring in .env:"
echo "   LOGGING_ENABLED=true"
echo "   ERROR_TRACKING_ENABLED=true"
echo "   PERFORMANCE_MONITORING_ENABLED=true"
echo "   ALERTS_ENABLED=true"
echo "   ALERT_EMAIL=admin@karyalay.com"
echo ""
echo "2. (Optional) Set up Sentry for error tracking:"
echo "   composer require sentry/sentry"
echo "   SENTRY_DSN=https://your-dsn@sentry.io/project"
echo ""
echo "3. Add monitoring to crontab:"
echo "   */5 * * * * $PROJECT_ROOT/bin/monitor.sh >> /var/log/karyalay-monitor.log 2>&1"
echo ""
echo "4. Access admin monitoring dashboard:"
echo "   https://your-domain.com/admin/monitoring.php"
echo ""
echo "5. Read the documentation:"
echo "   cat MONITORING_AND_LOGGING.md"
echo ""
echo -e "${GREEN}Setup complete!${NC}"

