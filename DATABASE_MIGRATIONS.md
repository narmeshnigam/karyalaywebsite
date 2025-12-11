# Database Migrations Guide

This document provides comprehensive guidance on database migrations for the SellerPortal System.

## Table of Contents

1. [Overview](#overview)
2. [Migration System Architecture](#migration-system-architecture)
3. [Running Migrations](#running-migrations)
4. [Creating New Migrations](#creating-new-migrations)
5. [Migration Best Practices](#migration-best-practices)
6. [Rollback Procedures](#rollback-procedures)
7. [Production Migration Strategy](#production-migration-strategy)
8. [Troubleshooting](#troubleshooting)
9. [Migration Reference](#migration-reference)

---

## Overview

The SellerPortal System uses a custom migration system to manage database schema changes. Migrations are SQL files that are executed in order to create, modify, or delete database structures.

### Key Features

- **Sequential Execution**: Migrations run in alphabetical order
- **Tracking**: Executed migrations are tracked to prevent re-execution
- **Idempotent**: Safe to run multiple times
- **Version Control**: Migrations are committed to Git
- **Rollback Support**: Ability to revert changes

---

## Migration System Architecture

### Directory Structure

```
database/
├── migrations/
│   ├── 001_create_users_table.sql
│   ├── 002_create_sessions_table.sql
│   ├── 003_create_password_reset_tokens_table.sql
│   ├── ...
│   └── 020_add_performance_indexes.sql
└── README.md
```

### Migration Tracking

The system uses a `migrations` table to track executed migrations:

```sql
CREATE TABLE migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL UNIQUE,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Migration Class

Location: `classes/Database/Migration.php`

Key methods:
- `run()` - Execute all pending migrations
- `reset()` - Drop all tables and re-run migrations
- `status()` - Show migration status
- `rollback()` - Rollback last migration (if supported)

---

## Running Migrations

### Initial Setup

```bash
# 1. Ensure database exists
mysql -u root -p -e "CREATE DATABASE karyalay_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Configure .env file
cp .env.example .env
nano .env  # Update DB_* variables

# 3. Run migrations
php bin/migrate.php
```

### Check Migration Status

```bash
# Show which migrations have been executed
php bin/migrate.php --status
```

Output example:
```
Migration Status:
✓ 001_create_users_table.sql (executed 2024-01-15 10:30:00)
✓ 002_create_sessions_table.sql (executed 2024-01-15 10:30:01)
✓ 003_create_password_reset_tokens_table.sql (executed 2024-01-15 10:30:02)
⏳ 021_add_new_feature.sql (pending)
```

### Run Pending Migrations

```bash
# Run all pending migrations
php bin/migrate.php

# Dry run (show what would be executed)
php bin/migrate.php --dry-run
```

### Reset Database

**⚠️ WARNING: This deletes ALL data!**

```bash
# Drop all tables and re-run all migrations
php bin/migrate.php --reset

# With confirmation prompt
php bin/migrate.php --reset --confirm
```

---

## Creating New Migrations

### Naming Convention

Format: `NNN_description.sql`

- `NNN` - Three-digit sequential number (001, 002, 003, etc.)
- `description` - Snake_case description of the change
- `.sql` - File extension

Examples:
- `021_add_user_preferences_table.sql`
- `022_add_email_verified_column.sql`
- `023_create_notifications_table.sql`

### Migration Template

```sql
-- Migration: [Brief description]
-- Created: [Date]
-- Author: [Your name]

-- Create new table
CREATE TABLE table_name (
    id CHAR(36) PRIMARY KEY,
    column1 VARCHAR(255) NOT NULL,
    column2 TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_column1 (column1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key
ALTER TABLE table_name
ADD CONSTRAINT fk_table_reference
FOREIGN KEY (reference_id) REFERENCES other_table(id)
ON DELETE CASCADE;
```

### Step-by-Step Process

#### 1. Determine Next Migration Number

```bash
# List existing migrations
ls -1 database/migrations/ | tail -1
# Output: 020_add_performance_indexes.sql

# Next number: 021
```

#### 2. Create Migration File

```bash
# Create new migration file
touch database/migrations/021_add_user_preferences_table.sql
```

#### 3. Write Migration SQL

```sql
-- Migration: Add user preferences table
-- Created: 2024-01-20
-- Author: Dev Team

CREATE TABLE user_preferences (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    theme VARCHAR(50) DEFAULT 'light',
    language VARCHAR(10) DEFAULT 'en',
    timezone VARCHAR(50) DEFAULT 'UTC',
    notifications_enabled BOOLEAN DEFAULT TRUE,
    email_frequency VARCHAR(20) DEFAULT 'daily',
    preferences JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 4. Test Migration Locally

```bash
# Run migration in development
php bin/migrate.php

# Verify table was created
mysql -u root -p karyalay_portal -e "DESCRIBE user_preferences;"

# Test with sample data
mysql -u root -p karyalay_portal -e "INSERT INTO user_preferences (id, user_id, theme) VALUES (UUID(), 'user-id-here', 'dark');"
```

#### 5. Commit to Version Control

```bash
git add database/migrations/021_add_user_preferences_table.sql
git commit -m "Add user preferences table migration"
git push
```

---

## Migration Best Practices

### 1. One Change Per Migration

**Good:**
```
021_add_user_preferences_table.sql
022_add_email_verified_column.sql
023_add_user_avatar_column.sql
```

**Bad:**
```
021_add_multiple_features.sql  # Too broad
```

### 2. Use Descriptive Names

**Good:**
- `021_add_user_preferences_table.sql`
- `022_add_email_verified_to_users.sql`
- `023_create_notifications_table.sql`

**Bad:**
- `021_update.sql`
- `022_changes.sql`
- `023_fix.sql`

### 3. Always Use Transactions (When Possible)

```sql
START TRANSACTION;

-- Your migration statements here
CREATE TABLE ...;
ALTER TABLE ...;

COMMIT;
```

**Note:** Some DDL statements (CREATE TABLE, ALTER TABLE) auto-commit in MySQL.

### 4. Add Indexes for Foreign Keys

```sql
CREATE TABLE orders (
    id CHAR(36) PRIMARY KEY,
    customer_id CHAR(36) NOT NULL,
    plan_id CHAR(36) NOT NULL,
    
    -- Add indexes for foreign keys
    INDEX idx_customer_id (customer_id),
    INDEX idx_plan_id (plan_id),
    
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (plan_id) REFERENCES plans(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 5. Use Appropriate Data Types

```sql
-- Good
id CHAR(36)                    -- UUID
email VARCHAR(255)             -- Email addresses
status ENUM('active', 'inactive')  -- Fixed values
price DECIMAL(10,2)            -- Money
description TEXT               -- Long text
is_active BOOLEAN              -- True/false
created_at TIMESTAMP           -- Timestamps

-- Bad
id VARCHAR(255)                -- Too large for UUID
email TEXT                     -- Inefficient for emails
status VARCHAR(255)            -- Use ENUM instead
price FLOAT                    -- Precision issues with money
```

### 6. Set Default Values

```sql
CREATE TABLE users (
    id CHAR(36) PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'customer',
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 7. Use Proper Character Set and Collation

```sql
CREATE TABLE table_name (
    ...
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 8. Add Comments for Complex Changes

```sql
-- Migration: Add composite index for performance
-- This index improves query performance for subscription lookups by customer and status
-- Expected to reduce query time from 500ms to 50ms on production dataset

CREATE INDEX idx_customer_status ON subscriptions(customer_id, status);
```

### 9. Consider Data Migration

If migration includes data changes:

```sql
-- Add new column with default
ALTER TABLE users ADD COLUMN full_name VARCHAR(255) DEFAULT '';

-- Populate from existing data
UPDATE users SET full_name = CONCAT(first_name, ' ', last_name);

-- Make NOT NULL after population
ALTER TABLE users MODIFY full_name VARCHAR(255) NOT NULL;
```

### 10. Test Before Production

Always test migrations in this order:
1. Local development environment
2. Staging environment
3. Production environment

---

## Rollback Procedures

### Automatic Rollback

```bash
# Rollback last migration
php bin/migrate.php --rollback
```

**Note:** This only works if the migration includes rollback SQL.

### Manual Rollback

#### 1. Identify Migration to Rollback

```bash
# Check migration history
php bin/migrate.php --status

# Or query database
mysql -u root -p karyalay_portal -e "SELECT * FROM migrations ORDER BY executed_at DESC LIMIT 5;"
```

#### 2. Create Rollback SQL

For migration `021_add_user_preferences_table.sql`:

```sql
-- Rollback: Remove user preferences table
DROP TABLE IF EXISTS user_preferences;
```

#### 3. Execute Rollback

```bash
# Execute rollback SQL
mysql -u root -p karyalay_portal < rollback_021.sql

# Remove from migrations table
mysql -u root -p karyalay_portal -e "DELETE FROM migrations WHERE migration = '021_add_user_preferences_table.sql';"
```

#### 4. Verify Rollback

```bash
# Check table is gone
mysql -u root -p karyalay_portal -e "SHOW TABLES;"

# Check migration status
php bin/migrate.php --status
```

### Rollback Strategies

#### Strategy 1: Drop and Recreate

**Use when:** Adding new tables or columns

```sql
-- Rollback
DROP TABLE IF EXISTS new_table;
```

#### Strategy 2: Restore Previous State

**Use when:** Modifying existing structures

```sql
-- Original migration
ALTER TABLE users ADD COLUMN new_column VARCHAR(255);

-- Rollback
ALTER TABLE users DROP COLUMN new_column;
```

#### Strategy 3: Data Restoration

**Use when:** Data was modified

```sql
-- Backup before migration
CREATE TABLE users_backup AS SELECT * FROM users;

-- Rollback
DELETE FROM users;
INSERT INTO users SELECT * FROM users_backup;
DROP TABLE users_backup;
```

---

## Production Migration Strategy

### Pre-Migration Checklist

- [ ] Migration tested in development
- [ ] Migration tested in staging
- [ ] Database backup created
- [ ] Rollback plan prepared
- [ ] Downtime window scheduled (if needed)
- [ ] Team notified
- [ ] Monitoring alerts configured

### Migration Process

#### 1. Create Backup

```bash
# Backup database
mysqldump -u karyalay_prod -p karyalay_production > backup_pre_migration_$(date +%Y%m%d_%H%M%S).sql

# Compress backup
gzip backup_pre_migration_*.sql

# Verify backup
gunzip -c backup_pre_migration_*.sql.gz | head -n 20
```

#### 2. Enable Maintenance Mode (If Needed)

```bash
# Create maintenance flag
touch /var/www/production/maintenance.flag

# Verify maintenance page is showing
curl https://karyalay.com
```

#### 3. Run Migration

```bash
# SSH to production server
ssh deploy@karyalay.com

# Navigate to application directory
cd /var/www/production

# Check migration status
php bin/migrate.php --status

# Run migrations
php bin/migrate.php

# Verify success
echo $?  # Should output 0
```

#### 4. Verify Migration

```bash
# Check database structure
mysql -u karyalay_prod -p karyalay_production -e "DESCRIBE new_table;"

# Run application health check
curl https://karyalay.com/health.php

# Check application logs
tail -f storage/logs/app.log
```

#### 5. Disable Maintenance Mode

```bash
# Remove maintenance flag
rm /var/www/production/maintenance.flag

# Verify application is accessible
curl https://karyalay.com
```

#### 6. Monitor Application

```bash
# Watch error logs
tail -f storage/logs/error.log

# Monitor database performance
mysql -u karyalay_prod -p -e "SHOW PROCESSLIST;"

# Check application metrics
curl https://karyalay.com/admin/monitoring
```

### Post-Migration Checklist

- [ ] Migration completed successfully
- [ ] Application is accessible
- [ ] No errors in logs
- [ ] Database queries performing normally
- [ ] User functionality tested
- [ ] Backup retained for 30 days
- [ ] Team notified of completion
- [ ] Documentation updated

---

## Troubleshooting

### Migration Fails with Syntax Error

**Error:**
```
ERROR 1064 (42000): You have an error in your SQL syntax
```

**Solution:**
1. Check SQL syntax in migration file
2. Test SQL in MySQL client
3. Verify semicolons and statement separators
4. Check for reserved keywords

### Foreign Key Constraint Fails

**Error:**
```
ERROR 1215 (HY000): Cannot add foreign key constraint
```

**Solution:**
1. Ensure referenced table exists
2. Verify referenced column exists
3. Check data types match exactly
4. Ensure referenced column is indexed
5. Verify both tables use InnoDB engine

### Migration Already Executed

**Error:**
```
Migration 021_add_user_preferences_table.sql already executed
```

**Solution:**
This is normal - migrations only run once. To re-run:

```bash
# Option 1: Remove from migrations table
mysql -u root -p karyalay_portal -e "DELETE FROM migrations WHERE migration = '021_add_user_preferences_table.sql';"

# Option 2: Reset and re-run all migrations (⚠️ deletes data)
php bin/migrate.php --reset
```

### Table Already Exists

**Error:**
```
ERROR 1050 (42S01): Table 'users' already exists
```

**Solution:**
Add `IF NOT EXISTS` clause:

```sql
CREATE TABLE IF NOT EXISTS users (
    ...
);
```

### Column Already Exists

**Error:**
```
ERROR 1060 (42S21): Duplicate column name 'email_verified'
```

**Solution:**
Check if column exists before adding:

```sql
-- Check if column exists
SELECT COUNT(*) 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'karyalay_portal' 
  AND TABLE_NAME = 'users' 
  AND COLUMN_NAME = 'email_verified';

-- Or use ALTER TABLE with IF NOT EXISTS (MySQL 8.0.29+)
ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified BOOLEAN DEFAULT FALSE;
```

### Migration Timeout

**Error:**
```
ERROR 2013 (HY000): Lost connection to MySQL server during query
```

**Solution:**
1. Increase timeout in MySQL configuration
2. Break large migrations into smaller ones
3. Run during low-traffic period
4. Optimize migration queries

### Insufficient Privileges

**Error:**
```
ERROR 1142 (42000): CREATE command denied to user
```

**Solution:**
Grant necessary privileges:

```sql
GRANT CREATE, ALTER, DROP, INDEX ON karyalay_portal.* TO 'karyalay_user'@'localhost';
FLUSH PRIVILEGES;
```

---

## Migration Reference

### Current Migrations

| # | Migration | Description |
|---|-----------|-------------|
| 001 | create_users_table | User accounts with roles |
| 002 | create_sessions_table | User sessions |
| 003 | create_password_reset_tokens_table | Password reset tokens |
| 004 | create_plans_table | Subscription plans |
| 005 | create_orders_table | Payment transactions |
| 006 | create_ports_table | Pre-provisioned instances |
| 007 | create_subscriptions_table | Active subscriptions |
| 008 | add_ports_foreign_key | Link ports to subscriptions |
| 009 | create_port_allocation_logs_table | Port assignment history |
| 010 | create_tickets_table | Support tickets |
| 011 | create_ticket_messages_table | Ticket message threads |
| 012 | create_modules_table | Product modules |
| 013 | create_features_table | Product features |
| 014 | create_blog_posts_table | Blog articles |
| 015 | create_case_studies_table | Customer case studies |
| 016 | create_leads_table | Contact form submissions |
| 017 | create_media_assets_table | Uploaded files |
| 018 | create_settings_table | Application settings |
| 019 | add_sales_role_to_users | Add sales role option |
| 020 | add_performance_indexes | Performance optimization indexes |

### Common Migration Patterns

#### Add Column

```sql
ALTER TABLE users 
ADD COLUMN phone VARCHAR(20) AFTER email;
```

#### Modify Column

```sql
ALTER TABLE users 
MODIFY COLUMN email VARCHAR(320) NOT NULL;
```

#### Drop Column

```sql
ALTER TABLE users 
DROP COLUMN old_column;
```

#### Add Index

```sql
CREATE INDEX idx_email ON users(email);
```

#### Add Foreign Key

```sql
ALTER TABLE orders
ADD CONSTRAINT fk_customer
FOREIGN KEY (customer_id) REFERENCES users(id)
ON DELETE CASCADE;
```

#### Rename Table

```sql
RENAME TABLE old_name TO new_name;
```

#### Add Unique Constraint

```sql
ALTER TABLE users
ADD UNIQUE KEY unique_email (email);
```

---

## Additional Resources

- [MySQL ALTER TABLE Documentation](https://dev.mysql.com/doc/refman/8.0/en/alter-table.html)
- [MySQL CREATE TABLE Documentation](https://dev.mysql.com/doc/refman/8.0/en/create-table.html)
- [MySQL Data Types](https://dev.mysql.com/doc/refman/8.0/en/data-types.html)
- [Database Design Best Practices](https://www.mysqltutorial.org/mysql-database-design/)

---

## Support

For migration issues or questions:
- Email: devops@karyalay.com
- Documentation: [DATABASE.md](DATABASE.md)
- Deployment Guide: [DEPLOYMENT.md](DEPLOYMENT.md)
