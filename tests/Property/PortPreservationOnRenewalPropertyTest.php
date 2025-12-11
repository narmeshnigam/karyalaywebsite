<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Models\Order;
use Karyalay\Models\Subscription;
use Karyalay\Models\Plan;
use Karyalay\Models\User;
use Karyalay\Models\Port;
use Karyalay\Services\RenewalService;
use DateTime;

/**
 * Property-based tests for port preservation on renewal
 * 
 * Feature: karyalay-portal-system, Property 21: Port Preservation on Renewal
 * Validates: Requirements 6.4
 */
class PortPreservationOnRenewalPropertyTest extends TestCase
{
    use TestTrait;

    private Order $orderModel;
    private Subscription $subscriptionModel;
    private Plan $planModel;
    private User $userModel;
    private Port $portModel;
    private RenewalService $renewalService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderModel = new Order();
        $this->subscriptionModel = new Subscription();
        $this->planModel = new Plan();
        $this->userModel = new User();
        $this->portModel = new Port();
        $this->renewalService = new RenewalService();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Property 21: Port Preservation on Renewal
     * 
     * For any subscription renewal, when the renewal payment is successful, 
     * the assigned port identifier should remain unchanged.
     * 
     * Validates: Requirements 6.4
     * 
     * @test
     */
    public function renewalPreservesAssignedPort(): void
    {
        $this->forAll(
            Generator\choose(1, 12) // Billing period in months
        )
        ->then(function ($billingPeriodMonths) {
            // Arrange: Create test user, plan, port, and subscription with assigned port
            $testUser = $this->createTestUser();
            $testPlan = $this->createTestPlan(100, $billingPeriodMonths);
            $testPort = $this->createTestPort($testPlan['id']);
            
            $startDate = new DateTime();
            $endDate = clone $startDate;
            $endDate->modify("+{$billingPeriodMonths} months");
            
            $testSubscription = $this->createTestSubscription(
                $testUser['id'],
                $testPlan['id'],
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
                'ACTIVE',
                $testPort['id']
            );
            
            // Store original port ID
            $originalPortId = $testSubscription['assigned_port_id'];
            $this->assertNotNull($originalPortId, 'Subscription should have an assigned port');
            
            // Act: Initiate renewal
            $renewalData = $this->renewalService->initiateRenewal($testSubscription['id']);
            $this->assertNotFalse($renewalData, 'Renewal should be initiated successfully');
            
            $renewalOrder = $renewalData['order'];
            
            // Act: Process successful renewal payment
            $renewalSuccess = $this->renewalService->processSuccessfulRenewal(
                $renewalOrder['id'],
                $testSubscription['id']
            );
            
            $this->assertTrue($renewalSuccess, 'Renewal should be processed successfully');
            
            // Assert: Fetch updated subscription
            $updatedSubscription = $this->subscriptionModel->findById($testSubscription['id']);
            $this->assertNotFalse($updatedSubscription, 'Subscription should exist after renewal');
            
            // Assert: Port ID remains unchanged
            $this->assertEquals(
                $originalPortId,
                $updatedSubscription['assigned_port_id'],
                'Assigned port ID should remain unchanged after renewal'
            );
            
            // Assert: Port is still assigned to the same subscription
            $port = $this->portModel->findById($originalPortId);
            $this->assertNotFalse($port, 'Port should still exist');
            $this->assertEquals(
                $testSubscription['id'],
                $port['assigned_subscription_id'],
                'Port should still be assigned to the same subscription'
            );
            
            // Assert: Port status is still ASSIGNED
            $this->assertEquals(
                'ASSIGNED',
                $port['status'],
                'Port status should remain ASSIGNED'
            );
            
            // Cleanup
            $this->cleanupSubscription($testSubscription['id']);
            $this->cleanupOrder($renewalOrder['id']);
            $this->cleanupPort($testPort['id']);
            $this->cleanupPlan($testPlan['id']);
            $this->cleanupUser($testUser['id']);
        });
    }

