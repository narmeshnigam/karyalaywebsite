<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Services\OrderService;
use Karyalay\Models\Order;
use Karyalay\Models\Plan;
use Karyalay\Models\User;

/**
 * Property-based tests for order creation on payment initiation
 * 
 * Feature: karyalay-portal-system, Property 11: Order Creation on Payment Initiation
 * Validates: Requirements 3.5
 */
class OrderCreationPropertyTest extends TestCase
{
    use TestTrait;

    private OrderService $orderService;
    private Order $orderModel;
    private Plan $planModel;
    private User $userModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = new OrderService();
        $this->orderModel = new Order();
        $this->planModel = new Plan();
        $this->userModel = new User();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Property 11: Order Creation on Payment Initiation
     * 
     * For any payment initiation, when a customer starts the payment process, 
     * a pending order record with status PENDING should be created.
     * 
     * Validates: Requirements 3.5
     * 
     * @test
     */
    public function orderCreationOnPaymentInitiationCreatesPendingOrder(): void
    {
        $this->forAll(
            Generator\choose(1, 100), // Random price
            Generator\elements(['card', 'upi', 'netbanking', 'wallet'])
        )
        ->then(function ($price, $paymentMethod) {
            // Arrange: Create test user and plan
            $testUser = $this->createTestUser();
            $testPlan = $this->createTestPlan($price);
            
            // Act: Create order (simulating payment initiation)
            $order = $this->orderService->createOrder(
                $testUser['id'],
                $testPlan['id'],
                $paymentMethod
            );
            
            // Assert: Order was created
            $this->assertNotFalse(
                $order,
                'Order should be created on payment initiation'
            );
            $this->assertIsArray($order);
            $this->assertArrayHasKey('id', $order);
            
            // Assert: Order has PENDING status
            $this->assertEquals(
                'PENDING',
                $order['status'],
                'Order status should be PENDING on payment initiation'
            );
            
            // Assert: Order is linked to customer
            $this->assertEquals(
                $testUser['id'],
                $order['customer_id'],
                'Order should be linked to customer'
            );
            
            // Assert: Order is linked to plan
            $this->assertEquals(
                $testPlan['id'],
                $order['plan_id'],
                'Order should be linked to plan'
            );
            
            // Assert: Order amount matches plan effective price
            $effectivePrice = !empty($testPlan['discounted_price']) ? $testPlan['discounted_price'] : $testPlan['mrp'];
            $this->assertEquals(
                $effectivePrice,
                $order['amount'],
                'Order amount should match plan effective price (discounted_price if available, otherwise mrp)'
            );
            
            // Assert: Order currency matches plan currency
            $this->assertEquals(
                $testPlan['currency'],
                $order['currency'],
                'Order currency should match plan currency'
            );
            
            // Assert: Payment method is stored
            $this->assertEquals(
                $paymentMethod,
                $order['payment_method'],
                'Payment method should be stored in order'
            );
            
            // Assert: Order exists in database
            $dbOrder = $this->orderModel->findById($order['id']);
            $this->assertNotFalse(
                $dbOrder,
                'Order should exist in database'
            );
            $this->assertEquals('PENDING', $dbOrder['status']);
            
            // Cleanup
            $this->cleanupOrder($order['id']);
            $this->cleanupPlan($testPlan['id']);
            $this->cleanupUser($testUser['id']);
        });
    }

    /**
     * Property: Order creation should fail for inactive plans
     * 
     * @test
     */
    public function orderCreationFailsForInactivePlans(): void
    {
        // Arrange: Create test user and inactive plan
        $testUser = $this->createTestUser();
        $testPlan = $this->createTestPlan(100, 'INACTIVE');
        
        // Act: Attempt to create order
        $order = $this->orderService->createOrder(
            $testUser['id'],
            $testPlan['id'],
            'card'
        );
        
        // Assert: Order creation should fail
        $this->assertFalse(
            $order,
            'Order creation should fail for inactive plans'
        );
        
        // Cleanup
        $this->cleanupPlan($testPlan['id']);
        $this->cleanupUser($testUser['id']);
    }

