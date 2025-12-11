<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Models\Subscription;
use Karyalay\Models\Plan;
use Karyalay\Models\User;
use Karyalay\Models\Order;
use Karyalay\Services\ExpirationService;
use DateTime;

/**
 * Property-based tests for subscription expiration
 * 
 * Feature: karyalay-portal-system, Property 20: Subscription Expiration
 * Validates: Requirements 6.3
 */
class SubscriptionExpirationPropertyTest extends TestCase
{
    use TestTrait;

    private Subscription $subscriptionModel;
    private Plan $planModel;
    private User $userModel;
    private Order $orderModel;
    private ExpirationService $expirationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subscriptionModel = new Subscription();
        $this->planModel = new Plan();
        $this->userModel = new User();
        $this->orderModel = new Order();
        $this->expirationService = new ExpirationService();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Property 20: Subscription Expiration
     * 
     * For any subscription, when the current date is after the end date,
     * the subscription status should be EXPIRED.
     * 
     * Validates: Requirements 6.3
     * 
     * @test
     */
    public function subscriptionExpiresWhenEndDatePasses(): void
    {
        $this->forAll(
            Generator\choose(1, 365) // Days in the past (1-365)
        )
        ->then(function ($daysInPast) {
            // Arrange: Create test user and plan
            $testUser = $this->createTestUser();
            $testPlan = $this->createTestPlan();
            
            // Create subscription with end date in the past
            $startDate = new DateTime();
            $startDate->modify("-{$daysInPast} days");
            $startDate->modify('-30 days'); // Start 30 days before end
            
            $endDate = new DateTime();
            $endDate->modify("-{$daysInPast} days");
            
            $testSubscription = $this->createTestSubscription(
                $testUser['id'],
                $testPlan['id'],
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
                'ACTIVE' // Start as ACTIVE
            );
            
            // Verify subscription was created with ACTIVE status
            $this->assertEquals(
                'ACTIVE',
                $testSubscription['status'],
                'Subscription should be created with ACTIVE status'
            );
            
            // Verify end date is in the past
            $subscriptionEndDate = new DateTime($testSubscription['end_date']);
            $now = new DateTime();
            $this->assertLessThan(
                $now,
                $subscriptionEndDate,
                'Subscription end date should be in the past'
            );
            
            // Act: Process expired subscriptions
            $result = $this->expirationService->processExpiredSubscriptions();
            
            // Assert: Subscription was found and expired
            $this->assertGreaterThanOrEqual(
                1,
                $result['count'],
                'At least one subscription should be expired'
            );
            
            $this->assertContains(
                $testSubscription['id'],
                $result['subscription_ids'],
                'Test subscription should be in the list of expired subscriptions'
            );
            
            // Assert: Subscription status is now EXPIRED
            $updatedSubscription = $this->subscriptionModel->findById($testSubscription['id']);
            $this->assertNotFalse($updatedSubscription, 'Subscription should still exist');
            
            $this->assertEquals(
                'EXPIRED',
                $updatedSubscription['status'],
                'Subscription status should be EXPIRED after processing'
            );
            
            // Assert: Other fields remain unchanged
            $this->assertEquals(
                $testSubscription['customer_id'],
                $updatedSubscription['customer_id'],
                'Customer ID should remain unchanged'
            );
            
            $this->assertEquals(
                $testSubscription['plan_id'],
                $updatedSubscription['plan_id'],
                'Plan ID should remain unchanged'
            );
            
            $this->assertEquals(
                $testSubscription['start_date'],
                $updatedSubscription['start_date'],
                'Start date should remain unchanged'
            );
            
            $this->assertEquals(
                $testSubscription['end_date'],
                $updatedSubscription['end_date'],
                'End date should remain unchanged'
            );
            
            $this->assertEquals(
                $testSubscription['assigned_port_id'],
                $updatedSubscription['assigned_port_id'],
                'Assigned port ID should remain unchanged'
            );
            
            // Cleanup
            $this->cleanupSubscription($testSubscription['id']);
            $this->cleanupPlan($testPlan['id']);
            $this->cleanupUser($testUser['id']);
        });
    }

    /**
     * Property: Active subscriptions with future end dates should not be expired
     * 
     * @test
     */
    public function activeSubscriptionsWithFutureEndDatesAreNotExpired(): void
    {
        $this->forAll(
            Generator\choose(1, 365) // Days in the future (1-365)
        )
        ->then(function ($daysInFuture) {
            // Arrange: Create test user and plan
            $testUser = $this->createTestUser();
            $testPlan = $this->createTestPlan();
            
            // Create subscription with end date in the future
            $startDate = new DateTime();
            $endDate = new DateTime();
            $endDate->modify("+{$daysInFuture} days");
            
            $testSubscription = $this->createTestSubscription(
                $testUser['id'],
                $testPlan['id'],
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
                'ACTIVE'
            );
            
            // Verify end date is in the future
            $subscriptionEndDate = new DateTime($testSubscription['end_date']);
            $now = new DateTime();
            $this->assertGreaterThan(
                $now,
                $subscriptionEndDate,
                'Subscription end date should be in the future'
            );
            
            // Act: Process expired subscriptions
            $result = $this->expirationService->processExpiredSubscriptions();
            
            // Assert: Test subscription should NOT be in the expired list
            $this->assertNotContains(
                $testSubscription['id'],
                $result['subscription_ids'],
                'Subscription with future end date should not be expired'
            );
            
            // Assert: Subscription status remains ACTIVE
            $updatedSubscription = $this->subscriptionModel->findById($testSubscription['id']);
            $this->assertEquals(
                'ACTIVE',
                $updatedSubscription['status'],
                'Subscription status should remain ACTIVE'
            );
            
            // Cleanup
            $this->cleanupSubscription($testSubscription['id']);
            $this->cleanupPlan($testPlan['id']);
            $this->cleanupUser($testUser['id']);
        });
    }

