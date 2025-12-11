#!/bin/bash

###############################################################################
# Application Monitoring Script
# Checks application health and sends alerts if issues detected
###############################################################################

# Configuration
APP_URL="${APP_URL:-https://karyalay.com}"
HEALTH_ENDPOINT="${APP_URL}/health.php"
ALERT_EMAIL="${ALERT_EMAIL:-admin@karyalay.com}"
LOG_FILE="/var/log/karyalay-monitor.log"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Timestamp
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

# Function to log messages
log_message() {
    echo "[$TIMESTAMP] $1" | tee -a "$LOG_FILE"
}

# Function to send alert
send_alert() {
    local subject="$1"
    local message="$2"
    
    # Send email alert (requires mail command)
    if command -v mail &> /dev/null; then
        echo "$message" | mail -s "$subject" "$ALERT_EMAIL"
    fi
    
    # Log alert
    log_message "ALERT: $subject - $message"
}

# Check HTTP response
check_http() {
    local url="$1"
    local response=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "$url")
    
    if [ "$response" = "200" ]; then
        echo -e "${GREEN}✓${NC} HTTP check passed (Status: $response)"
        log_message "HTTP check passed for $url (Status: $response)"
        return 0
    else
        echo -e "${RED}✗${NC} HTTP check failed (Status: $response)"
        send_alert "HTTP Check Failed" "URL: $url returned status code: $response"
        return 1
    fi
}

# Check health endpoint
check_health() {
    local response=$(curl -s "$HEALTH_ENDPOINT")
    local status=$(echo "$response" | grep -o '"status":"[^"]*"' | cut -d'"' -f4)
    
    if [ "$status" = "healthy" ]; then
        echo -e "${GREEN}✓${NC} Health check passed"
        log_message "Health check passed"
        return 0
    else
        echo -e "${RED}✗${NC} Health check failed"
        send_alert "Health Check Failed" "Application health status: $status\n\nDetails:\n$response"
        return 1
    fi
}

# Check SSL certificate
check_ssl() {
    local domain=$(echo "$APP_URL" | sed -e 's|^[^/]*//||' -e 's|/.*$||')
    local expiry_date=$(echo | openssl s_client -servername "$domain" -connect "$domain:443" 2>/dev/null | openssl x509 -noout -enddate 2>/dev/null | cut -d= -f2)
    
    if [ -n "$expiry_date" ]; then
        local expiry_epoch=$(date -d "$expiry_date" +%s 2>/dev/null || date -j -f "%b %d %T %Y %Z" "$expiry_date" +%s 2>/dev/null)
        local current_epoch=$(date +%s)
        local days_until_expiry=$(( ($expiry_epoch - $current_epoch) / 86400 ))
        
        if [ $days_until_expiry -lt 30 ]; then
            echo -e "${YELLOW}⚠${NC} SSL certificate expires in $days_until_expiry days"
            send_alert "SSL Certificate Expiring Soon" "SSL certificate for $domain expires in $days_until_expiry days on $expiry_date"
        else
            echo -e "${GREEN}✓${NC} SSL certificate valid (expires in $days_until_expiry days)"
            log_message "SSL certificate valid for $days_until_expiry days"
        fi
        return 0
    else
        echo -e "${RED}✗${NC} Could not check SSL certificate"
        return 1
    fi
}

# Check disk space
check_disk_space() {
    local usage=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
    
    if [ "$usage" -gt 90 ]; then
        echo -e "${RED}✗${NC} Disk space critical: ${usage}% used"
        send_alert "Disk Space Critical" "Disk usage is at ${usage}%"
        return 1
    elif [ "$usage" -gt 80 ]; then
        echo -e "${YELLOW}⚠${NC} Disk space warning: ${usage}% used"
        log_message "Disk space warning: ${usage}% used"
        return 0
    else
        echo -e "${GREEN}✓${NC} Disk space OK: ${usage}% used"
        log_message "Disk space OK: ${usage}% used"
        return 0
    fi
}

# Check memory usage
check_memory() {
    local usage=$(free | awk 'NR==2 {printf "%.0f", $3/$2 * 100}')
    
    if [ "$usage" -gt 90 ]; then
        echo -e "${RED}✗${NC} Memory usage critical: ${usage}%"
        send_alert "Memory Usage Critical" "Memory usage is at ${usage}%"
        return 1
    elif [ "$usage" -gt 80 ]; then
        echo -e "${YELLOW}⚠${NC} Memory usage warning: ${usage}%"
        log_message "Memory usage warning: ${usage}%"
        return 0
    else
        echo -e "${GREEN}✓${NC} Memory usage OK: ${usage}%"
        log_message "Memory usage OK: ${usage}%"
        return 0
    fi
}

# Main monitoring function
main() {
    echo "========================================="
    echo "SellerPortal - Health Monitoring"
    echo "========================================="
    echo "Time: $TIMESTAMP"
    echo "URL: $APP_URL"
    echo ""
    
    local failed=0
    
    # Run checks
    check_http "$APP_URL" || ((failed++))
    check_health || ((failed++))
    check_ssl || ((failed++))
    
    # Only check disk and memory if running on the server
    if [ -f "/var/www/production" ]; then
        check_disk_space || ((failed++))
        check_memory || ((failed++))
    fi
    
    echo ""
    echo "========================================="
    
    if [ $failed -eq 0 ]; then
        echo -e "${GREEN}All checks passed!${NC}"
        log_message "All monitoring checks passed"
        exit 0
    else
        echo -e "${RED}$failed check(s) failed!${NC}"
        log_message "$failed monitoring checks failed"
        exit 1
    fi
}

# Run monitoring
main