    /**
     * Property: Order creation should fail for non-existent plans
     * 
     * @test
     */
    public function orderCreationFailsForNonExistentPlans(): void
    {
        // Arrange: Create test user
        $testUser = $this->createTestUser();
        $nonExistentPlanId = $this->generateUuid();
        
        // Act: Attempt to create order with non-existent plan
        $order = $this->orderService->createOrder(
            $testUser['id'],
            $nonExistentPlanId,
            'card'
        );
        
        // Assert: Order creation should fail
        $this->assertFalse(
            $order,
            'Order creation should fail for non-existent plans'
        );
        
        // Cleanup
        $this->cleanupUser($testUser['id']);
    }

    /**
     * Property: Multiple orders can be created for the same customer
     * 
     * @test
     */
    public function multipleOrdersCanBeCreatedForSameCustomer(): void
    {
        $this->forAll(
            Generator\choose(2, 5) // Number of orders to create
        )
        ->then(function ($orderCount) {
            // Arrange: Create test user and plan
            $testUser = $this->createTestUser();
            $testPlan = $this->createTestPlan(100);
            
            $createdOrders = [];
            
            // Act: Create multiple orders
            for ($i = 0; $i < $orderCount; $i++) {
                $order = $this->orderService->createOrder(
                    $testUser['id'],
                    $testPlan['id'],
                    'card'
                );
                
                $this->assertNotFalse($order, "Order {$i} should be created");
                $createdOrders[] = $order;
            }
            
            // Assert: All orders were created
            $this->assertCount(
                $orderCount,
                $createdOrders,
                "Should create {$orderCount} orders"
            );
            
            // Assert: All orders have unique IDs
            $orderIds = array_map(fn($o) => $o['id'], $createdOrders);
            $uniqueIds = array_unique($orderIds);
            $this->assertCount(
                $orderCount,
                $uniqueIds,
                'All orders should have unique IDs'
            );
            
            // Assert: All orders have PENDING status
            foreach ($createdOrders as $order) {
                $this->assertEquals('PENDING', $order['status']);
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
     * Property: Order timestamps should be set on creation
     * 
     * @test
     */
    public function orderTimestampsAreSetOnCreation(): void
    {
        // Arrange: Create test user and plan
        $testUser = $this->createTestUser();
        $testPlan = $this->createTestPlan(100);
        
        // Act: Create order
        $order = $this->orderService->createOrder(
            $testUser['id'],
            $testPlan['id'],
            'card'
        );
        
        // Assert: Order has timestamps
        $this->assertNotFalse($order);
        $this->assertArrayHasKey('created_at', $order);
        $this->assertArrayHasKey('updated_at', $order);
        
        // Assert: Timestamps are not null
        $this->assertNotNull(
            $order['created_at'],
            'created_at should not be null'
        );
        $this->assertNotNull(
            $order['updated_at'],
            'updated_at should not be null'
        );
        
        // Assert: Timestamps are valid datetime strings
        $createdAt = strtotime($order['created_at']);
        $updatedAt = strtotime($order['updated_at']);
        
        $this->assertNotFalse(
            $createdAt,
            'created_at should be a valid datetime'
        );
        $this->assertNotFalse(
            $updatedAt,
            'updated_at should be a valid datetime'
        );
        
        // Cleanup
        $this->cleanupOrder($order['id']);
        $this->cleanupPlan($testPlan['id']);
        $this->cleanupUser($testUser['id']);
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
        
        $user = $this->userModel->create($userData);
        return $user;
    }

    /**
     * Helper: Create test plan
     */
    private function createTestPlan(float $price, string $status = 'ACTIVE'): array
    {
        $slug = 'test-plan-' . bin2hex(random_bytes(8));
        $planData = [
            'name' => 'Test Plan',
            'slug' => $slug,
            'description' => 'Test plan for property testing',
            'mrp' => $price,
            'discounted_price' => null,
            'currency' => 'USD',
            'billing_period_months' => 1,
            'features' => json_encode(['Feature 1', 'Feature 2']),
            'status' => $status
        ];
        
        $plan = $this->planModel->create($planData);
        return $plan;
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

    /**
     * Helper: Generate UUID v4
     */
    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

