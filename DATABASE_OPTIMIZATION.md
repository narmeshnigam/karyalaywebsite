# Database Optimization Guide

This document describes the database optimization techniques implemented in the SellerPortal System.

## Overview

The system implements several database optimization strategies:

1. **Indexes** - Single and composite indexes on frequently queried fields
2. **Connection Pooling** - Persistent database connections
3. **Query Result Caching** - Caching frequently accessed data
4. **Query Optimization** - Efficient query patterns and best practices

## 1. Database Indexes

### Single Column Indexes

Single column indexes are created on fields that are frequently used in WHERE clauses, JOIN conditions, or ORDER BY clauses.

**Implemented Indexes:**

```sql
-- Users
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_created_at ON users(created_at);

-- Plans
CREATE INDEX idx_plans_slug ON plans(slug);
CREATE INDEX idx_plans_status ON plans(status);

-- Subscriptions
CREATE INDEX idx_subscriptions_customer_id ON subscriptions(customer_id);
CREATE INDEX idx_subscriptions_status ON subscriptions(status);
CREATE INDEX idx_subscriptions_end_date ON subscriptions(end_date);

-- Orders
CREATE INDEX idx_orders_customer_id ON orders(customer_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created_at ON orders(created_at);

-- Ports
CREATE INDEX idx_ports_status ON ports(status);
CREATE INDEX idx_ports_plan_id ON ports(plan_id);
CREATE INDEX idx_ports_assigned_subscription_id ON ports(assigned_subscription_id);

-- Tickets
CREATE INDEX idx_tickets_customer_id ON tickets(customer_id);
CREATE INDEX idx_tickets_status ON tickets(status);
CREATE INDEX idx_tickets_assigned_to ON tickets(assigned_to);
CREATE INDEX idx_tickets_created_at ON tickets(created_at);

-- And more...
```

### Composite Indexes

Composite indexes are created for queries that filter on multiple columns. The order of columns in the index matters - most selective column first.

**Implemented Composite Indexes:**

```sql
-- Subscriptions: Filter by customer and status
CREATE INDEX idx_subscriptions_customer_status ON subscriptions(customer_id, status);

-- Subscriptions: Filter by status and end date (for expiration checks)
CREATE INDEX idx_subscriptions_status_end_date ON subscriptions(status, end_date);

-- Orders: Filter by customer and status
CREATE INDEX idx_orders_customer_status ON orders(customer_id, status);

-- Ports: Filter by plan and status (for port allocation)
CREATE INDEX idx_ports_plan_status ON ports(plan_id, status);

-- Tickets: Filter by customer and status
CREATE INDEX idx_tickets_customer_status ON tickets(customer_id, status);

-- And more...
```

### Index Usage Examples

**Query without index:**
```sql
-- Slow: Full table scan
SELECT * FROM subscriptions WHERE customer_id = 'abc123' AND status = 'ACTIVE';
```

**Query with composite index:**
```sql
-- Fast: Uses idx_subscriptions_customer_status
SELECT * FROM subscriptions WHERE customer_id = 'abc123' AND status = 'ACTIVE';
```

### Analyzing Index Usage

Use `EXPLAIN` to verify index usage:

```sql
EXPLAIN SELECT * FROM subscriptions 
WHERE customer_id = 'abc123' AND status = 'ACTIVE';
```

Look for:
- `type: ref` or `type: range` (good)
- `key: idx_subscriptions_customer_status` (index is being used)
- `rows: low number` (fewer rows scanned)

## 2. Connection Pooling

### Implementation

Persistent connections are enabled in the database configuration to reuse existing connections instead of creating new ones for each request.

**Configuration:**

```php
// config/database.php
'options' => [
    PDO::ATTR_PERSISTENT => true,  // Enable connection pooling
    PDO::ATTR_TIMEOUT => 5,        // Connection timeout
]
```

**Environment Variable:**

```env
DB_PERSISTENT=true
```

### Benefits

- **Reduced Connection Overhead**: Reuses existing connections
- **Better Performance**: Eliminates connection establishment time
- **Lower Resource Usage**: Fewer connections to manage
- **Improved Scalability**: Handles more concurrent requests

### Considerations

- **Connection Limits**: Monitor database connection limits
- **Connection Leaks**: Ensure connections are properly closed
- **Stale Connections**: Database may close idle connections

## 3. Query Result Caching

### Implementation

The `CacheService` class provides caching functionality for frequently accessed data.

**Supported Cache Drivers:**

