# Database Setup Guide

## Overview

The SellerPortal System uses MySQL/MariaDB as its database. This guide covers database configuration, migrations, and seeding.

## Prerequisites

- MySQL 5.7+ or MariaDB 10.3+
- PHP 8.0+ with PDO MySQL extension
- Composer dependencies installed

## Quick Start

### 1. Create Database

```bash
# Login to MySQL
mysql -u root -p

# Create database
CREATE DATABASE karyalay_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create user (optional)
CREATE USER 'karyalay_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON karyalay_portal.* TO 'karyalay_user'@'localhost';
FLUSH PRIVILEGES;

# Exit MySQL
EXIT;
```

### 2. Configure Environment

Copy `.env.example` to `.env` and update database credentials:

```bash
cp .env.example .env
```

Edit `.env`:

```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=karyalay_portal
DB_USER=karyalay_user
DB_PASS=your_password
```

### 3. Run Migrations

```bash
php bin/migrate.php
```

This will create all database tables with proper schema, indexes, and foreign keys.

### 4. Seed Development Data (Optional)

```bash
php bin/seed.php
```

This populates the database with sample data for development:
- 3 users (admin, customer, support)
- 3 subscription plans
- 15 available ports
- 2 modules
- 2 features
- 2 blog posts
- 1 case study
- 2 leads

**Sample Credentials:**
- Admin: `admin@karyalay.com` / `admin123`
- Customer: `customer@example.com` / `customer123`
- Support: `support@karyalay.com` / `support123`

## Database Architecture

### Core Tables

#### Users & Authentication
- `users` - User accounts with roles
- `sessions` - Active user sessions
- `password_reset_tokens` - Password reset tokens

#### Subscription & Billing
- `plans` - Subscription plans
- `orders` - Payment transactions
- `subscriptions` - Active subscriptions
- `ports` - Pre-provisioned instances
- `port_allocation_logs` - Port assignment history

#### Support System
- `tickets` - Support tickets
- `ticket_messages` - Ticket message threads

#### Content Management
- `modules` - Product modules
- `features` - Product features
- `blog_posts` - Blog articles
- `case_studies` - Customer case studies
- `media_assets` - Uploaded files

#### Lead Management
- `leads` - Contact form and demo requests

### Database Schema Diagram

```
users
  ├─→ sessions
  ├─→ password_reset_tokens
  ├─→ orders
  ├─→ subscriptions
  ├─→ tickets
  ├─→ blog_posts
  └─→ media_assets

plans
  ├─→ orders
  ├─→ subscriptions
  └─→ ports

subscriptions
  ├─→ ports (assigned_port_id)
  ├─→ tickets
  └─→ port_allocation_logs

ports
  └─→ port_allocation_logs

tickets
  └─→ ticket_messages
```

## Migration System

### How It Works

1. Migrations are SQL files in `database/migrations/`
2. Files are executed in alphabetical order
3. Executed migrations are tracked in the `migrations` table
4. Only pending migrations are run

### Migration Files

Migrations follow the naming convention: `NNN_description.sql`

Example: `001_create_users_table.sql`

### Creating New Migrations

1. Create a new SQL file in `database/migrations/`
2. Use the next sequential number
3. Write SQL statements separated by semicolons
4. Run `php bin/migrate.php`

Example migration:

```sql
-- 018_add_user_preferences.sql
CREATE TABLE user_preferences (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    preferences JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Reset Database

**WARNING: This deletes all data!**

```bash
php bin/migrate.php reset
```

This will:
1. Drop all tables
2. Re-run all migrations
3. Create a fresh database schema

## Database Connection

### Using the Connection Class

```php
use Karyalay\Database\Connection;

// Get PDO instance
$pdo = Connection::getInstance();

// Execute query
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute(['user@example.com']);
$user = $stmt->fetch();

// Transactions
Connection::beginTransaction();
try {
    // Your database operations
    Connection::commit();
} catch (Exception $e) {
    Connection::rollback();
    throw $e;
}
```

### Configuration

Database configuration is in `config/database.php`:

```php
return [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_NAME') ?: 'karyalay_portal',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
```

## Seeding System

### Custom Seeders

The `Seeder` class in `classes/Database/Seeder.php` contains methods for seeding each table.

To add custom seed data:

1. Edit `classes/Database/Seeder.php`
2. Modify existing seed methods or add new ones
3. Run `php bin/seed.php`

### UUID Generation

The seeder uses UUID v4 for primary keys:

```php
private function uuid(): string
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
```

## Troubleshooting

### Connection Failed

**Error:** `Database connection failed: SQLSTATE[HY000] [2002] Connection refused`

**Solution:**
1. Verify MySQL is running: `mysql -u root -p`
2. Check credentials in `.env`
3. Verify database exists: `SHOW DATABASES;`

### Foreign Key Constraint Errors

**Error:** `Cannot add or update a child row: a foreign key constraint fails`

**Solution:**
1. Ensure migrations run in correct order
2. Check that referenced records exist
3. Verify foreign key relationships

### Migration Already Executed

**Error:** Migration shows as "skipped"

**Solution:**
- This is normal - migrations only run once
- To re-run: `php bin/migrate.php reset` (WARNING: deletes data)

### Character Encoding Issues

**Error:** Garbled text or encoding errors

**Solution:**
1. Ensure database uses `utf8mb4` charset
2. Verify connection charset in `config/database.php`
3. Check that tables use `utf8mb4_unicode_ci` collation

## Best Practices

### 1. Always Use Prepared Statements

```php
// Good
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);

// Bad - SQL injection risk!
$result = $pdo->query("SELECT * FROM users WHERE id = $userId");
```

### 2. Use Transactions for Multi-Step Operations

```php
Connection::beginTransaction();
try {
    // Create order
    $stmt = $pdo->prepare("INSERT INTO orders ...");
    $stmt->execute([...]);
    
    // Create subscription
    $stmt = $pdo->prepare("INSERT INTO subscriptions ...");
    $stmt->execute([...]);
    
    Connection::commit();
} catch (Exception $e) {
    Connection::rollback();
    throw $e;
}
```

### 3. Index Frequently Queried Columns

All foreign keys and frequently filtered columns have indexes:

```sql
INDEX idx_customer_id (customer_id),
INDEX idx_status (status),
INDEX idx_created_at (created_at)
```

### 4. Use JSON for Flexible Data

Arrays and complex data use JSON columns:

```sql
features JSON,
tags JSON,
attachments JSON
```

Access in PHP:

```php
$features = json_decode($plan['features'], true);
$tags = json_decode($post['tags'], true);
```

## Production Considerations

### 1. Backup Strategy

```bash
# Daily backup
mysqldump -u karyalay_user -p karyalay_portal > backup_$(date +%Y%m%d).sql

# Restore from backup
mysql -u karyalay_user -p karyalay_portal < backup_20231215.sql
```

### 2. Performance Optimization

- Enable query caching
- Use connection pooling
- Monitor slow queries
- Add indexes for common queries
- Use EXPLAIN to analyze query performance

### 3. Security

- Use strong database passwords
- Limit database user privileges
- Enable SSL for database connections
- Regular security updates
- Monitor for suspicious queries

### 4. Monitoring

Monitor these metrics:
- Connection pool usage
- Query execution time
- Slow query log
- Table sizes
- Index usage

## Additional Resources

- [MySQL Documentation](https://dev.mysql.com/doc/)
- [MariaDB Documentation](https://mariadb.com/kb/en/)
- [PDO Documentation](https://www.php.net/manual/en/book.pdo.php)
- [Database Design Best Practices](https://www.mysqltutorial.org/mysql-database-design/)

