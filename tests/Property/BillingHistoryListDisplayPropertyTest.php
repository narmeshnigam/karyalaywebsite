<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Models\Order;
use Karyalay\Models\Plan;
use Karyalay\Models\User;
use Karyalay\Services\OrderService;

/**
 * Property-based tests for billing history list display completeness
 * 
 * Feature: karyalay-portal-system, Property 18: List Display Completeness
 * Validates: Requirements 5.3
 */
class BillingHistoryListDisplayPropertyTest extends TestCase
{
    use TestTrait;

    private Order $orderModel;
    private Plan $planModel;
    private User $userModel;
    private OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderModel = new Order();
        $this->planModel = new Plan();
        $this->userModel = new User();
        $this->orderService = new OrderService();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Property 18: List Display Completeness
     * 
     * For any set of orders, when the billing history page is accessed,
     * all orders should be displayed with date, plan, amount, and status.
     * 
     * Validates: Requirements 5.3
     * 
     * @test
     */
    public function billingHistoryDisplaysAllOrdersWithRequiredFields(): void
    {
        $this->forAll(
            Generator\choose(1, 10), // Number of orders to create
            Generator\elements(['PENDING', 'SUCCESS', 'FAILED', 'CANCELLED'])
        )
        ->then(function ($orderCount, $status) {
            // Arrange: Create test user
            $testUser = $this->createTestUser();
            
            // Arrange: Create test plan
            $testPlan = $this->createTestPlan();
            
            // Arrange: Create multiple orders for the customer
            $createdOrders = [];
            for ($i = 0; $i < $orderCount; $i++) {
                $order = $this->createTestOrder($testUser['id'], $testPlan['id'], $status);
                $this->assertNotFalse($order, 'Order should be created');
                $createdOrders[] = $order;
            }
            
            // Act: Fetch orders for customer (as billing history page would)
            $fetchedOrders = $this->orderService->getOrdersByCustomer($testUser['id']);
            
            // Assert: All created orders are retrieved
            $this->assertCount(
                $orderCount,
                $fetchedOrders,
                'Billing history should display all orders for the customer'
            );
            
            // Assert: Each order has all required fields
            foreach ($fetchedOrders as $order) {
                // Assert: Order has date (created_at)
                $this->assertArrayHasKey(
                    'created_at',
                    $order,
                    'Billing history should display order date'
                );
                $this->assertNotNull($order['created_at']);
                
                // Assert: Date is valid
                $orderDate = strtotime($order['created_at']);
                $this->assertNotFalse(
                    $orderDate,
                    'Order date should be a valid datetime'
                );
                
                // Assert: Order has plan_id
                $this->assertArrayHasKey(
                    'plan_id',
                    $order,
                    'Billing history should include plan reference'
                );
                $this->assertNotNull($order['plan_id']);
                
                // Assert: Plan can be fetched (for display)
                $plan = $this->planModel->findById($order['plan_id']);
                $this->assertNotFalse(
                    $plan,
                    'Billing history should be able to fetch plan details'
                );
                $this->assertArrayHasKey('name', $plan);
                $this->assertNotNull($plan['name']);
                
                // Assert: Order has amount
                $this->assertArrayHasKey(
                    'amount',
                    $order,
                    'Billing history should display order amount'
                );
                $this->assertNotNull($order['amount']);
                $this->assertIsNumeric($order['amount']);
                $this->assertGreaterThanOrEqual(
                    0,
                    $order['amount'],
                    'Order amount should be non-negative'
                );
                
                // Assert: Order has currency
                $this->assertArrayHasKey('currency', $order);
                $this->assertNotNull($order['currency']);
                
                // Assert: Order has status
                $this->assertArrayHasKey(
                    'status',
                    $order,
                    'Billing history should display order status'
                );
                $this->assertNotNull($order['status']);
                $this->assertEquals(
                    $status,
                    $order['status'],
                    'Order status should match created status'
                );
                
                // Assert: Status is valid
                $validStatuses = ['PENDING', 'SUCCESS', 'FAILED', 'CANCELLED'];
                $this->assertContains(
                    $order['status'],
                    $validStatuses,
                    'Order status should be one of the valid statuses'
                );
            }
            
            // Assert: Orders are sorted by date (most recent first)
            if (count($fetchedOrders) > 1) {
                for ($i = 0; $i < count($fetchedOrders) - 1; $i++) {
                    $currentDate = strtotime($fetchedOrders[$i]['created_at']);
                    $nextDate = strtotime($fetchedOrders[$i + 1]['created_at']);
                    $this->assertGreaterThanOrEqual(
                        $nextDate,
                        $currentDate,
                        'Orders should be sorted by date (most recent first)'
                    );
                }
            }
            
            // Cleanup
            foreach ($createdOrders as $order) {
                $this->cleanupOrder($order['id']);
            }
            $this->cleanupPlan($testPlan['id']);
            $this->cleanupUser($testUser['id']);
        });
    }

