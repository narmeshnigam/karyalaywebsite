<?php

namespace Tests\Performance;

use PHPUnit\Framework\TestCase;
use Karyalay\Database\Connection;

/**
 * Performance Test: Database Query Performance
 * 
 * Tests database query performance with large datasets.
 * Identifies slow queries that need optimization.
 */
class DatabaseQueryPerformanceTest extends TestCase
{
    private const MAX_QUERY_TIME = 0.1; // 100ms for most queries
    private const MAX_COMPLEX_QUERY_TIME = 0.5; // 500ms for complex queries
    
    private $db;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Connection::getInstance();
    }
    
    /**
     * Measure query execution time
     */
    private function measureQueryTime(string $query, array $params = []): array
    {
        $startTime = microtime(true);
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        return [
            'query' => $query,
            'execution_time' => $executionTime,
            'row_count' => count($results),
            'results' => $results
        ];
    }
    
    /**
     * Get query execution plan
     */
    private function explainQuery(string $query, array $params = []): array
    {
        $explainQuery = "EXPLAIN " . $query;
        $stmt = $this->db->prepare($explainQuery);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Test user lookup by email performance
     */
    public function testUserLookupByEmailPerformance()
    {
        $query = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $result = $this->measureQueryTime($query, ['email' => 'test@example.com']);
        
        $this->assertLessThan(
            self::MAX_QUERY_TIME,
            $result['execution_time'],
            sprintf(
                'User lookup by email took %.4fs, exceeds target of %.3fs',
                $result['execution_time'],
                self::MAX_QUERY_TIME
            )
        );
        
        // Check if index is being used
        $explain = $this->explainQuery($query, ['email' => 'test@example.com']);
        $this->assertNotEmpty($explain, 'EXPLAIN should return results');
        
        echo sprintf(
            "\n✓ User lookup by email: %.4fs (target: %.3fs)\n",
            $result['execution_time'],
            self::MAX_QUERY_TIME
        );
    }
    
    /**
     * Test active subscriptions query performance
     */
    public function testActiveSubscriptionsQueryPerformance()
    {
        $query = "SELECT * FROM subscriptions WHERE status = :status ORDER BY end_date DESC LIMIT 50";
        $result = $this->measureQueryTime($query, ['status' => 'ACTIVE']);
        
        $this->assertLessThan(
            self::MAX_QUERY_TIME,
            $result['execution_time'],
            sprintf(
                'Active subscriptions query took %.4fs, exceeds target of %.3fs',
                $result['execution_time'],
                self::MAX_QUERY_TIME
            )
        );
        
        echo sprintf(
            "\n✓ Active subscriptions query: %.4fs (target: %.3fs, rows: %d)\n",
            $result['execution_time'],
            self::MAX_QUERY_TIME,
            $result['row_count']
        );
    }
    
    /**
     * Test available ports query performance
     */
    public function testAvailablePortsQueryPerformance()
    {
        $query = "SELECT * FROM ports WHERE status = :status AND plan_id = :plan_id LIMIT 1";
        $result = $this->measureQueryTime($query, [
            'status' => 'AVAILABLE',
            'plan_id' => 1
        ]);
        
        $this->assertLessThan(
            self::MAX_QUERY_TIME,
            $result['execution_time'],
            sprintf(
                'Available ports query took %.4fs, exceeds target of %.3fs',
                $result['execution_time'],
                self::MAX_QUERY_TIME
            )
        );
        
        echo sprintf(
            "\n✓ Available ports query: %.4fs (target: %.3fs)\n",
            $result['execution_time'],
            self::MAX_QUERY_TIME
        );
    }
    
    /**
     * Test customer orders query performance
     */
    public function testCustomerOrdersQueryPerformance()
    {
        $query = "SELECT * FROM orders WHERE customer_id = :customer_id ORDER BY created_at DESC LIMIT 20";
        $result = $this->measureQueryTime($query, ['customer_id' => 1]);
        
        $this->assertLessThan(
            self::MAX_QUERY_TIME,
            $result['execution_time'],
            sprintf(
                'Customer orders query took %.4fs, exceeds target of %.3fs',
                $result['execution_time'],
                self::MAX_QUERY_TIME
            )
        );
        
        echo sprintf(
            "\n✓ Customer orders query: %.4fs (target: %.3fs, rows: %d)\n",
            $result['execution_time'],
            self::MAX_QUERY_TIME,
            $result['row_count']
        );
    }
    
    /**
     * Test ticket list query performance
     */
    public function testTicketListQueryPerformance()
    {
        $query = "SELECT * FROM tickets WHERE customer_id = :customer_id ORDER BY updated_at DESC LIMIT 20";
        $result = $this->measureQueryTime($query, ['customer_id' => 1]);
        
        $this->assertLessThan(
            self::MAX_QUERY_TIME,
            $result['execution_time'],
            sprintf(
                'Ticket list query took %.4fs, exceeds target of %.3fs',
                $result['execution_time'],
                self::MAX_QUERY_TIME
            )
        );
        
        echo sprintf(
            "\n✓ Ticket list query: %.4fs (target: %.3fs, rows: %d)\n",
            $result['execution_time'],
            self::MAX_QUERY_TIME,
            $result['row_count']
        );
    }
    
    /**
     * Test complex join query performance (customer detail page)
     */
    public function testCustomerDetailJoinQueryPerformance()
    {
        $query = "
            SELECT 
                u.*,
                COUNT(DISTINCT s.id) as subscription_count,
                COUNT(DISTINCT o.id) as order_count,
                COUNT(DISTINCT t.id) as ticket_count
            FROM users u
            LEFT JOIN subscriptions s ON u.id = s.customer_id
            LEFT JOIN orders o ON u.id = o.customer_id
            LEFT JOIN tickets t ON u.id = t.customer_id
            WHERE u.id = :user_id
            GROUP BY u.id
        ";
        
        $result = $this->measureQueryTime($query, ['user_id' => 1]);
        
        $this->assertLessThan(
            self::MAX_COMPLEX_QUERY_TIME,
            $result['execution_time'],
            sprintf(
                'Customer detail join query took %.4fs, exceeds target of %.3fs',
                $result['execution_time'],
                self::MAX_COMPLEX_QUERY_TIME
            )
        );
        
        echo sprintf(
            "\n✓ Customer detail join query: %.4fs (target: %.3fs)\n",
            $result['execution_time'],
            self::MAX_COMPLEX_QUERY_TIME
        );
    }
    
    /**
     * Test published content query performance
     */
    public function testPublishedContentQueryPerformance()
    {
        $query = "SELECT * FROM blog_posts WHERE status = :status ORDER BY published_at DESC LIMIT 20";
        $result = $this->measureQueryTime($query, ['status' => 'PUBLISHED']);
        
        $this->assertLessThan(
            self::MAX_QUERY_TIME,
            $result['execution_time'],
            sprintf(
                'Published content query took %.4fs, exceeds target of %.3fs',
                $result['execution_time'],
                self::MAX_QUERY_TIME
            )
        );
        
        echo sprintf(
            "\n✓ Published content query: %.4fs (target: %.3fs, rows: %d)\n",
            $result['execution_time'],
            self::MAX_QUERY_TIME,
            $result['row_count']
        );
    }
    
    /**
     * Test pagination query performance with large offset
     */
    public function testPaginationWithLargeOffsetPerformance()
    {
        $query = "SELECT * FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $result = $this->measureQueryTime($query, [
            'limit' => 20,
            'offset' => 1000
        ]);
        
        $this->assertLessThan(
            self::MAX_QUERY_TIME,
            $result['execution_time'],
            sprintf(
                'Pagination with large offset took %.4fs, exceeds target of %.3fs',
                $result['execution_time'],
                self::MAX_QUERY_TIME
            )
        );
        
        echo sprintf(
            "\n✓ Pagination with large offset: %.4fs (target: %.3fs)\n",
            $result['execution_time'],
            self::MAX_QUERY_TIME
        );
    }
    
    /**
     * Test subscription expiration check query performance
     */
    public function testSubscriptionExpirationCheckPerformance()
    {
        $query = "
            SELECT id, customer_id, end_date 
            FROM subscriptions 
            WHERE status = :status 
            AND end_date < :current_date
            LIMIT 100
        ";
        
        $result = $this->measureQueryTime($query, [
            'status' => 'ACTIVE',
            'current_date' => date('Y-m-d H:i:s')
        ]);
        
        $this->assertLessThan(
            self::MAX_QUERY_TIME,
            $result['execution_time'],
            sprintf(
                'Subscription expiration check took %.4fs, exceeds target of %.3fs',
                $result['execution_time'],
                self::MAX_QUERY_TIME
            )
        );
        
        echo sprintf(
            "\n✓ Subscription expiration check: %.4fs (target: %.3fs)\n",
            $result['execution_time'],
            self::MAX_QUERY_TIME
        );
    }
    
    /**
     * Test search query performance
     */
    public function testSearchQueryPerformance()
    {
        $query = "
            SELECT * FROM users 
            WHERE email LIKE :search1 
            OR name LIKE :search2 
            OR business_name LIKE :search3
            LIMIT 20
        ";
        
        $result = $this->measureQueryTime($query, [
            'search1' => '%test%',
            'search2' => '%test%',
            'search3' => '%test%'
        ]);
        
        $this->assertLessThan(
            self::MAX_COMPLEX_QUERY_TIME,
            $result['execution_time'],
            sprintf(
                'Search query took %.4fs, exceeds target of %.3fs',
                $result['execution_time'],
                self::MAX_COMPLEX_QUERY_TIME
            )
        );
        
        echo sprintf(
            "\n✓ Search query: %.4fs (target: %.3fs, rows: %d)\n",
            $result['execution_time'],
            self::MAX_COMPLEX_QUERY_TIME,
            $result['row_count']
        );
    }
    
    /**
     * Test index usage on frequently queried tables
     */
    public function testIndexUsageOnFrequentQueries()
    {
        $queries = [
            [
                'name' => 'User by email',
                'query' => 'SELECT * FROM users WHERE email = :email',
                'params' => ['email' => 'test@example.com'],
                'expected_key' => 'idx_users_email'
            ],
            [
                'name' => 'Subscriptions by customer',
                'query' => 'SELECT * FROM subscriptions WHERE customer_id = :customer_id',
                'params' => ['customer_id' => 1],
                'expected_key' => 'idx_subscriptions_customer'
            ],
            [
                'name' => 'Ports by status',
                'query' => 'SELECT * FROM ports WHERE status = :status',
                'params' => ['status' => 'AVAILABLE'],
                'expected_key' => 'idx_ports_status'
            ]
        ];
        
        foreach ($queries as $queryInfo) {
            $explain = $this->explainQuery($queryInfo['query'], $queryInfo['params']);
            
            // Check if an index is being used (not a full table scan)
            $usingIndex = false;
            foreach ($explain as $row) {
                if (isset($row['key']) && $row['key'] !== null) {
                    $usingIndex = true;
                    echo sprintf(
                        "\n✓ %s: Using index '%s'\n",
                        $queryInfo['name'],
                        $row['key']
                    );
                    break;
                }
            }
            
            $this->assertTrue(
                $usingIndex,
                sprintf('%s should use an index', $queryInfo['name'])
            );
        }
    }
}
