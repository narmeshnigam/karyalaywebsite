# Database Quick Reference

## CLI Tools

### Migrations

```bash
# Run all pending migrations
php bin/migrate.php

# Reset database (WARNING: deletes all data)
php bin/migrate.php reset
```

### Seeding

```bash
# Seed database with sample data
php bin/seed.php
```

## Sample Credentials (After Seeding)

| Role     | Email                    | Password     |
|----------|--------------------------|--------------|
| Admin    | admin@karyalay.com       | admin123     |
| Customer | customer@example.com     | customer123  |
| Support  | support@karyalay.com     | support123   |

## Migration Files

Migrations are located in `database/migrations/` and run in alphabetical order:

1. `001_create_users_table.sql` - User accounts
2. `002_create_sessions_table.sql` - Session management
3. `003_create_password_reset_tokens_table.sql` - Password resets
4. `004_create_plans_table.sql` - Subscription plans
5. `005_create_orders_table.sql` - Payment orders
6. `006_create_ports_table.sql` - Instance ports
7. `007_create_subscriptions_table.sql` - Active subscriptions
8. `008_add_ports_foreign_key.sql` - Port-subscription link
9. `009_create_port_allocation_logs_table.sql` - Allocation history
10. `010_create_tickets_table.sql` - Support tickets
11. `011_create_ticket_messages_table.sql` - Ticket messages
12. `012_create_modules_table.sql` - Product modules
13. `013_create_features_table.sql` - Product features
14. `014_create_blog_posts_table.sql` - Blog articles
15. `015_create_case_studies_table.sql` - Case studies
16. `016_create_leads_table.sql` - Contact leads
17. `017_create_media_assets_table.sql` - Uploaded files

## Database Classes

### Connection

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
    // Your operations
    Connection::commit();
} catch (Exception $e) {
    Connection::rollback();
    throw $e;
}
```

### Migration

```php
use Karyalay\Database\Migration;

$pdo = Connection::getInstance();
$migration = new Migration($pdo, 'database/migrations');

// Run all pending migrations
$results = $migration->runAll();

// Reset database (WARNING: deletes all data)
$migration->reset();
```

### Seeder

```php
use Karyalay\Database\Seeder;

$pdo = Connection::getInstance();
$seeder = new Seeder($pdo);

// Seed all tables
$seeder->runAll();
```

## Common Tasks

### Create New Migration

1. Create file: `database/migrations/018_your_migration.sql`
2. Write SQL statements
3. Run: `php bin/migrate.php`

### Fresh Database

```bash
# Reset and seed
php bin/migrate.php reset
php bin/seed.php
```

### Backup Database

```bash
mysqldump -u root -p karyalay_portal > backup.sql
```

### Restore Database

```bash
mysql -u root -p karyalay_portal < backup.sql
```

## Environment Variables

Configure in `.env`:

```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=karyalay_portal
DB_USER=root
DB_PASS=
```

## Troubleshooting

### Connection Failed

Check:
1. MySQL is running
2. Database exists
3. Credentials in `.env` are correct

### Migration Errors

- Ensure migrations run in order
- Check for syntax errors in SQL
- Verify foreign key references exist

### Seeding Errors

- Run migrations first
- Check for unique constraint violations
- Verify foreign key relationships

## More Information

See [DATABASE.md](../DATABASE.md) for comprehensive documentation.