    /**
     * Property: Billing history handles customer with no orders
     * 
     * @test
     */
    public function billingHistoryHandlesCustomerWithNoOrders(): void
    {
        // Arrange: Create test user without orders
        $testUser = $this->createTestUser();
        
        // Act: Fetch orders for customer
        $orders = $this->orderService->getOrdersByCustomer($testUser['id']);
        
        // Assert: Empty array is returned
        $this->assertIsArray($orders);
        $this->assertEmpty(
            $orders,
            'Billing history should return empty array for customer with no orders'
        );
        
        // Cleanup
        $this->cleanupUser($testUser['id']);
    }

    /**
     * Property: Billing history displays orders with different statuses correctly
     * 
     * @test
     */
    public function billingHistoryDisplaysOrdersWithDifferentStatuses(): void
    {
        $this->forAll(
            Generator\elements(['PENDING', 'SUCCESS', 'FAILED', 'CANCELLED']),
            Generator\elements(['PENDING', 'SUCCESS', 'FAILED', 'CANCELLED'])
        )
        ->then(function ($status1, $status2) {
            // Arrange: Create test user and plan
            $testUser = $this->createTestUser();
            $testPlan = $this->createTestPlan();
            
            // Arrange: Create orders with different statuses
            $order1 = $this->createTestOrder($testUser['id'], $testPlan['id'], $status1);
            $order2 = $this->createTestOrder($testUser['id'], $testPlan['id'], $status2);
            
            // Act: Fetch orders
            $orders = $this->orderService->getOrdersByCustomer($testUser['id']);
            
            // Assert: Both orders are displayed
            $this->assertCount(2, $orders);
            
            // Assert: Each order has correct status
            $statuses = array_column($orders, 'status');
            $this->assertContains($status1, $statuses);
            $this->assertContains($status2, $statuses);
            
            // Cleanup
            $this->cleanupOrder($order1['id']);
            $this->cleanupOrder($order2['id']);
            $this->cleanupPlan($testPlan['id']);
            $this->cleanupUser($testUser['id']);
        });
    }

    /**
     * Property: Billing history displays correct amount and currency
     * 
     * @test
     */
    public function billingHistoryDisplaysCorrectAmountAndCurrency(): void
    {
        $this->forAll(
            Generator\choose(10, 10000), // Amount in cents/smallest unit
            Generator\elements(['USD', 'EUR', 'GBP', 'INR'])
        )
        ->then(function ($amount, $currency) {
            // Arrange: Create test user and plan with specific price
            $testUser = $this->createTestUser();
            $testPlan = $this->createTestPlanWithPrice($amount, $currency);
            
            // Arrange: Create order
            $order = $this->createTestOrder($testUser['id'], $testPlan['id'], 'SUCCESS');
            
            // Act: Fetch orders
            $orders = $this->orderService->getOrdersByCustomer($testUser['id']);
            
            // Assert: Order has correct amount and currency
            $this->assertCount(1, $orders);
            $this->assertEquals(
                $amount,
                $orders[0]['amount'],
                'Billing history should display correct order amount'
            );
            $this->assertEquals(
                $currency,
                $orders[0]['currency'],
                'Billing history should display correct currency'
            );
            
            // Cleanup
            $this->cleanupOrder($order['id']);
            $this->cleanupPlan($testPlan['id']);
            $this->cleanupUser($testUser['id']);
        });
    }

