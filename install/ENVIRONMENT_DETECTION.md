# Environment Detection and Adaptation

The Installation Wizard includes automatic environment detection to provide an optimal setup experience on both localhost and production environments.

## Features

### 1. Automatic Environment Detection

The system automatically detects whether it's running on:
- **Localhost**: Development environments (XAMPP, MAMP, WAMP, local domains)
- **Production**: Live hosting environments (Hostinger, shared hosting, VPS, etc.)

### 2. Detection Criteria

The wizard identifies localhost environments by checking:
- Server name: `localhost`, `127.0.0.1`, `::1`, `localhost.localdomain`
- Server IP addresses: `127.0.0.1`, `::1`, `0.0.0.0`
- Local development domains: `.local`, `.test`, `.dev`
- Development server software: XAMPP, MAMP, WAMP indicators

### 3. Environment-Specific Adaptations

#### File Permissions
- **Localhost**: More relaxed permissions (0644 for config, 0755 for uploads)
- **Production**: Stricter permissions (0600 for config, 0755 for uploads)
- **Windows**: Permissions are set but ignored by the OS

#### Security Warnings
- **Production without HTTPS**: High severity warning
- **Production with display_errors enabled**: Medium severity warning
- **Production with default/empty database password**: High severity warning
- **Localhost**: Informational message about relaxed security

#### Path Resolution
- Automatic normalization of path separators (\ to /)
- Consistent path resolution across different operating systems
- Support for both absolute and relative paths

## Usage

### In Installation Wizard Steps

Environment information is automatically displayed at the top of the database configuration step:

```php
$envInfo = $installationService->getEnvironmentInfo();
```

This provides:
- Environment type (localhost/production)
- Server software and version
- PHP version and OS
- HTTPS status
- Security warnings

### Available Methods

#### `detectEnvironment(): string`
Returns either `'localhost'` or `'production'`

```php
$environment = $installationService->detectEnvironment();
// Returns: 'localhost' or 'production'
```

#### `isLocalhost(): bool`
Check if running on localhost

```php
if ($installationService->isLocalhost()) {
    // Running on localhost
}
```

#### `isProduction(): bool`
Check if running on production

```php
if ($installationService->isProduction()) {
    // Running on production
}
```

#### `getEnvironmentInfo(): array`
Get comprehensive environment information

```php
$info = $installationService->getEnvironmentInfo();
// Returns array with:
// - environment: 'localhost' or 'production'
// - is_localhost: bool
// - is_production: bool
// - server_software: string
// - php_version: string
// - php_os: string
// - is_windows: bool
// - is_https: bool
// - server_name: string
// - document_root: string
// - warnings: array
```

#### `getEnvironmentWarnings(): array`
Get environment-specific warnings

```php
$warnings = $installationService->getEnvironmentWarnings();
// Returns array of warnings with:
// - type: 'security', 'info', etc.
// - severity: 'high', 'medium', 'low'
// - message: string
```

#### `getEnvironmentPath(string $relativePath): string`
Get environment-adapted absolute path

```php
$configPath = $installationService->getEnvironmentPath('config');
// Returns: /full/path/to/config
```

#### `getEnvironmentPermissions(string $fileType): int`
Get appropriate file permissions for environment

```php
$configPerms = $installationService->getEnvironmentPermissions('config');
// Returns: 0600 (production) or 0644 (localhost)

$uploadPerms = $installationService->getEnvironmentPermissions('upload');
// Returns: 0755 (both environments)
```

#### `writeConfigWithEnvironmentPermissions(string $filePath, string $content): bool`
Write configuration file with appropriate permissions

```php
$result = $installationService->writeConfigWithEnvironmentPermissions(
    '/path/to/config.php',
    $configContent
);
```

## Testing

### Manual Testing

A test page is available at `/install/test-environment.php` that displays:
- Detected environment type
- All environment information
- Security warnings
- File permissions for different file types
- Path resolution examples
- Server variables

Access it by navigating to: `http://your-domain/install/test-environment.php`

### Automated Testing

Unit tests are available in `tests/Unit/Services/InstallationServiceEnvironmentTest.php`

Run tests with:
```bash
./vendor/bin/phpunit tests/Unit/Services/InstallationServiceEnvironmentTest.php
```

Tests cover:
- Localhost detection with various server names
- Production detection
- Path resolution
- Permission calculation
- Environment info structure
- Warning generation
- HTTPS detection

## Environment-Specific Behavior

### Localhost (XAMPP/MAMP/WAMP)

**Detected when:**
- Server name is `localhost`, `127.0.0.1`, or `::1`
- Domain ends with `.local`, `.test`, or `.dev`
- Server software contains XAMPP, MAMP, or WAMP

**Behavior:**
- Relaxed file permissions
- Informational messages instead of warnings
- No HTTPS requirement
- Simplified error messages

### Production (Hostinger/Shared Hosting/VPS)

**Detected when:**
- Server has public IP address
- Domain is not a local development domain
- Server software doesn't indicate local development

**Behavior:**
- Strict file permissions (0600 for config files)
- Security warnings for missing HTTPS
- Warnings for insecure configurations
- Detailed security recommendations

## Security Considerations

### Production Warnings

The system will warn about:
1. **Missing HTTPS** (High severity)
   - Recommendation: Install SSL certificate
   - Impact: Credentials transmitted in plain text

2. **Display Errors Enabled** (Medium severity)
   - Recommendation: Disable in php.ini
   - Impact: Sensitive information exposure

3. **Default/Empty Database Password** (High severity)
   - Recommendation: Use strong password
   - Impact: Unauthorized database access

### File Permissions

- **Config files** (`.env`): 0600 on production (owner read/write only)
- **Upload directories**: 0755 (owner full, others read/execute)
- **General files**: 0644 (owner write, all read)

## Troubleshooting

### Environment Detected Incorrectly

If the wizard detects the wrong environment:

1. Check server variables:
   - `$_SERVER['SERVER_NAME']`
   - `$_SERVER['SERVER_ADDR']`
   - `$_SERVER['REMOTE_ADDR']`

2. Use the test page to see detection details:
   - Navigate to `/install/test-environment.php`

3. Common issues:
   - Local domain not ending in `.local`, `.test`, or `.dev`
   - Reverse proxy changing server variables
   - Custom server configuration

### File Permission Issues

If file permissions are not being set correctly:

1. **On Windows**: Permissions are ignored by the OS
2. **On Linux/Unix**: Check if PHP has permission to set file modes
3. **Shared Hosting**: Some hosts restrict `chmod()` operations

### Path Resolution Issues

If paths are not resolving correctly:

1. Check that `__DIR__` is available
2. Verify file system structure matches expected layout
3. Check for symlinks that might affect path resolution

## Requirements Validation

This implementation satisfies the following requirements:

- **6.1**: Installation wizard functions on localhost ✓
- **6.2**: Installation wizard functions on Hostinger/production ✓
- **6.3**: Environment detection adapts file paths appropriately ✓
- **6.4**: Configuration files use appropriate permissions for environment ✓

## Future Enhancements

Potential improvements for future versions:

1. **Custom Environment Detection**: Allow manual override via config
2. **Additional Environments**: Support for staging, testing environments
3. **Cloud Platform Detection**: Specific detection for AWS, Azure, GCP
4. **Container Detection**: Detect Docker, Kubernetes environments
5. **Performance Optimization**: Cache environment detection results
