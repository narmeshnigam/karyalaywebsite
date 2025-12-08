<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Services\PortService;
use Karyalay\Models\Port;
use Karyalay\Models\Plan;

/**
 * Property-based tests for port CRUD persistence
 * 
 * Feature: karyalay-portal-system, Property 30: Port CRUD Persistence
 * Validates: Requirements 9.1
 */
class PortCrudPersistencePropertyTest extends TestCase
{
    use TestTrait;

    private PortService $portService;
    private Port $portModel;
    private Plan $planModel;
    private array $testPlanIds = [];
    private array $testPortIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->portService = new PortService();
        $this->portModel = new Port();
        $this->planModel = new Plan();
    }

    protected function tearDown(): void
    {
        // Clean up test ports
        foreach ($this->testPortIds as $portId) {
            $this->portModel->delete($portId);
        }
        
        // Clean up test plans
        foreach ($this->testPlanIds as $planId) {
            $this->planModel->delete($planId);
        }
        
        parent::tearDown();
    }

    /**
     * Property 30: Port CRUD Persistence
     * 
     * For any port data, when an admin creates or updates a port, the changes 
     * should be persisted to the database with all required fields.
     * 
     * Validates: Requirements 9.1
     * 
     * @test
     */
    public function portCreatePersistsAllRequiredFields(): void
    {
        $this->forAll(
            Generator\choose(1, 65535),
            Generator\elements('AVAILABLE', 'RESERVED', 'ASSIGNED', 'DISABLED')
        )
        ->then(function ($portNumber, $status) {
            // Create a test plan
            $planId = $this->createTestPlan();
            
            // Generate unique instance URL and constrained data
            $uniqueInstanceUrl = 'https://test-' . bin2hex(random_bytes(8)) . '.example.com';
            $serverRegion = 'region-' . bin2hex(random_bytes(4)); // Max 50 chars
            $notes = 'Test notes ' . bin2hex(random_bytes(8));
            
            $portData = [
                'instance_url' => $uniqueInstanceUrl,
                'port_number' => $portNumber,
                'plan_id' => $planId,
                'status' => $status,
                'server_region' => $serverRegion,
                'notes' => $notes
            ];
            
            // Act: Create port
            $result = $this->portService->createPort($portData);
            
            // Assert: Creation succeeded
            $this->assertTrue(
                $result['success'],
                'Port creation should succeed with valid data'
            );
            $this->assertNull($result['error'] ?? null, 'No error should be returned');
            $this->assertNotNull($result['port'], 'Port data should be returned');
            
            $portId = $result['port']['id'];
            $this->testPortIds[] = $portId;
            
            // Assert: Port was persisted to database
            $port = $this->portModel->findById($portId);
            $this->assertNotFalse($port, 'Port should exist in database');
            
            // Assert: All required fields are persisted correctly
            $this->assertEquals($uniqueInstanceUrl, $port['instance_url']);
            $this->assertEquals($portNumber, $port['port_number']);
            $this->assertEquals($planId, $port['plan_id']);
            $this->assertEquals($status, $port['status']);
            $this->assertEquals($serverRegion, $port['server_region']);
            $this->assertEquals($notes, $port['notes']);
            
            // Assert: Port has ID and timestamps
            $this->assertNotEmpty($port['id']);
            $this->assertNotEmpty($port['created_at']);
            $this->assertNotEmpty($port['updated_at']);
        });
    }

    /**
     * Property: Port update persists changes to database
     * 
     * @test
     */
    public function portUpdatePersistsChanges(): void
    {
        $this->forAll(
            Generator\elements('AVAILABLE', 'RESERVED', 'ASSIGNED', 'DISABLED')
        )
        ->then(function ($newStatus) {
            // Generate constrained data
            $newServerRegion = 'updated-' . bin2hex(random_bytes(4));
            $newNotes = 'Updated notes ' . bin2hex(random_bytes(8));
            // Create initial port
            $planId = $this->createTestPlan();
            $initialPortData = [
                'instance_url' => 'https://test-' . bin2hex(random_bytes(8)) . '.example.com',
                'port_number' => rand(1000, 9999),
                'plan_id' => $planId,
                'status' => 'AVAILABLE',
                'server_region' => 'initial-region',
                'notes' => 'initial notes'
            ];
            
            $createResult = $this->portService->createPort($initialPortData);
            $this->assertTrue($createResult['success']);
            $portId = $createResult['port']['id'];
            $this->testPortIds[] = $portId;
            
            // Act: Update port
            $updateData = [
                'status' => $newStatus,
                'server_region' => $newServerRegion,
                'notes' => $newNotes
            ];
            
            $updateResult = $this->portService->updatePort($portId, $updateData);
            
            // Assert: Update succeeded
            $this->assertTrue(
                $updateResult['success'],
                'Port update should succeed'
            );
            
            // Assert: Changes were persisted
            $updatedPort = $this->portModel->findById($portId);
            $this->assertEquals($newStatus, $updatedPort['status']);
            $this->assertEquals($newServerRegion, $updatedPort['server_region']);
            $this->assertEquals($newNotes, $updatedPort['notes']);
            
            // Assert: Unchanged fields remain the same
            $this->assertEquals($initialPortData['instance_url'], $updatedPort['instance_url']);
            $this->assertEquals($initialPortData['port_number'], $updatedPort['port_number']);
            $this->assertEquals($planId, $updatedPort['plan_id']);
        });
    }

    /**
     * Property: Port read retrieves correct data from database
     * 
     * @test
     */
    public function portReadRetrievesCorrectData(): void
    {
        // Create a port
        $planId = $this->createTestPlan();
        $instanceUrl = 'https://test-' . bin2hex(random_bytes(8)) . '.example.com';
        $portNumber = rand(1000, 9999);
        $status = 'AVAILABLE';
        
        $portData = [
            'instance_url' => $instanceUrl,
            'port_number' => $portNumber,
            'plan_id' => $planId,
            'status' => $status
        ];
        
        $createResult = $this->portService->createPort($portData);
        $this->assertTrue($createResult['success']);
        $portId = $createResult['port']['id'];
        $this->testPortIds[] = $portId;
        
        // Act: Read port
        $readResult = $this->portService->getPort($portId);
        
        // Assert: Read succeeded
        $this->assertTrue($readResult['success']);
        $this->assertNotNull($readResult['port']);
        
        // Assert: Retrieved data matches created data
        $retrievedPort = $readResult['port'];
        $this->assertEquals($portId, $retrievedPort['id']);
        $this->assertEquals($instanceUrl, $retrievedPort['instance_url']);
        $this->assertEquals($portNumber, $retrievedPort['port_number']);
        $this->assertEquals($planId, $retrievedPort['plan_id']);
        $this->assertEquals($status, $retrievedPort['status']);
    }

    /**
     * Property: Port delete removes port from database
     * 
     * @test
     */
    public function portDeleteRemovesFromDatabase(): void
    {
        // Create a port
        $planId = $this->createTestPlan();
        $portData = [
            'instance_url' => 'https://test-' . bin2hex(random_bytes(8)) . '.example.com',
            'port_number' => rand(1000, 9999),
            'plan_id' => $planId,
            'status' => 'AVAILABLE'
        ];
        
        $createResult = $this->portService->createPort($portData);
        $this->assertTrue($createResult['success']);
        $portId = $createResult['port']['id'];
        
        // Verify port exists
        $port = $this->portModel->findById($portId);
        $this->assertNotFalse($port);
        
        // Act: Delete port
        $deleteResult = $this->portService->deletePort($portId);
        
        // Assert: Delete succeeded
        $this->assertTrue($deleteResult['success']);
        
        // Assert: Port no longer exists in database
        $deletedPort = $this->portModel->findById($portId);
        $this->assertFalse($deletedPort, 'Port should not exist after deletion');
    }

    /**
     * Property: Cannot delete assigned port
     * 
     * @test
     */
    public function cannotDeleteAssignedPort(): void
    {
        // Create a test subscription first
        $planId = $this->createTestPlan();
        $subscriptionId = $this->createTestSubscription($planId);
        
        // Create a port and assign it to the subscription
        $portData = [
            'instance_url' => 'https://test-' . bin2hex(random_bytes(8)) . '.example.com',
            'port_number' => rand(1000, 9999),
            'plan_id' => $planId,
            'status' => 'ASSIGNED',
            'assigned_subscription_id' => $subscriptionId
        ];
        
        $createResult = $this->portService->createPort($portData);
        $this->assertTrue($createResult['success']);
        $portId = $createResult['port']['id'];
        $this->testPortIds[] = $portId;
        
        // Act: Attempt to delete assigned port
        $deleteResult = $this->portService->deletePort($portId);
        
        // Assert: Delete should fail
        $this->assertFalse(
            $deleteResult['success'],
            'Should not be able to delete assigned port'
        );
        $this->assertNotNull($deleteResult['error']);
        
        // Assert: Port still exists in database
        $port = $this->portModel->findById($portId);
        $this->assertNotFalse($port, 'Port should still exist after failed deletion');
    }

    /**
     * Property: Port status management works correctly
     * 
     * @test
     */
    public function portStatusManagementWorks(): void
    {
        $validStatuses = ['AVAILABLE', 'RESERVED', 'ASSIGNED', 'DISABLED'];
        
        foreach ($validStatuses as $status) {
            // Create port
            $planId = $this->createTestPlan();
            $portData = [
                'instance_url' => 'https://test-' . bin2hex(random_bytes(8)) . '.example.com',
                'port_number' => rand(1000, 9999),
                'plan_id' => $planId,
                'status' => 'AVAILABLE'
            ];
            
            $createResult = $this->portService->createPort($portData);
            $this->assertTrue($createResult['success']);
            $portId = $createResult['port']['id'];
            $this->testPortIds[] = $portId;
            
            // Act: Update status
            $updateResult = $this->portService->updatePortStatus($portId, $status);
            
            // Assert: Status update succeeded
            $this->assertTrue(
                $updateResult['success'],
                "Status update to {$status} should succeed"
            );
            
            // Assert: Status was persisted
            $port = $this->portModel->findById($portId);
            $this->assertEquals($status, $port['status']);
        }
    }

    /**
     * Property: Invalid status is rejected
     * 
     * @test
     */
    public function invalidStatusIsRejected(): void
    {
        $planId = $this->createTestPlan();
        $portData = [
            'instance_url' => 'https://test-' . bin2hex(random_bytes(8)) . '.example.com',
            'port_number' => rand(1000, 9999),
            'plan_id' => $planId,
            'status' => 'INVALID_STATUS'
        ];
        
        // Act: Attempt to create port with invalid status
        $result = $this->portService->createPort($portData);
        
        // Assert: Creation should fail
        $this->assertFalse(
            $result['success'],
            'Port creation with invalid status should fail'
        );
        $this->assertNotNull($result['error']);
    }

    /**
     * Property: Duplicate port is rejected
     * 
     * @test
     */
    public function duplicatePortIsRejected(): void
    {
        $planId = $this->createTestPlan();
        $instanceUrl = 'https://test-' . bin2hex(random_bytes(8)) . '.example.com';
        $portNumber = rand(1000, 9999);
        
        $portData = [
            'instance_url' => $instanceUrl,
            'port_number' => $portNumber,
            'plan_id' => $planId,
            'status' => 'AVAILABLE'
        ];
        
        // Create first port
        $result1 = $this->portService->createPort($portData);
        $this->assertTrue($result1['success']);
        $this->testPortIds[] = $result1['port']['id'];
        
        // Act: Attempt to create duplicate port
        $result2 = $this->portService->createPort($portData);
        
        // Assert: Second creation should fail
        $this->assertFalse(
            $result2['success'],
            'Duplicate port creation should fail'
        );
        $this->assertNotNull($result2['error']);
    }

    /**
     * Helper: Create test plan
     */
    private function createTestPlan(): string
    {
        $planData = [
            'name' => 'Test Plan ' . bin2hex(random_bytes(4)),
            'slug' => 'test-plan-' . bin2hex(random_bytes(4)),
            'description' => 'Test plan for port tests',
            'price' => 99.99,
            'currency' => 'USD',
            'billing_period_months' => 1,
            'features' => json_encode(['feature1', 'feature2']),
            'status' => 'ACTIVE'
        ];
        
        $plan = $this->planModel->create($planData);
        $this->testPlanIds[] = $plan['id'];
        
        return $plan['id'];
    }

    /**
     * Helper: Create test subscription
     */
    private function createTestSubscription(string $planId): string
    {
        $subscriptionModel = new \Karyalay\Models\Subscription();
        $userModel = new \Karyalay\Models\User();
        $orderModel = new \Karyalay\Models\Order();
        
        // Create a test user
        $userData = [
            'email' => 'test-' . bin2hex(random_bytes(8)) . '@example.com',
            'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
            'name' => 'Test User',
            'role' => 'CUSTOMER'
        ];
        $user = $userModel->create($userData);
        
        // Create a test order
        $orderData = [
            'customer_id' => $user['id'],
            'plan_id' => $planId,
            'amount' => 99.99,
            'currency' => 'USD',
            'status' => 'SUCCESS'
        ];
        $order = $orderModel->create($orderData);
        
        // Create subscription
        $subscriptionData = [
            'customer_id' => $user['id'],
            'plan_id' => $planId,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+1 month')),
            'status' => 'ACTIVE',
            'order_id' => $order['id']
        ];
        $subscription = $subscriptionModel->create($subscriptionData);
        
        return $subscription['id'];
    }

    /**
     * Helper: Generate UUID
     */
    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
