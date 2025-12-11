<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Models\Order;
use Karyalay\Models\Subscription;
use Karyalay\Models\Plan;
use Karyalay\Models\User;
use Karyalay\Services\RenewalService;
use DateTime;

/**
 * Property-based tests for failed renewal immutability
 * 
 * Feature: karyalay-portal-system, Property 22: Failed Renewal Immutability
 * Validates: Requirements 6.5
 */
class FailedRenewalImmutabilityPropertyTest extends TestCase
{
    use TestTrait;

    private Order $orderModel;
    private Subscription $subscriptionModel;
    private Plan $planModel;
    private User $userModel;
    private RenewalService $renewalService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderModel = new Order();
        $this->subscriptionModel = new Subscription();
        $this->planModel = new Plan();
        $this->userModel = new User();
        $this->renewalService = new RenewalService();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Property 22: Failed Renewal Immutability
     * 
     * For any renewal attempt, when the payment fails, the subscription record 
     * should remain unchanged (same end date, same status).
     * 
     * Validates: Requirements 6.5
     * 
     * @test
     */
    public function failedRenewalDoesNotModifySubscription(): void
    {
        $this->forAll(
            Generator\choose(1, 12), // Billing period in months
            Generator\elements(['ACTIVE', 'EXPIRED']) // Subscription status
        )
        ->then(function ($billingPeriodMonths, $subscriptionStatus) {
            // Arrange: Create test user, plan, and subscription
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
                $subscriptionStatus
            );
            
            // Store original subscription state
            $originalEndDate = $testSubscription['end_date'];
            $originalStatus = $testSubscription['status'];
            $originalStartDate = $testSubscription['start_date'];
            $originalPlanId = $testSubscription['plan_id'];
            $originalCustomerId = $testSubscription['customer_id'];
            $originalPortId = $testSubscription['assigned_port_id'];
            
            // Act: Initiate renewal
            $renewalData = $this->renewalService->initiateRenewal($testSubscription['id']);
            $this->assertNotFalse($renewalData, 'Renewal should be initiated successfully');
            
            $renewalOrder = $renewalData['order'];
            
            // Act: Process failed renewal payment
            $failureResult = $this->renewalService->processFailedRenewal($renewalOrder['id']);
            $this->assertTrue($failureResult, 'Failed renewal should be processed');
            
            // Assert: Fetch subscription after failed renewal
            $updatedSubscription = $this->subscriptionModel->findById($testSubscription['id']);
            $this->assertNotFalse($updatedSubscription, 'Subscription should still exist');
            
            // Assert: End date remains unchanged
            $this->assertEquals(
                $originalEndDate,
                $updatedSubscription['end_date'],
                'End date should remain unchanged after failed renewal'
            );
            
            // Assert: Status remains unchanged
            $this->assertEquals(
                $originalStatus,
                $updatedSubscription['status'],
                'Status should remain unchanged after failed renewal'
            );
            
            // Assert: Start date remains unchanged
            $this->assertEquals(
                $originalStartDate,
                $updatedSubscription['start_date'],
                'Start date should remain unchanged after failed renewal'
            );
            
            // Assert: Plan ID remains unchanged
            $this->assertEquals(
                $originalPlanId,
                $updatedSubscription['plan_id'],
                'Plan ID should remain unchanged after failed renewal'
            );
            
            // Assert: Customer ID remains unchanged
            $this->assertEquals(
                $originalCustomerId,
                $updatedSubscription['customer_id'],
                'Customer ID should remain unchanged after failed renewal'
            );
            
            // Assert: Port ID remains unchanged
            $this->assertEquals(
                $originalPortId,
                $updatedSubscription['assigned_port_id'],
                'Port ID should remain unchanged after failed renewal'
            );
            
            // Assert: Order status is FAILED
            $failedOrder = $this->orderModel->findById($renewalOrder['id']);
            $this->assertEquals(
                'FAILED',
                $failedOrder['status'],
                'Renewal order status should be FAILED'
            );
            
            // Cleanup
            $this->cleanupSubscription($testSubscription['id']);
            $this->cleanupOrder($renewalOrder['id']);
            $this->cleanupPlan($testPlan['id']);
            $this->cleanupUser($testUser['id']);
        });
    }

    /**
     * Property: Multiple failed renewals do not modify subscription
     * 
     * @test
     */
    public function multipleFailedRenewalsDoNotModifySubscription(): void
    {
        $this->forAll(
            Generator\choose(1, 6),  // Billing period in months
            Generator\choose(2, 4)   // Number of failed renewal attempts
        )
        ->then(function ($billingPeriodMonths, $failureCount) {
            // Arrange: Create test user, plan, and subscription
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
                'ACTIVE'
            );
            
            // Store original subscription state
            $originalEndDate = $testSubscription['end_date'];
            $originalStatus = $testSubscription['status'];
            
            $failedOrders = [];
            
            // Act: Perform multiple failed renewal attempts
            for ($i = 0; $i < $failureCount; $i++) {
                // Initiate renewal
                $renewalData = $this->renewalService->initiateRenewal($testSubscription['id']);
                $this->assertNotFalse($renewalData, "Renewal attempt {$i} should be initiated");
                
                $renewalOrder = $renewalData['order'];
                $failedOrders[] = $renewalOrder;
                
                // Process failed renewal
                $failureResult = $this->renewalService->processFailedRenewal($renewalOrder['id']);
                $this->assertTrue($failureResult, "Failed renewal {$i} should be processed");
                
                // Verify subscription remains unchanged after each failure
                $currentSubscription = $this->subscriptionModel->findById($testSubscription['id']);
                $this->assertEquals(
                    $originalEndDate,
                    $currentSubscription['end_date'],
                    "After failed renewal {$i}, end date should remain unchanged"
                );
                $this->assertEquals(
                    $originalStatus,
                    $currentSubscription['status'],
                    "After failed renewal {$i}, status should remain unchanged"
                );
            }
            
            // Assert: Final check - subscription is still unchanged
            $finalSubscription = $this->subscriptionModel->findById($testSubscription['id']);
            $this->assertEquals(
                $originalEndDate,
                $finalSubscription['end_date'],
                "After {$failureCount} failed renewals, end date should still be unchanged"
            );
            $this->assertEquals(
                $originalStatus,
                $finalSubscription['status'],
                "After {$failureCount} failed renewals, status should still be unchanged"
            );
            
            // Assert: All orders are marked as FAILED
            foreach ($failedOrders as $order) {
                $failedOrder = $this->orderModel->findById($order['id']);
                $this->assertEquals('FAILED', $failedOrder['status']);
            }
            
            // Cleanup
            $this->cleanupSubscription($testSubscription['id']);
            foreach ($failedOrders as $order) {
                $this->cleanupOrder($order['id']);
            }
            $this->cleanupPlan($testPlan['id']);
            $this->cleanupUser($testUser['id']);
        });
    }

    /**
     * Property: Failed renewal followed by successful renewal works correctly
     * 
     * @test
     */
    public function failedRenewalFollowedBySuccessfulRenewalWorks(): void
    {
        $this->forAll(
            Generator\choose(1, 12) // Billing period in months
        )
        ->then(function ($billingPeriodMonths) {
            // Arrange: Create test user, plan, and subscription
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
                'ACTIVE'
            );
            
            $originalEndDate = new DateTime($testSubscription['end_date']);
            
            // Act: First renewal attempt fails
            $failedRenewalData = $this->renewalService->initiateRenewal($testSubscription['id']);
            $failedOrder = $failedRenewalData['order'];
            $this->renewalService->processFailedRenewal($failedOrder['id']);
            
            // Verify subscription unchanged after failure
            $subscriptionAfterFailure = $this->subscriptionModel->findById($testSubscription['id']);
            $this->assertEquals(
                $originalEndDate->format('Y-m-d'),
                $subscriptionAfterFailure['end_date'],
                'End date should be unchanged after failed renewal'
            );
            
            // Act: Second renewal attempt succeeds
            $successfulRenewalData = $this->renewalService->initiateRenewal($testSubscription['id']);
            $successfulOrder = $successfulRenewalData['order'];
            $this->renewalService->processSuccessfulRenewal(
                $successfulOrder['id'],
                $testSubscription['id']
            );
            
            // Assert: Subscription is now extended
            $subscriptionAfterSuccess = $this->subscriptionModel->findById($testSubscription['id']);
            $expectedEndDate = clone $originalEndDate;
            $expectedEndDate->modify("+{$billingPeriodMonths} months");
            
            $this->assertEquals(
                $expectedEndDate->format('Y-m-d'),
                $subscriptionAfterSuccess['end_date'],
                'End date should be extended after successful renewal'
            );
            
            // Assert: Failed order is FAILED, successful order is SUCCESS
            $failedOrderFinal = $this->orderModel->findById($failedOrder['id']);
            $successfulOrderFinal = $this->orderModel->findById($successfulOrder['id']);
            
            $this->assertEquals('FAILED', $failedOrderFinal['status']);
            $this->assertEquals('SUCCESS', $successfulOrderFinal['status']);
            
            // Cleanup
            $this->cleanupSubscription($testSubscription['id']);
            $this->cleanupOrder($failedOrder['id']);
            $this->cleanupOrder($successfulOrder['id']);
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
            'mrp' => $price,
            'discounted_price' => null,
            'currency' => 'USD',
            'billing_period_months' => $billingPeriodMonths,
            'features' => json_encode(['Feature 1', 'Feature 2']),
            'status' => 'ACTIVE'
        ];
        
        return $this->planModel->create($planData);
    }

    /**
     * Helper: Create test subscription
     */
    private function createTestSubscription(
        string $customerId,
        string $planId,
        string $startDate,
        string $endDate,
        string $status
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
            'order_id' => $order['id']
        ];
        
        return $this->subscriptionModel->create($subscriptionData);
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