    /**
     * Property: Multiple renewals preserve the same port
     * 
     * @test
     */
    public function multipleRenewalsPreserveSamePort(): void
    {
        $this->forAll(
            Generator\choose(1, 6),  // Billing period in months
            Generator\choose(2, 4)   // Number of renewals
        )
        ->then(function ($billingPeriodMonths, $renewalCount) {
            // Arrange: Create test user, plan, port, and subscription
            $testUser = $this->createTestUser();
            $testPlan = $this->createTestPlan(100, $billingPeriodMonths);
            $testPort = $this->createTestPort($testPlan['id']);
            
            $startDate = new DateTime();
            $endDate = clone $startDate;
            $endDate->modify("+{$billingPeriodMonths} months");
            
            $testSubscription = $this->createTestSubscription(
                $testUser['id'],
                $testPlan['id'],
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
                'ACTIVE',
                $testPort['id']
            );
            
            $originalPortId = $testSubscription['assigned_port_id'];
            $this->assertNotNull($originalPortId, 'Subscription should have an assigned port');
            
            $orders = [];
            
            // Act: Perform multiple renewals
            for ($i = 0; $i < $renewalCount; $i++) {
                // Initiate renewal
                $renewalData = $this->renewalService->initiateRenewal($testSubscription['id']);
                $this->assertNotFalse($renewalData, "Renewal {$i} should be initiated");
                
                $renewalOrder = $renewalData['order'];
                $orders[] = $renewalOrder;
                
                // Process successful renewal
                $renewalSuccess = $this->renewalService->processSuccessfulRenewal(
                    $renewalOrder['id'],
                    $testSubscription['id']
                );
                
                $this->assertTrue($renewalSuccess, "Renewal {$i} should be processed successfully");
                
                // Verify port is still the same
                $updatedSubscription = $this->subscriptionModel->findById($testSubscription['id']);
                $this->assertEquals(
                    $originalPortId,
                    $updatedSubscription['assigned_port_id'],
                    "After renewal {$i}, port ID should remain unchanged"
                );
            }
            
            // Assert: Final check - port is still the original one
            $finalSubscription = $this->subscriptionModel->findById($testSubscription['id']);
            $this->assertEquals(
                $originalPortId,
                $finalSubscription['assigned_port_id'],
                "After {$renewalCount} renewals, port ID should still be the original"
            );
            
            // Assert: Port is still assigned to the subscription
            $port = $this->portModel->findById($originalPortId);
            $this->assertEquals(
                $testSubscription['id'],
                $port['assigned_subscription_id'],
                'Port should still be assigned to the subscription'
            );
            
            // Cleanup
            $this->cleanupSubscription($testSubscription['id']);
            foreach ($orders as $order) {
                $this->cleanupOrder($order['id']);
            }
            $this->cleanupPort($testPort['id']);
            $this->cleanupPlan($testPlan['id']);
            $this->cleanupUser($testUser['id']);
        });
    }