    /**
     * Property: Billing history only displays orders for the authenticated customer
     * 
     * @test
     */
    public function billingHistoryOnlyDisplaysOrdersForAuthenticatedCustomer(): void
    {
        // Arrange: Create two different users
        $testUser1 = $this->createTestUser();
        $testUser2 = $this->createTestUser();
        
        // Arrange: Create plan
        $testPlan = $this->createTestPlan();
        
        // Arrange: Create orders for both users
        $order1 = $this->createTestOrder($testUser1['id'], $testPlan['id'], 'SUCCESS');
        $order2 = $this->createTestOrder($testUser2['id'], $testPlan['id'], 'SUCCESS');
        
        // Act: Fetch orders for user 1
        $user1Orders = $this->orderService->getOrdersByCustomer($testUser1['id']);
        
        // Assert: Only user 1's order is returned
        $this->assertCount(1, $user1Orders);
        $this->assertEquals(
            $testUser1['id'],
            $user1Orders[0]['customer_id'],
            'Billing history should only display orders for the authenticated customer'
        );
        
        // Act: Fetch orders for user 2
        $user2Orders = $this->orderService->getOrdersByCustomer($testUser2['id']);
        
        // Assert: Only user 2's order is returned
        $this->assertCount(1, $user2Orders);
        $this->assertEquals(
            $testUser2['id'],
            $user2Orders[0]['customer_id'],
            'Billing history should only display orders for the authenticated customer'
        );
        
        // Cleanup
        $this->cleanupOrder($order1['id']);
        $this->cleanupOrder($order2['id']);
        $this->cleanupPlan($testPlan['id']);
        $this->cleanupUser($testUser1['id']);
        $this->cleanupUser($testUser2['id']);
    }

    /**
     * Helper: Create test user
     */
    private function createTestUser(): array
    {
        $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
        $userData = [
            'email' => $email,
            'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
            'name' => 'Test User',
            'phone' => '1234567890',
            'role' => 'CUSTOMER'
        ];
        
        return $this->userModel->create($userData);
    }

    /**
     * Helper: Create test plan
     */
    private function createTestPlan(): array
    {
        $slug = 'test-plan-' . bin2hex(random_bytes(8));
        $planData = [
            'name' => 'Test Plan',
            'slug' => $slug,
            'description' => 'Test plan for billing history testing',
            'price' => 999.99,
            'currency' => 'USD',
            'billing_period_months' => 1,
            'features' => json_encode(['Feature 1', 'Feature 2']),
            'status' => 'ACTIVE'
        ];
        
        return $this->planModel->create($planData);
    }

    /**
     * Helper: Create test plan with specific price
     */
    private function createTestPlanWithPrice(float $price, string $currency): array
    {
        $slug = 'test-plan-' . bin2hex(random_bytes(8));
        $planData = [
            'name' => 'Test Plan',
            'slug' => $slug,
            'description' => 'Test plan for billing history testing',
            'price' => $price,
            'currency' => $currency,
            'billing_period_months' => 1,
            'features' => json_encode(['Feature 1', 'Feature 2']),
            'status' => 'ACTIVE'
        ];
        
        return $this->planModel->create($planData);
    }

    /**
     * Helper: Create test order
     */
    private function createTestOrder(string $customerId, string $planId, string $status): array
    {
        $plan = $this->planModel->findById($planId);
        
        $orderData = [
            'customer_id' => $customerId,
            'plan_id' => $planId,
            'amount' => !empty($plan['discounted_price']) ? $plan['discounted_price'] : $plan['mrp'],
            'currency' => $plan['currency'],
            'status' => $status,
            'payment_method' => 'card'
        ];
        
        return $this->orderModel->create($orderData);
    }

    /**
     * Helper: Clean up order
     */
    private function cleanupOrder(string $orderId): void
    {
        $this->orderModel->delete($orderId);
    }

    /**
     * Helper: Clean up plan
     */
    private function cleanupPlan(string $planId): void
    {
        $this->planModel->delete($planId);
    }

    /**
     * Helper: Clean up user
     */
    private function cleanupUser(string $userId): void
    {
        $this->userModel->delete($userId);
    }
}
