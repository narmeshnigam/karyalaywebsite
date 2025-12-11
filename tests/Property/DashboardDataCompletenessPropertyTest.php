<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Models\Subscription;
use Karyalay\Models\Plan;
use Karyalay\Models\Port;
use Karyalay\Models\User;
use Karyalay\Models\Order;

/**
 * Property-based tests for dashboard data completeness
 * 
 * Feature: karyalay-portal-system, Property 17: Dashboard Data Completeness
 * Validates: Requirements 5.1
 */
class DashboardDataCompletenessPropertyTest extends TestCase
{
    use TestTrait;

    private Subscription $subscriptionModel;
    private Plan $planModel;
    private Port $portModel;
    private User $userModel;
    private Order $orderModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subscriptionModel = new Subscription();
        $this->planModel = new Plan();
        $this->portModel = new Port();
        $this->userModel = new User();
        $this->orderModel = new Order();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Property 17: Dashboard Data Completeness
     * 
     * For any customer with an active subscription, when the dashboard is accessed,
     * the active plan name, expiry date, and assigned port address should all be displayed.
     * 
     * Validates: Requirements 5.1
     * 
     * @test
     */
    public function dashboardDisplaysAllRequiredDataForActiveSubscription(): void
    {
        $this->forAll(
            Generator\choose(1, 12), // Billing period in months
            Generator\elements(['Professional', 'Enterprise', 'Starter', 'Business'])
        )
        ->then(function ($billingPeriodMonths, $planName) {
            // Arrange: Create test user
            $testUser = $this->createTestUser();
            
            // Arrange: Create test plan
            $testPlan = $this->createTestPlan($planName, 100, $billingPeriodMonths);
            
            // Arrange: Create test order
            $testOrder = $this->createTestOrder($testUser['id'], $testPlan['id'], 'SUCCESS');
            
            // Arrange: Create test port
            $testPort = $this->createTestPort($testPlan['id']);
            
            // Arrange: Create active subscription with assigned port
            $subscriptionData = [
                'customer_id' => $testUser['id'],
                'plan_id' => $testPlan['id'],
                'order_id' => $testOrder['id'],
                'status' => 'ACTIVE',
                'assigned_port_id' => $testPort['id']
            ];
            
            $subscription = $this->subscriptionModel->create($subscriptionData);
            $this->assertNotFalse($subscription, 'Subscription should be created');
            
            // Act: Simulate dashboard data retrieval
            $activeSubscription = $this->subscriptionModel->findActiveByCustomerId($testUser['id']);
            
            // Assert: Active subscription exists
            $this->assertNotFalse(
                $activeSubscription,
                'Dashboard should retrieve active subscription for customer'
            );
            
            // Assert: Subscription has plan_id
            $this->assertArrayHasKey('plan_id', $activeSubscription);
            $this->assertNotNull($activeSubscription['plan_id']);
            
            // Act: Fetch plan details (as dashboard would)
            $plan = $this->planModel->findById($activeSubscription['plan_id']);
            
            // Assert: Plan name is available
            $this->assertNotFalse($plan, 'Dashboard should retrieve plan details');
            $this->assertArrayHasKey('name', $plan);
            $this->assertNotNull($plan['name']);
            $this->assertEquals($planName, $plan['name'], 'Plan name should match');
            
            // Assert: Expiry date (end_date) is available
            $this->assertArrayHasKey('end_date', $activeSubscription);
            $this->assertNotNull(
                $activeSubscription['end_date'],
                'Dashboard should display subscription expiry date'
            );
            
            // Assert: End date is a valid date
            $endDate = strtotime($activeSubscription['end_date']);
            $this->assertNotFalse($endDate, 'End date should be a valid datetime');
            
            // Assert: End date is in the future (for active subscription)
            $startDate = strtotime($activeSubscription['start_date']);
            $this->assertGreaterThan(
                $startDate,
                $endDate,
                'End date should be after start date'
            );
            
            // Assert: Assigned port ID is available
            $this->assertArrayHasKey('assigned_port_id', $activeSubscription);
            $this->assertNotNull(
                $activeSubscription['assigned_port_id'],
                'Dashboard should have assigned port ID'
            );
            
            // Act: Fetch port details (as dashboard would)
            $port = $this->portModel->findById($activeSubscription['assigned_port_id']);
            
            // Assert: Port address is available
            $this->assertNotFalse($port, 'Dashboard should retrieve port details');
            $this->assertArrayHasKey('instance_url', $port);
            $this->assertNotNull(
                $port['instance_url'],
                'Dashboard should display assigned port address'
            );
            
            // Assert: Port instance URL is not empty
            $this->assertNotEmpty(
                $port['instance_url'],
                'Port instance URL should not be empty'
            );
            
            // Assert: All three required fields are present and non-null
            $this->assertTrue(
                !empty($plan['name']) && 
                !empty($activeSubscription['end_date']) && 
                !empty($port['instance_url']),
                'Dashboard should display all required data: plan name, expiry date, and port address'
            );
            
            // Cleanup
            $this->cleanupSubscription($subscription['id']);
            $this->cleanupPort($testPort['id']);
            $this->cleanupOrder($testOrder['id']);
            $this->cleanupPlan($testPlan['id']);
            $this->cleanupUser($testUser['id']);
        });
    }

