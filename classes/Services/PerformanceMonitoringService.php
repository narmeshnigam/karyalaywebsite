<?php

namespace Karyalay\Services;

/**
 * Performance Monitoring Service
 * Tracks application performance metrics and slow operations
 */
class PerformanceMonitoringService
{
    private static ?PerformanceMonitoringService $instance = null;
    private bool $enabled;
    private array $timers = [];
    private array $metrics = [];
    private float $requestStartTime;
    
    // Performance thresholds (in milliseconds)
    const SLOW_QUERY_THRESHOLD = 1000; // 1 second
    const SLOW_REQUEST_THRESHOLD = 2000; // 2 seconds
    const MEMORY_WARNING_THRESHOLD = 128 * 1024 * 1024; // 128MB
    
    private function __construct()
    {
        $this->enabled = getenv('PERFORMANCE_MONITORING_ENABLED') !== 'false';
        $this->requestStartTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
    }
    
    public static function getInstance(): PerformanceMonitoringService
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Start a timer for an operation
     */
    public function startTimer(string $name): void
    {
        if (!$this->enabled) {
            return;
        }
        
        $this->timers[$name] = [
            'start' => microtime(true),
            'memory_start' => memory_get_usage(true),
        ];
    }
    
    /**
     * Stop a timer and record the metric
     */
    public function stopTimer(string $name, array $context = []): ?float
    {
        if (!$this->enabled || !isset($this->timers[$name])) {
            return null;
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $duration = ($endTime - $this->timers[$name]['start']) * 1000; // Convert to milliseconds
        $memoryUsed = $endMemory - $this->timers[$name]['memory_start'];
        
        $this->recordMetric($name, $duration, array_merge($context, [
            'memory_used' => $memoryUsed,
        ]));
        
        unset($this->timers[$name]);
        
        return $duration;
    }
    
    /**
     * Record a performance metric
     */
    public function recordMetric(string $name, float $value, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }
        
        $metric = [
            'name' => $name,
            'value' => $value,
            'unit' => 'ms',
            'timestamp' => microtime(true),
            'context' => $context,
        ];
        
        $this->metrics[] = $metric;
        
        // Log slow operations
        if ($this->isSlowOperation($name, $value)) {
            $logger = LoggerService::getInstance();
            $logger->warning("Slow operation detected: {$name}", [
                'duration_ms' => $value,
                'threshold_ms' => $this->getThreshold($name),
                'context' => $context,
            ]);
        }
    }
    
    /**
     * Track database query performance
     */
    public function trackQuery(string $query, float $duration, array $params = []): void
    {
        if (!$this->enabled) {
            return;
        }
        
        $this->recordMetric('database.query', $duration, [
            'query' => $this->sanitizeQuery($query),
            'params_count' => count($params),
        ]);
        
        if ($duration > self::SLOW_QUERY_THRESHOLD) {
            $logger = LoggerService::getInstance();
            $logger->warning('Slow database query detected', [
                'query' => $this->sanitizeQuery($query),
                'duration_ms' => $duration,
                'threshold_ms' => self::SLOW_QUERY_THRESHOLD,
            ]);
        }
    }
    
    /**
     * Track HTTP request performance
     */
    public function trackRequest(): void
    {
        if (!$this->enabled) {
            return;
        }
        
        $duration = (microtime(true) - $this->requestStartTime) * 1000;
        $memoryPeak = memory_get_peak_usage(true);
        
        $context = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'memory_peak' => $memoryPeak,
            'memory_peak_mb' => round($memoryPeak / 1024 / 1024, 2),
        ];
        
        $this->recordMetric('http.request', $duration, $context);
        
        // Check for memory warnings
        if ($memoryPeak > self::MEMORY_WARNING_THRESHOLD) {
            $logger = LoggerService::getInstance();
            $logger->warning('High memory usage detected', $context);
        }
        
        // Log slow requests
        if ($duration > self::SLOW_REQUEST_THRESHOLD) {
            $logger = LoggerService::getInstance();
            $logger->warning('Slow HTTP request detected', array_merge($context, [
                'duration_ms' => $duration,
                'threshold_ms' => self::SLOW_REQUEST_THRESHOLD,
            ]));
        }
    }
    
    /**
     * Get all recorded metrics
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }
    
    /**
     * Get performance summary
     */
    public function getSummary(): array
    {
        $summary = [
            'request_duration_ms' => (microtime(true) - $this->requestStartTime) * 1000,
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'metrics_count' => count($this->metrics),
        ];
        
        // Calculate averages by metric name
        $metricsByName = [];
        foreach ($this->metrics as $metric) {
            $name = $metric['name'];
            if (!isset($metricsByName[$name])) {
                $metricsByName[$name] = [
                    'count' => 0,
                    'total' => 0,
                    'min' => PHP_FLOAT_MAX,
                    'max' => 0,
                ];
            }
            
            $metricsByName[$name]['count']++;
            $metricsByName[$name]['total'] += $metric['value'];
            $metricsByName[$name]['min'] = min($metricsByName[$name]['min'], $metric['value']);
            $metricsByName[$name]['max'] = max($metricsByName[$name]['max'], $metric['value']);
        }
        
        foreach ($metricsByName as $name => $data) {
            $summary['metrics'][$name] = [
                'count' => $data['count'],
                'avg_ms' => round($data['total'] / $data['count'], 2),
                'min_ms' => round($data['min'], 2),
                'max_ms' => round($data['max'], 2),
            ];
        }
        
        return $summary;
    }
    
    /**
     * Write metrics to log file
     */
    public function flush(): void
    {
        if (!$this->enabled || empty($this->metrics)) {
            return;
        }
        
        $logDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $date = date('Y-m-d');
        $filename = "{$logDir}/performance-{$date}.log";
        
        $summary = $this->getSummary();
        $logEntry = json_encode([
            'timestamp' => date('c'),
            'summary' => $summary,
            'metrics' => $this->metrics,
        ], JSON_UNESCAPED_SLASHES) . PHP_EOL;
        
        file_put_contents($filename, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Check if operation is slow
     */
    private function isSlowOperation(string $name, float $value): bool
    {
        $threshold = $this->getThreshold($name);
        return $value > $threshold;
    }
    
    /**
     * Get threshold for operation type
     */
    private function getThreshold(string $name): float
    {
        if (strpos($name, 'database') !== false) {
            return self::SLOW_QUERY_THRESHOLD;
        }
        if (strpos($name, 'http') !== false) {
            return self::SLOW_REQUEST_THRESHOLD;
        }
        return 1000; // Default 1 second
    }
    
    /**
     * Sanitize SQL query for logging (remove sensitive data)
     */
    private function sanitizeQuery(string $query): string
    {
        // Remove potential sensitive data from queries
        $query = preg_replace('/password\s*=\s*[\'"][^\'"]*[\'"]/i', 'password=***', $query);
        $query = preg_replace('/token\s*=\s*[\'"][^\'"]*[\'"]/i', 'token=***', $query);
        
        // Truncate very long queries
        if (strlen($query) > 500) {
            $query = substr($query, 0, 500) . '... [truncated]';
        }
        
        return $query;
    }
}

