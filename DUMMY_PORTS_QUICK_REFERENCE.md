# Dummy Ports - Quick Reference

## Quick Stats
- **Total Ports Added:** 20
- **Status:** All AVAILABLE
- **Regions:** 5 (US-East, US-West, EU-Central, Asia-Pacific, UK-London)
- **Total Ports in DB:** 22

## Instance URL Patterns
```
https://demo-instance-{001-020}.sellerportal.com
https://test-portal-{001-020}.sellerportal.com
https://staging-app-{001-020}.sellerportal.com
https://dev-system-{001-020}.sellerportal.com
https://sandbox-{001-020}.sellerportal.com
```

## Database Pattern
```
Host: db-server-{region}.internal
Name: portal_db_{001-020}
User: portal_user_{001-020}
Pass: {32-char hex string}
```

## Quick Commands

### View All Available Ports
```bash
php -r "require 'vendor/autoload.php'; \$db = Karyalay\Database\Connection::getInstance(); \$stmt = \$db->query('SELECT instance_url, server_region FROM ports WHERE status=\"AVAILABLE\" ORDER BY instance_url'); while(\$r = \$stmt->fetch(PDO::FETCH_ASSOC)) echo \$r['instance_url'] . ' (' . \$r['server_region'] . ')' . PHP_EOL;"
```

### Count by Status
```bash
php -r "require 'vendor/autoload.php'; \$db = Karyalay\Database\Connection::getInstance(); \$stmt = \$db->query('SELECT status, COUNT(*) as c FROM ports GROUP BY status'); while(\$r = \$stmt->fetch(PDO::FETCH_ASSOC)) echo \$r['status'] . ': ' . \$r['c'] . PHP_EOL;"
```

### Count by Region
```bash
php -r "require 'vendor/autoload.php'; \$db = Karyalay\Database\Connection::getInstance(); \$stmt = \$db->query('SELECT server_region, COUNT(*) as c FROM ports GROUP BY server_region'); while(\$r = \$stmt->fetch(PDO::FETCH_ASSOC)) echo \$r['server_region'] . ': ' . \$r['c'] . PHP_EOL;"
```

### Get Random Available Port
```bash
php -r "require 'vendor/autoload.php'; \$db = Karyalay\Database\Connection::getInstance(); \$port = \$db->query('SELECT * FROM ports WHERE status=\"AVAILABLE\" ORDER BY RAND() LIMIT 1')->fetch(PDO::FETCH_ASSOC); echo 'URL: ' . \$port['instance_url'] . PHP_EOL . 'Region: ' . \$port['server_region'] . PHP_EOL . 'DB: ' . \$port['db_host'] . '/' . \$port['db_name'] . PHP_EOL;"
```

## Testing Workflows

### 1. Test Port Assignment
1. Go to admin panel → Subscriptions
2. Create new subscription
3. System should auto-assign one of the available ports
4. Verify port status changes to ASSIGNED

### 2. Test Customer View
1. Login as customer
2. Go to "My Port" page
3. Should see assigned instance URL
4. Should see setup instructions

### 3. Test Admin Port Management
1. Go to admin panel → Ports
2. View list of all ports
3. Filter by status/region
4. View individual port details

### 4. Test Email Notifications
1. Complete a purchase
2. Check "Instance Provisioned" email
3. Should contain instance URL and setup instructions

## Cleanup Script

To remove all dummy ports:
```bash
php -r "
require 'vendor/autoload.php';
\$db = Karyalay\Database\Connection::getInstance();
\$stmt = \$db->prepare('DELETE FROM ports WHERE instance_url LIKE ?');
for(\$i=1; \$i<=20; \$i++) {
    \$num = str_pad(\$i, 3, '0', STR_PAD_LEFT);
    \$stmt->execute(['%-' . \$num . '.sellerportal.com']);
}
echo 'Dummy ports removed' . PHP_EOL;
"
```

## Re-add Ports
```bash
php database/add_dummy_ports.php
```

## Port Distribution

| Region | Count |
|--------|-------|
| UK-London | 6 |
| EU-Central | 5 |
| Asia-Pacific | 4 |
| US-East | 3 |
| US-West | 2 |

## Sample Ports for Quick Testing

**US-East:**
- https://demo-instance-006.sellerportal.com
- https://sandbox-012.sellerportal.com
- https://dev-system-011.sellerportal.com

**EU-Central:**
- https://staging-app-004.sellerportal.com
- https://test-portal-005.sellerportal.com
- https://demo-instance-014.sellerportal.com

**Asia-Pacific:**
- https://dev-system-003.sellerportal.com
- https://staging-app-009.sellerportal.com
- https://sandbox-016.sellerportal.com

**UK-London:**
- https://staging-app-001.sellerportal.com
- https://dev-system-002.sellerportal.com
- https://test-portal-018.sellerportal.com
