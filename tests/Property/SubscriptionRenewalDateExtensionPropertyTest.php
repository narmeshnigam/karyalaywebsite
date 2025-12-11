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
 * Property-based tests for subscription renewal date extension
 * 
 * Feature: karyalay-portal-system, Property 19: Subscription Renewal Date Extension
 * Validates: Requirements 6.2
 */
class SubscriptionRenewalDateExtensionPropertyTest extends TestCase
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
     * Property 19: Subscription Renewal Date Extension
     * 
     * For any active subscription, when a renewal payment is successful, 
     * the subscription end date should be extended by exactly the plan's 
     * billing period in months.
     * 
     * Validates: Requirements 6.2
     * 
     * @test
     */
    public function renewalExtendsSubscriptionEndDateByBillingPeriod(): void
    {
        $this->forAll(
            Generator\choose(1, 24), // Billing period in months (1-24)
            Generator\choose(0, 365) // Days until current end date (0-365)
        )
        ->then(function ($billingPeriodMonths, $daysUntilEnd) {
            // Arrange: Create test user, plan, and active subscription
            $testUser = $this->createTestUser();
            $testPlan = $this->createTestPlan(100, $billingPeriodMonths);
            
            // Create subscription with specific end date
            $startDate = new DateTime();
            $endDate = clone $startDate;
            $endDate->modify("+{$daysUntilEnd} days");
            
            $testSubscription = $this->createTestSubscription(
                $testUser['id'],
                $testPlan['id'],
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
                'ACTIVE'
            );
            
            // Store original end date for comparison
            $originalEndDate = new DateTime($testSubscription['end_date']);
            
            // Act: Initiate renewal
            $renewalData = $this->renewalService->initiateRenewal($testSubscription['id']);
            $this->assertNotFalse($renewalData, 'Renewal should be initiated successfully');
            $this->assertArrayHasKey('order', $renewalData);
            
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
            
            // Assert: End date is extended by billing period
            $newEndDate = new DateTime($updatedSubscription['end_date']);
            $expectedEndDate = clone $originalEndDate;
            $expectedEndDate->modify("+{$billingPeriodMonths} months");
            
            $this->assertEquals(
                $expectedEndDate->format('Y-m-d'),
                $newEndDate->format('Y-m-d'),
                "End date should be extended by exactly {$billingPeriodMonths} months from original end date"
            );
            
            // Assert: Subscription status is ACTIVE
            $this->assertEquals(
                'ACTIVE',
                $updatedSubscription['status'],
                'Subscription status should be ACTIVE after renewal'
            );
            
            // Assert: Order status is SUCCESS
            $updatedOrder = $this->orderModel->findById($renewalOrder['id']);
            $this->assertEquals(
                'SUCCESS',
                $updatedOrder['status'],
                'Renewal order status should be SUCCESS'
            );
            
            // Assert: Start date remains unchanged
            $this->assertEquals(
                $testSubscription['start_date'],
                $updatedSubscription['start_date'],
                'Start date should remain unchanged after renewal'
            );
            
            // Cleanup
            $this->cleanupSubscription($testSubscription['id']);
            $this->cleanupOrder($renewalOrder['id']);
            $this->cleanupPlan($testPlan['id']);
            $this->cleanupUser($testUser['id']);
        });
    }

    /**
     * Property: Multiple renewals should compound correctly
     * 
     * @test
     */
    public function multipleRenewalsCompoundCorrectly(): void
    {
        $this->forAll(
            Generator\choose(1, 12), // Billing period in months
            Generator\choose(2, 4)   // Number of renewals
        )
        ->then(function ($billingPeriodMonths, $renewalCount) {
            // Arrange: Create test user, plan, and subscription
            $testUser = $this->createTestUser();
            $testPlan = $this->createTestPlan(100, $billingPeriodMonths);
            
            $startDate = new DateTime();
            $initialEndDate = clone $startDate;
            $initialEndDate->modify("+{$billingPeriodMonths} months");
            
            $testSubscription = $this->createTestSubscription(
                $testUser['id'],
                $testPlan['id'],
                $startDate->format('Y-m-d'),
                $initialEndDate->format('Y-m-d'),
                'ACTIVE'
            );
            
            $orders = [];
            
            // Act: Perform multiple renewals
            for ($i = 0; $i < $renewalCount; $i++) {
                // Get current subscription state
                $currentSubscription = $this->subscriptionModel->findById($testSubscription['id']);
                $currentEndDate = new DateTime($currentSubscription['end_date']);
                
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
                
                // Verify end date was extended correctly
                $updatedSubscription = $this->subscriptionModel->findById($testSubscription['id']);
                $newEndDate = new DateTime($updatedSubscription['end_date']);
                $expectedEndDate = clone $currentEndDate;
                $expectedEndDate->modify("+{$billingPeriodMonths} months");
                
                $this->assertEquals(
                    $expectedEndDate->format('Y-m-d'),
                    $newEndDate->format('Y-m-d'),
                    "Renewal {$i}: End date should be extended by {$billingPeriodMonths} months"
                );
            }
            
            // Assert: Final end date is correct
            $finalSubscription = $this->subscriptionModel->findById($testSubscription['id']);
            $finalEndDate = new DateTime($finalSubscription['end_date']);
            
            $expectedFinalEndDate = clone $initialEndDate;
            $totalMonths = $billingPeriodMonths * $renewalCount;
            $expectedFinalEndDate->modify("+{$totalMonths} months");
            
            $this->assertEquals(
                $expectedFinalEndDate->format('Y-m-d'),
                $finalEndDate->format('Y-m-d'),
                "After {$renewalCount} renewals, end date should be extended by {$totalMonths} months total"
            );
            
            // Cleanup
            $this->cleanupSubscription($testSubscription['id']);
            foreach ($orders as $order) {
                $this->cleanupOrder($order['id']);
            }
            $this->cleanupPlan($testPlan['id']);
            $this->cleanupUser($testUser['id']);
        });
    }

    /**
     * Property: Renewal of expired subscription should extend from original end date
     * 
     * @test
     */
    public function renewalOfExpiredSubscriptionExtendsFromOriginalEndDate(): void
    {
        $this->forAll(
            Generator\choose(1, 12) // Billing period in months
        )
        ->then(function ($billingPeriodMonths) {
            // Arrange: Create test user, plan, and expired subscription
            $testUser = $this->createTestUser();
            $testPlan = $this->createTestPlan(100, $billingPeriodMonths);
            
            // Create subscription with end date in the past
            $startDate = new DateTime();
            $startDate->modify('-6 months');
            $endDate = clone $startDate;
            $endDate->modify("+{$billingPeriodMonths} months");
            
            $testSubscription = $this->createTestSubscription(
                $testUser['id'],
                $testPlan['id'],
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
                'EXPIRED'
            );
            
            $originalEndDate = new DateTime($testSubscription['end_date']);
            
            // Act: Initiate and process renewal
            $renewalData = $this->renewalService->initiateRenewal($testSubscription['id']);
            $this->assertNotFalse($renewalData, 'Renewal of expired subscription should be initiated');
            
            $renewalOrder = $renewalData['order'];
            
            $renewalSuccess = $this->renewalService->processSuccessfulRenewal(
                $renewalOrder['id'],
                $testSubscription['id']
            );
            
            $this->assertTrue($renewalSuccess, 'Renewal should be processed successfully');
            
            // Assert: End date is extended from original end date
            $updatedSubscription = $this->subscriptionModel->findById($testSubscription['id']);
            $newEndDate = new DateTime($updatedSubscription['end_date']);
            $expectedEndDate = clone $originalEndDate;
            $expectedEndDate->modify("+{$billingPeriodMonths} months");
            
            $this->assertEquals(
                $expectedEndDate->format('Y-m-d'),
                $newEndDate->format('Y-m-d'),
                "Expired subscription renewal should extend from original end date by {$billingPeriodMonths} months"
            );
            
            // Assert: Status is updated to ACTIVE
            $this->assertEquals(
                'ACTIVE',
                $updatedSubscription['status'],
                'Expired subscription should become ACTIVE after renewal'
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

