<?php

namespace Tests\Performance;

use PHPUnit\Framework\TestCase;
use Karyalay\Database\Connection;

/**
 * Performance Test: Generate Performance Report
 * 
 * Generates a comprehensive performance report with metrics
 * and recommendations for optimization.
 */
class PerformanceReportTest extends TestCase
{
    private $db;
    private $report = [];
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Connection::getInstance();
        $this->report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'metrics' => [],
            'slow_queries' => [],
            'recommendations' => []
        ];
    }
    
    /**
     * Generate comprehensive performance report
     */
    public function testGeneratePerformanceReport()
    {
        echo "\n\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "              PERFORMANCE TEST REPORT                          \n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "Generated: " . $this->report['timestamp'] . "\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";
        
        // 1. Database Statistics
        $this->collectDatabaseStatistics();
        
        // 2. Table Sizes
        $this->collectTableSizes();
        
        // 3. Index Usage
        $this->checkIndexUsage();
        
        // 4. Slow Query Analysis
        $this->analyzeSlowQueries();
        
        // 5. Cache Statistics
        $this->collectCacheStatistics();
        
        // 6. Recommendations
        $this->generateRecommendations();
        
        // 7. Summary
        $this->printSummary();
        
        $this->assertTrue(true); // Always pass, this is a report
    }
    
    /**
     * Collect database statistics
     */
    private function collectDatabaseStatistics()
    {
        echo "1. DATABASE STATISTICS\n";
        echo "───────────────────────────────────────────────────────────────\n";
        
        // Get database size
        $stmt = $this->db->query("
            SELECT 
                SUM(data_length + index_length) / 1024 / 1024 AS size_mb
            FROM information_schema.TABLES
            WHERE table_schema = DATABASE()
        ");
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $dbSize = round($result['size_mb'], 2);
        
        echo sprintf("Database Size: %.2f MB\n", $dbSize);
        $this->report['metrics']['database_size_mb'] = $dbSize;
        
        // Get connection info
        $stmt = $this->db->query("SHOW STATUS LIKE 'Threads_connected'");
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        echo sprintf("Active Connections: %s\n", $result['Value']);
        
        // Get uptime
        $stmt = $this->db->query("SHOW STATUS LIKE 'Uptime'");
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $uptime = $result['Value'];
        $uptimeHours = round($uptime / 3600, 2);
        echo sprintf("Database Uptime: %.2f hours\n", $uptimeHours);
        
        echo "\n";
    }
    
    /**
     * Collect table sizes
     */
    private function collectTableSizes()
    {
        echo "2. TABLE SIZES\n";
        echo "───────────────────────────────────────────────────────────────\n";
        
        $stmt = $this->db->query("
            SELECT 
                table_name,
                table_rows,
                ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb,
                ROUND(data_length / 1024 / 1024, 2) AS data_mb,
                ROUND(index_length / 1024 / 1024, 2) AS index_mb
            FROM information_schema.TABLES
            WHERE table_schema = DATABASE()
            ORDER BY (data_length + index_length) DESC
        ");
        
        $tables = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        echo sprintf("%-30s %10s %10s %10s %10s\n", "Table", "Rows", "Total MB", "Data MB", "Index MB");
        echo str_repeat("─", 75) . "\n";
        
        foreach ($tables as $table) {
            echo sprintf(
                "%-30s %10s %10s %10s %10s\n",
                $table['table_name'],
                number_format($table['table_rows']),
                $table['size_mb'],
                $table['data_mb'],
                $table['index_mb']
            );
            
            // Flag large tables
            if ($table['table_rows'] > 10000) {
                $this->report['metrics']['large_tables'][] = $table['table_name'];
            }
        }
        
        echo "\n";
    }
    
    /**
     * Check index usage
     */
    private function checkIndexUsage()
    {
        echo "3. INDEX USAGE\n";
        echo "───────────────────────────────────────────────────────────────\n";
        
        $stmt = $this->db->query("
            SELECT 
                table_name,
                index_name,
                non_unique,
                seq_in_index,
                column_name
            FROM information_schema.STATISTICS
            WHERE table_schema = DATABASE()
            ORDER BY table_name, index_name, seq_in_index
        ");
        
        $indexes = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $indexCount = [];
        
        foreach ($indexes as $index) {
            $table = $index['table_name'];
            if (!isset($indexCount[$table])) {
                $indexCount[$table] = 0;
            }
            $indexCount[$table]++;
        }
        
        echo sprintf("%-30s %10s\n", "Table", "Indexes");
        echo str_repeat("─", 45) . "\n";
        
        foreach ($indexCount as $table => $count) {
            echo sprintf("%-30s %10d\n", $table, $count);
        }
        
        echo "\n";
    }
    
    /**
     * Analyze slow queries
     */
    private function analyzeSlowQueries()
    {
        echo "4. QUERY PERFORMANCE ANALYSIS\n";
        echo "───────────────────────────────────────────────────────────────\n";
        
        $testQueries = [
            'User lookup by email' => [
                'query' => "SELECT * FROM users WHERE email = :email LIMIT 1",
                'params' => ['email' => 'test@example.com'],
                'threshold' => 0.1
            ],
            'Active subscriptions' => [
                'query' => "SELECT * FROM subscriptions WHERE status = :status LIMIT 50",
                'params' => ['status' => 'ACTIVE'],
                'threshold' => 0.1
            ],
            'Available ports' => [
                'query' => "SELECT * FROM ports WHERE status = :status LIMIT 10",
                'params' => ['status' => 'AVAILABLE'],
                'threshold' => 0.1
            ],
            'Customer orders' => [
                'query' => "SELECT * FROM orders WHERE customer_id = :customer_id ORDER BY created_at DESC LIMIT 20",
                'params' => ['customer_id' => 1],
                'threshold' => 0.1
            ]
        ];
        
        echo sprintf("%-30s %15s %10s\n", "Query", "Time (ms)", "Status");
        echo str_repeat("─", 60) . "\n";
        
        foreach ($testQueries as $name => $queryInfo) {
            $startTime = microtime(true);
            $stmt = $this->db->prepare($queryInfo['query']);
            $stmt->execute($queryInfo['params']);
            $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $endTime = microtime(true);
            
            $executionTime = ($endTime - $startTime) * 1000; // Convert to ms
            $status = $executionTime < ($queryInfo['threshold'] * 1000) ? '✓ OK' : '⚠ SLOW';
            
            echo sprintf(
                "%-30s %12.2f ms %10s\n",
                $name,
                $executionTime,
                $status
            );
            
            if ($executionTime >= ($queryInfo['threshold'] * 1000)) {
                $this->report['slow_queries'][] = [
                    'name' => $name,
                    'time_ms' => $executionTime,
                    'query' => $queryInfo['query']
                ];
            }
        }
        
        echo "\n";
    }
    
    /**
     * Collect cache statistics
     */
    private function collectCacheStatistics()
    {
        echo "5. CACHE STATISTICS\n";
        echo "───────────────────────────────────────────────────────────────\n";
        
        // Check if APCu is available
        if (function_exists('apcu_cache_info')) {
            $cacheInfo = apcu_cache_info();
            $memInfo = apcu_sma_info();
            
            $hitRate = $cacheInfo['num_hits'] / max(1, $cacheInfo['num_hits'] + $cacheInfo['num_misses']) * 100;
            
            echo sprintf("Cache Type: APCu\n");
            echo sprintf("Memory Size: %.2f MB\n", $memInfo['num_seg'] * $memInfo['seg_size'] / 1024 / 1024);
            echo sprintf("Memory Used: %.2f MB\n", ($memInfo['num_seg'] * $memInfo['seg_size'] - $memInfo['avail_mem']) / 1024 / 1024);
            echo sprintf("Cache Hits: %s\n", number_format($cacheInfo['num_hits']));
            echo sprintf("Cache Misses: %s\n", number_format($cacheInfo['num_misses']));
            echo sprintf("Hit Rate: %.2f%%\n", $hitRate);
            
            $this->report['metrics']['cache_hit_rate'] = $hitRate;
            
            if ($hitRate < 80) {
                $this->report['recommendations'][] = "Cache hit rate is below 80%. Consider increasing cache TTL or reviewing cache strategy.";
            }
        } else {
            echo "APCu cache not available\n";
            $this->report['recommendations'][] = "Consider enabling APCu cache for better performance.";
        }
        
        echo "\n";
    }
    
    /**
     * Generate recommendations
     */
    private function generateRecommendations()
    {
        // Add recommendations based on collected data
        
        // Check for large tables without indexes
        if (isset($this->report['metrics']['large_tables'])) {
            foreach ($this->report['metrics']['large_tables'] as $table) {
                $this->report['recommendations'][] = sprintf(
                    "Table '%s' has many rows. Ensure proper indexes are in place.",
                    $table
                );
            }
        }
        
        // Check for slow queries
        if (!empty($this->report['slow_queries'])) {
            $this->report['recommendations'][] = sprintf(
                "Found %d slow queries. Review and optimize these queries.",
                count($this->report['slow_queries'])
            );
        }
        
        // Check database size
        if (isset($this->report['metrics']['database_size_mb']) && $this->report['metrics']['database_size_mb'] > 1000) {
            $this->report['recommendations'][] = "Database size exceeds 1GB. Consider archiving old data.";
        }
    }
    
    /**
     * Print summary
     */
    private function printSummary()
    {
        echo "6. RECOMMENDATIONS\n";
        echo "───────────────────────────────────────────────────────────────\n";
        
        if (empty($this->report['recommendations'])) {
            echo "✓ No performance issues detected. System is performing well.\n";
        } else {
            foreach ($this->report['recommendations'] as $i => $recommendation) {
                echo sprintf("%d. %s\n", $i + 1, $recommendation);
            }
        }
        
        echo "\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "                    END OF REPORT                              \n";
        echo "═══════════════════════════════════════════════════════════════\n\n";
    }
}
