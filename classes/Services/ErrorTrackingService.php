<?php

namespace Karyalay\Services;

/**
 * Error Tracking Service
 * Integrates with error tracking platforms (Sentry-compatible)
 * Captures errors, exceptions, and messages for monitoring
 */
class ErrorTrackingService
{
    private static ?ErrorTrackingService $instance = null;
    private bool $enabled;
    private string $dsn;
    private string $environment;
    private string $release;
    private array $tags = [];
    
    private function __construct()
    {
        $this->enabled = getenv('ERROR_TRACKING_ENABLED') === 'true';
        $this->dsn = getenv('SENTRY_DSN') ?: '';
        $this->environment = getenv('APP_ENV') ?: 'development';
        $this->release = getenv('APP_VERSION') ?: 'unknown';
        
        // Initialize Sentry if available and enabled
        if ($this->enabled && !empty($this->dsn) && class_exists('\Sentry\init')) {
            \Sentry\init([
                'dsn' => $this->dsn,
                'environment' => $this->environment,
                'release' => $this->release,
                'traces_sample_rate' => $this->getTraceSampleRate(),
            ]);
        }
    }
    
    public static function getInstance(): ErrorTrackingService
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Capture an exception
     */
    public function captureException(\Throwable $exception, array $context = []): ?string
    {
        if (!$this->enabled || empty($this->dsn)) {
            return null;
        }
        
        // If Sentry SDK is available, use it
        if (function_exists('\Sentry\captureException')) {
            if (!empty($context)) {
                \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($context): void {
                    foreach ($context as $key => $value) {
                        $scope->setContext($key, $value);
                    }
                });
            }
            
            $eventId = \Sentry\captureException($exception);
            return (string) $eventId;
        }
        
        // Fallback: send to custom endpoint
        return $this->sendToEndpoint('exception', [
            'exception' => [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace(),
            ],
            'context' => $context,
        ]);
    }
    
    /**
     * Capture a message
     */
    public function captureMessage(string $message, string $level = 'error', array $context = []): ?string
    {
        if (!$this->enabled || empty($this->dsn)) {
            return null;
        }
        
        // If Sentry SDK is available, use it
        if (function_exists('\Sentry\captureMessage')) {
            if (!empty($context)) {
                \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($context): void {
                    foreach ($context as $key => $value) {
                        $scope->setContext($key, $value);
                    }
                });
            }
            
            $sentryLevel = $this->mapLevelToSentry($level);
            $eventId = \Sentry\captureMessage($message, $sentryLevel);
            return (string) $eventId;
        }
        
        // Fallback: send to custom endpoint
        return $this->sendToEndpoint('message', [
            'message' => $message,
            'level' => $level,
            'context' => $context,
        ]);
    }
    
    /**
     * Set user context
     */
    public function setUser(?array $user): void
    {
        if (!$this->enabled || empty($this->dsn)) {
            return;
        }
        
        if (function_exists('\Sentry\configureScope')) {
            \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($user): void {
                if ($user === null) {
                    $scope->setUser(null);
                } else {
                    $scope->setUser([
                        'id' => $user['id'] ?? null,
                        'email' => $user['email'] ?? null,
                        'username' => $user['name'] ?? null,
                    ]);
                }
            });
        }
    }
    
    /**
     * Add breadcrumb for debugging
     */
    public function addBreadcrumb(string $message, string $category = 'default', array $data = []): void
    {
        if (!$this->enabled || empty($this->dsn)) {
            return;
        }
        
        if (function_exists('\Sentry\addBreadcrumb')) {
            \Sentry\addBreadcrumb([
                'message' => $message,
                'category' => $category,
                'data' => $data,
                'timestamp' => time(),
            ]);
        }
    }
    
    /**
     * Set custom tag
     */
    public function setTag(string $key, string $value): void
    {
        $this->tags[$key] = $value;
        
        if (!$this->enabled || empty($this->dsn)) {
            return;
        }
        
        if (function_exists('\Sentry\configureScope')) {
            \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($key, $value): void {
                $scope->setTag($key, $value);
            });
        }
    }
    
    /**
     * Start a performance transaction
     */
    public function startTransaction(string $name, string $op = 'http.request'): ?object
    {
        if (!$this->enabled || empty($this->dsn)) {
            return null;
        }
        
        if (function_exists('\Sentry\startTransaction')) {
            $transactionContext = new \Sentry\Tracing\TransactionContext();
            $transactionContext->setName($name);
            $transactionContext->setOp($op);
            
            return \Sentry\startTransaction($transactionContext);
        }
        
        return null;
    }
    
    /**
     * Map log level to Sentry severity
     */
    private function mapLevelToSentry(string $level): string
    {
        $mapping = [
            'emergency' => \Sentry\Severity::fatal(),
            'alert' => \Sentry\Severity::fatal(),
            'critical' => \Sentry\Severity::fatal(),
            'error' => \Sentry\Severity::error(),
            'warning' => \Sentry\Severity::warning(),
            'notice' => \Sentry\Severity::info(),
            'info' => \Sentry\Severity::info(),
            'debug' => \Sentry\Severity::debug(),
        ];
        
        return $mapping[$level] ?? \Sentry\Severity::error();
    }
    
    /**
     * Get trace sample rate based on environment
     */
    private function getTraceSampleRate(): float
    {
        if ($this->environment === 'production') {
            return 0.1; // 10% sampling in production
        } elseif ($this->environment === 'staging') {
            return 0.5; // 50% sampling in staging
        }
        return 1.0; // 100% sampling in development
    }
    
    /**
     * Send error data to custom endpoint (fallback)
     */
    private function sendToEndpoint(string $type, array $data): ?string
    {
        $payload = [
            'type' => $type,
            'data' => $data,
            'environment' => $this->environment,
            'release' => $this->release,
            'timestamp' => date('c'),
            'server' => [
                'hostname' => gethostname(),
                'php_version' => PHP_VERSION,
            ],
            'tags' => $this->tags,
        ];
        
        // Add request context
        if (isset($_SERVER['REQUEST_URI'])) {
            $payload['request'] = [
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
                'uri' => $_SERVER['REQUEST_URI'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ];
        }
        
        // In a real implementation, this would send to an error tracking endpoint
        // For now, we'll just log it
        $logFile = __DIR__ . '/../../storage/logs/error-tracking.log';
        file_put_contents(
            $logFile,
            json_encode($payload, JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
        
        return uniqid('evt_', true);
    }
}

