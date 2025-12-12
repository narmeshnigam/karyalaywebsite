<?php

namespace Karyalay\Services;

use PDO;
use PDOException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Karyalay\Database\Migration;
use Karyalay\Models\User;
use Karyalay\Models\Setting;

/**
 * Installation Service
 * 
 * Handles the installation wizard operations including database setup,
 * migrations, admin user creation, SMTP configuration, and brand settings.
 */
class InstallationService
{
    private const LOCK_FILE_PATH = __DIR__ . '/../../config/.installed';
    private const SESSION_KEY = 'installation_wizard_progress';

    /**
     * Check if system is already installed
     * 
     * @return bool True if installed, false otherwise
     */
    public function isInstalled(): bool
    {
        return file_exists(self::LOCK_FILE_PATH);
    }

    /**
     * Test database connection with provided credentials
     * 
     * @param array $credentials Database credentials (host, port, database, username, password, unix_socket)
     * @return array Returns ['success' => bool, 'error' => string|null]
     */
    public function testDatabaseConnection(array $credentials): array
    {
        try {
            // Build DSN based on whether unix_socket is provided
            if (!empty($credentials['unix_socket']) && file_exists($credentials['unix_socket'])) {
                $dsn = sprintf(
                    'mysql:unix_socket=%s;dbname=%s;charset=utf8mb4',
                    $credentials['unix_socket'],
                    $credentials['database']
                );
            } else {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    $credentials['host'],
                    $credentials['port'] ?? 3306,
                    $credentials['database']
                );
            }

            // Attempt connection
            $pdo = new PDO(
                $dsn,
                $credentials['username'],
                $credentials['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

            // Test with a simple query
            $pdo->query('SELECT 1');

            return [
                'success' => true,
                'error' => null
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Write database configuration to .env file
     * 
     * @param array $credentials Database credentials
     * @return bool True on success, false on failure
     */
    public function writeDatabaseConfig(array $credentials): bool
    {
        try {
            $envPath = __DIR__ . '/../../.env';
            
            // Read existing .env file or use .env.example as template
            if (file_exists($envPath)) {
                $envContent = file_get_contents($envPath);
            } else {
                $envExamplePath = __DIR__ . '/../../.env.example';
                if (file_exists($envExamplePath)) {
                    $envContent = file_get_contents($envExamplePath);
                } else {
                    $envContent = '';
                }
            }

            // Update database configuration values
            $envContent = $this->updateEnvValue($envContent, 'DB_HOST', $credentials['host']);
            $envContent = $this->updateEnvValue($envContent, 'DB_PORT', $credentials['port'] ?? 3306);
            $envContent = $this->updateEnvValue($envContent, 'DB_NAME', $credentials['database']);
            $envContent = $this->updateEnvValue($envContent, 'DB_USER', $credentials['username']);
            $envContent = $this->updateEnvValue($envContent, 'DB_PASS', $credentials['password']);
            $envContent = $this->updateEnvValue($envContent, 'DB_UNIX_SOCKET', $credentials['unix_socket'] ?? '');

            // Write to .env file with environment-appropriate permissions
            return $this->writeConfigWithEnvironmentPermissions($envPath, $envContent);
        } catch (\Exception $e) {
            error_log('Failed to write database config: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update or add an environment variable in .env content
     * 
     * @param string $content Current .env content
     * @param string $key Environment variable key
     * @param mixed $value Environment variable value
     * @return string Updated .env content
     */
    private function updateEnvValue(string $content, string $key, $value): string
    {
        // Escape special characters in value
        $escapedValue = $this->escapeEnvValue($value);
        
        // Check if key exists
        $pattern = "/^{$key}=.*/m";
        
        if (preg_match($pattern, $content)) {
            // Update existing key
            $content = preg_replace($pattern, "{$key}={$escapedValue}", $content);
        } else {
            // Add new key
            $content .= "\n{$key}={$escapedValue}";
        }

        return $content;
    }

    /**
     * Escape environment variable value
     * 
     * @param mixed $value Value to escape
     * @return string Escaped value
     */
    private function escapeEnvValue($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $value = (string) $value;

        // If value contains spaces or special characters, wrap in quotes
        if (preg_match('/[\s#]/', $value)) {
            return '"' . str_replace('"', '\\"', $value) . '"';
        }

        return $value;
    }

    /**
     * Execute all database migrations
     * 
     * @return array Returns ['success' => bool, 'results' => array, 'error' => string|null]
     */
    public function runMigrations(): array
    {
        try {
            // Get database connection using the newly written config
            $this->reloadEnvironmentVariables();
            
            $config = require __DIR__ . '/../../config/database.php';
            
            // Create PDO connection
            if (!empty($config['unix_socket']) && file_exists($config['unix_socket'])) {
                $dsn = sprintf(
                    'mysql:unix_socket=%s;dbname=%s;charset=%s',
                    $config['unix_socket'],
                    $config['database'],
                    $config['charset']
                );
            } else {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $config['host'],
                    $config['port'],
                    $config['database'],
                    $config['charset']
                );
            }

            $pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );

            // Run migrations
            $migrationsPath = __DIR__ . '/../../database/migrations';
            $migration = new Migration($pdo, $migrationsPath);
            $results = $migration->runAll();

            // Check if any migrations failed
            $hasFailures = false;
            foreach ($results as $result) {
                if (strpos($result, 'failed') !== false) {
                    $hasFailures = true;
                    break;
                }
            }

            return [
                'success' => !$hasFailures,
                'results' => $results,
                'error' => $hasFailures ? 'One or more migrations failed' : null
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'results' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Reload environment variables from .env file
     * 
     * @return void
     */
    private function reloadEnvironmentVariables(): void
    {
        $envPath = __DIR__ . '/../../.env';
        
        if (!file_exists($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse key=value
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                if (preg_match('/^"(.*)"$/', $value, $matches)) {
                    $value = $matches[1];
                }

                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }

    /**
     * Create root admin user
     * 
     * @param array $userData User data (name, email, password)
     * @return array Returns ['success' => bool, 'user' => array|null, 'error' => string|null]
     */
    public function createAdminUser(array $userData): array
    {
        try {
            // Validate required fields
            if (empty($userData['name']) || empty($userData['email']) || empty($userData['password'])) {
                return [
                    'success' => false,
                    'user' => null,
                    'error' => 'Name, email, and password are required'
                ];
            }

            // Validate email format
            if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'user' => null,
                    'error' => 'Invalid email format'
                ];
            }

            // Validate password strength
            if (strlen($userData['password']) < 8) {
                return [
                    'success' => false,
                    'user' => null,
                    'error' => 'Password must be at least 8 characters'
                ];
            }

            // Create user model
            $userModel = new User();

            // Check if email already exists
            if ($userModel->emailExists($userData['email'])) {
                return [
                    'success' => false,
                    'user' => null,
                    'error' => 'Email already exists'
                ];
            }

            // Create admin user
            $user = $userModel->create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'], // Will be hashed in User model
                'phone' => null, // Admin users don't require phone numbers
                'role' => 'ADMIN',
                'email_verified' => true
            ]);

            if (!$user) {
                return [
                    'success' => false,
                    'user' => null,
                    'error' => 'Failed to create admin user'
                ];
            }

            // Remove password_hash from response
            unset($user['password_hash']);

            return [
                'success' => true,
                'user' => $user,
                'error' => null
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'user' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test SMTP configuration by sending a test email
     * 
     * @param array $smtpConfig SMTP configuration
     * @return array Returns ['success' => bool, 'error' => string|null]
     */
    public function testSmtpConnection(array $smtpConfig): array
    {
        try {
            $mail = new PHPMailer(true);

            // Server settings
            $mail->isSMTP();
            $mail->Host = $smtpConfig['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $smtpConfig['smtp_username'];
            $mail->Password = $smtpConfig['smtp_password'];
            
            // Set encryption
            if ($smtpConfig['smtp_encryption'] === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($smtpConfig['smtp_encryption'] === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }
            
            $mail->Port = (int) $smtpConfig['smtp_port'];

            // Recipients
            $mail->setFrom($smtpConfig['smtp_from_address'], $smtpConfig['smtp_from_name']);
            $mail->addAddress($smtpConfig['smtp_from_address']); // Send to self

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'SMTP Test Email - Portal Installation';
            $mail->Body = '<p>If you receive this email, your SMTP configuration is working correctly.</p>';
            $mail->AltBody = 'If you receive this email, your SMTP configuration is working correctly.';

            $mail->send();

            return [
                'success' => true,
                'error' => null
            ];
        } catch (PHPMailerException $e) {
            return [
                'success' => false,
                'error' => $mail->ErrorInfo
            ];
        }
    }

    /**
     * Save SMTP settings to database
     * 
     * @param array $smtpConfig SMTP configuration
     * @return bool True on success, false on failure
     */
    public function saveSmtpSettings(array $smtpConfig): bool
    {
        try {
            $settingModel = new Setting();

            $settings = [
                'smtp_host' => $smtpConfig['smtp_host'],
                'smtp_port' => (string) $smtpConfig['smtp_port'],
                'smtp_username' => $smtpConfig['smtp_username'],
                'smtp_password' => $smtpConfig['smtp_password'],
                'smtp_encryption' => $smtpConfig['smtp_encryption'],
                'smtp_from_address' => $smtpConfig['smtp_from_address'],
                'smtp_from_name' => $smtpConfig['smtp_from_name']
            ];

            // Use batch save for efficiency
            return $settingModel->setMultiple($settings, 'string');
        } catch (\Exception $e) {
            error_log('Failed to save SMTP settings: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Save brand settings to database
     * 
     * @param array $brandData Brand settings
     * @return bool True on success, false on failure
     */
    public function saveBrandSettings(array $brandData): bool
    {
        try {
            $settingModel = new Setting();

            $settings = [
                'company_name' => $brandData['company_name'] ?? '',
                'company_tagline' => $brandData['company_tagline'] ?? '',
                'contact_email' => $brandData['contact_email'] ?? '',
                'contact_phone' => $brandData['contact_phone'] ?? '',
                'contact_address' => $brandData['contact_address'] ?? ''
            ];

            // Add logo path if provided
            if (!empty($brandData['logo_path'])) {
                $settings['branding_logo'] = $brandData['logo_path'];
            }

            // Use batch save for efficiency
            return $settingModel->setMultiple($settings, 'string');
        } catch (\Exception $e) {
            error_log('Failed to save brand settings: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Process and save uploaded logo file
     * 
     * @param array $file Uploaded file from $_FILES
     * @return array Returns ['success' => bool, 'path' => string|null, 'error' => string|null]
     */
    public function processLogoUpload(array $file): array
    {
        try {
            // Validate file was uploaded
            if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                return [
                    'success' => false,
                    'path' => null,
                    'error' => 'No file uploaded'
                ];
            }

            // Validate file size (max 2MB)
            if ($file['size'] > 2 * 1024 * 1024) {
                return [
                    'success' => false,
                    'path' => null,
                    'error' => 'File size exceeds 2MB limit'
                ];
            }

            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedTypes)) {
                return [
                    'success' => false,
                    'path' => null,
                    'error' => 'Invalid file type. Only JPG, PNG, GIF, and SVG are allowed'
                ];
            }

            // Get file extension
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            
            // Generate unique filename
            $filename = 'logo_' . time() . '.' . $extension;
            
            // Create branding directory if it doesn't exist
            $uploadDir = __DIR__ . '/../../uploads/branding';
            if (!is_dir($uploadDir)) {
                $permissions = $this->getEnvironmentPermissions('upload');
                mkdir($uploadDir, $permissions, true);
            }

            // Move uploaded file
            $destination = $uploadDir . '/' . $filename;
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                return [
                    'success' => false,
                    'path' => null,
                    'error' => 'Failed to move uploaded file'
                ];
            }

            // Return relative path
            $relativePath = '/uploads/branding/' . $filename;

            return [
                'success' => true,
                'path' => $relativePath,
                'error' => null
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'path' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create installation lock file
     * 
     * @return bool True on success, false on failure
     */
    public function createLockFile(): bool
    {
        try {
            $lockData = [
                'installed_at' => date('c'), // ISO 8601 format
                'version' => '1.0.0',
                'installer_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ];

            $result = file_put_contents(
                self::LOCK_FILE_PATH,
                json_encode($lockData, JSON_PRETTY_PRINT),
                LOCK_EX
            );

            if ($result === false) {
                return false;
            }

            // Set file permissions to 0644 (owner write, all read)
            @chmod(self::LOCK_FILE_PATH, 0644);

            return true;
        } catch (\Exception $e) {
            error_log('Failed to create lock file: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get installation wizard progress state
     * 
     * @return array Progress state
     */
    public function getProgress(): array
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        return $_SESSION[self::SESSION_KEY] ?? [
            'current_step' => 1,
            'completed_steps' => [],
            'database_configured' => false,
            'migrations_run' => false,
            'admin_created' => false,
            'smtp_configured' => false,
            'brand_configured' => false
        ];
    }

    /**
     * Save installation wizard progress state
     * 
     * @param array $state Progress state to save
     * @return bool True on success, false on failure
     */
    public function saveProgress(array $state): bool
    {
        try {
            if (!isset($_SESSION)) {
                session_start();
            }

            $_SESSION[self::SESSION_KEY] = $state;

            return true;
        } catch (\Exception $e) {
            error_log('Failed to save progress: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Save form data to session for a specific step
     * 
     * @param int $step Step number
     * @param array $data Form data to save
     * @return bool True on success, false on failure
     */
    public function saveStepData(int $step, array $data): bool
    {
        try {
            if (!isset($_SESSION)) {
                session_start();
            }

            if (!isset($_SESSION['wizard_data'])) {
                $_SESSION['wizard_data'] = [];
            }

            $_SESSION['wizard_data'][$step] = $data;

            return true;
        } catch (\Exception $e) {
            error_log('Failed to save step data: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get saved form data for a specific step
     * 
     * @param int $step Step number
     * @return array|null Saved form data or null if not found
     */
    public function getStepData(int $step): ?array
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        return $_SESSION['wizard_data'][$step] ?? null;
    }

    /**
     * Clear form data for a specific step
     * 
     * @param int $step Step number
     * @return bool True on success, false on failure
     */
    public function clearStepData(int $step): bool
    {
        try {
            if (!isset($_SESSION)) {
                session_start();
            }

            if (isset($_SESSION['wizard_data'][$step])) {
                unset($_SESSION['wizard_data'][$step]);
            }

            return true;
        } catch (\Exception $e) {
            error_log('Failed to clear step data: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all saved wizard form data
     * 
     * @return array All saved form data indexed by step number
     */
    public function getAllStepData(): array
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        return $_SESSION['wizard_data'] ?? [];
    }

    /**
     * Clear wizard session data after installation completion
     * 
     * @return bool True on success, false on failure
     */
    public function clearWizardSession(): bool
    {
        try {
            if (!isset($_SESSION)) {
                session_start();
            }

            // Clear installation wizard progress
            if (isset($_SESSION[self::SESSION_KEY])) {
                unset($_SESSION[self::SESSION_KEY]);
            }

            // Clear any other wizard-related session data
            if (isset($_SESSION['wizard_data'])) {
                unset($_SESSION['wizard_data']);
            }

            // Clear re-run mode
            if (isset($_SESSION['wizard_rerun_mode'])) {
                unset($_SESSION['wizard_rerun_mode']);
            }

            // Clear legacy session keys
            if (isset($_SESSION['database_credentials'])) {
                unset($_SESSION['database_credentials']);
            }
            if (isset($_SESSION['smtp_config'])) {
                unset($_SESSION['smtp_config']);
            }
            if (isset($_SESSION['admin_email'])) {
                unset($_SESSION['admin_email']);
            }

            return true;
        } catch (\Exception $e) {
            error_log('Failed to clear wizard session: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log installation completion event
     * 
     * @return bool True on success, false on failure
     */
    public function logInstallationCompletion(): bool
    {
        try {
            $loggerService = LoggerService::getInstance();
            
            $loggerService->info('Installation wizard completed successfully', [
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => '1.0.0'
            ]);

            return true;
        } catch (\Exception $e) {
            error_log('Failed to log installation completion: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Complete the installation process
     * 
     * This method orchestrates the final steps of installation:
     * - Creates the lock file
     * - Clears wizard session data
     * - Logs the completion event
     * 
     * @return array Returns ['success' => bool, 'error' => string|null]
     */
    public function completeInstallation(): array
    {
        try {
            // Create lock file
            if (!$this->createLockFile()) {
                return [
                    'success' => false,
                    'error' => 'Failed to create installation lock file'
                ];
            }

            // Log installation completion
            $this->logInstallationCompletion();

            // Clear wizard session data
            $this->clearWizardSession();

            return [
                'success' => true,
                'error' => null
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Detect if there is existing installation data
     * 
     * Checks for:
     * - Existing database tables
     * - Existing admin users
     * - Existing settings
     * 
     * @return array Returns ['has_data' => bool, 'details' => array]
     */
    public function detectExistingData(): array
    {
        $details = [
            'has_database' => false,
            'has_users' => false,
            'has_settings' => false,
            'user_count' => 0,
            'setting_count' => 0
        ];

        try {
            // Try to get database connection
            $this->reloadEnvironmentVariables();
            $config = require __DIR__ . '/../../config/database.php';
            
            // Create PDO connection
            if (!empty($config['unix_socket']) && file_exists($config['unix_socket'])) {
                $dsn = sprintf(
                    'mysql:unix_socket=%s;dbname=%s;charset=%s',
                    $config['unix_socket'],
                    $config['database'],
                    $config['charset']
                );
            } else {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $config['host'],
                    $config['port'],
                    $config['database'],
                    $config['charset']
                );
            }

            $pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );

            $details['has_database'] = true;

            // Check for users table and count users
            try {
                $stmt = $pdo->query('SELECT COUNT(*) as count FROM users');
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $details['user_count'] = (int) $result['count'];
                $details['has_users'] = $details['user_count'] > 0;
            } catch (PDOException $e) {
                // Table doesn't exist or query failed
                $details['has_users'] = false;
            }

            // Check for settings table and count settings
            try {
                $stmt = $pdo->query('SELECT COUNT(*) as count FROM settings');
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $details['setting_count'] = (int) $result['count'];
                $details['has_settings'] = $details['setting_count'] > 0;
            } catch (PDOException $e) {
                // Table doesn't exist or query failed
                $details['has_settings'] = false;
            }

        } catch (PDOException $e) {
            // Database connection failed - no existing data
            $details['has_database'] = false;
        }

        $hasData = $details['has_database'] || $details['has_users'] || $details['has_settings'];

        return [
            'has_data' => $hasData,
            'details' => $details
        ];
    }

    /**
     * Reset existing installation data
     * 
     * Drops all tables and clears configuration
     * WARNING: This is destructive and cannot be undone
     * 
     * @return array Returns ['success' => bool, 'error' => string|null]
     */
    public function resetExistingData(): array
    {
        try {
            // Get database connection
            $this->reloadEnvironmentVariables();
            $config = require __DIR__ . '/../../config/database.php';
            
            // Create PDO connection
            if (!empty($config['unix_socket']) && file_exists($config['unix_socket'])) {
                $dsn = sprintf(
                    'mysql:unix_socket=%s;dbname=%s;charset=%s',
                    $config['unix_socket'],
                    $config['database'],
                    $config['charset']
                );
            } else {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $config['host'],
                    $config['port'],
                    $config['database'],
                    $config['charset']
                );
            }

            $pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );

            // Get all tables in the database
            $stmt = $pdo->query('SHOW TABLES');
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Disable foreign key checks
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

            // Drop all tables
            foreach ($tables as $table) {
                $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
            }

            // Re-enable foreign key checks
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

            return [
                'success' => true,
                'error' => null
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Set re-run mode in session
     * 
     * @param string $mode Either 'preserve' or 'reset'
     * @return bool True on success, false on failure
     */
    public function setRerunMode(string $mode): bool
    {
        try {
            if (!isset($_SESSION)) {
                session_start();
            }

            if (!in_array($mode, ['preserve', 'reset'])) {
                return false;
            }

            $_SESSION['wizard_rerun_mode'] = $mode;

            return true;
        } catch (\Exception $e) {
            error_log('Failed to set rerun mode: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get re-run mode from session
     * 
     * @return string|null Either 'preserve', 'reset', or null if not set
     */
    public function getRerunMode(): ?string
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        return $_SESSION['wizard_rerun_mode'] ?? null;
    }

    /**
     * Clear re-run mode from session
     * 
     * @return bool True on success, false on failure
     */
    public function clearRerunMode(): bool
    {
        try {
            if (!isset($_SESSION)) {
                session_start();
            }

            if (isset($_SESSION['wizard_rerun_mode'])) {
                unset($_SESSION['wizard_rerun_mode']);
            }

            return true;
        } catch (\Exception $e) {
            error_log('Failed to clear rerun mode: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if wizard is in re-run mode
     * 
     * @return bool True if re-running, false otherwise
     */
    public function isRerunMode(): bool
    {
        return $this->getRerunMode() !== null;
    }

    /**
     * Detect the current environment
     * 
     * @return string Either 'localhost' or 'production'
     */
    public function detectEnvironment(): string
    {
        // Check for localhost indicators
        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        $serverAddr = $_SERVER['SERVER_ADDR'] ?? '';
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
        
        // Common localhost identifiers
        $localhostNames = ['localhost', '127.0.0.1', '::1', 'localhost.localdomain'];
        $localhostIPs = ['127.0.0.1', '::1', '0.0.0.0'];
        
        // Check server name
        if (in_array(strtolower($serverName), $localhostNames)) {
            return 'localhost';
        }
        
        // Check server address
        if (in_array($serverAddr, $localhostIPs)) {
            return 'localhost';
        }
        
        // Check remote address
        if (in_array($remoteAddr, $localhostIPs)) {
            return 'localhost';
        }
        
        // Check for common local development domains
        if (preg_match('/\.(local|test|dev)$/i', $serverName)) {
            return 'localhost';
        }
        
        // Check for XAMPP/MAMP/WAMP indicators
        if (stripos($_SERVER['SERVER_SOFTWARE'] ?? '', 'xampp') !== false ||
            stripos($_SERVER['SERVER_SOFTWARE'] ?? '', 'mamp') !== false ||
            stripos($_SERVER['SERVER_SOFTWARE'] ?? '', 'wamp') !== false) {
            return 'localhost';
        }
        
        return 'production';
    }

    /**
     * Check if running on localhost
     * 
     * @return bool True if localhost, false otherwise
     */
    public function isLocalhost(): bool
    {
        return $this->detectEnvironment() === 'localhost';
    }

    /**
     * Check if running on production
     * 
     * @return bool True if production, false otherwise
     */
    public function isProduction(): bool
    {
        return $this->detectEnvironment() === 'production';
    }

    /**
     * Get environment-specific file path
     * 
     * Adapts file paths based on the current environment
     * 
     * @param string $relativePath Relative path from project root
     * @return string Absolute path adapted for environment
     */
    public function getEnvironmentPath(string $relativePath): string
    {
        $basePath = __DIR__ . '/../..';
        
        // Normalize path separators
        $relativePath = str_replace('\\', '/', $relativePath);
        $relativePath = ltrim($relativePath, '/');
        
        // Build full path
        $fullPath = $basePath . '/' . $relativePath;
        
        // Normalize the path
        $fullPath = realpath($fullPath);
        
        // If realpath fails (file doesn't exist yet), construct manually
        if ($fullPath === false) {
            $fullPath = $basePath . '/' . $relativePath;
            $fullPath = str_replace('\\', '/', $fullPath);
        }
        
        return $fullPath;
    }

    /**
     * Get environment-specific file permissions
     * 
     * Returns appropriate file permissions based on environment
     * 
     * @param string $fileType Either 'config', 'upload', or 'general'
     * @return int Unix file permission mode
     */
    public function getEnvironmentPermissions(string $fileType = 'general'): int
    {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        // Windows doesn't use Unix permissions
        if ($isWindows) {
            return 0777; // Will be ignored on Windows
        }
        
        $environment = $this->detectEnvironment();
        
        switch ($fileType) {
            case 'config':
                // Configuration files should be restrictive
                return $environment === 'localhost' ? 0644 : 0600;
                
            case 'upload':
                // Upload directories need to be writable
                return $environment === 'localhost' ? 0755 : 0755;
                
            case 'general':
            default:
                // General files
                return $environment === 'localhost' ? 0644 : 0644;
        }
    }

    /**
     * Get environment-specific security warnings
     * 
     * Returns warnings appropriate for the current environment
     * 
     * @return array Array of warning messages
     */
    public function getEnvironmentWarnings(): array
    {
        $warnings = [];
        $environment = $this->detectEnvironment();
        
        if ($environment === 'production') {
            // Check for HTTPS
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                       (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
            
            if (!$isHttps) {
                $warnings[] = [
                    'type' => 'security',
                    'severity' => 'high',
                    'message' => 'You are installing on a production server without HTTPS. It is strongly recommended to use HTTPS for security.'
                ];
            }
            
            // Check for display_errors
            if (ini_get('display_errors')) {
                $warnings[] = [
                    'type' => 'security',
                    'severity' => 'medium',
                    'message' => 'PHP display_errors is enabled. This should be disabled in production for security.'
                ];
            }
            
            // Check for default database credentials
            $envPath = __DIR__ . '/../../.env';
            if (file_exists($envPath)) {
                $envContent = file_get_contents($envPath);
                if (stripos($envContent, 'DB_PASS=') !== false && 
                    (stripos($envContent, 'DB_PASS=""') !== false || 
                     stripos($envContent, "DB_PASS=''") !== false ||
                     stripos($envContent, 'DB_PASS=root') !== false)) {
                    $warnings[] = [
                        'type' => 'security',
                        'severity' => 'high',
                        'message' => 'Using default or empty database password in production is a security risk.'
                    ];
                }
            }
        } else {
            // Localhost warnings
            $warnings[] = [
                'type' => 'info',
                'severity' => 'low',
                'message' => 'You are installing on a local development environment. Some security checks are relaxed.'
            ];
        }
        
        return $warnings;
    }

    /**
     * Get environment information
     * 
     * Returns detailed information about the current environment
     * 
     * @return array Environment information
     */
    public function getEnvironmentInfo(): array
    {
        $environment = $this->detectEnvironment();
        
        return [
            'environment' => $environment,
            'is_localhost' => $environment === 'localhost',
            'is_production' => $environment === 'production',
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'php_version' => PHP_VERSION,
            'php_os' => PHP_OS,
            'is_windows' => strtoupper(substr(PHP_OS, 0, 3)) === 'WIN',
            'is_https' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                          (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443),
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
            'warnings' => $this->getEnvironmentWarnings()
        ];
    }

    /**
     * Adapt configuration writing based on environment
     * 
     * Writes configuration with environment-appropriate permissions
     * 
     * @param string $filePath Path to configuration file
     * @param string $content Content to write
     * @return bool True on success, false on failure
     */
    public function writeConfigWithEnvironmentPermissions(string $filePath, string $content): bool
    {
        try {
            // Write file
            $result = file_put_contents($filePath, $content, LOCK_EX);
            
            if ($result === false) {
                return false;
            }
            
            // Set appropriate permissions
            $permissions = $this->getEnvironmentPermissions('config');
            @chmod($filePath, $permissions);
            
            return true;
        } catch (\Exception $e) {
            error_log('Failed to write config file: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Save credentials for a specific environment
     * 
     * Validates and tests the database connection before saving.
     * Uses EnvironmentConfigManager to write credentials with appropriate prefixes.
     * 
     * @param string $environment 'local' or 'live'
     * @param array $credentials Database credentials (host, port, database, username, password, unix_socket)
     * @return array Returns ['success' => bool, 'error' => string|null]
     */
    public function saveEnvironmentCredentials(string $environment, array $credentials): array
    {
        // Validate environment parameter
        if (!in_array($environment, ['local', 'live'])) {
            return [
                'success' => false,
                'error' => 'Invalid environment. Must be "local" or "live".'
            ];
        }

        // Validate required credential fields
        if (empty($credentials['host'])) {
            return [
                'success' => false,
                'error' => 'Database host is required.'
            ];
        }

        if (empty($credentials['database'])) {
            return [
                'success' => false,
                'error' => 'Database name is required.'
            ];
        }

        // Test the database connection before saving
        $connectionTest = $this->testDatabaseConnection($credentials);
        if (!$connectionTest['success']) {
            return [
                'success' => false,
                'error' => 'Connection test failed: ' . $connectionTest['error']
            ];
        }

        // Get existing credentials for the other environment
        $configManager = new EnvironmentConfigManager();
        $existingLocal = $configManager->readEnvironmentCredentials('local');
        $existingLive = $configManager->readEnvironmentCredentials('live');

        // Update the appropriate credential set
        if ($environment === 'local') {
            $localCredentials = $credentials;
            $liveCredentials = $existingLive;
        } else {
            $localCredentials = $existingLocal;
            $liveCredentials = $credentials;
        }

        // Write the dual configuration
        $writeResult = $configManager->writeDualConfig($localCredentials, $liveCredentials);

        if (!$writeResult) {
            return [
                'success' => false,
                'error' => 'Failed to write credentials to configuration file.'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Get credentials for a specific environment
     * 
     * Reads credentials from the .env file using the appropriate prefix.
     * 
     * @param string $environment 'local' or 'live'
     * @return array|null Credentials array or null if not configured
     */
    public function getEnvironmentCredentials(string $environment): ?array
    {
        // Validate environment parameter
        if (!in_array($environment, ['local', 'live'])) {
            return null;
        }

        $configManager = new EnvironmentConfigManager();
        return $configManager->readEnvironmentCredentials($environment);
    }

    /**
     * Check if credentials exist for an environment
     * 
     * @param string $environment 'local' or 'live'
     * @return bool True if valid credentials exist
     */
    public function hasEnvironmentCredentials(string $environment): bool
    {
        // Validate environment parameter
        if (!in_array($environment, ['local', 'live'])) {
            return false;
        }

        $configManager = new EnvironmentConfigManager();
        return $configManager->hasEnvironmentCredentials($environment);
    }

    /**
     * Get the active environment based on detection
     * 
     * Uses the existing detectEnvironment method and maps the result
     * to the credential environment names ('local' or 'live').
     * 
     * @return string 'local' or 'live'
     */
    public function getActiveEnvironment(): string
    {
        $detected = $this->detectEnvironment();
        
        // Map 'localhost' to 'local' and 'production' to 'live'
        return $detected === 'localhost' ? 'local' : 'live';
    }

    /**
     * Resolve and set active database credentials
     * 
     * Integrates with EnvironmentConfigManager to resolve the appropriate
     * credentials based on the detected environment and sets them as
     * active DB_ environment variables.
     * 
     * @return array Returns resolved credentials info with keys:
     *               - 'success' => bool
     *               - 'credentials' => array|null
     *               - 'environment' => string|null ('local' or 'live')
     *               - 'detected_environment' => string ('localhost' or 'production')
     *               - 'error' => string|null
     */
    public function resolveActiveCredentials(): array
    {
        try {
            $configManager = new EnvironmentConfigManager(null, $this);
            
            // Resolve credentials based on environment
            $resolved = $configManager->resolveCredentials();
            
            // Check if we have valid credentials
            if ($resolved['credentials'] === null) {
                return [
                    'success' => false,
                    'credentials' => null,
                    'environment' => null,
                    'detected_environment' => $resolved['detected_environment'],
                    'error' => 'No valid database credentials available for the detected environment.'
                ];
            }
            
            // Set the active credentials in environment variables
            $setResult = $configManager->setActiveCredentials($resolved['credentials']);
            
            if (!$setResult) {
                return [
                    'success' => false,
                    'credentials' => $resolved['credentials'],
                    'environment' => $resolved['environment'],
                    'detected_environment' => $resolved['detected_environment'],
                    'error' => 'Failed to set active database credentials in environment.'
                ];
            }
            
            return [
                'success' => true,
                'credentials' => $resolved['credentials'],
                'environment' => $resolved['environment'],
                'detected_environment' => $resolved['detected_environment'],
                'local_available' => $resolved['local_available'],
                'live_available' => $resolved['live_available'],
                'error' => null
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'credentials' => null,
                'environment' => null,
                'detected_environment' => $this->detectEnvironment(),
                'error' => 'Error resolving credentials: ' . $e->getMessage()
            ];
        }
    }
}
