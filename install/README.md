# SellerPortal System - Installation Wizard

## Table of Contents

1. [Overview](#overview)
2. [System Requirements](#system-requirements)
3. [Quick Start](#quick-start)
4. [Installation Steps](#installation-steps)
5. [Dual-Environment Configuration](#dual-environment-configuration)
6. [Environment-Specific Instructions](#environment-specific-instructions)
7. [Troubleshooting](#troubleshooting)
8. [Manual Installation](#manual-installation)
9. [Re-running the Wizard](#re-running-the-wizard)
10. [Security Considerations](#security-considerations)
11. [Support](#support)

---

## Overview

The SellerPortal System Installation Wizard is a web-based setup interface that guides you through the initial configuration of your system. It automates the process of:

- Database connection setup
- Database schema migration
- Administrator account creation
- Email (SMTP) configuration
- Brand and business information setup

The wizard is designed to work seamlessly on both localhost development environments (XAMPP, MAMP, WAMP) and production hosting environments (Hostinger, shared hosting, VPS).

### Key Features

- **Zero Configuration Files**: No need to manually edit configuration files
- **Environment Detection**: Automatically adapts to localhost or production environments
- **Step-by-Step Guidance**: Clear progress indication and validation at each step
- **Error Recovery**: Graceful error handling with the ability to retry failed steps
- **Re-run Capability**: Can be re-run to update configuration or reset the system
- **Security First**: Implements best practices for credential storage and access control

---

## System Requirements

### Minimum Requirements

- **PHP**: 7.4 or higher (8.0+ recommended)
- **Database**: MySQL 5.7+ or MariaDB 10.2+
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP Extensions**:
  - PDO
  - pdo_mysql
  - mbstring
  - openssl
  - json
  - fileinfo
  - gd or imagick (for image processing)

### Recommended Requirements

- **PHP**: 8.1 or higher
- **Database**: MySQL 8.0+ or MariaDB 10.6+
- **Memory**: 256MB PHP memory limit
- **Disk Space**: 500MB minimum
- **SSL Certificate**: For production environments

### Server Configuration

- **mod_rewrite** (Apache) or equivalent URL rewriting capability
- **File Permissions**: Write access to `config/`, `storage/`, and `uploads/` directories
- **Session Support**: PHP sessions must be enabled
- **.htaccess Support**: For Apache servers

---

## Quick Start

### For Localhost (XAMPP/MAMP/WAMP)

1. Extract the SellerPortal System files to your web server directory
   - XAMPP: `C:\xampp\htdocs\karyalay\`
   - MAMP: `/Applications/MAMP/htdocs/karyalay/`
   - WAMP: `C:\wamp64\www\karyalay\`

2. Create a MySQL database:
   ```sql
   CREATE DATABASE karyalay_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. Start your web server and MySQL

4. Navigate to `http://localhost/karyalay/` in your browser

5. You'll be automatically redirected to the installation wizard

6. Follow the on-screen instructions

### For Production (Hostinger/Shared Hosting)

1. Upload the SellerPortal System files to your hosting account
   - Via FTP/SFTP to `public_html/` or your domain directory
   - Or use the hosting control panel's file manager

2. Create a MySQL database through your hosting control panel

3. Navigate to your domain (e.g., `https://yourdomain.com/`)

4. You'll be automatically redirected to the installation wizard

5. Follow the on-screen instructions

---

## Installation Steps

The installation wizard consists of 5 main steps:

### Step 1: Database Configuration

**What you'll need:**
- Database host (usually `localhost`)
- Database name
- Database username
- Database password
- Database port (default: 3306)

**Instructions:**
1. Enter your database credentials
2. Click "Test Connection" to verify the connection
3. If successful, click "Next" to proceed
4. If failed, check your credentials and try again

**Common Issues:**
- **Connection refused**: Ensure MySQL is running
- **Access denied**: Verify username and password
- **Unknown database**: Create the database first

### Step 2: Database Migrations

**What happens:**
- The wizard executes all database migrations
- Creates tables for users, orders, subscriptions, etc.
- Sets up indexes and relationships

**Instructions:**
1. Review the list of migrations to be executed
2. Click "Run Migrations"
3. Wait for all migrations to complete
4. Verify all migrations show "Success"
5. Click "Next" to proceed

**Note**: This step is automatic and requires no input. If any migration fails, the wizard will display the error and prevent proceeding.

### Step 3: Administrator Account

**What you'll need:**
- Your full name
- Email address (for admin login)
- Strong password (minimum 8 characters)

**Instructions:**
1. Enter your administrator details
2. Choose a strong password (the wizard shows a strength indicator)
3. Confirm your password
4. Click "Create Admin Account"
5. **Important**: Save your credentials securely

**Password Requirements:**
- Minimum 8 characters
- Mix of uppercase and lowercase letters
- At least one number
- At least one special character (recommended)

### Step 4: SMTP Configuration (Optional)

**What you'll need:**
- SMTP server host (e.g., `smtp.gmail.com`)
- SMTP port (usually 587 for TLS, 465 for SSL)
- SMTP username
- SMTP password
- Encryption type (TLS/SSL/None)
- From email address
- From name

**Instructions:**
1. Enter your SMTP server details
2. Click "Test SMTP" to send a test email
3. Check your inbox for the test email
4. If successful, click "Next"
5. If you don't have SMTP, click "Skip" (you can configure it later)

**Popular SMTP Providers:**
- **Gmail**: `smtp.gmail.com`, port 587, TLS
- **Outlook**: `smtp-mail.outlook.com`, port 587, TLS
- **SendGrid**: `smtp.sendgrid.net`, port 587, TLS
- **Mailgun**: `smtp.mailgun.org`, port 587, TLS

**Note**: If you skip SMTP configuration, email features (password reset, notifications) will not work until you configure it later.

### Step 5: Brand Settings

**What you'll need:**
- Company/Business name
- Tagline or slogan
- Contact email
- Contact phone
- Business address
- Logo image (optional, JPG/PNG/GIF/SVG, max 2MB)

**Instructions:**
1. Enter your business information
2. Upload your logo (optional)
3. Preview your logo
4. Click "Save Settings"
5. Click "Complete Installation"

**Logo Requirements:**
- Formats: JPG, PNG, GIF, SVG
- Maximum size: 2MB
- Recommended dimensions: 200x60 pixels (or similar aspect ratio)

### Completion

After completing all steps:
- A lock file is created to prevent re-running the wizard
- You're redirected to the admin panel login
- Use the credentials you created in Step 3 to log in

---

## Dual-Environment Configuration

The SellerPortal System supports dual-environment database configuration, allowing you to configure both localhost (development) and live (production) database credentials during installation. This enables a seamless "git push and go live" workflow where the same codebase works on both environments without manual configuration changes.

### How It Works

The system stores two sets of database credentials in your `.env` file:

- **DB_LOCAL_*** - Credentials for your local development environment
- **DB_LIVE_*** - Credentials for your production hosting environment

When the application starts, it automatically:
1. Detects the current environment (localhost or production)
2. Selects the appropriate credentials
3. Sets the active `DB_*` variables for database connections

### Environment Detection

The system detects **localhost** when the server name or IP matches:
- `127.0.0.1` or `::1` (IPv4/IPv6 loopback)
- `localhost`
- Domains ending in `.local`, `.test`, or `.dev`

All other environments are treated as **production/live**.

### Credential Resolution Logic

The system follows this priority when resolving credentials:

| Detected Environment | Live Credentials | Local Credentials | Result |
|---------------------|------------------|-------------------|--------|
| Production | Available | Any | Uses Live |
| Production | Empty/Invalid | Available | Falls back to Local |
| Localhost | Any | Available | Uses Local |
| Localhost | Available | Empty/Invalid | Falls back to Live |
| Any | Empty | Empty | Redirects to Install Wizard |

### Setting Up Dual-Environment

#### During Installation

1. In the Database Configuration step, select your primary environment (Localhost or Live)
2. Enter credentials for your selected environment
3. Check "Also configure Live credentials" (or Local, depending on selection)
4. Enter credentials for the secondary environment
5. Test both connections
6. Continue with installation

#### After Installation

1. Go to Admin Panel → Settings → Database Settings
2. Add or update Live credentials
3. Test the connection before saving
4. Changes take effect on the next request

### Common Scenarios

#### Scenario 1: Local Development First

You're developing locally and will deploy to production later.

1. During installation, select "Localhost"
2. Enter your local database credentials (e.g., XAMPP defaults)
3. Skip live credentials for now
4. After deployment, add live credentials via Admin Settings

#### Scenario 2: Production Setup with Local Testing

You have production hosting ready and want to test locally too.

1. During installation, select "Live"
2. Enter your production database credentials
3. Check "Also configure Local credentials"
4. Enter your local development credentials
5. Both environments are ready immediately

#### Scenario 3: Forcing Localhost Credentials

You want to test locally even though live credentials are configured.

**Option 1: Comment out live credentials in `.env`**
```env
# DB_LIVE_HOST=production.host.com
# DB_LIVE_PORT=3306
# DB_LIVE_NAME=prod_db
# DB_LIVE_USER=prod_user
# DB_LIVE_PASS=prod_password
```

**Option 2: Clear live host value**
```env
DB_LIVE_HOST=
```

When live credentials are empty or commented out, the system automatically uses localhost credentials regardless of the detected environment.

### .env File Structure

After dual-environment setup, your `.env` file will contain:

```env
# =============================================================================
# DUAL ENVIRONMENT DATABASE CONFIGURATION
# =============================================================================
# The system automatically detects the environment and uses appropriate credentials.
# - On localhost: Uses DB_LOCAL_* credentials
# - On production: Uses DB_LIVE_* credentials (if available, else falls back to local)

# -----------------------------------------------------------------------------
# ACTIVE DATABASE CREDENTIALS (Auto-populated based on environment)
# -----------------------------------------------------------------------------
DB_HOST=localhost
DB_PORT=3306
DB_NAME=myapp_db
DB_USER=root
DB_PASS=
DB_UNIX_SOCKET=

# -----------------------------------------------------------------------------
# LOCALHOST CREDENTIALS (Development Environment)
# -----------------------------------------------------------------------------
DB_LOCAL_HOST=localhost
DB_LOCAL_PORT=3306
DB_LOCAL_NAME=myapp_dev
DB_LOCAL_USER=root
DB_LOCAL_PASS=
DB_LOCAL_UNIX_SOCKET=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock

# -----------------------------------------------------------------------------
# LIVE CREDENTIALS (Production Environment)
# -----------------------------------------------------------------------------
DB_LIVE_HOST=mysql.hostinger.com
DB_LIVE_PORT=3306
DB_LIVE_NAME=u123456789_myapp
DB_LIVE_USER=u123456789_admin
DB_LIVE_PASS=SecurePassword123
DB_LIVE_UNIX_SOCKET=
```

### Troubleshooting Dual-Environment

#### "Wrong database connected"

**Symptoms**: Application connects to wrong database (local instead of live, or vice versa)

**Solutions**:
1. Check environment detection: Visit `/install/test-environment.php`
2. Verify credentials are in correct sections (DB_LOCAL_* vs DB_LIVE_*)
3. Clear any cached configuration
4. Check if live credentials are empty (causes fallback to local)

#### "Credentials not switching"

**Symptoms**: Same credentials used regardless of environment

**Solutions**:
1. Ensure both credential sets are properly configured
2. Check for typos in environment variable names
3. Verify the `.env` file is being read (check file permissions)
4. Restart web server to clear any cached environment

#### "Connection works locally but fails on production"

**Symptoms**: Local development works, production deployment fails

**Solutions**:
1. Verify DB_LIVE_* credentials are correct
2. Check if production host allows remote connections
3. Verify database exists on production server
4. Check firewall rules on production server
5. Some hosts require specific hostnames (not `localhost`)

### Best Practices

1. **Always test both environments** before deploying
2. **Use different database names** for local and production to avoid confusion
3. **Keep production credentials secure** - never commit `.env` to version control
4. **Document your setup** for team members
5. **Use strong passwords** for production databases
6. **Backup before switching** environments in production

---

## Environment-Specific Instructions

### Localhost Development

#### XAMPP (Windows/Mac/Linux)

1. **Start Services**:
   - Open XAMPP Control Panel
   - Start Apache and MySQL

2. **Database Setup**:
   - Click "Admin" next to MySQL (opens phpMyAdmin)
   - Create new database: `karyalay_db`

3. **File Location**:
   - Place files in `C:\xampp\htdocs\karyalay\` (Windows)
   - Or `/Applications/XAMPP/htdocs/karyalay/` (Mac)

4. **Access**:
   - Navigate to `http://localhost/karyalay/`

#### MAMP (Mac)

1. **Start Services**:
   - Open MAMP application
   - Click "Start Servers"

2. **Database Setup**:
   - Click "Open WebStart page"
   - Go to phpMyAdmin
   - Create new database: `karyalay_db`

3. **File Location**:
   - Place files in `/Applications/MAMP/htdocs/karyalay/`

4. **Access**:
   - Navigate to `http://localhost:8888/karyalay/` (default MAMP port)

#### WAMP (Windows)

1. **Start Services**:
   - Click WAMP icon in system tray
   - Ensure icon is green (all services running)

2. **Database Setup**:
   - Left-click WAMP icon → phpMyAdmin
   - Create new database: `karyalay_db`

3. **File Location**:
   - Place files in `C:\wamp64\www\karyalay\`

4. **Access**:
   - Navigate to `http://localhost/karyalay/`

### Production Hosting

#### Hostinger

1. **Upload Files**:
   - Use File Manager or FTP
   - Upload to `public_html/` directory

2. **Database Setup**:
   - Go to Hosting → Databases
   - Click "Create Database"
   - Note the database name, username, and password

3. **File Permissions**:
   - Ensure `config/`, `storage/`, and `uploads/` are writable
   - Usually 755 for directories, 644 for files

4. **Access**:
   - Navigate to your domain (e.g., `https://yourdomain.com/`)

5. **SSL Certificate**:
   - Hostinger provides free SSL certificates
   - Enable SSL in the hosting control panel

#### cPanel Hosting

1. **Upload Files**:
   - Use File Manager or FTP client
   - Upload to `public_html/` or domain-specific directory

2. **Database Setup**:
   - Go to cPanel → MySQL Databases
   - Create database and user
   - Add user to database with all privileges

3. **File Permissions**:
   - Right-click directories → Change Permissions
   - Set `config/`, `storage/`, `uploads/` to 755

4. **Access**:
   - Navigate to your domain

#### VPS/Dedicated Server

1. **Upload Files**:
   - Use SFTP or SCP
   - Place in web server document root

2. **Database Setup**:
   ```bash
   mysql -u root -p
   CREATE DATABASE karyalay_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'karyalay_user'@'localhost' IDENTIFIED BY 'strong_password';
   GRANT ALL PRIVILEGES ON karyalay_db.* TO 'karyalay_user'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   ```

3. **File Permissions**:
   ```bash
   chmod 755 config/ storage/ uploads/
   chmod 644 config/.env
   ```

4. **Web Server Configuration**:
   - Configure Apache or Nginx virtual host
   - Enable mod_rewrite (Apache) or URL rewriting (Nginx)

---

## Troubleshooting

### Common Issues and Solutions

#### 1. "Cannot connect to database"

**Possible Causes:**
- MySQL server is not running
- Incorrect database credentials
- Database does not exist
- Firewall blocking connection

**Solutions:**
- Verify MySQL is running: `mysql -u root -p`
- Check credentials in hosting control panel
- Create the database if it doesn't exist
- For localhost, use `localhost` or `127.0.0.1` as host
- For some hosts, use `localhost:/tmp/mysql.sock` or specific socket path

#### 2. "Access denied for user"

**Possible Causes:**
- Wrong username or password
- User doesn't have permissions on the database
- User is restricted to specific host

**Solutions:**
- Verify credentials in hosting control panel
- Grant user permissions: `GRANT ALL PRIVILEGES ON database.* TO 'user'@'localhost';`
- Check if user is allowed to connect from the current host

#### 3. "Migration failed"

**Possible Causes:**
- SQL syntax error in migration file
- Table already exists (re-run scenario)
- Insufficient database privileges

**Solutions:**
- Check error message for specific SQL error
- If re-running, choose "Reset and Start Fresh" mode
- Verify user has CREATE, ALTER, DROP privileges
- Check database logs for detailed error

#### 4. "Cannot write to configuration file"

**Possible Causes:**
- Insufficient file permissions
- Directory doesn't exist
- Disk space full

**Solutions:**
- Set permissions: `chmod 755 config/`
- Verify directory exists
- Check disk space: `df -h`
- On shared hosting, check file ownership

#### 5. "SMTP test failed"

**Possible Causes:**
- Incorrect SMTP credentials
- Port blocked by firewall
- SSL/TLS configuration mismatch
- SMTP server requires app-specific password

**Solutions:**
- Verify SMTP credentials with your email provider
- Try different ports (587 for TLS, 465 for SSL, 25 for none)
- For Gmail, enable "Less secure app access" or use App Password
- Check if hosting provider blocks outgoing SMTP
- Try using hosting provider's SMTP server

#### 6. "Logo upload failed"

**Possible Causes:**
- File too large (>2MB)
- Invalid file format
- Upload directory not writable
- PHP upload limits too low

**Solutions:**
- Resize image to under 2MB
- Use JPG, PNG, GIF, or SVG format
- Set permissions: `chmod 755 uploads/branding/`
- Increase PHP limits in `php.ini`:
  ```ini
  upload_max_filesize = 5M
  post_max_size = 5M
  ```

#### 7. "Session expired" or "Lost progress"

**Possible Causes:**
- PHP session timeout
- Browser cleared cookies
- Server restarted

**Solutions:**
- Complete wizard in one session
- Don't close browser during installation
- Increase session timeout in `php.ini`:
  ```ini
  session.gc_maxlifetime = 3600
  ```

#### 8. "Wizard redirects to itself"

**Possible Causes:**
- Lock file not created
- File permissions prevent lock file creation
- Bootstrap check not working

**Solutions:**
- Manually create lock file: `touch config/.installed`
- Check permissions on `config/` directory
- Verify `.htaccess` is working
- Check web server error logs

#### 9. "500 Internal Server Error"

**Possible Causes:**
- PHP syntax error
- Missing PHP extensions
- .htaccess configuration error
- Insufficient memory

**Solutions:**
- Check error logs: `storage/logs/errors-*.log`
- Verify all required PHP extensions are installed
- Test without .htaccess (rename temporarily)
- Increase PHP memory: `memory_limit = 256M` in `php.ini`

#### 10. "Blank page" or "White screen"

**Possible Causes:**
- PHP fatal error
- Display errors disabled
- Memory exhausted

**Solutions:**
- Enable error display in `php.ini`:
  ```ini
  display_errors = On
  error_reporting = E_ALL
  ```
- Check error logs
- Increase memory limit
- Check file permissions

### Environment-Specific Issues

#### Windows/XAMPP

- **Issue**: Database socket error
  - **Solution**: Use `localhost` instead of `127.0.0.1` or vice versa

- **Issue**: File permissions not working
  - **Solution**: Windows ignores Unix permissions; ensure user has write access

#### Mac/MAMP

- **Issue**: Port conflicts
  - **Solution**: MAMP uses port 8888 by default; access via `http://localhost:8888/`

- **Issue**: MySQL socket path
  - **Solution**: Use `/Applications/MAMP/tmp/mysql/mysql.sock` if needed

#### Linux/Production

- **Issue**: SELinux blocking file writes
  - **Solution**: Adjust SELinux policies or set directories to permissive mode

- **Issue**: Apache not reading .htaccess
  - **Solution**: Enable `AllowOverride All` in Apache configuration

### Debugging Tips

1. **Enable Error Logging**:
   ```php
   // Add to config/bootstrap.php temporarily
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```

2. **Check PHP Info**:
   - Create `info.php` with `<?php phpinfo(); ?>`
   - Access via browser to see PHP configuration
   - Delete file after checking

3. **Test Database Connection**:
   ```php
   <?php
   try {
       $pdo = new PDO('mysql:host=localhost;dbname=karyalay_db', 'username', 'password');
       echo "Connection successful!";
   } catch (PDOException $e) {
       echo "Connection failed: " . $e->getMessage();
   }
   ?>
   ```

4. **Check File Permissions**:
   ```bash
   ls -la config/ storage/ uploads/
   ```

5. **View Error Logs**:
   - Application logs: `storage/logs/errors-*.log`
   - Apache logs: `/var/log/apache2/error.log` (Linux)
   - PHP logs: Check `php.ini` for `error_log` location

---

## Manual Installation

If the wizard fails or you prefer manual installation, follow these steps:

### Step 1: Configure Database

1. Copy `.env.example` to `.env` in the root directory:
   ```bash
   cp .env.example .env
   ```

2. Edit `.env` and configure your database credentials:
   ```env
   # For localhost development
   DB_LOCAL_HOST=localhost
   DB_LOCAL_PORT=3306
   DB_LOCAL_NAME=karyalay_db
   DB_LOCAL_USER=root
   DB_LOCAL_PASS=
   DB_LOCAL_UNIX_SOCKET=

   # For production (optional, add later if needed)
   DB_LIVE_HOST=
   DB_LIVE_PORT=3306
   DB_LIVE_NAME=
   DB_LIVE_USER=
   DB_LIVE_PASS=
   DB_LIVE_UNIX_SOCKET=
   ```

3. Set file permissions:
   ```bash
   chmod 600 .env
   ```

**Note**: The system will automatically detect your environment and use the appropriate credentials. See [Dual-Environment Configuration](#dual-environment-configuration) for details.

### Step 2: Run Migrations

1. Access your database via phpMyAdmin or command line

2. Execute migration files in order from `database/migrations/`:
   ```bash
   mysql -u username -p karyalay_db < database/migrations/001_create_users_table.sql
   mysql -u username -p karyalay_db < database/migrations/002_create_sessions_table.sql
   # ... continue for all migration files
   ```

   Or execute all at once:
   ```bash
   for file in database/migrations/*.sql; do
       mysql -u username -p karyalay_db < "$file"
   done
   ```

### Step 3: Create Admin User

1. Generate password hash:
   ```php
   <?php
   echo password_hash('your_password', PASSWORD_BCRYPT);
   ?>
   ```

2. Insert admin user:
   ```sql
   INSERT INTO users (name, email, password, role, created_at)
   VALUES ('Admin Name', 'admin@example.com', '$2y$10$...', 'ADMIN', NOW());
   ```

### Step 4: Configure SMTP (Optional)

Insert SMTP settings into the `settings` table:

```sql
INSERT INTO settings (setting_key, setting_value) VALUES
('smtp_host', 'smtp.gmail.com'),
('smtp_port', '587'),
('smtp_username', 'your_email@gmail.com'),
('smtp_password', 'your_password'),
('smtp_encryption', 'tls'),
('smtp_from_address', 'noreply@yourdomain.com'),
('smtp_from_name', 'Your Company');
```

### Step 5: Configure Brand Settings

Insert brand settings into the `settings` table:

```sql
INSERT INTO settings (setting_key, setting_value) VALUES
('company_name', 'Your Company'),
('company_tagline', 'Your Tagline'),
('contact_email', 'contact@yourdomain.com'),
('contact_phone', '+1234567890'),
('contact_address', 'Your Address');
```

### Step 6: Create Lock File

Create the installation lock file:

```bash
echo '{"installed_at":"'$(date -u +"%Y-%m-%dT%H:%M:%SZ")'","version":"1.0.0"}' > config/.installed
chmod 644 config/.installed
```

### Step 7: Set Permissions

```bash
chmod 755 config/ storage/ uploads/
chmod 644 config/.env
chmod 755 storage/cache/ storage/logs/
chmod 755 uploads/branding/ uploads/media/
```

### Step 8: Test Installation

1. Navigate to your domain
2. You should see the main website (not the wizard)
3. Go to `/admin/` and log in with your admin credentials

---

## Re-running the Wizard

The wizard can be re-run to update configuration or reset the system. See [RERUN_GUIDE.md](RERUN_GUIDE.md) for detailed instructions.

### Quick Re-run Steps

1. **Remove lock file**:
   ```bash
   rm config/.installed
   ```

2. **Access wizard**:
   - Navigate to `/install/` in your browser

3. **Choose mode**:
   - **Preserve Existing Data**: Updates configuration without losing data
   - **Reset and Start Fresh**: Deletes all data and starts over (destructive)

4. **Complete wizard**:
   - Follow the same steps as initial installation

### When to Re-run

- Update database credentials
- Reconfigure SMTP settings
- Change brand information
- Recover from configuration errors
- Migrate to new database

### Important Notes

- **Backup first**: Always backup your database before re-running
- **Preserve mode**: Recommended for configuration updates
- **Reset mode**: Only use if you want to completely start over
- **Data loss**: Reset mode permanently deletes all data

---

## Security Considerations

### Production Environment

1. **Use HTTPS**:
   - Install SSL certificate
   - Force HTTPS in `.htaccess`:
     ```apache
     RewriteEngine On
     RewriteCond %{HTTPS} off
     RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
     ```

2. **Secure Configuration Files**:
   ```bash
   chmod 600 .env
   chmod 644 config/.installed
   ```

3. **Disable Error Display**:
   ```ini
   display_errors = Off
   log_errors = On
   error_log = /path/to/logs/php-error.log
   ```

4. **Strong Database Password**:
   - Use complex passwords (16+ characters)
   - Mix of letters, numbers, symbols
   - Don't use default passwords

5. **Restrict File Access**:
   - Ensure `.htaccess` files are in place
   - Protect sensitive directories
   - Don't expose configuration files

6. **Remove Installation Wizard** (Optional):
   - After installation, you can remove the `/install/` directory
   - Or ensure `.htaccess` blocks access when installed

### Localhost Environment

- Localhost has relaxed security for development
- Don't use localhost credentials in production
- Test security features before deploying

### Post-Installation

1. **Change default admin password** if you used a simple one during setup
2. **Review user permissions** and create additional admin users if needed
3. **Configure firewall** to allow only necessary ports
4. **Enable logging** and monitor for suspicious activity
5. **Regular backups** of database and files
6. **Keep software updated** (PHP, MySQL, web server)

---

## Support

### Documentation

- **Environment Detection**: [ENVIRONMENT_DETECTION.md](ENVIRONMENT_DETECTION.md)
- **Re-run Guide**: [RERUN_GUIDE.md](RERUN_GUIDE.md)
- **Message Helpers**: [includes/README.md](includes/README.md)

### Getting Help

1. **Check Error Logs**:
   - Application: `storage/logs/errors-*.log`
   - Web server: Check Apache/Nginx error logs
   - PHP: Check PHP error log location

2. **Review Documentation**:
   - Read this README thoroughly
   - Check troubleshooting section
   - Review environment-specific guides

3. **Test Environment**:
   - Use `/install/test-environment.php` to check environment detection
   - Verify PHP extensions and configuration

4. **Community Support**:
   - Check project documentation
   - Search for similar issues
   - Contact system administrator

### Reporting Issues

When reporting issues, include:

1. **Environment Information**:
   - Operating system
   - Web server (Apache/Nginx) and version
   - PHP version
   - MySQL/MariaDB version
   - Hosting provider (if applicable)

2. **Error Details**:
   - Exact error message
   - Steps to reproduce
   - Screenshots (if applicable)
   - Relevant log entries

3. **Configuration**:
   - PHP configuration (from `phpinfo()`)
   - File permissions
   - .htaccess contents (if relevant)

### Additional Resources

- **PHP Documentation**: https://www.php.net/docs.php
- **MySQL Documentation**: https://dev.mysql.com/doc/
- **Apache Documentation**: https://httpd.apache.org/docs/
- **Nginx Documentation**: https://nginx.org/en/docs/

---

## Appendix

### File Structure

```
karyalay/
├── admin/                  # Admin panel
├── app/                    # Customer portal
├── assets/                 # CSS, JS, images
├── classes/                # PHP classes
│   ├── Models/            # Data models
│   └── Services/          # Business logic
├── config/                 # Configuration files
│   ├── .installed         # Lock file (created by wizard)
│   └── bootstrap.php      # Application bootstrap
├── database/              # Database files
│   └── migrations/        # SQL migration files
├── install/               # Installation wizard
│   ├── api/              # API endpoints
│   ├── assets/           # Wizard CSS/JS
│   ├── includes/         # Helper functions
│   ├── steps/            # Wizard step files
│   ├── templates/        # Wizard templates
│   └── index.php         # Wizard controller
├── public/                # Public website
├── storage/               # Logs and cache
├── templates/             # HTML templates
├── uploads/               # User uploads
├── .env                   # Environment configuration
└── composer.json          # PHP dependencies
```

### Database Tables

Created by migrations:

- `users` - User accounts
- `sessions` - User sessions
- `password_reset_tokens` - Password reset tokens
- `plans` - Subscription plans
- `orders` - Customer orders
- `ports` - Port allocations
- `subscriptions` - Active subscriptions
- `port_allocation_logs` - Port allocation history
- `tickets` - Support tickets
- `ticket_messages` - Ticket messages
- `modules` - System modules
- `features` - Feature list
- `blog_posts` - Blog content
- `case_studies` - Case studies
- `leads` - Sales leads
- `media_assets` - Media library
- `settings` - System settings

### Environment Variables

Key variables in `.env`:

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Active Database (auto-populated from LOCAL or LIVE based on environment)
DB_HOST=localhost
DB_PORT=3306
DB_NAME=karyalay_db
DB_USER=username
DB_PASS=password
DB_UNIX_SOCKET=

# Localhost Database Credentials
DB_LOCAL_HOST=localhost
DB_LOCAL_PORT=3306
DB_LOCAL_NAME=karyalay_dev
DB_LOCAL_USER=root
DB_LOCAL_PASS=
DB_LOCAL_UNIX_SOCKET=

# Live/Production Database Credentials
DB_LIVE_HOST=mysql.hostinger.com
DB_LIVE_PORT=3306
DB_LIVE_NAME=u123456789_karyalay
DB_LIVE_USER=u123456789_admin
DB_LIVE_PASS=SecurePassword123
DB_LIVE_UNIX_SOCKET=

# Session
SESSION_LIFETIME=120

# Logging
LOG_LEVEL=error
```

**Note**: The system automatically detects the environment and uses either `DB_LOCAL_*` or `DB_LIVE_*` credentials. See [Dual-Environment Configuration](#dual-environment-configuration) for details.

### PHP Extensions Required

- `pdo` - Database abstraction
- `pdo_mysql` - MySQL driver
- `mbstring` - Multibyte string handling
- `openssl` - Encryption
- `json` - JSON handling
- `fileinfo` - File type detection
- `gd` or `imagick` - Image processing
- `curl` - HTTP requests
- `zip` - Archive handling

### Recommended PHP Settings

```ini
memory_limit = 256M
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
max_input_time = 300
session.gc_maxlifetime = 3600
```

---

## License

Copyright © 2025 SellerPortal System. All rights reserved.

---

## Changelog

### Version 1.0.0 (2025-12-07)

- Initial release
- Web-based installation wizard
- Environment detection (localhost/production)
- Database configuration and migration
- Admin account creation
- SMTP configuration
- Brand settings
- Re-run capability
- Comprehensive error handling

---

**Thank you for choosing SellerPortal System!**

For the latest updates and documentation, visit the project repository.