    /**
     * Property: Already expired subscriptions should not be processed again
     * 
     * @test
     */
    public function alreadyExpiredSubscriptionsAreNotProcessedAgain(): void
    {
        $this->forAll(
            Generator\choose(1, 365) // Days in the past
        )
        ->then(function ($daysInPast) {
            // Arrange: Create test user and plan
            $testUser = $this->createTestUser();
            $testPlan = $this->createTestPlan();
            
            // Create subscription with end date in the past and status EXPIRED
            $startDate = new DateTime();
            $startDate->modify("-{$daysInPast} days");
            $startDate->modify('-30 days');
            
            $endDate = new DateTime();
            $endDate->modify("-{$daysInPast} days");
            
            $testSubscription = $this->createTestSubscription(
                $testUser['id'],
                $testPlan['id'],
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
                'EXPIRED' // Already expired
            );
            
            // Act: Process expired subscriptions
            $result = $this->expirationService->processExpiredSubscriptions();
            
            // Assert: Already expired subscription should NOT be in the list
            $this->assertNotContains(
                $testSubscription['id'],
                $result['subscription_ids'],
                'Already expired subscription should not be processed again'
            );
            
            // Assert: Status remains EXPIRED
            $updatedSubscription = $this->subscriptionModel->findById($testSubscription['id']);
            $this->assertEquals(
                'EXPIRED',
                $updatedSubscription['status'],
                'Subscription status should remain EXPIRED'
            );
            
            // Cleanup
            $this->cleanupSubscription($testSubscription['id']);
            $this->cleanupPlan($testPlan['id']);
            $this->cleanupUser($testUser['id']);
        });
    }

    /**
     * Property: Cancelled subscriptions should not be expired
     * 
     * @test
     */
    public function cancelledSubscriptionsAreNotExpired(): void
    {
        $this->forAll(
            Generator\choose(1, 365) // Days in the past
        )
        ->then(function ($daysInPast) {
            // Arrange: Create test user and plan
            $testUser = $this->createTestUser();
            $testPlan = $this->createTestPlan();
            
            // Create subscription with end date in the past but status CANCELLED
            $startDate = new DateTime();
            $startDate->modify("-{$daysInPast} days");
            $startDate->modify('-30 days');
            
            $endDate = new DateTime();
            $endDate->modify("-{$daysInPast} days");
            
            $testSubscription = $this->createTestSubscription(
                $testUser['id'],
                $testPlan['id'],
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
                'CANCELLED'
            );
            
            // Act: Process expired subscriptions
            $result = $this->expirationService->processExpiredSubscriptions();
            
            // Assert: Cancelled subscription should NOT be expired
            $this->assertNotContains(
                $testSubscription['id'],
                $result['subscription_ids'],
                'Cancelled subscription should not be expired'
            );
            
            // Assert: Status remains CANCELLED
            $updatedSubscription = $this->subscriptionModel->findById($testSubscription['id']);
            $this->assertEquals(
                'CANCELLED',
                $updatedSubscription['status'],
                'Subscription status should remain CANCELLED'
            );
            
            // Cleanup
            $this->cleanupSubscription($testSubscription['id']);
            $this->cleanupPlan($testPlan['id']);
            $this->cleanupUser($testUser['id']);
        });
    }

    /**
     * Property: Subscription expiring today should NOT be expired yet
     * (expires the day after end date)
     * 
     * @test
     */
    public function subscriptionExpiringTodayIsNotExpiredYet(): void
    {
        $this->forAll(
            Generator\choose(1, 12) // Billing period in months
        )
        ->then(function ($billingPeriodMonths) {
            // Arrange: Create test user and plan
            $testUser = $this->createTestUser();
            $testPlan = $this->createTestPlan($billingPeriodMonths);
            
            // Create subscription with end date = today
            $startDate = new DateTime();
            $startDate->modify("-{$billingPeriodMonths} months");
            
            $endDate = new DateTime();
            $endDate->setTime(0, 0, 0); // Set to midnight today
            
            $testSubscription = $this->createTestSubscription(
                $testUser['id'],
                $testPlan['id'],
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
                'ACTIVE'
            );
            
            // Act: Process expired subscriptions
            $result = $this->expirationService->processExpiredSubscriptions();
            
            // Assert: Subscription expiring today should NOT be expired yet
            // (specification says "after the end date", not "on or after")
            $this->assertNotContains(
                $testSubscription['id'],
                $result['subscription_ids'],
                'Subscription expiring today should not be expired yet (expires day after)'
            );
            
            // Assert: Status remains ACTIVE
            $updatedSubscription = $this->subscriptionModel->findById($testSubscription['id']);
            $this->assertEquals(
                'ACTIVE',
                $updatedSubscription['status'],
                'Subscription status should remain ACTIVE on end date'
            );
            
            // Cleanup
            $this->cleanupSubscription($testSubscription['id']);
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
    private function createTestPlan(int $billingPeriodMonths = 1): array
    {
        $slug = 'test-plan-' . bin2hex(random_bytes(8));
        $planData = [
            'name' => 'Test Plan',
            'slug' => $slug,
            'description' => 'Test plan for property testing',
            'price' => 100.00,
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
        $orderId = null;
        if ($subscription && isset($subscription['order_id'])) {
            $orderId = $subscription['order_id'];
        }
        
        // Delete subscription first (due to foreign key constraints)
        $this->subscriptionModel->delete($subscriptionId);
        
        // Then delete the order
        if ($orderId) {
            $this->orderModel->delete($orderId);
        }
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

