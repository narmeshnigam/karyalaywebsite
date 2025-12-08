<?php

/**
 * Property Test: Subscription Filtering by Date Range
 * Feature: karyalay-portal-system, Property 34: Subscription Filtering by Date Range
 * Validates: Requirements 10.5
 * 
 * Property: For any date range filter, when applied to the subscriptions list, 
 * only subscriptions with end dates within the specified range should be returned.
 */

namespace Karyalay\Tests\Property;

use PHPUnit\Framework\TestCase;
use Karyalay\Database\Connection;
use Karyalay\Models\Subscription;
use Karyalay\Models\Plan;
use Karyalay\Models\User;
use Karyalay\Models\Order;
use PDO;
use DateTime;

class SubscriptionFilteringByDateRangePropertyTest extends TestCase
{
    private PDO $db;
    private Subscription $subscriptionModel;
    private Plan $planModel;
    private User $userModel;
    private Order $orderModel;
    private array $testData = [];

    protected function setUp(): void
    {
        $this->db = Connection::getInstance();
        $this->subscriptionModel = new Subscription();
        $this->planModel = new Plan();
        $this->userModel = new User();
        $this->orderModel = new Order();
        
        // Start transaction for test isolation
        $this->db->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Rollback transaction to clean up test data
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        
        // Clean up any test data that might have been committed
        foreach ($this->testData as $data) {
            if (isset($data['subscription_id'])) {
                try {
                    $this->subscriptionModel->delete($data['subscription_id']);
                } catch (\Exception $e) {
                    // Ignore errors during cleanup
                }
            }
            if (isset($data['order_id'])) {
                try {
                    $this->orderModel->delete($data['order_id']);
                } catch (\Exception $e) {
                    // Ignore errors during cleanup
                }
            }
            if (isset($data['user_id'])) {
                try {
                    $this->userModel->delete($data['user_id']);
                } catch (\Exception $e) {
                    // Ignore errors during cleanup
                }
            }
            if (isset($data['plan_id'])) {
                try {
                    $this->planModel->delete($data['plan_id']);
                } catch (\Exception $e) {
                    // Ignore errors during cleanup
                }
            }
        }
        
        $this->testData = [];
    }

    /**
     * Test Property 34: Subscription Filtering by Date Range
     * 
     * For any date range filter, when applied to the subscriptions list,
     * only subscriptions with end dates within the specified range should be returned.
     * 
     * @test
     */
    public function test_subscription_filtering_by_date_range_returns_only_subscriptions_within_range()
    {
        // Run the property test multiple times with different random data
        $iterations = 20;
        
        for ($i = 0; $i < $iterations; $i++) {
            $this->runSingleIteration();
            
            // Clean up after each iteration
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
                $this->db->beginTransaction();
            }
        }
        
