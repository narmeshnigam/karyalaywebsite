<?php

namespace Karyalay\Services;

use PDO;
use PDOException;

/**
 * Database Validation Service
 * 
 * Provides specific error messages for database connection validation failures.
 * Implements Requirements 6.3, 6.4, 6.5 for validation error handling.
 */
class DatabaseValidationService
{
    /**
     * Error type constants
     */
    public const ERROR_HOST_UNREACHABLE = 'host_unreachable';
    public const ERROR_INVALID_CREDENTIALS = 'invalid_credentials';
    public const ERROR_DATABASE_NOT_FOUND = 'database_not_found';
    public const ERROR_SOCKET_NOT_FOUND = 'socket_not_found';
    public const ERROR_CONNECTION_REFUSED = 'connection_refused';
    public const ERROR_TIMEOUT = 'timeout';
    public const ERROR_UNKNOWN = 'unknown';

    /**
     * Test database connection and return detailed result
     * 
     * @param array $credentials Database credentials
     * @return array Returns detailed result with:
     *               - 'success' => bool
     *               - 'error_type' => string|null (one of ERROR_* constants)
     *               - 'error_message' => string|null (user-friendly message)
     *               - 'error_details' => string|null (technical details)
     *               - 'troubleshooting' => array|null (suggested fixes)
     *               - 'field' => string|null (related form field)
     *               - 'server_info' => array|null (on success: version, connection info)
     */
    public function testConnection(array $credentials): array
    {
        try {
            // Build DSN based on whether unix_socket is provided
            if (!empty($credentials['unix_socket'])) {
                // Check if socket file exists before attempting connection
                if (!file_exists($credentials['unix_socket'])) {
                    return $this->createSocketNotFoundError($credentials['unix_socket']);
                }
                
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

            // Attempt connection with timeout
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5 // 5 second timeout
            ];

            $pdo = new PDO(
                $dsn,
                $credentials['username'],
                $credentials['password'],
                $options
            );

            // Test with a simple query and get server info
            $pdo->query('SELECT 1');
            $serverVersion = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
            $connectionStatus = $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);

            return $this->createSuccessResult($serverVersion, $connectionStatus, $credentials);

        } catch (PDOException $e) {
            return $this->parseConnectionError($e, $credentials);
        }
    }

    /**
     * Parse PDO exception and return specific error information
     * 
     * @param PDOException $e The exception to parse
     * @param array $credentials The credentials that were used
     * @return array Detailed error result
     */
    private function parseConnectionError(PDOException $e, array $credentials): array
    {
        $errorCode = $e->getCode();
        $errorMessage = $e->getMessage();

        // Check for host unreachable / connection refused
        if ($this->isHostUnreachableError($errorCode, $errorMessage)) {
            return $this->createHostUnreachableError($credentials);
        }

        // Check for invalid credentials (access denied)
        if ($this->isInvalidCredentialsError($errorCode, $errorMessage)) {
            return $this->createInvalidCredentialsError($credentials);
        }

        // Check for database not found
        if ($this->isDatabaseNotFoundError($errorCode, $errorMessage)) {
            return $this->createDatabaseNotFoundError($credentials);
        }

        // Check for socket errors
        if ($this->isSocketError($errorCode, $errorMessage)) {
            return $this->createSocketNotFoundError($credentials['unix_socket'] ?? '');
        }

        // Check for timeout
        if ($this->isTimeoutError($errorCode, $errorMessage)) {
            return $this->createTimeoutError($credentials);
        }

        // Unknown error - return generic message with details
        return $this->createUnknownError($errorMessage);
    }

    /**
     * Check if error indicates host is unreachable
     */
    private function isHostUnreachableError($code, string $message): bool
    {
        // MySQL error codes for connection issues
        $connectionCodes = [2002, 2003, 2005, 2006, 2013];
        
        if (in_array((int)$code, $connectionCodes)) {
            return true;
        }

        // Check message patterns
        $patterns = [
            '/connection refused/i',
            '/no route to host/i',
            '/network is unreachable/i',
            '/host.*not.*found/i',
            '/unknown.*host/i',
            '/getaddrinfo failed/i',
            '/name or service not known/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if error indicates invalid credentials
     */
    private function isInvalidCredentialsError($code, string $message): bool
    {
        // MySQL error code 1045 = Access denied
        if ((int)$code === 1045) {
            return true;
        }

        // Check message patterns
        $patterns = [
            '/access denied/i',
            '/authentication failed/i',
            '/password.*incorrect/i',
            '/invalid.*password/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if error indicates database not found
     */
    private function isDatabaseNotFoundError($code, string $message): bool
    {
        // MySQL error code 1049 = Unknown database
        if ((int)$code === 1049) {
            return true;
        }

        // Check message patterns
        $patterns = [
            '/unknown database/i',
            '/database.*not.*exist/i',
            '/no.*database.*selected/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if error indicates socket issue
     */
    private function isSocketError($code, string $message): bool
    {
        // MySQL error code 2002 can also be socket related
        $patterns = [
            '/socket/i',
            '/\.sock/i',
            '/no such file or directory/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if error indicates timeout
     */
    private function isTimeoutError($code, string $message): bool
    {
        $patterns = [
            '/timeout/i',
            '/timed out/i',
            '/connection.*expired/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create success result with server information
     */
    private function createSuccessResult(string $serverVersion, string $connectionStatus, array $credentials): array
    {
        $host = !empty($credentials['unix_socket']) 
            ? 'Unix Socket' 
            : ($credentials['host'] . ':' . ($credentials['port'] ?? 3306));

        return [
            'success' => true,
            'error_type' => null,
            'error_message' => null,
            'error_details' => null,
            'troubleshooting' => null,
            'field' => null,
            'server_info' => [
                'version' => $serverVersion,
                'connection_status' => $connectionStatus,
                'host' => $host,
                'database' => $credentials['database'],
                'message' => sprintf(
                    'Successfully connected to MySQL %s on %s',
                    $serverVersion,
                    $host
                )
            ]
        ];
    }

    /**
     * Create host unreachable error result
     */
    private function createHostUnreachableError(array $credentials): array
    {
        $host = $credentials['host'] ?? 'unknown';
        $port = $credentials['port'] ?? 3306;

        return [
            'success' => false,
            'error_type' => self::ERROR_HOST_UNREACHABLE,
            'error_message' => sprintf(
                'Cannot connect to database server at %s:%s. Please verify the host and port are correct.',
                htmlspecialchars($host),
                htmlspecialchars($port)
            ),
            'error_details' => 'The database server is not responding or the host/port combination is incorrect.',
            'troubleshooting' => [
                'Verify the database host address is correct',
                'Check that the database server is running',
                'Ensure the port number is correct (default MySQL port is 3306)',
                'Check if a firewall is blocking the connection',
                'For localhost, try using "127.0.0.1" instead of "localhost"'
            ],
            'field' => 'host'
        ];
    }

    /**
     * Create invalid credentials error result
     */
    private function createInvalidCredentialsError(array $credentials): array
    {
        $username = $credentials['username'] ?? 'unknown';

        return [
            'success' => false,
            'error_type' => self::ERROR_INVALID_CREDENTIALS,
            'error_message' => sprintf(
                'Database access denied for user "%s". Please verify username and password.',
                htmlspecialchars($username)
            ),
            'error_details' => 'The username or password is incorrect, or the user does not have permission to access the database.',
            'troubleshooting' => [
                'Double-check the username spelling',
                'Verify the password is correct (passwords are case-sensitive)',
                'Ensure the user has been granted access to the specified database',
                'For localhost, the default username is often "root"',
                'Check if the user is allowed to connect from your current host'
            ],
            'field' => 'username'
        ];
    }

    /**
     * Create database not found error result
     */
    private function createDatabaseNotFoundError(array $credentials): array
    {
        $database = $credentials['database'] ?? 'unknown';

        return [
            'success' => false,
            'error_type' => self::ERROR_DATABASE_NOT_FOUND,
            'error_message' => sprintf(
                'Database "%s" does not exist. Please create it first or check the name.',
                htmlspecialchars($database)
            ),
            'error_details' => 'The specified database was not found on the server.',
            'troubleshooting' => [
                'Verify the database name is spelled correctly',
                'Create the database using phpMyAdmin or MySQL command line',
                'Check if the database name is case-sensitive on your server',
                'Ensure you have permission to access the database',
                'For shared hosting, database names often have a prefix (e.g., "username_dbname")'
            ],
            'field' => 'database'
        ];
    }

    /**
     * Create socket not found error result
     */
    private function createSocketNotFoundError(string $socketPath): array
    {
        return [
            'success' => false,
            'error_type' => self::ERROR_SOCKET_NOT_FOUND,
            'error_message' => sprintf(
                'Unix socket not found at "%s". Please verify the socket path.',
                htmlspecialchars($socketPath)
            ),
            'error_details' => 'The specified Unix socket file does not exist or is not accessible.',
            'troubleshooting' => [
                'Verify the socket path is correct',
                'Common socket paths:',
                '  - /var/run/mysqld/mysqld.sock (Linux)',
                '  - /tmp/mysql.sock (macOS/Linux)',
                '  - /Applications/XAMPP/xamppfiles/var/mysql/mysql.sock (XAMPP on macOS)',
                'Try using TCP/IP connection instead (host: localhost, port: 3306)',
                'Check if MySQL server is running'
            ],
            'field' => 'unix_socket'
        ];
    }

    /**
     * Create timeout error result
     */
    private function createTimeoutError(array $credentials): array
    {
        $host = $credentials['host'] ?? 'unknown';

        return [
            'success' => false,
            'error_type' => self::ERROR_TIMEOUT,
            'error_message' => sprintf(
                'Connection to %s timed out. The server may be slow or unreachable.',
                htmlspecialchars($host)
            ),
            'error_details' => 'The connection attempt exceeded the timeout limit.',
            'troubleshooting' => [
                'Check your internet connection',
                'Verify the database server is running and accepting connections',
                'The server may be under heavy load - try again later',
                'Check if a firewall is blocking the connection',
                'For remote servers, verify the host allows external connections'
            ],
            'field' => 'host'
        ];
    }

    /**
     * Create unknown error result
     */
    private function createUnknownError(string $originalMessage): array
    {
        return [
            'success' => false,
            'error_type' => self::ERROR_UNKNOWN,
            'error_message' => 'Database connection failed. Please check your credentials and try again.',
            'error_details' => $originalMessage,
            'troubleshooting' => [
                'Verify all connection details are correct',
                'Ensure the database server is running',
                'Check the error details above for more information',
                'Contact your hosting provider if the issue persists'
            ],
            'field' => null
        ];
    }

    /**
     * Get a user-friendly error message for a given error type
     * 
     * @param string $errorType One of the ERROR_* constants
     * @param array $context Additional context (credentials, etc.)
     * @return string User-friendly error message
     */
    public function getErrorMessage(string $errorType, array $context = []): string
    {
        switch ($errorType) {
            case self::ERROR_HOST_UNREACHABLE:
                $host = $context['host'] ?? 'the server';
                $port = $context['port'] ?? 3306;
                return "Cannot connect to database server at {$host}:{$port}. Please verify the host and port are correct.";

            case self::ERROR_INVALID_CREDENTIALS:
                $username = $context['username'] ?? 'the specified user';
                return "Database access denied for user \"{$username}\". Please verify username and password.";

            case self::ERROR_DATABASE_NOT_FOUND:
                $database = $context['database'] ?? 'the specified database';
                return "Database \"{$database}\" does not exist. Please create it first or check the name.";

            case self::ERROR_SOCKET_NOT_FOUND:
                $socket = $context['unix_socket'] ?? 'the specified path';
                return "Unix socket not found at \"{$socket}\". Please verify the socket path.";

            case self::ERROR_TIMEOUT:
                return "Connection timed out. The server may be slow or unreachable.";

            case self::ERROR_CONNECTION_REFUSED:
                return "Connection refused. Please verify the database server is running.";

            default:
                return "Database connection failed. Please check your credentials and try again.";
        }
    }

    /**
     * Get troubleshooting suggestions for a given error type
     * 
     * @param string $errorType One of the ERROR_* constants
     * @return array List of troubleshooting suggestions
     */
    public function getTroubleshootingSuggestions(string $errorType): array
    {
        switch ($errorType) {
            case self::ERROR_HOST_UNREACHABLE:
                return [
                    'Verify the database host address is correct',
                    'Check that the database server is running',
                    'Ensure the port number is correct (default MySQL port is 3306)',
                    'Check if a firewall is blocking the connection'
                ];

            case self::ERROR_INVALID_CREDENTIALS:
                return [
                    'Double-check the username spelling',
                    'Verify the password is correct (passwords are case-sensitive)',
                    'Ensure the user has been granted access to the specified database'
                ];

            case self::ERROR_DATABASE_NOT_FOUND:
                return [
                    'Verify the database name is spelled correctly',
                    'Create the database using phpMyAdmin or MySQL command line',
                    'Check if the database name is case-sensitive on your server'
                ];

            case self::ERROR_SOCKET_NOT_FOUND:
                return [
                    'Verify the socket path is correct',
                    'Try using TCP/IP connection instead',
                    'Check if MySQL server is running'
                ];

            case self::ERROR_TIMEOUT:
                return [
                    'Check your internet connection',
                    'Verify the database server is running',
                    'The server may be under heavy load - try again later'
                ];

            default:
                return [
                    'Verify all connection details are correct',
                    'Ensure the database server is running',
                    'Contact your hosting provider if the issue persists'
                ];
        }
    }
}
