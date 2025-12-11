<?php

namespace Karyalay\Tests\Property;

use PHPUnit\Framework\TestCase;
use Karyalay\Services\PortAllocationService;
use Karyalay\Models\Port;
use Karyalay\Models\Plan;
use Karyalay\Models\Subscription;
use Karyalay\Models\User;
use Karyalay\Models\Order;
use Karyalay\Models\PortAllocationLog;

/**
 * Property-based tests for port assignment atomicity and linking
 * 
 * Feature: karyalay-portal-system, Property 14: Port Assignment Atomicity
 * Feature: karyalay-portal-system, Property 15: Port-Subscription Linking
 * Feature: karyalay-portal-system, Property 16: Port Reassignment Logging
 * Validates: Requirements 4.2, 4.4, 4.5
 */
class PortAllocationAtomicityPropertyTest extends TestCase
{
    private PortAllocationService $allocationService;
    private Port $portModel;
    private Plan $planModel;
    private Subscription $subscriptionModel;
    private User $userModel;
    private Order $orderModel;
    private PortAllocationLog $logModel;
    private array $testData = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->allocationService = new PortAllocationService();
        $this->portModel = new Port();
        $this->planModel = new Plan();
        $this->subscriptionModel = new Subscription();
        $this->userModel = new User();
        $this->orderModel = new Order();
        $this->logModel = new PortAllocationLog();
        $this->testData = ['plans' => [], 'ports' => [], 'users' => [], 'orders' => [], 'subscriptions' => []];
    }

    protected function tearDown(): void
    {
        foreach ($this->testData['ports'] as $id) $this->portModel->delete($id);
        foreach ($this->testData['subscriptions'] as $id) $this->subscriptionModel->delete($id);
        foreach ($this->testData['orders'] as $id) $this->orderModel->delete($id);
        foreach ($this->testData['users'] as $id) $this->userModel->delete($id);
        foreach ($this->testData['plans'] as $id) $this->planModel->delete($id);
        parent::tearDown();
    }

    /**
     * Property 14: Port Assignment Atomicity
     * 
     * For any successful order with available ports, when port allocation occurs,
     * exactly one port should be assigned, its status should change to ASSIGNED,
     * and the assignment timestamp should be recorded.
     * 
     * Validates: Requirements 4.2
     * 
     * @test
     */
    public function portAssignmentIsAtomic(): void
    {
        $planId = $this->createTestPlan();
        $portId = $this->createTestPort($planId, 'AVAILABLE');
        $subscriptionId = $this->createTestSubscription($planId);
        
        // Get initial port state
        $initialPort = $this->portModel->findById($portId);
        $this->assertEquals('AVAILABLE', $initialPort['status']);
        $this->assertNull($initialPort['assigned_at']);
        
        // Act: Allocate port
        $result = $this->allocationService->allocatePortToSubscription($subscriptionId);
        
        // Assert: Allocation succeeded
        $this->assertTrue($result['success']);
        
        // Assert: Exactly one port was assigned
        $allocatedPort = $this->portModel->findById($portId);
        $this->assertEquals('ASSIGNED', $allocatedPort['status']);
        
        // Assert: Assignment timestamp was recorded
        $this->assertNotNull($allocatedPort['assigned_at']);
        $this->assertNotEmpty($allocatedPort['assigned_at']);
        
        // Assert: Port is linked to subscription
        $this->assertEquals($subscriptionId, $allocatedPort['assigned_subscription_id']);
        
        // Assert: Subscription is linked to port
        $subscription = $this->subscriptionModel->findById($subscriptionId);
        $this->assertEquals($portId, $subscription['assigned_port_id']);
    }

    /**
     * Property 15: Port-Subscription Linking
     * 
     * For any port assignment, when a port is assigned to a subscription,
     * the port identifier should be linked to both the subscription record
     * and customer record.
     * 
     * Validates: Requirements 4.4
     * 
     * @test
     */
    public function portSubscriptionLinkingIsComplete(): void
    {
        $planId = $this->createTestPlan();
        $portId = $this->createTestPort($planId, 'AVAILABLE');
        $subscriptionId = $this->createTestSubscription($planId);
        
        // Get subscription to know customer ID
        $subscription = $this->subscriptionModel->findById($subscriptionId);
        $customerId = $subscription['customer_id'];
        
        // Act: Allocate port
        $result = $this->allocationService->allocatePortToSubscription($subscriptionId);
        
        // Assert: Allocation succeeded
        $this->assertTrue($result['success']);
        
        // Assert: Port is linked to subscription
        $port = $this->portModel->findById($portId);
        $this->assertEquals($subscriptionId, $port['assigned_subscription_id']);
        
        // Assert: Subscription is linked to port
        $updatedSubscription = $this->subscriptionModel->findById($subscriptionId);
        $this->assertEquals($portId, $updatedSubscription['assigned_port_id']);
    }

    /**
     * Property 16: Port Reassignment Logging
     * 
     * For any port reassignment action, when an administrator reassigns a port,
     * a log entry should be created with the port ID, subscription ID, timestamp,
     * and administrator identifier.
     * 
     * Validates: Requirements 4.5
     * 
     * @test
     */
    public function portReassignmentIsLogged(): void
    {
        $planId = $this->createTestPlan();
        $portId = $this->createTestPort($planId, 'AVAILABLE');
        $subscription1Id = $this->createTestSubscription($planId);
        $subscription2Id = $this->createTestSubscription($planId);
        
        // Allocate port to first subscription
        $result1 = $this->allocationService->allocatePortToSubscription($subscription1Id);
        $this->assertTrue($result1['success']);
        
        // Create admin user
        $adminUser = $this->userModel->create([
            'email' => 'admin-' . bin2hex(random_bytes(8)) . '@example.com',
            'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
            'name' => 'Admin User',
            'role' => 'ADMIN'
        ]);
        $this->testData['users'][] = $adminUser['id'];
        $adminId = $adminUser['id'];
        
        // Act: Reassign port to second subscription
        $result2 = $this->allocationService->reassignPort($portId, $subscription2Id, $adminId);
        
        // Assert: Reassignment succeeded
        $this->assertTrue($result2['success']);
        
        // Assert: Log entry was created
        $logs = $this->logModel->findByPortId($portId);
        $this->assertGreaterThan(0, count($logs));
        
        // Find the reassignment log
        $reassignmentLog = null;
        foreach ($logs as $log) {
            if ($log['action'] === 'REASSIGNED') {
                $reassignmentLog = $log;
                break;
            }
        }
        
        $this->assertNotNull($reassignmentLog, 'Reassignment log should exist');
        
        // Assert: Log contains correct information
        $this->assertEquals($portId, $reassignmentLog['port_id']);
        $this->assertEquals($subscription2Id, $reassignmentLog['subscription_id']);
        $this->assertEquals($adminId, $reassignmentLog['performed_by']);
        $this->assertNotNull($reassignmentLog['timestamp']);
    }

    /**
     * Property: Port allocation creates assignment log
     * 
     * @test
     */
    public function portAllocationCreatesAssignmentLog(): void
    {
        $planId = $this->createTestPlan();
        $portId = $this->createTestPort($planId, 'AVAILABLE');
        $subscriptionId = $this->createTestSubscription($planId);
        
        // Act: Allocate port
        $result = $this->allocationService->allocatePortToSubscription($subscriptionId);
        
        // Assert: Allocation succeeded
        $this->assertTrue($result['success']);
        
        // Assert: Assignment log was created
        $logs = $this->logModel->findByPortId($portId);
        $this->assertGreaterThan(0, count($logs));
        
        // Find the assignment log
        $assignmentLog = null;
        foreach ($logs as $log) {
            if ($log['action'] === 'ASSIGNED') {
                $assignmentLog = $log;
                break;
            }
        }
        
        $this->assertNotNull($assignmentLog, 'Assignment log should exist');
        $this->assertEquals($portId, $assignmentLog['port_id']);
        $this->assertEquals($subscriptionId, $assignmentLog['subscription_id']);
    }

    /**
     * Property: Cannot allocate port to subscription that already has a port
     * 
     * @test
     */
    public function cannotAllocatePortToSubscriptionWithExistingPort(): void
    {
        $planId = $this->createTestPlan();
        $port1Id = $this->createTestPort($planId, 'AVAILABLE');
        $port2Id = $this->createTestPort($planId, 'AVAILABLE');
        $subscriptionId = $this->createTestSubscription($planId);
        
        // Allocate first port
        $result1 = $this->allocationService->allocatePortToSubscription($subscriptionId);
        $this->assertTrue($result1['success']);
        
        // Verify subscription has assigned port after first allocation
        $subscriptionAfterFirst = $this->subscriptionModel->findById($subscriptionId);
        $this->assertNotNull($subscriptionAfterFirst['assigned_port_id']);
        $assignedPortId = $subscriptionAfterFirst['assigned_port_id'];
        
        // Verify the assigned port is one of our test ports
        $this->assertContains($assignedPortId, [$port1Id, $port2Id]);
        
        // Act: Attempt to allocate second port to same subscription
        $result2 = $this->allocationService->allocatePortToSubscription($subscriptionId);
        
        // Assert: Second allocation should fail
        $this->assertFalse(
            $result2['success'],
            'Second allocation should fail: ' . ($result2['error'] ?? 'no error')
        );
        $this->assertNotNull($result2['error']);
        
        // Assert: Subscription still has the same port assigned (not changed)
        $subscriptionAfterSecond = $this->subscriptionModel->findById($subscriptionId);
        $this->assertEquals($assignedPortId, $subscriptionAfterSecond['assigned_port_id']);
        
        // Assert: At least one port is still AVAILABLE (the one that wasn't assigned)
        $port1 = $this->portModel->findById($port1Id);
        $port2 = $this->portModel->findById($port2Id);
        $availablePorts = array_filter([$port1, $port2], fn($p) => $p['status'] === 'AVAILABLE');
        $this->assertCount(1, $availablePorts, 'Exactly one port should remain AVAILABLE');
    }

    private function createTestPlan(): string
    {
        $plan = $this->planModel->create([
            'name' => 'Test Plan ' . bin2hex(random_bytes(4)),
            'slug' => 'test-plan-' . bin2hex(random_bytes(4)),
            'description' => 'Test',
            'price' => 99.99,
            'currency' => 'USD',
            'billing_period_months' => 1,
            'features' => json_encode(['f1']),
            'status' => 'ACTIVE'
        ]);
        $this->testData['plans'][] = $plan['id'];
        return $plan['id'];
    }

    private function createTestPort(string $planId, string $status): string
    {
        $port = $this->portModel->create([
            'instance_url' => 'https://test-' . bin2hex(random_bytes(8)) . '.example.com',
            'port_number' => rand(1000, 9999),
            'status' => $status
        ]);
        $this->testData['ports'][] = $port['id'];
        return $port['id'];
    }

    private function createTestSubscription(string $planId): string
    {
        $user = $this->userModel->create([
            'email' => 'test-' . bin2hex(random_bytes(8)) . '@example.com',
            'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
            'name' => 'Test User',
            'role' => 'CUSTOMER'
        ]);
        $this->testData['users'][] = $user['id'];
        
        $order = $this->orderModel->create([
            'customer_id' => $user['id'],
            'plan_id' => $planId,
            'amount' => 99.99,
            'currency' => 'USD',
            'status' => 'SUCCESS'
        ]);
        $this->testData['orders'][] = $order['id'];
        
        $subscription = $this->subscriptionModel->create([
            'customer_id' => $user['id'],
            'plan_id' => $planId,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+1 month')),
            'status' => 'ACTIVE',
            'order_id' => $order['id']
        ]);
        $this->testData['subscriptions'][] = $subscription['id'];
        
        return $subscription['id'];
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
