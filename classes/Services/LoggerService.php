<?php

namespace Karyalay\Services;

/**
 * Structured Logging Service
 * Provides centralized logging with different severity levels and structured data
 */
class LoggerService
{
    private static ?LoggerService $instance = null;
    private string $logDir;
    private string $environment;
    private bool $enabled;
    
    // Log levels (PSR-3 compatible)
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';
    
    private array $levelPriority = [
        self::EMERGENCY => 800,
        self::ALERT => 700,
        self::CRITICAL => 600,
        self::ERROR => 500,
        self::WARNING => 400,
        self::NOTICE => 300,
        self::INFO => 200,
        self::DEBUG => 100,
    ];
    
    private function __construct()
    {
        $this->logDir = __DIR__ . '/../../storage/logs';
        $this->environment = getenv('APP_ENV') ?: 'development';
        $this->enabled = getenv('LOGGING_ENABLED') !== 'false';
        
        // Create logs directory if it doesn't exist
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }
    
    public static function getInstance(): LoggerService
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Log a message with context
     */
    public function log(string $level, string $message, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }
        
        $logEntry = $this->formatLogEntry($level, $message, $context);
        $this->writeToFile($level, $logEntry);
        
        // Send critical errors to error tracking service
        if ($this->isCriticalLevel($level)) {
            $this->sendToErrorTracking($level, $message, $context);
        }
    }
    
    /**
     * Format log entry as structured JSON
     */
    private function formatLogEntry(string $level, string $message, array $context): string
    {
        $entry = [
            'timestamp' => date('c'),
            'level' => $level,
            'message' => $message,
            'environment' => $this->environment,
            'context' => $context,
        ];
        
        // Add request information if available
        if (isset($_SERVER['REQUEST_URI'])) {
            $entry['request'] = [
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
                'uri' => $_SERVER['REQUEST_URI'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            ];
        }
        
        // Add user information if available
        if (isset($_SESSION['user_id'])) {
            $entry['user'] = [
                'id' => $_SESSION['user_id'],
                'email' => $_SESSION['user_email'] ?? null,
            ];
        }
        
        return json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }
    
    /**
     * Write log entry to file
     */
    private function writeToFile(string $level, string $logEntry): void
    {
        $date = date('Y-m-d');
        $filename = "{$this->logDir}/{$this->environment}-{$date}.log";
        
        // Also write to level-specific file for errors and above
        if ($this->isCriticalLevel($level)) {
            $errorFilename = "{$this->logDir}/errors-{$date}.log";
            file_put_contents($errorFilename, $logEntry, FILE_APPEND | LOCK_EX);
        }
        
        file_put_contents($filename, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Check if log level is critical
     */
    private function isCriticalLevel(string $level): bool
    {
        return in_array($level, [
            self::EMERGENCY,
            self::ALERT,
            self::CRITICAL,
            self::ERROR
        ]);
    }
    
    /**
     * Send critical errors to error tracking service
     */
    private function sendToErrorTracking(string $level, string $message, array $context): void
    {
        $errorTracker = ErrorTrackingService::getInstance();
        $errorTracker->captureMessage($message, $level, $context);
    }
    
    // Convenience methods for each log level
    
    public function emergency(string $message, array $context = []): void
    {
        $this->log(self::EMERGENCY, $message, $context);
    }
    
    public function alert(string $message, array $context = []): void
    {
        $this->log(self::ALERT, $message, $context);
    }
    
    public function critical(string $message, array $context = []): void
    {
        $this->log(self::CRITICAL, $message, $context);
    }
    
    public function error(string $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }
    
    public function warning(string $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }
    
    public function notice(string $message, array $context = []): void
    {
        $this->log(self::NOTICE, $message, $context);
    }
    
    public function info(string $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }
    
    public function debug(string $message, array $context = []): void
    {
        $this->log(self::DEBUG, $message, $context);
    }
    
    /**
     * Log an exception
     */
    public function logException(\Throwable $exception, array $context = []): void
    {
        $context['exception'] = [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];
        
        $this->error($exception->getMessage(), $context);
    }
}