1. **APCu** (Recommended for production)
2. **File-based** (Fallback if APCu not available)
3. **Memory** (In-memory cache for current request)

### Usage Examples

**Basic Caching:**

```php
use Karyalay\Services\CacheService;

// Store value in cache
CacheService::set('active_plans', $plans, 3600); // Cache for 1 hour

// Retrieve value from cache
$plans = CacheService::get('active_plans');

// Check if key exists
if (CacheService::has('active_plans')) {
    $plans = CacheService::get('active_plans');
}

// Delete from cache
CacheService::delete('active_plans');
```

**Remember Pattern:**

```php
// Get from cache or execute callback and cache result
$plans = CacheService::remember('active_plans', function() {
    return $planService->getActivePlans();
}, 3600);
```

### Caching Strategies

**1. Cache Frequently Accessed Data:**

```php
// Cache active plans (rarely change)
$plans = CacheService::remember('active_plans', function() use ($db) {
    $stmt = $db->query("SELECT * FROM plans WHERE status = 'ACTIVE'");
    return $stmt->fetchAll();
}, 3600);
```

**2. Cache Expensive Queries:**

```php
// Cache dashboard statistics
$stats = CacheService::remember('dashboard_stats', function() use ($db) {
    return [
        'total_customers' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'CUSTOMER'")->fetchColumn(),
        'active_subscriptions' => $db->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'ACTIVE'")->fetchColumn(),
        'total_revenue' => $db->query("SELECT SUM(amount) FROM orders WHERE status = 'SUCCESS'")->fetchColumn(),
    ];
}, 300); // Cache for 5 minutes
```

**3. Cache Per-User Data:**

```php
// Cache user's subscriptions
$cacheKey = 'user_subscriptions_' . $userId;
$subscriptions = CacheService::remember($cacheKey, function() use ($userId, $db) {
    $stmt = $db->prepare("SELECT * FROM subscriptions WHERE customer_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}, 600); // Cache for 10 minutes
```

### Cache Invalidation

**Invalidate on Update:**

```php
// When creating/updating a plan
function updatePlan($planId, $data) {
    // Update database
    $result = $planService->update($planId, $data);
    
    // Invalidate cache
    CacheService::delete('active_plans');
    CacheService::delete('plan_' . $planId);
    
    return $result;
}
```

**Time-Based Expiration:**

```php
// Cache with short TTL for frequently changing data
CacheService::set('recent_orders', $orders, 60); // 1 minute
```

### Cache Statistics

```php
// Get cache statistics
$stats = CacheService::getStats();
print_r($stats);

// Output:
// Array (
//     [driver] => apcu
//     [memory_cache_size] => 5
//     [apcu_memory_size] => 1048576
//     [apcu_num_entries] => 12
// )
```

## 4. Query Optimization Best Practices

### Use Prepared Statements

**Bad:**
```php
$sql = "SELECT * FROM users WHERE email = '$email'";
$result = $db->query($sql);
```

**Good:**
```php
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$result = $stmt->fetch();
```

### Select Only Needed Columns

**Bad:**
```php
$stmt = $db->query("SELECT * FROM subscriptions");
```

**Good:**
```php
$stmt = $db->query("SELECT id, customer_id, plan_id, status FROM subscriptions");
```

### Use LIMIT for Large Results

**Bad:**
```php
$stmt = $db->query("SELECT * FROM orders");
$orders = $stmt->fetchAll();
```

**Good:**
```php
$stmt = $db->query("SELECT * FROM orders LIMIT 20 OFFSET 0");
$orders = $stmt->fetchAll();
```

### Avoid N+1 Queries

**Bad:**
```php
// N+1 query problem
$subscriptions = $db->query("SELECT * FROM subscriptions")->fetchAll();
foreach ($subscriptions as $sub) {
    $plan = $db->query("SELECT * FROM plans WHERE id = '{$sub['plan_id']}'")->fetch();
}
```

**Good:**
```php
// Use JOIN to fetch related data in one query
$stmt = $db->query("
    SELECT s.*, p.name as plan_name, p.price 
    FROM subscriptions s 
    JOIN plans p ON s.plan_id = p.id
");
$subscriptions = $stmt->fetchAll();
```

### Use EXISTS Instead of COUNT

**Bad:**
```php
$count = $db->query("SELECT COUNT(*) FROM subscriptions WHERE customer_id = '$id'")->fetchColumn();
if ($count > 0) {
    // Customer has subscriptions
}
```