        $this->assertTrue(true, "All $iterations iterations passed");
    }

    private function runSingleIteration(): void
    {
        // Generate random test data
        $testPlan = $this->createTestPlan();
        $testUser = $this->createTestUser();
        $testOrder = $this->createTestOrder($testUser['id'], $testPlan['id']);
        
        // Generate random date range for filtering
        $baseDate = new DateTime();
        $baseDate->modify('+' . rand(1, 365) . ' days');
        
        $filterStartDate = clone $baseDate;
        $filterStartDate->modify('-' . rand(0, 30) . ' days');
        
        $filterEndDate = clone $baseDate;
        $filterEndDate->modify('+' . rand(0, 30) . ' days');
        
        // Create subscriptions with various end dates
        $subscriptionsInRange = [];
        $subscriptionsOutOfRange = [];
        
        // Create 3-5 subscriptions within the range
        $numInRange = rand(3, 5);
        for ($j = 0; $j < $numInRange; $j++) {
            $endDate = clone $filterStartDate;
            $daysOffset = rand(0, $filterEndDate->diff($filterStartDate)->days);
            $endDate->modify("+{$daysOffset} days");
            
            $subscription = $this->createTestSubscription(
                $testUser['id'],
                $testPlan['id'],
                $testOrder['id'],
                $endDate->format('Y-m-d')
            );
            $subscriptionsInRange[] = $subscription['id'];
        }
        
        // Create 2-4 subscriptions outside the range (before)
        $numOutBefore = rand(2, 4);
        for ($j = 0; $j < $numOutBefore; $j++) {
            $endDate = clone $filterStartDate;
            $endDate->modify('-' . rand(1, 60) . ' days');
            
            $subscription = $this->createTestSubscription(
                $testUser['id'],
                $testPlan['id'],
                $testOrder['id'],
                $endDate->format('Y-m-d')
            );
            $subscriptionsOutOfRange[] = $subscription['id'];
        }
        
        // Create 2-4 subscriptions outside the range (after)
        $numOutAfter = rand(2, 4);
        for ($j = 0; $j < $numOutAfter; $j++) {
            $endDate = clone $filterEndDate;
            $endDate->modify('+' . rand(1, 60) . ' days');
            
            $subscription = $this->createTestSubscription(
                $testUser['id'],
                $testPlan['id'],
                $testOrder['id'],
                $endDate->format('Y-m-d')
            );
            $subscriptionsOutOfRange[] = $subscription['id'];
        }
        
        // Apply the date range filter using the Subscription model
        $filters = [
            'end_date_from' => $filterStartDate->format('Y-m-d'),
            'end_date_to' => $filterEndDate->format('Y-m-d')
        ];
        
        $filteredSubscriptions = $this->subscriptionModel->findAll($filters, 100, 0);
        
        // Extract IDs from filtered results
        $filteredIds = array_map(function($sub) {
            return $sub['id'];
        }, $filteredSubscriptions);
        
        // Property assertion: All subscriptions in range should be in results
        foreach ($subscriptionsInRange as $expectedId) {
            $this->assertContains(
                $expectedId,
                $filteredIds,
                "Subscription with ID {$expectedId} should be in filtered results (end date within range)"
            );
        }
        
        // Property assertion: No subscriptions outside range should be in results
        foreach ($subscriptionsOutOfRange as $unexpectedId) {
            $this->assertNotContains(
                $unexpectedId,
                $filteredIds,
                "Subscription with ID {$unexpectedId} should NOT be in filtered results (end date outside range)"
            );
        }
        
        // Property assertion: All returned subscriptions have end dates within the range
        foreach ($filteredSubscriptions as $subscription) {
            $endDate = new DateTime($subscription['end_date']);
            
            $this->assertGreaterThanOrEqual(
                $filterStartDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
                "Subscription end date should be >= filter start date"
            );
            
            $this->assertLessThanOrEqual(
                $filterEndDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
                "Subscription end date should be <= filter end date"
            );
        }
    }

    /**
     * Test edge case: Empty date range returns no results
     * 
     * @test
     */
    public function test_filtering_with_impossible_date_range_returns_empty()
    {
        // Create test data
        $testPlan = $this->createTestPlan();
        $testUser = $this->createTestUser();
        $testOrder = $this->createTestOrder($testUser['id'], $testPlan['id']);
        
        // Create a subscription
        $endDate = (new DateTime())->modify('+30 days')->format('Y-m-d');
        $this->createTestSubscription($testUser['id'], $testPlan['id'], $testOrder['id'], $endDate);
        
        // Apply impossible date range (end before start)
        $filters = [
            'end_date_from' => '2025-12-31',
            'end_date_to' => '2025-01-01'
        ];
        
        $filteredSubscriptions = $this->subscriptionModel->findAll($filters, 100, 0);
        
        // Should return empty results
        $this->assertEmpty(
            $filteredSubscriptions,
            "Impossible date range (end before start) should return no results"
        );
    }

    /**
     * Test edge case: Single date filter (from only)
     * 
     * @test
     */
    public function test_filtering_with_only_start_date_returns_subscriptions_after_date()
    {
        // Create test data
        $testPlan = $this->createTestPlan();
        $testUser = $this->createTestUser();
        $testOrder = $this->createTestOrder($testUser['id'], $testPlan['id']);
        
        $filterDate = new DateTime('2025-06-01');
        
        // Create subscription before filter date
        $beforeSub = $this->createTestSubscription(
            $testUser['id'],
            $testPlan['id'],
            $testOrder['id'],
            '2025-05-15'
        );
        
        // Create subscription after filter date
        $afterSub = $this->createTestSubscription(
            $testUser['id'],
            $testPlan['id'],
            $testOrder['id'],
            '2025-07-15'
        );
        
        // Apply filter with only start date
        $filters = [
            'end_date_from' => $filterDate->format('Y-m-d')
        ];
        
        $filteredSubscriptions = $this->subscriptionModel->findAll($filters, 100, 0);
        $filteredIds = array_map(fn($s) => $s['id'], $filteredSubscriptions);
        
        // Should include subscription after date
        $this->assertContains($afterSub['id'], $filteredIds);
        
        // Should not include subscription before date
        $this->assertNotContains($beforeSub['id'], $filteredIds);
    }

    /**
     * Test edge case: Single date filter (to only)
     * 
     * @test
     */
    public function test_filtering_with_only_end_date_returns_subscriptions_before_date()
    {
        // Create test data
        $testPlan = $this->createTestPlan();
        $testUser = $this->createTestUser();
        $testOrder = $this->createTestOrder($testUser['id'], $testPlan['id']);
        
        $filterDate = new DateTime('2025-06-01');
        
        // Create subscription before filter date
        $beforeSub = $this->createTestSubscription(
            $testUser['id'],
            $testPlan['id'],
            $testOrder['id'],
            '2025-05-15'
        );
        
        // Create subscription after filter date
        $afterSub = $this->createTestSubscription(
            $testUser['id'],
            $testPlan['id'],
            $testOrder['id'],
            '2025-07-15'
        );
        
        // Apply filter with only end date
        $filters = [
            'end_date_to' => $filterDate->format('Y-m-d')
        ];
        
        $filteredSubscriptions = $this->subscriptionModel->findAll($filters, 100, 0);
        $filteredIds = array_map(fn($s) => $s['id'], $filteredSubscriptions);
        
        // Should include subscription before date
        $this->assertContains($beforeSub['id'], $filteredIds);
        
        // Should not include subscription after date
        $this->assertNotContains($afterSub['id'], $filteredIds);
    }

    // Helper methods

    private function createTestPlan(): array
    {
        $plan = $this->planModel->create([
            'name' => 'Test Plan ' . uniqid(),
            'slug' => 'test-plan-' . uniqid(),
            'description' => 'Test plan for property testing',
            'price' => rand(100, 1000),
            'currency' => 'USD',
            'billing_period_months' => rand(1, 12),
            'features' => json_encode(['feature1', 'feature2']),
            'status' => 'ACTIVE'
        ]);
        
        $this->testData[] = ['plan_id' => $plan['id']];
        return $plan;
    }

    private function createTestUser(): array
    {
        $uniqueId = uniqid();
        $user = $this->userModel->create([
            'email' => "test-{$uniqueId}@example.com",
            'password' => 'password123',
            'name' => "Test User {$uniqueId}",
            'phone' => '1234567890',
            'role' => 'CUSTOMER'
        ]);
        
        $this->testData[] = ['user_id' => $user['id']];
        return $user;
    }

    private function createTestOrder(string $userId, string $planId): array
    {
        $order = $this->orderModel->create([
            'customer_id' => $userId,
            'plan_id' => $planId,
            'amount' => rand(100, 1000),
            'currency' => 'USD',
            'status' => 'SUCCESS'
        ]);
        
        $this->testData[] = ['order_id' => $order['id']];
        return $order;
    }

    private function createTestSubscription(
        string $userId,
        string $planId,
        string $orderId,
        string $endDate
    ): array {
        $startDate = (new DateTime($endDate))->modify('-30 days')->format('Y-m-d');
        
        $subscription = $this->subscriptionModel->create([
            'customer_id' => $userId,
            'plan_id' => $planId,
            'order_id' => $orderId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'ACTIVE'
        ]);
        
        $this->testData[] = ['subscription_id' => $subscription['id']];
        return $subscription;
    }
}