    /**
     * Property: Renewal of subscription without port preserves null port
     * 
     * @test
     */
    public function renewalOfSubscriptionWithoutPortPreservesNullPort(): void
    {
        $this->forAll(
            Generator\choose(1, 12) // Billing period in months
        )
        ->then(function ($billingPeriodMonths) {
            // Arrange: Create test user, plan, and subscription WITHOUT assigned port
            $testUser = $this->createTestUser();
            $testPlan = $this->createTestPlan(100, $billingPeriodMonths);
            
            $startDate = new DateTime();
            $endDate = clone $startDate;
            $endDate->modify("+{$billingPeriodMonths} months");
            
            $testSubscription = $this->createTestSubscription(
                $testUser['id'],
                $testPlan['id'],
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
                'ACTIVE',
                null // No port assigned
            );
            
            // Verify no port is assigned
            $this->assertNull(
                $testSubscription['assigned_port_id'],
                'Subscription should not have an assigned port'
            );
            
            // Act: Initiate and process renewal
            $renewalData = $this->renewalService->initiateRenewal($testSubscription['id']);
            $this->assertNotFalse($renewalData, 'Renewal should be initiated');
            
            $renewalOrder = $renewalData['order'];
            
            $renewalSuccess = $this->renewalService->processSuccessfulRenewal(
                $renewalOrder['id'],
                $testSubscription['id']
            );
            
            $this->assertTrue($renewalSuccess, 'Renewal should be processed successfully');
            
            // Assert: Port is still null after renewal
            $updatedSubscription = $this->subscriptionModel->findById($testSubscription['id']);
            $this->assertNull(
                $updatedSubscription['assigned_port_id'],
                'Subscription should still not have an assigned port after renewal'
            );
            
            // Cleanup
            $this->cleanupSubscription($testSubscription['id']);
            $this->cleanupOrder($renewalOrder['id']);
            $this->cleanupPlan($testPlan['id']);
            $this->cleanupUser($testUser['id']);
        });
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
    private function createTestPlan(float $price, int $billingPeriodMonths): array
    {
        $slug = 'test-plan-' . bin2hex(random_bytes(8));
        $planData = [
            'name' => 'Test Plan',
            'slug' => $slug,
            'description' => 'Test plan for property testing',
            'price' => $price,
            'currency' => 'USD',
            'billing_period_months' => $billingPeriodMonths,
            'features' => json_encode(['Feature 1', 'Feature 2']),
            'status' => 'ACTIVE'
        ];
        
        return $this->planModel->create($planData);
    }

    /**
     * Helper: Create test port
     */
    private function createTestPort(string $planId): array
    {
        $portData = [
            'instance_url' => 'https://test-' . bin2hex(random_bytes(4)) . '.example.com',
            'port_number' => rand(8000, 9000),
            'plan_id' => $planId,
            'status' => 'AVAILABLE'
        ];
        
        return $this->portModel->create($portData);
    }

    /**
     * Helper: Create test subscription
     */
    private function createTestSubscription(
        string $customerId,
        string $planId,
        string $startDate,
        string $endDate,
        string $status,
        ?string $portId = null
    ): array {
        // Create a dummy order first
        $plan = $this->planModel->findById($planId);
        $orderData = [
            'customer_id' => $customerId,
            'plan_id' => $planId,
            'amount' => !empty($plan['discounted_price']) ? $plan['discounted_price'] : $plan['mrp'],
            'currency' => $plan['currency'],
            'status' => 'SUCCESS'
        ];
        $order = $this->orderModel->create($orderData);
        
        $subscriptionData = [
            'customer_id' => $customerId,
            'plan_id' => $planId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $status,
            'order_id' => $order['id'],
            'assigned_port_id' => $portId
        ];
        
        $subscription = $this->subscriptionModel->create($subscriptionData);
        
        // If port is assigned, update port to reflect assignment
        if ($portId) {
            $this->portModel->assignToSubscription(
                $portId,
                $subscription['id'],
                $customerId,
                date('Y-m-d H:i:s')
            );
        }
        
        return $subscription;
    }

    /**
     * Helper: Clean up subscription
     */
    private function cleanupSubscription(string $subscriptionId): void
    {
        $subscription = $this->subscriptionModel->findById($subscriptionId);
        $originalOrderId = null;
        if ($subscription && isset($subscription['order_id'])) {
            $originalOrderId = $subscription['order_id'];
        }
        
        // Delete subscription first (due to foreign key constraints)
        $this->subscriptionModel->delete($subscriptionId);
        
        // Then delete the original order
        if ($originalOrderId) {
            $this->cleanupOrder($originalOrderId);
        }
    }

    /**
     * Helper: Clean up order
     */
    private function cleanupOrder(string $orderId): void
    {
        $this->orderModel->delete($orderId);
    }

    /**
     * Helper: Clean up port
     */
    private function cleanupPort(string $portId): void
    {
        $this->portModel->delete($portId);
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