    /**
     * Property: Dashboard handles customer with no active subscription
     * 
     * @test
     */
    public function dashboardHandlesCustomerWithNoActiveSubscription(): void
    {
        // Arrange: Create test user without subscription
        $testUser = $this->createTestUser();
        
        // Act: Try to fetch active subscription
        $activeSubscription = $this->subscriptionModel->findActiveByCustomerId($testUser['id']);
        
        // Assert: No active subscription found
        $this->assertFalse(
            $activeSubscription,
            'Dashboard should handle customer with no active subscription'
        );
        
        // Cleanup
        $this->cleanupUser($testUser['id']);
    }

    /**
     * Property: Dashboard displays correct data for subscription without assigned port
     * 
     * @test
     */
    public function dashboardHandlesSubscriptionWithoutAssignedPort(): void
    {
        // Arrange: Create test user, plan, order
        $testUser = $this->createTestUser();
        $testPlan = $this->createTestPlan('Test Plan', 100, 1);
        $testOrder = $this->createTestOrder($testUser['id'], $testPlan['id'], 'SUCCESS');
        
        // Arrange: Create subscription without assigned port
        $subscriptionData = [
            'customer_id' => $testUser['id'],
            'plan_id' => $testPlan['id'],
            'order_id' => $testOrder['id'],
            'status' => 'PENDING_ALLOCATION',
            'assigned_port_id' => null
        ];
        
        $subscription = $this->subscriptionModel->create($subscriptionData);
        $this->assertNotFalse($subscription);
        
        // Act: Fetch subscription
        $activeSubscription = $this->subscriptionModel->findById($subscription['id']);
        
        // Assert: Subscription exists but has no assigned port
        $this->assertNotFalse($activeSubscription);
        $this->assertNull(
            $activeSubscription['assigned_port_id'],
            'Subscription without port should have null assigned_port_id'
        );
        
        // Assert: Plan name and expiry date are still available
        $plan = $this->planModel->findById($activeSubscription['plan_id']);
        $this->assertNotFalse($plan);
        $this->assertNotNull($plan['name']);
        $this->assertNotNull($activeSubscription['end_date']);
        
        // Cleanup
        $this->cleanupSubscription($subscription['id']);
        $this->cleanupOrder($testOrder['id']);
        $this->cleanupPlan($testPlan['id']);
        $this->cleanupUser($testUser['id']);
    }

    /**
     * Property: Dashboard data includes all subscription fields
     * 
     * @test
     */
    public function dashboardDataIncludesAllSubscriptionFields(): void
    {
        // Arrange: Create complete test setup
        $testUser = $this->createTestUser();
        $testPlan = $this->createTestPlan('Complete Plan', 150, 3);
        $testOrder = $this->createTestOrder($testUser['id'], $testPlan['id'], 'SUCCESS');
        $testPort = $this->createTestPort($testPlan['id']);
        
        $subscription = $this->subscriptionModel->create([
            'customer_id' => $testUser['id'],
            'plan_id' => $testPlan['id'],
            'order_id' => $testOrder['id'],
            'status' => 'ACTIVE',
            'assigned_port_id' => $testPort['id']
        ]);
        
        // Act: Fetch subscription
        $activeSubscription = $this->subscriptionModel->findActiveByCustomerId($testUser['id']);
        
        // Assert: All required fields are present
        $requiredFields = ['id', 'customer_id', 'plan_id', 'start_date', 'end_date', 
                          'status', 'assigned_port_id', 'order_id', 'created_at', 'updated_at'];
        
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey(
                $field,
                $activeSubscription,
                "Dashboard data should include {$field} field"
            );
        }
        
        // Cleanup
        $this->cleanupSubscription($subscription['id']);
        $this->cleanupPort($testPort['id']);
        $this->cleanupOrder($testOrder['id']);
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
        
        return $this->userModel->create($userData);
    }

    /**
     * Helper: Create test plan
     */
    private function createTestPlan(string $name, float $price, int $billingPeriodMonths): array
    {
        $slug = 'test-plan-' . bin2hex(random_bytes(8));
        $planData = [
            'name' => $name,
            'slug' => $slug,
            'description' => 'Test plan for dashboard testing',
            'price' => $price,
            'currency' => 'USD',
            'billing_period_months' => $billingPeriodMonths,
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
     * Helper: Create test port
     */
    private function createTestPort(string $planId): array
    {
        $instanceUrl = 'https://test-' . bin2hex(random_bytes(4)) . '.karyalay.com';
        $portData = [
            'instance_url' => $instanceUrl,
            'port_number' => rand(8000, 9000),
            'plan_id' => $planId,
            'status' => 'AVAILABLE'
        ];
        
        return $this->portModel->create($portData);
    }

    /**
     * Helper: Clean up subscription
     */
    private function cleanupSubscription(string $subscriptionId): void
    {
        $this->subscriptionModel->delete($subscriptionId);
    }

    /**
     * Helper: Clean up port
     */
    private function cleanupPort(string $portId): void
    {
        $this->portModel->delete($portId);
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

