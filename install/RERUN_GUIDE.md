# Installation Wizard Re-run Guide

## Overview

The installation wizard includes a re-run capability that allows you to reconfigure the system after the initial installation. This is useful for:

- Recovering from configuration errors
- Migrating to a new database
- Resetting the system to factory defaults
- Updating configuration settings

## How to Re-run the Wizard

### Step 1: Remove the Lock File

The installation wizard is protected by a lock file that prevents accidental re-runs. To enable the wizard again:

```bash
rm config/.installed
```

Or manually delete the file `config/.installed` from your installation directory.

### Step 2: Access the Wizard

Navigate to `/install/` in your web browser. The wizard will automatically detect if you have existing installation data.

### Step 3: Choose Your Re-run Mode

If existing data is detected, you'll see a warning screen with two options:

#### Option 1: Preserve Existing Data (Recommended)

- **What it does**: Keeps all existing database tables, users, settings, and content
- **Use when**: You want to update configuration without losing data
- **Behavior**: 
  - Steps with existing data will be skipped automatically
  - For example, if admin users exist, the admin creation step will be skipped
  - You can still update database credentials, SMTP settings, and brand settings

#### Option 2: Reset and Start Fresh (Destructive)

- **What it does**: Deletes ALL existing database tables and data
- **Use when**: You want a completely fresh installation
- **Warning**: This action cannot be undone. All data will be permanently lost.
- **Behavior**:
  - All database tables are dropped
  - The wizard runs as if it were a fresh installation
  - You'll need to recreate admin users and reconfigure everything

## What Gets Preserved vs Reset

### Preserve Mode

| Component | Behavior |
|-----------|----------|
| Database Tables | Kept intact |
| Users | Preserved (admin creation step skipped) |
| Settings | Can be updated |
| Content (Blog, Case Studies, etc.) | Preserved |
| Orders & Subscriptions | Preserved |
| Uploaded Files | Preserved |

### Reset Mode

| Component | Behavior |
|-----------|----------|
| Database Tables | Dropped and recreated |
| Users | Deleted (must recreate) |
| Settings | Cleared (must reconfigure) |
| Content | Deleted |
| Orders & Subscriptions | Deleted |
| Uploaded Files | Preserved (not in database) |

## Technical Details

### Detection Logic

The wizard detects existing data by checking:

1. Database connection can be established
2. `users` table exists and contains records
3. `settings` table exists and contains records

### Session Management

The re-run mode is stored in the PHP session:

- `$_SESSION['wizard_rerun_mode']` = `'preserve'` or `'reset'`
- Cleared automatically after installation completes

### Step Skipping (Preserve Mode)

When in preserve mode, steps are automatically skipped if:

- **Admin Creation**: Users table has existing records
- **Other steps**: Always run to allow configuration updates

## Safety Features

1. **Confirmation Required**: Reset mode requires double confirmation
2. **Detailed Warning**: Clear explanation of what will be lost
3. **Cancel Option**: Can exit wizard without making changes
4. **Lock File Protection**: Wizard only accessible when lock file is removed

## Troubleshooting

### "Cannot access wizard after removing lock file"

- Check file permissions on the `install/` directory
- Ensure `.htaccess` is not blocking access
- Clear browser cache and try again

### "Reset mode fails to drop tables"

- Check database user has DROP TABLE privileges
- Verify database connection is working
- Check error logs for specific SQL errors

### "Preserve mode still shows all steps"

- This is expected behavior
- Steps with existing data will show a skip message
- You can still update configuration in other steps

## Best Practices

1. **Backup First**: Always backup your database before re-running the wizard
2. **Use Preserve Mode**: Unless you specifically need a fresh start
3. **Test in Staging**: Test the re-run process in a staging environment first
4. **Document Changes**: Keep track of what configuration changes you make

## Example Scenarios

### Scenario 1: Update SMTP Settings

1. Remove lock file
2. Access wizard
3. Choose "Preserve Existing Data"
4. Skip through steps until SMTP configuration
5. Update SMTP settings
6. Complete wizard

### Scenario 2: Migrate to New Database

1. Backup existing database
2. Remove lock file
3. Access wizard
4. Choose "Reset and Start Fresh"
5. Enter new database credentials
6. Complete wizard with new configuration

### Scenario 3: Fix Broken Configuration

1. Remove lock file
2. Access wizard
3. Choose "Preserve Existing Data"
4. Update the problematic configuration
5. Complete wizard

## Security Considerations

- Only system administrators should have access to remove the lock file
- The wizard has no authentication (it's pre-auth setup)
- Ensure the server is not publicly accessible during re-run
- Consider using `.htaccess` to restrict access by IP during re-run

## Support

If you encounter issues with the re-run capability:

1. Check the error logs in `storage/logs/`
2. Verify database credentials are correct
3. Ensure file permissions are set correctly
4. Contact support with specific error messages