**Good:**
```php
$exists = $db->query("SELECT EXISTS(SELECT 1 FROM subscriptions WHERE customer_id = '$id')")->fetchColumn();
if ($exists) {
    // Customer has subscriptions
}
```

### Batch Operations

**Bad:**
```php
foreach ($ports as $port) {
    $stmt = $db->prepare("INSERT INTO ports (id, instance_url, plan_id) VALUES (?, ?, ?)");
    $stmt->execute([$port['id'], $port['url'], $port['plan_id']]);
}
```

**Good:**
```php
$db->beginTransaction();
$stmt = $db->prepare("INSERT INTO ports (id, instance_url, plan_id) VALUES (?, ?, ?)");
foreach ($ports as $port) {
    $stmt->execute([$port['id'], $port['url'], $port['plan_id']]);
}
$db->commit();
```

## 5. Monitoring and Maintenance

### Enable Slow Query Log

```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2; -- Log queries taking > 2 seconds
SET GLOBAL slow_query_log_file = '/var/log/mysql/slow-query.log';
```

### Analyze Slow Queries

```bash
# View slow query log
tail -f /var/log/mysql/slow-query.log

# Analyze with mysqldumpslow
mysqldumpslow -s t -t 10 /var/log/mysql/slow-query.log
```

### Check Index Usage

```sql
-- Show index usage statistics
SELECT 
    table_name,
    index_name,
    cardinality,
    seq_in_index
FROM information_schema.statistics
WHERE table_schema = 'karyalay_portal'
ORDER BY table_name, seq_in_index;
```

### Optimize Tables

```sql
-- Optimize tables to reclaim space and rebuild indexes
OPTIMIZE TABLE users;
OPTIMIZE TABLE subscriptions;
OPTIMIZE TABLE orders;
```

### Monitor Database Performance

```sql
-- Show current queries
SHOW PROCESSLIST;

-- Show database status
SHOW STATUS LIKE '%connection%';
SHOW STATUS LIKE '%thread%';
SHOW STATUS LIKE '%query%';
```

## 6. Performance Testing

### Benchmark Queries

```php
// Measure query execution time
$start = microtime(true);
$result = $db->query("SELECT * FROM subscriptions WHERE status = 'ACTIVE'")->fetchAll();
$end = microtime(true);
echo "Query took: " . ($end - $start) . " seconds\n";
```

### Load Testing

```bash
# Using Apache Bench
ab -n 1000 -c 10 http://localhost/app/dashboard.php

# Using wrk
wrk -t12 -c400 -d30s http://localhost/app/dashboard.php
```

### Profile Database Queries

```php
// Enable query profiling
$db->query("SET profiling = 1");

// Run queries
$db->query("SELECT * FROM subscriptions WHERE status = 'ACTIVE'");

// Show profile
$profile = $db->query("SHOW PROFILES")->fetchAll();
print_r($profile);
```

## 7. Troubleshooting

### Slow Queries

1. **Check if indexes are being used**: Use `EXPLAIN`
2. **Add missing indexes**: Create indexes on filtered columns
3. **Optimize query structure**: Rewrite inefficient queries
4. **Cache results**: Use CacheService for frequently accessed data

### High Database Load

1. **Enable query caching**: Use CacheService
2. **Optimize slow queries**: Analyze slow query log
3. **Add indexes**: Create indexes on frequently queried fields
4. **Implement pagination**: Limit result sets
5. **Use connection pooling**: Enable persistent connections

### Cache Issues

1. **Clear cache**: `CacheService::clear()`
2. **Check cache driver**: `CacheService::getDriver()`
3. **Verify APCu is enabled**: `php -m | grep apcu`
4. **Check cache statistics**: `CacheService::getStats()`

## 8. Best Practices Summary

1. ✅ Create indexes on frequently queried columns
2. ✅ Use composite indexes for multi-column filters
3. ✅ Enable persistent connections (connection pooling)
4. ✅ Cache frequently accessed data
5. ✅ Use prepared statements for all queries
6. ✅ Select only needed columns
7. ✅ Use LIMIT for large result sets
8. ✅ Avoid N+1 queries with JOINs
9. ✅ Monitor slow queries
10. ✅ Regularly optimize tables

## Additional Resources

- [MySQL Performance Tuning](https://dev.mysql.com/doc/refman/8.0/en/optimization.html)
- [MySQL Indexing Best Practices](https://dev.mysql.com/doc/refman/8.0/en/optimization-indexes.html)
- [PDO Documentation](https://www.php.net/manual/en/book.pdo.php)
- [APCu Documentation](https://www.php.net/manual/en/book.apcu.php)
