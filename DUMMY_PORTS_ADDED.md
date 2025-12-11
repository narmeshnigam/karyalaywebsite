# Dummy Ports Added for Testing

## Overview
Successfully added 20 dummy ports with complete details for testing purposes on the website.

## Execution Summary
- **Script:** `database/add_dummy_ports.php`
- **Ports Added:** 20 out of 20 (100% success)
- **Status:** All ports set to `AVAILABLE`
- **Total Ports in Database:** 22 (including 2 existing ports)

## Port Details

### Instance URLs
The dummy ports use realistic instance URLs with different prefixes:
- `https://demo-instance-XXX.sellerportal.com`
- `https://test-portal-XXX.sellerportal.com`
- `https://staging-app-XXX.sellerportal.com`
- `https://dev-system-XXX.sellerportal.com`
- `https://sandbox-XXX.sellerportal.com`

Where XXX is a 3-digit number (001-020)

### Server Regions
Ports are distributed across 5 regions:
- **US-East** - East Coast United States
- **US-West** - West Coast United States
- **EU-Central** - Central Europe
- **Asia-Pacific** - Asia Pacific region
- **UK-London** - United Kingdom

### Database Configuration
Each port has complete database connection details:
- **DB Host:** `db-server-{region}.internal`
- **DB Name:** `portal_db_{number}`
- **DB Username:** `portal_user_{number}`
- **DB Password:** Randomly generated 32-character hex string

### Setup Instructions
Each port includes one of 5 different setup instruction templates covering:
1. Initial access and login
2. Password setup and security
3. Onboarding and configuration
4. Team member invitation
5. Data import and customization

### Notes
Each port has descriptive notes such as:
- "High-performance instance with SSD storage"
- "Standard configuration with daily backups"
- "Premium tier with enhanced security features"
- "Development environment for testing"
- "Production-ready instance with monitoring"
- "Optimized for high-traffic applications"
- "Includes advanced analytics features"
- "Configured with custom domain support"
- "Enhanced with CDN integration"
- "Includes automated backup and restore"

## Port Structure

Each port includes the following fields:
```
- id: UUID (36 characters)
- instance_url: Full HTTPS URL
- db_host: Database server hostname
- db_name: Database name
- db_username: Database username
- db_password: Encrypted database password
- status: AVAILABLE
- server_region: Geographic region
- notes: Descriptive information
- setup_instructions: Step-by-step setup guide
- created_at: Timestamp
- updated_at: Timestamp
```

## Sample Port Data

### Port 1
```
Instance URL: https://staging-app-001.sellerportal.com
Region: UK-London
Database: db-server-uklondon.internal/portal_db_001
Username: portal_user_001
Status: AVAILABLE
```

### Port 6
```
Instance URL: https://demo-instance-006.sellerportal.com
Region: US-East
Database: db-server-useast.internal/portal_db_006
Username: portal_user_006
Status: AVAILABLE
```

### Port 12
```
Instance URL: https://sandbox-012.sellerportal.com
Region: US-East
Database: db-server-useast.internal/portal_db_012
Username: portal_user_012
Status: AVAILABLE
```

### Port 16
```
Instance URL: https://sandbox-016.sellerportal.com
Region: Asia-Pacific
Database: db-server-asiapacific.internal/portal_db_016
Username: portal_user_016
Status: AVAILABLE
```

### Port 20
```
Instance URL: https://staging-app-020.sellerportal.com
Region: Asia-Pacific
Database: db-server-asiapacific.internal/portal_db_020
Username: portal_user_020
Status: AVAILABLE
```

## Usage

### View All Available Ports
```sql
SELECT id, instance_url, server_region, status 
FROM ports 
WHERE status = 'AVAILABLE' 
ORDER BY created_at DESC;
```

### View Port Details
```sql
SELECT * FROM ports WHERE instance_url LIKE '%demo-instance%';
```

### Count Ports by Region
```sql
SELECT server_region, COUNT(*) as count 
FROM ports 
GROUP BY server_region;
```

### Count Ports by Status
```sql
SELECT status, COUNT(*) as count 
FROM ports 
GROUP BY status;
```

## Testing Scenarios

These dummy ports can be used for:

1. **Port Allocation Testing**
   - Test automatic port assignment during subscription creation
   - Verify port availability checks
   - Test port reservation logic

2. **Admin Panel Testing**
   - View and manage ports in admin interface
   - Test port filtering and search
   - Verify port details display

3. **Customer Portal Testing**
   - Test instance provisioning flow
   - Verify setup instructions display
   - Test "My Port" page functionality

4. **Subscription Testing**
   - Create subscriptions and assign ports
   - Test port assignment on payment success
   - Verify port status changes

5. **Email Testing**
   - Test instance provisioned email with real URLs
   - Verify setup instructions in emails
   - Test database credentials display

## Cleanup

To remove all dummy ports (if needed):
```sql
-- Remove only the newly added dummy ports
DELETE FROM ports 
WHERE instance_url LIKE '%-001.sellerportal.com'
   OR instance_url LIKE '%-002.sellerportal.com'
   -- ... continue for all 20 ports
   OR instance_url LIKE '%-020.sellerportal.com';
```

Or to remove all AVAILABLE ports:
```sql
-- CAUTION: This removes ALL available ports
DELETE FROM ports WHERE status = 'AVAILABLE';
```

## Re-running the Script

The script can be run multiple times. Each execution will:
- Generate new UUIDs for each port
- Create unique instance URLs
- Generate new database credentials
- Add 20 additional ports to the database

## Notes

- All ports are set to `AVAILABLE` status by default
- Ports are not assigned to any plan (plan_id column was removed in migration 035)
- Database passwords are randomly generated and secure
- Setup instructions are varied to provide realistic testing scenarios
- All timestamps are set to the current time when the script runs

## Verification

Run the test query to verify all ports were added:
```bash
php -r "
require 'vendor/autoload.php';
\$db = Karyalay\Database\Connection::getInstance();
\$count = \$db->query('SELECT COUNT(*) FROM ports WHERE status = \"AVAILABLE\"')->fetchColumn();
echo \"Available ports: \$count\n\";
"
```

Expected output: `Available ports: 21` (or more if additional ports exist)
