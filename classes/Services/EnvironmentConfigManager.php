<?php

namespace Karyalay\Services;

/**
 * Environment Configuration Manager
 * 
 * Handles dual-environment configuration for database credentials.
 * Supports both localhost (development) and live (production) environments
 * with automatic detection and credential resolution.
 * 
 * @package Karyalay\Services
 */
class EnvironmentConfigManager
{
    /**
     * Prefix for localhost database credentials in .env file
     */
    public const LOCAL_PREFIX = 'DB_LOCAL_';
    
    /**
     * Prefix for live/production database credentials in .env file
     */
    public const LIVE_PREFIX = 'DB_LIVE_';
    
    /**
     * Prefix for active database credentials in .env file
     */
    public const ACTIVE_PREFIX = 'DB_';
    
    /**
     * Database credential keys (without prefix)
     */
    private const CREDENTIAL_KEYS = ['HOST', 'PORT', 'NAME', 'USER', 'PASS', 'UNIX_SOCKET'];
    
    /**
     * Path to the .env file
     */
    private string $envPath;
    
    /**
     * InstallationService instance for environment detection
     */
    private ?InstallationService $installationService;

    /**
     * Constructor
     * 
     * @param string|null $envPath Custom path to .env file (defaults to project root)
     * @param InstallationService|null $installationService Service for environment detection
     */
    public function __construct(?string $envPath = null, ?InstallationService $installationService = null)
    {
        $this->envPath = $envPath ?? dirname(__DIR__, 2) . '/.env';
        $this->installationService = $installationService;
    }

    /**
     * Get the InstallationService instance
     * 
     * @return InstallationService
     */
    private function getInstallationService(): InstallationService
    {
        if ($this->installationService === null) {
            $this->installationService = new InstallationService();
        }
        return $this->installationService;
    }


