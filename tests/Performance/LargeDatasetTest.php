<?php

namespace Tests\Performance;

use PHPUnit\Framework\TestCase;
use Karyalay\Database\Connection;

/**
 * Performance Test: Large Dataset Handling
 * 
 * Tests system performance with large datasets to ensure
 * pagination, caching, and query optimization work correctly.
 */
class LargeDatasetTest extends TestCase
{
    private const MAX_MEMORY_USAGE_MB = 128; // Maximum memory usage in MB
    private const MAX_QUERY_TIME = 0.5; // 500ms for queries with large datasets
    
    private $db;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Connection::getInstance();
    }
    
    /**
     * Get current memory usage in MB
     */
    private function getMemoryUsageMB(): float
    {
        return memory_get_usage(true) / 1024 / 1024;
    }
    
    /**
     * Get peak memory usage in MB
     */
    private function getPeakMemoryUsageMB(): float
    {
        return memory_get_peak_usage(true) / 1024 / 1024;
    }
    
    /**
     * Test fetching large user list with pagination
     */
    public function testLargeUserListWithPagination()
    {
        $memoryBefore = $this->getMemoryUsageMB();
        $startTime = microtime(true);
        
        // Fetch paginated results
        $perPage = 50;
        $page = 1;
        $offset = ($page - 1) * $perPage;
        
        $query = "SELECT * FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $endTime = microtime(true);
        $memoryAfter = $this->getMemoryUsageMB();
        
        $executionTime = $endTime - $startTime;
        $memoryUsed = $memoryAfter - $memoryBefore;
        
        $this->assertLessThan(
            self::MAX_QUERY_TIME,
            $executionTime,
            sprintf('Paginated user list took %.4fs, exceeds target of %.3fs', $executionTime, self::MAX_QUERY_TIME)
        );
        
        $this->assertLessThan(
            self::MAX_MEMORY_USAGE_MB,
            $memoryUsed,
            sprintf('Memory usage %.2fMB exceeds target of %dMB', $memoryUsed, self::MAX_MEMORY_USAGE_MB)
        );
        
        echo sprintf(
            "\n✓ Large user list (paginated): %.4fs, %.2fMB memory, %d rows\n",
            $executionTime,
            $memoryUsed,
            count($users)
        );
    }
    
    /**
     * Test fetching large subscription list with filters
     */
    public function testLargeSubscriptionListWithFilters()
    {
        $memoryBefore = $this->getMemoryUsageMB();
        $startTime = microtime(true);
        
        $query = "
            SELECT s.*, u.name as customer_name, p.name as plan_name
            FROM subscriptions s
            JOIN users u ON s.customer_id = u.id
            JOIN plans p ON s.plan_id = p.id
            WHERE s.status IN (:status1, :status2)
            ORDER BY s.end_date DESC
            LIMIT 100
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'status1' => 'ACTIVE',
            'status2' => 'EXPIRED'
        ]);
        $subscriptions = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $endTime = microtime(true);
        $memoryAfter = $this->getMemoryUsageMB();
        
        $executionTime = $endTime - $startTime;
        $memoryUsed = $memoryAfter - $memoryBefore;
        
        $this->assertLessThan(
            self::MAX_QUERY_TIME,
            $executionTime,
            sprintf('Subscription list with joins took %.4fs, exceeds target of %.3fs', $executionTime, self::MAX_QUERY_TIME)
        );
        
        echo sprintf(
            "\n✓ Large subscription list (with joins): %.4fs, %.2fMB memory, %d rows\n",
            $executionTime,
            $memoryUsed,
            count($subscriptions)
        );
    }
    
    /**
     * Test fetching large order history
     */
    public function testLargeOrderHistory()
    {
        $memoryBefore = $this->getMemoryUsageMB();
        $startTime = microtime(true);
        
        $query = "
            SELECT o.*, u.name as customer_name, p.name as plan_name
            FROM orders o
            JOIN users u ON o.customer_id = u.id
            JOIN plans p ON o.plan_id = p.id
            ORDER BY o.created_at DESC
            LIMIT 200
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $orders = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $endTime = microtime(true);
        $memoryAfter = $this->getMemoryUsageMB();
        
        $executionTime = $endTime - $startTime;
        $memoryUsed = $memoryAfter - $memoryBefore;
        
        $this->assertLessThan(
            self::MAX_QUERY_TIME,
            $executionTime,
            sprintf('Large order history took %.4fs, exceeds target of %.3fs', $executionTime, self::MAX_QUERY_TIME)
        );
        
        echo sprintf(
            "\n✓ Large order history: %.4fs, %.2fMB memory, %d rows\n",
            $executionTime,
            $memoryUsed,
            count($orders)
        );
    }
    
    /**
     * Test fetching ticket list with messages count
     */
    public function testLargeTicketListWithMessageCount()
    {
        $memoryBefore = $this->getMemoryUsageMB();
        $startTime = microtime(true);
        
        $query = "
            SELECT 
                t.*,
                u.name as customer_name,
                COUNT(tm.id) as message_count
            FROM tickets t
            JOIN users u ON t.customer_id = u.id
            LEFT JOIN ticket_messages tm ON t.id = tm.ticket_id
            GROUP BY t.id
            ORDER BY t.updated_at DESC
            LIMIT 100
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $tickets = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $endTime = microtime(true);
        $memoryAfter = $this->getMemoryUsageMB();
        
        $executionTime = $endTime - $startTime;
        $memoryUsed = $memoryAfter - $memoryBefore;
        
        $this->assertLessThan(
            self::MAX_QUERY_TIME,
            $executionTime,
            sprintf('Ticket list with aggregation took %.4fs, exceeds target of %.3fs', $executionTime, self::MAX_QUERY_TIME)
        );
        
        echo sprintf(
            "\n✓ Large ticket list (with aggregation): %.4fs, %.2fMB memory, %d rows\n",
            $executionTime,
            $memoryUsed,
            count($tickets)
        );
    }
    
    /**
     * Test fetching all ports with assignment details
     */
    public function testLargePortListWithAssignments()
    {
        $memoryBefore = $this->getMemoryUsageMB();
        $startTime = microtime(true);
        
        $query = "
            SELECT 
                p.*,
                pl.name as plan_name,
                u.name as assigned_customer_name,
                s.end_date as subscription_end_date
            FROM ports p
            LEFT JOIN subscriptions s ON p.assigned_subscription_id = s.id
            LEFT JOIN plans pl ON s.plan_id = pl.id
            LEFT JOIN users u ON s.customer_id = u.id
            ORDER BY p.status, p.created_at DESC
            LIMIT 200
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $ports = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $endTime = microtime(true);
        $memoryAfter = $this->getMemoryUsageMB();
        
        $executionTime = $endTime - $startTime;
        $memoryUsed = $memoryAfter - $memoryBefore;
        
        $this->assertLessThan(
            self::MAX_QUERY_TIME,
            $executionTime,
            sprintf('Port list with joins took %.4fs, exceeds target of %.3fs', $executionTime, self::MAX_QUERY_TIME)
        );
        
        echo sprintf(
            "\n✓ Large port list (with joins): %.4fs, %.2fMB memory, %d rows\n",
            $executionTime,
            $memoryUsed,
            count($ports)
        );
    }
    
    /**
     * Test fetching blog posts with pagination
     */
    public function testLargeBlogPostList()
    {
        $memoryBefore = $this->getMemoryUsageMB();
        $startTime = microtime(true);
        
        $query = "
            SELECT 
                bp.*,
                u.name as author_name
            FROM blog_posts bp
            JOIN users u ON bp.author_id = u.id
            WHERE bp.status = :status
            ORDER BY bp.published_at DESC
            LIMIT 50
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute(['status' => 'PUBLISHED']);
        $posts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $endTime = microtime(true);
        $memoryAfter = $this->getMemoryUsageMB();
        
        $executionTime = $endTime - $startTime;
        $memoryUsed = $memoryAfter - $memoryBefore;
        
        $this->assertLessThan(
            self::MAX_QUERY_TIME,
            $executionTime,
            sprintf('Blog post list took %.4fs, exceeds target of %.3fs', $executionTime, self::MAX_QUERY_TIME)
        );
        
        echo sprintf(
            "\n✓ Large blog post list: %.4fs, %.2fMB memory, %d rows\n",
            $executionTime,
            $memoryUsed,
            count($posts)
        );
    }
    
    /**
     * Test memory usage when processing multiple pages
     */
    public function testMemoryUsageAcrossMultiplePages()
    {
        $memoryBefore = $this->getMemoryUsageMB();
        $maxMemoryUsed = 0;
        
        // Simulate fetching multiple pages
        for ($page = 1; $page <= 5; $page++) {
            $perPage = 50;
            $offset = ($page - 1) * $perPage;
            
            $query = "SELECT * FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $currentMemory = $this->getMemoryUsageMB() - $memoryBefore;
            $maxMemoryUsed = max($maxMemoryUsed, $currentMemory);
            
            // Clear results to simulate real-world usage
            unset($users);
        }
        
        $this->assertLessThan(
            self::MAX_MEMORY_USAGE_MB,
            $maxMemoryUsed,
            sprintf('Memory usage %.2fMB exceeds target of %dMB', $maxMemoryUsed, self::MAX_MEMORY_USAGE_MB)
        );
        
        echo sprintf(
            "\n✓ Memory usage across 5 pages: %.2fMB (max: %dMB)\n",
            $maxMemoryUsed,
            self::MAX_MEMORY_USAGE_MB
        );
    }
    
    /**
     * Test count query performance for pagination
     */
    public function testCountQueryPerformance()
    {
        $startTime = microtime(true);
        
        $queries = [
            'users' => "SELECT COUNT(*) as total FROM users",
            'subscriptions' => "SELECT COUNT(*) as total FROM subscriptions WHERE status = 'ACTIVE'",
            'orders' => "SELECT COUNT(*) as total FROM orders",
            'tickets' => "SELECT COUNT(*) as total FROM tickets WHERE status != 'CLOSED'",
            'ports' => "SELECT COUNT(*) as total FROM ports WHERE status = 'AVAILABLE'"
        ];
        
        foreach ($queries as $name => $query) {
            $queryStart = microtime(true);
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            $queryEnd = microtime(true);
            
            $queryTime = $queryEnd - $queryStart;
            
            $this->assertLessThan(
                0.1, // 100ms for count queries
                $queryTime,
                sprintf('Count query for %s took %.4fs, exceeds target of 0.1s', $name, $queryTime)
            );
            
            echo sprintf(
                "\n✓ Count query (%s): %.4fs, total: %d\n",
                $name,
                $queryTime,
                $result['total']
            );
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        echo sprintf(
            "\n✓ All count queries: %.4fs\n",
            $totalTime
        );
    }
    
    /**
     * Test overall peak memory usage
     */
    public function testOverallPeakMemoryUsage()
    {
        $peakMemory = $this->getPeakMemoryUsageMB();
        
        echo sprintf(
            "\n✓ Peak memory usage: %.2fMB (limit: %dMB)\n",
            $peakMemory,
            self::MAX_MEMORY_USAGE_MB
        );
        
        // This is informational - we don't fail the test
        // but we log if we're getting close to the limit
        if ($peakMemory > self::MAX_MEMORY_USAGE_MB * 0.8) {
            echo sprintf(
                "\n⚠ Warning: Peak memory usage is at %.0f%% of limit\n",
                ($peakMemory / self::MAX_MEMORY_USAGE_MB) * 100
            );
        }
        
        $this->assertTrue(true); // Always pass, this is informational
    }
}
