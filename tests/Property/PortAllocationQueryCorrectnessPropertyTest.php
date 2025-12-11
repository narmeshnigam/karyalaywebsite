<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Services\PortAllocationService;
use Karyalay\Models\Port;
use Karyalay\Models\Plan;
use Karyalay\Models\Subscription;
use Karyalay\Models\User;
use Karyalay\Models\Order;

/**
 * Property-based tests for port allocation query correctness
 * 
 * Feature: karyalay-portal-system, Property 13: Port Allocation Query Correctness
 * Validates: Requirements 4.1
 */
class PortAllocationQueryCorrectnessPropertyTest extends TestCase
{
    use TestTrait;

    private PortAllocationService $allocationService;
    private Port $portModel;
    private Plan $planModel;
    private Subscription $subscriptionModel;
    private User $userModel;
    private Order $orderModel;
    private array $testPlanIds = [];
    private array $testPortIds = [];
    private array $testUserIds = [];
    private array $testOrderIds = [];
    private array $testSubscriptionIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->allocationService = new PortAllocationService();
        $this->portModel = new Port();
        $this->planModel = new Plan();
        $this->subscriptionModel = new Subscription();
        $this->userModel = new User();
        $this->orderModel = new Order();
    }

    protected function tearDown(): void
    {
        // Clean up test data
        foreach ($this->testPortIds as $portId) {
            $this->portModel->delete($portId);
        }
        foreach ($this->testSubscriptionIds as $subscriptionId) {
            $this->subscriptionModel->delete($subscriptionId);
        }
        foreach ($this->testOrderIds as $orderId) {
            $this->orderModel->delete($orderId);
        }
        foreach ($this->testUserIds as $userId) {
            $this->userModel->delete($userId);
        }
        foreach ($this->testPlanIds as $planId) {
            $this->planModel->delete($planId);
        }
        
        parent::tearDown();
    }

    /**
     * Property 13: Port Allocation Query Correctness
     * 
     * For any successful order, when port allocation is triggered, the system 
     * should query for ports with status AVAILABLE matching the plan criteria.
     * 
     * Validates: Requirements 4.1
     * 
     * @test
     */
    public function portAllocationQueriesAvailablePortsForPlan(): void
    {
        // Create a plan
        $planId = $this->createTestPlan();
        
        // Create multiple ports for the plan with different statuses
        $availablePort1 = $this->createTestPort($planId, 'AVAILABLE');
        $availablePort2 = $this->createTestPort($planId, 'AVAILABLE');
        $assignedPort = $this->createTestPort($planId, 'ASSIGNED');
        $disabledPort = $this->createTestPort($planId, 'DISABLED');
        $reservedPort = $this->createTestPort($planId, 'RESERVED');
        
        // Create a subscription
        $subscriptionId = $this->createTestSubscription($planId);
        
        // Act: Allocate port to subscription
        $result = $this->allocationService->allocatePortToSubscription($subscriptionId);
        
        // Assert: Allocation succeeded
        $this->assertTrue(
            $result['success'],
            'Port allocation should succeed when available ports exist'
        );
        $this->assertNotNull($result['port']);
        
        // Assert: Allocated port is one of the AVAILABLE ports
        $allocatedPortId = $result['port']['id'];
        $this->assertTrue(
            $allocatedPortId === $availablePort1 || $allocatedPortId === $availablePort2,
            'Allocated port should be one of the AVAILABLE ports'
        );
        
        // Assert: Allocated port is not one of the non-available ports
        $this->assertNotEquals($assignedPort, $allocatedPortId);
        $this->assertNotEquals($disabledPort, $allocatedPortId);
        $this->assertNotEquals($reservedPort, $allocatedPortId);
        
        // Assert: Allocated port status is now ASSIGNED
        $allocatedPort = $this->portModel->findById($allocatedPortId);
        $this->assertEquals('ASSIGNED', $allocatedPort['status']);
    }

    /**
     * Property: Port allocation queries any available port (plan-agnostic)
     * 
     * @test
     */
    public function portAllocationQueriesAnyAvailablePort(): void
    {
        // Create two different plans
        $plan1Id = $this->createTestPlan();
        $plan2Id = $this->createTestPlan();
        
        // Create available ports (ports are now plan-agnostic)
        $port1 = $this->createTestPort($plan1Id, 'AVAILABLE');
        $port2 = $this->createTestPort($plan2Id, 'AVAILABLE');
        
        // Create subscription for plan1
        $subscriptionId = $this->createTestSubscription($plan1Id);
        
        // Act: Allocate port to subscription
        $result = $this->allocationService->allocatePortToSubscription($subscriptionId);
        
        // Assert: Allocation succeeded
        $this->assertTrue($result['success']);
        
        // Assert: Allocated port is one of the available ports (any port can be allocated)
        $allocatedPortId = $result['port']['id'];
        $this->assertTrue(
            $allocatedPortId === $port1 || $allocatedPortId === $port2,
            'Allocated port should be one of the available ports'
        );
        
        // Assert: Allocated port status is now ASSIGNED
        $allocatedPort = $this->portModel->findById($allocatedPortId);
        $this->assertEquals('ASSIGNED', $allocatedPort['status']);
    }

    /**
     * Property: Port allocation fails when no available ports exist
     * 
     * @test
     */
    public function portAllocationFailsWhenNoAvailablePortsExist(): void
    {
        // Create a plan with no available ports
        $planId = $this->createTestPlan();
        
        // Create only non-available ports
        $this->createTestPort($planId, 'ASSIGNED');
        $this->createTestPort($planId, 'DISABLED');
        $this->createTestPort($planId, 'RESERVED');
        
        // Create subscription
        $subscriptionId = $this->createTestSubscription($planId);
        
        // Act: Attempt to allocate port
        $result = $this->allocationService->allocatePortToSubscription($subscriptionId);
        
        // Assert: Allocation failed
        $this->assertFalse(
            $result['success'],
            'Port allocation should fail when no available ports exist'
        );
        $this->assertEquals('NO_AVAILABLE_PORTS', $result['error']);
    }

    /**
     * Property: Port allocation excludes DISABLED ports
     * 
     * @test
     */
    public function portAllocationExcludesDisabledPorts(): void
    {
        // Create a plan
        $planId = $this->createTestPlan();
        
        // Create only DISABLED ports
        $disabledPort1 = $this->createTestPort($planId, 'DISABLED');
        $disabledPort2 = $this->createTestPort($planId, 'DISABLED');
        
        // Create subscription
        $subscriptionId = $this->createTestSubscription($planId);
        
        // Act: Attempt to allocate port
        $result = $this->allocationService->allocatePortToSubscription($subscriptionId);
        
        // Assert: Allocation failed (no available ports)
        $this->assertFalse($result['success']);
        $this->assertEquals('NO_AVAILABLE_PORTS', $result['error']);
        
        // Assert: DISABLED ports remain DISABLED
        $port1 = $this->portModel->findById($disabledPort1);
        $port2 = $this->portModel->findById($disabledPort2);
        $this->assertEquals('DISABLED', $port1['status']);
        $this->assertEquals('DISABLED', $port2['status']);
    }

    /**
     * Property: Port allocation selects first available port
     * 
     * @test
     */
    public function portAllocationSelectsFirstAvailablePort(): void
    {
        // Create a plan
        $planId = $this->createTestPlan();
        
        // Create multiple available ports (they'll be ordered by created_at ASC)
        $firstPort = $this->createTestPort($planId, 'AVAILABLE');
        sleep(1); // Ensure different timestamps
        $secondPort = $this->createTestPort($planId, 'AVAILABLE');
        
        // Create subscription
        $subscriptionId = $this->createTestSubscription($planId);
        
        // Act: Allocate port
        $result = $this->allocationService->allocatePortToSubscription($subscriptionId);
        
        // Assert: Allocation succeeded
        $this->assertTrue($result['success']);
        
        // Assert: First port was allocated (oldest created_at)
        $allocatedPortId = $result['port']['id'];
        $this->assertEquals($firstPort, $allocatedPortId);
        
        // Assert: Second port is still AVAILABLE
        $secondPortData = $this->portModel->findById($secondPort);
        $this->assertEquals('AVAILABLE', $secondPortData['status']);
    }

    /**
     * Helper: Create test plan
     */
    private function createTestPlan(): string
    {
        $planData = [
            'name' => 'Test Plan ' . bin2hex(random_bytes(4)),
            'slug' => 'test-plan-' . bin2hex(random_bytes(4)),
            'description' => 'Test plan',
            'price' => 99.99,
            'currency' => 'USD',
            'billing_period_months' => 1,
            'features' => json_encode(['feature1']),
            'status' => 'ACTIVE'
        ];
        
        $plan = $this->planModel->create($planData);
        $this->testPlanIds[] = $plan['id'];
        
        return $plan['id'];
    }

    /**
     * Helper: Create test port (plan-agnostic)
     */
    private function createTestPort(string $planId, string $status): string
    {
        $portData = [
            'instance_url' => 'https://test-' . bin2hex(random_bytes(8)) . '.example.com',
            'port_number' => rand(1000, 9999),
            'status' => $status
        ];
        
        $port = $this->portModel->create($portData);
        $this->testPortIds[] = $port['id'];
        
        return $port['id'];
    }

    /**
     * Helper: Create test subscription
     */
    private function createTestSubscription(string $planId): string
    {
        // Create user
        $userData = [
            'email' => 'test-' . bin2hex(random_bytes(8)) . '@example.com',
            'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
            'name' => 'Test User',
            'role' => 'CUSTOMER'
        ];
        $user = $this->userModel->create($userData);
        $this->testUserIds[] = $user['id'];
        
        // Create order
        $orderData = [
            'customer_id' => $user['id'],
            'plan_id' => $planId,
            'amount' => 99.99,
            'currency' => 'USD',
            'status' => 'SUCCESS'
        ];
        $order = $this->orderModel->create($orderData);
        $this->testOrderIds[] = $order['id'];
        
        // Create subscription
        $subscriptionData = [
            'customer_id' => $user['id'],
            'plan_id' => $planId,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+1 month')),
            'status' => 'ACTIVE',
            'order_id' => $order['id']
        ];
        $subscription = $this->subscriptionModel->create($subscriptionData);
        $this->testSubscriptionIds[] = $subscription['id'];
        
        return $subscription['id'];
    }
}