    /**
     * Write dual-environment configuration to .env file
     * 
     * Saves both local and live credentials with appropriate prefixes.
     * Preserves existing non-database configuration values.
     * 
     * @param array|null $localCredentials Localhost database credentials
     * @param array|null $liveCredentials Live/production database credentials
     * @return bool True on success, false on failure
     */
    public function writeDualConfig(?array $localCredentials, ?array $liveCredentials): bool
    {
        try {
            // Read existing .env content or start fresh
            $envContent = '';
            if (file_exists($this->envPath)) {
                $envContent = file_get_contents($this->envPath);
            }

            // Remove existing dual-environment database sections
            $envContent = $this->removeExistingDatabaseSections($envContent);

            // Build the dual-environment configuration section
            $dualConfigSection = $this->buildDualConfigSection($localCredentials, $liveCredentials);

            // Append the new configuration section
            $envContent = rtrim($envContent) . "\n\n" . $dualConfigSection;

            // Write to .env file
            $result = file_put_contents($this->envPath, $envContent, LOCK_EX);
            
            if ($result === false) {
                return false;
            }

            // Set appropriate file permissions
            @chmod($this->envPath, 0600);

            return true;
        } catch (\Exception $e) {
            error_log('EnvironmentConfigManager: Failed to write dual config - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove existing database configuration sections from .env content
     * 
     * @param string $content Current .env content
     * @return string Content with database sections removed
     */
    private function removeExistingDatabaseSections(string $content): string
    {
        $lines = explode("\n", $content);
        $filteredLines = [];
        $inDatabaseSection = false;

        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            
            // Check if this is a database-related line
            $isDatabaseLine = false;
            
            // Check for database variable prefixes
            foreach ([self::ACTIVE_PREFIX, self::LOCAL_PREFIX, self::LIVE_PREFIX] as $prefix) {
                if (strpos($trimmedLine, $prefix) === 0) {
                    $isDatabaseLine = true;
                    break;
                }
            }
            
            // Check for database section comments
            if (preg_match('/^#.*(?:DATABASE|DUAL ENVIRONMENT|LOCALHOST CREDENTIALS|LIVE CREDENTIALS|ACTIVE DATABASE)/i', $trimmedLine)) {
                $inDatabaseSection = true;
                continue;
            }
            
            // Check for section separator lines
            if (preg_match('/^#\s*[-=]{10,}/', $trimmedLine) && $inDatabaseSection) {
                continue;
            }
            
            // Reset section flag on new non-database section
            if (preg_match('/^#\s*[A-Z]/', $trimmedLine) && !preg_match('/DATABASE|DUAL ENVIRONMENT|LOCALHOST|LIVE CREDENTIALS/i', $trimmedLine)) {
                $inDatabaseSection = false;
            }
            
            // Skip database-related lines
            if ($isDatabaseLine) {
                continue;
            }
            
            // Skip lines in database section
            if ($inDatabaseSection && (empty($trimmedLine) || strpos($trimmedLine, '#') === 0)) {
                continue;
            }
            
            $filteredLines[] = $line;
        }

        // Remove trailing empty lines
        while (!empty($filteredLines) && trim(end($filteredLines)) === '') {
            array_pop($filteredLines);
        }

        return implode("\n", $filteredLines);
    }

    /**
     * Build the dual-environment configuration section
     * 
     * @param array|null $localCredentials Localhost credentials
     * @param array|null $liveCredentials Live credentials
     * @return string Configuration section content
     */
    private function buildDualConfigSection(?array $localCredentials, ?array $liveCredentials): string
    {
        $section = [];
        
        // Header
        $section[] = '# =============================================================================';
        $section[] = '# DUAL ENVIRONMENT DATABASE CONFIGURATION';
        $section[] = '# =============================================================================';
        $section[] = '# The system automatically detects the environment and uses appropriate credentials.';
        $section[] = '# - On localhost: Uses DB_LOCAL_* credentials';
        $section[] = '# - On production: Uses DB_LIVE_* credentials (if available, else falls back to local)';
        $section[] = '# To force localhost credentials on any environment, comment out or remove DB_LIVE_* values.';
        $section[] = '';

        // Active credentials section (will be populated at runtime)
        $section[] = '# -----------------------------------------------------------------------------';
        $section[] = '# ACTIVE DATABASE CREDENTIALS (Auto-populated based on environment)';
        $section[] = '# -----------------------------------------------------------------------------';
        
        // Determine which credentials to use as active
        $activeCredentials = $this->determineActiveCredentials($localCredentials, $liveCredentials);
        $section[] = $this->formatCredentialLine(self::ACTIVE_PREFIX . 'HOST', $activeCredentials['host'] ?? '');
        $section[] = $this->formatCredentialLine(self::ACTIVE_PREFIX . 'PORT', $activeCredentials['port'] ?? '3306');
        $section[] = $this->formatCredentialLine(self::ACTIVE_PREFIX . 'NAME', $activeCredentials['database'] ?? '');
        $section[] = $this->formatCredentialLine(self::ACTIVE_PREFIX . 'USER', $activeCredentials['username'] ?? '');
        $section[] = $this->formatCredentialLine(self::ACTIVE_PREFIX . 'PASS', $activeCredentials['password'] ?? '');
        $section[] = $this->formatCredentialLine(self::ACTIVE_PREFIX . 'UNIX_SOCKET', $activeCredentials['unix_socket'] ?? '');
        $section[] = '';

        // Localhost credentials section
        $section[] = '# -----------------------------------------------------------------------------';
        $section[] = '# LOCALHOST CREDENTIALS (Development Environment)';
        $section[] = '# -----------------------------------------------------------------------------';
        
        if ($localCredentials !== null) {
            $section[] = $this->formatCredentialLine(self::LOCAL_PREFIX . 'HOST', $localCredentials['host'] ?? 'localhost');
            $section[] = $this->formatCredentialLine(self::LOCAL_PREFIX . 'PORT', $localCredentials['port'] ?? '3306');
            $section[] = $this->formatCredentialLine(self::LOCAL_PREFIX . 'NAME', $localCredentials['database'] ?? '');
            $section[] = $this->formatCredentialLine(self::LOCAL_PREFIX . 'USER', $localCredentials['username'] ?? '');
            $section[] = $this->formatCredentialLine(self::LOCAL_PREFIX . 'PASS', $localCredentials['password'] ?? '');
            $section[] = $this->formatCredentialLine(self::LOCAL_PREFIX . 'UNIX_SOCKET', $localCredentials['unix_socket'] ?? '');
        } else {
            $section[] = '# Not configured';
            $section[] = $this->formatCredentialLine(self::LOCAL_PREFIX . 'HOST', '');
            $section[] = $this->formatCredentialLine(self::LOCAL_PREFIX . 'PORT', '3306');
            $section[] = $this->formatCredentialLine(self::LOCAL_PREFIX . 'NAME', '');
            $section[] = $this->formatCredentialLine(self::LOCAL_PREFIX . 'USER', '');
            $section[] = $this->formatCredentialLine(self::LOCAL_PREFIX . 'PASS', '');
            $section[] = $this->formatCredentialLine(self::LOCAL_PREFIX . 'UNIX_SOCKET', '');
        }
        $section[] = '';

        // Live credentials section
        $section[] = '# -----------------------------------------------------------------------------';
        $section[] = '# LIVE CREDENTIALS (Production Environment)';
        $section[] = '# -----------------------------------------------------------------------------';
        $section[] = '# Leave empty if not yet configured for production';
        
        if ($liveCredentials !== null) {
            $section[] = $this->formatCredentialLine(self::LIVE_PREFIX . 'HOST', $liveCredentials['host'] ?? '');
            $section[] = $this->formatCredentialLine(self::LIVE_PREFIX . 'PORT', $liveCredentials['port'] ?? '3306');
            $section[] = $this->formatCredentialLine(self::LIVE_PREFIX . 'NAME', $liveCredentials['database'] ?? '');
            $section[] = $this->formatCredentialLine(self::LIVE_PREFIX . 'USER', $liveCredentials['username'] ?? '');
            $section[] = $this->formatCredentialLine(self::LIVE_PREFIX . 'PASS', $liveCredentials['password'] ?? '');
            $section[] = $this->formatCredentialLine(self::LIVE_PREFIX . 'UNIX_SOCKET', $liveCredentials['unix_socket'] ?? '');
        } else {
            $section[] = $this->formatCredentialLine(self::LIVE_PREFIX . 'HOST', '');
            $section[] = $this->formatCredentialLine(self::LIVE_PREFIX . 'PORT', '3306');
            $section[] = $this->formatCredentialLine(self::LIVE_PREFIX . 'NAME', '');
            $section[] = $this->formatCredentialLine(self::LIVE_PREFIX . 'USER', '');
            $section[] = $this->formatCredentialLine(self::LIVE_PREFIX . 'PASS', '');
            $section[] = $this->formatCredentialLine(self::LIVE_PREFIX . 'UNIX_SOCKET', '');
        }

        return implode("\n", $section);
    }

    /**
     * Determine which credentials should be active based on environment
     * 
     * @param array|null $localCredentials Localhost credentials
     * @param array|null $liveCredentials Live credentials
     * @return array Active credentials
     */
    private function determineActiveCredentials(?array $localCredentials, ?array $liveCredentials): array
    {
        $environment = $this->getInstallationService()->detectEnvironment();
        
        if ($environment === 'production' && $this->isCredentialSetValid($liveCredentials)) {
            return $liveCredentials;
        }
        
        if ($this->isCredentialSetValid($localCredentials)) {
            return $localCredentials;
        }
        
        if ($this->isCredentialSetValid($liveCredentials)) {
            return $liveCredentials;
        }
        
        return [];
    }

    /**
     * Check if a credential set is valid (has required fields)
     * 
     * @param array|null $credentials Credentials to check
     * @return bool True if valid, false otherwise
     */
    private function isCredentialSetValid(?array $credentials): bool
    {
        if ($credentials === null) {
            return false;
        }
        
        // At minimum, we need host and database name
        return !empty($credentials['host']) && !empty($credentials['database']);
    }

    /**
     * Format a credential line for .env file
     * 
     * @param string $key Environment variable key
     * @param mixed $value Environment variable value
     * @return string Formatted line
     */
    private function formatCredentialLine(string $key, $value): string
    {
        if ($value === null || $value === '') {
            return "{$key}=";
        }
        
        $value = (string) $value;
        
        // If value contains spaces, quotes, or special characters, wrap in quotes
        if (preg_match('/[\s#"\'\\\\]/', $value)) {
            $value = '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
        }
        
        return "{$key}={$value}";
    }


    /**
     * Read credentials for a specific environment from .env file
     * 
     * @param string $environment 'local' or 'live'
     * @return array|null Credentials array or null if not configured
     */
    public function readEnvironmentCredentials(string $environment): ?array
    {
        if (!file_exists($this->envPath)) {
            return null;
        }

        $prefix = $environment === 'local' ? self::LOCAL_PREFIX : self::LIVE_PREFIX;
        
        $envContent = file_get_contents($this->envPath);
        $credentials = $this->parseCredentialsWithPrefix($envContent, $prefix);
        
        // Check if credentials are actually configured (not just empty placeholders)
        if (!$this->isCredentialSetValid($credentials)) {
            return null;
        }
        
        return $credentials;
    }

    /**
     * Parse credentials from .env content using a specific prefix
     * 
     * @param string $content .env file content
     * @param string $prefix Credential prefix (e.g., 'DB_LOCAL_')
     * @return array Parsed credentials
     */
    private function parseCredentialsWithPrefix(string $content, string $prefix): array
    {
        $credentials = [
            'host' => '',
            'port' => '3306',
            'database' => '',
            'username' => '',
            'password' => '',
            'unix_socket' => ''
        ];

        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip comments and empty lines
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            // Parse key=value
            if (strpos($line, '=') === false) {
                continue;
            }
            
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            $value = $this->unquoteValue($value);
            
            // Check if this key matches our prefix
            if (strpos($key, $prefix) !== 0) {
                continue;
            }
            
            // Extract the credential type (HOST, PORT, etc.)
            $credentialType = substr($key, strlen($prefix));
            
            switch ($credentialType) {
                case 'HOST':
                    $credentials['host'] = $value;
                    break;
                case 'PORT':
                    $credentials['port'] = $value ?: '3306';
                    break;
                case 'NAME':
                    $credentials['database'] = $value;
                    break;
                case 'USER':
                    $credentials['username'] = $value;
                    break;
                case 'PASS':
                    $credentials['password'] = $value;
                    break;
                case 'UNIX_SOCKET':
                    $credentials['unix_socket'] = $value;
                    break;
            }
        }
        
        return $credentials;
    }

    /**
     * Remove quotes from an environment variable value
     * 
     * @param string $value Quoted or unquoted value
     * @return string Unquoted value
     */
    private function unquoteValue(string $value): string
    {
        // Handle double-quoted strings
        if (preg_match('/^"(.*)"$/s', $value, $matches)) {
            return str_replace(['\\"', '\\\\'], ['"', '\\'], $matches[1]);
        }
        
        // Handle single-quoted strings
        if (preg_match("/^'(.*)'$/s", $value, $matches)) {
            return $matches[1];
        }
        
        return $value;
    }


    /**
     * Resolve which credentials to use based on environment detection
     * 
     * Resolution logic:
     * - If detected as production AND live credentials are valid → use live credentials
     * - If detected as production AND live credentials are empty/invalid AND local credentials are valid → use local credentials
     * - If detected as localhost AND local credentials are valid → use local credentials
     * - If detected as localhost AND local credentials are empty/invalid AND live credentials are valid → use live credentials
     * 
     * @return array Resolved credentials array with 'credentials' and 'environment' keys
     */
    public function resolveCredentials(): array
    {
        $detectedEnvironment = $this->getInstallationService()->detectEnvironment();
        
        $localCredentials = $this->readEnvironmentCredentials('local');
        $liveCredentials = $this->readEnvironmentCredentials('live');
        
        $localValid = $this->isCredentialSetValid($localCredentials);
        $liveValid = $this->isCredentialSetValid($liveCredentials);
        
        $resolvedCredentials = null;
        $usedEnvironment = null;
        
        if ($detectedEnvironment === 'production') {
            // Production environment: prefer live credentials
            if ($liveValid) {
                $resolvedCredentials = $liveCredentials;
                $usedEnvironment = 'live';
            } elseif ($localValid) {
                // Fallback to local credentials
                $resolvedCredentials = $localCredentials;
                $usedEnvironment = 'local';
            }
        } else {
            // Localhost environment: prefer local credentials
            if ($localValid) {
                $resolvedCredentials = $localCredentials;
                $usedEnvironment = 'local';
            } elseif ($liveValid) {
                // Fallback to live credentials
                $resolvedCredentials = $liveCredentials;
                $usedEnvironment = 'live';
            }
        }
        
        return [
            'credentials' => $resolvedCredentials,
            'environment' => $usedEnvironment,
            'detected_environment' => $detectedEnvironment,
            'local_available' => $localValid,
            'live_available' => $liveValid
        ];
    }

    /**
     * Set active database credentials in environment variables
     * 
     * Updates the active DB_* environment variables based on resolved credentials.
     * 
     * @param array $credentials Credentials to set as active
     * @return bool True on success, false on failure
     */
    public function setActiveCredentials(array $credentials): bool
    {
        try {
            $mappings = [
                'host' => 'DB_HOST',
                'port' => 'DB_PORT',
                'database' => 'DB_NAME',
                'username' => 'DB_USER',
                'password' => 'DB_PASS',
                'unix_socket' => 'DB_UNIX_SOCKET'
            ];
            
            foreach ($mappings as $credKey => $envKey) {
                $value = $credentials[$credKey] ?? '';
                $_ENV[$envKey] = $value;
                putenv("{$envKey}={$value}");
            }
            
            return true;
        } catch (\Exception $e) {
            error_log('EnvironmentConfigManager: Failed to set active credentials - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the path to the .env file
     * 
     * @return string Path to .env file
     */
    public function getEnvPath(): string
    {
        return $this->envPath;
    }

    /**
     * Check if credentials exist for a specific environment
     * 
     * @param string $environment 'local' or 'live'
     * @return bool True if valid credentials exist
     */
    public function hasEnvironmentCredentials(string $environment): bool
    {
        $credentials = $this->readEnvironmentCredentials($environment);
        return $this->isCredentialSetValid($credentials);
    }

    /**
     * Get the prefix for a specific environment
     * 
     * @param string $environment 'local' or 'live'
     * @return string The prefix constant
     */
    public static function getPrefixForEnvironment(string $environment): string
    {
        return $environment === 'local' ? self::LOCAL_PREFIX : self::LIVE_PREFIX;
    }
}
