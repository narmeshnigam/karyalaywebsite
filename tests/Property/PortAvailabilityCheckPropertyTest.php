<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Models\Plan;
use Karyalay\Models\Port;
use Karyalay\Services\PortAvailabilityService;

/**
 * Property-based tests for port availability check
 * 
 * Feature: karyalay-portal-system, Property 10: Port Availability Check
 * Validates: Requirements 3.2
 */
class PortAvailabilityCheckPropertyTest extends TestCase
{
    use TestTrait;

    private Plan $planModel;
    private Port $portModel;
    private PortAvailabilityService $portAvailabilityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->planModel = new Plan();
        $this->portModel = new Port();
        $this->portAvailabilityService = new PortAvailabilityService();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Property 10: Port Availability Check
     * 
     * For any plan, when checkout is initiated, the system should correctly 
     * determine whether at least one AVAILABLE port exists for that plan.
     * 
     * Validates: Requirements 3.2
     * 
     * @test
     */
    public function portAvailabilityCheckCorrectlyDeterminesAvailability(): void
    {
        $this->forAll(
            Generator\choose(0, 5)
        )
        ->then(function ($numAvailablePorts) {
            // Arrange: Create a test plan
            $planData = [
                'name' => 'Test Plan ' . bin2hex(random_bytes(4)),
                'slug' => 'test-plan-' . bin2hex(random_bytes(8)),
                'description' => 'Test plan for port availability',
                'price' => 99.99,
                'currency' => 'USD',
                'billing_period_months' => 1,
                'features' => ['Feature 1'],
                'status' => 'ACTIVE'
            ];
            
            $plan = $this->planModel->create($planData);
            $this->assertNotFalse($plan, 'Plan should be created successfully');
            
            // Create specified number of available ports
            $createdPorts = [];
            for ($i = 0; $i < $numAvailablePorts; $i++) {
                $portData = [
                    'instance_url' => 'https://instance-' . bin2hex(random_bytes(8)) . '.karyalay.com',
                    'port_number' => 8000 + $i,
                    'plan_id' => $plan['id'],
                    'status' => 'AVAILABLE'
                ];
                $port = $this->portModel->create($portData);
                $this->assertNotFalse($port);
                $createdPorts[] = $port;
            }
            
            // Act: Check port availability
            $availabilityCheck = $this->portAvailabilityService->checkAvailability($plan['id']);
            
            // Assert: Availability should match expected value
            $expectedAvailable = $numAvailablePorts > 0;
            $this->assertEquals(
                $expectedAvailable,
                $availabilityCheck['available'],
                "Port availability should be {$expectedAvailable} when {$numAvailablePorts} ports exist"
            );
            
            // Assert: Count should match number of available ports
            $this->assertEquals(
                $numAvailablePorts,
                $availabilityCheck['count'],
                "Available port count should be {$numAvailablePorts}"
            );
            
            // Assert: hasAvailablePorts should return correct boolean
            $hasAvailable = $this->portAvailabilityService->hasAvailablePorts($plan['id']);
            $this->assertEquals(
                $expectedAvailable,
                $hasAvailable,
                "hasAvailablePorts should return {$expectedAvailable}"
            );
            
            // Assert: getAvailablePortsCount should return correct count
            $count = $this->portAvailabilityService->getAvailablePortsCount($plan['id']);
            $this->assertEquals(
                $numAvailablePorts,
                $count,
                "getAvailablePortsCount should return {$numAvailablePorts}"
            );
            
            // Cleanup
            foreach ($createdPorts as $port) {
                $this->portModel->delete($port['id']);
            }
            $this->planModel->delete($plan['id']);
        });
    }

    /**
     * Property: Assigned ports should not be counted as available
     * 
     * @test
     */
    public function assignedPortsAreNotCountedAsAvailable(): void
    {
        $this->forAll(
            Generator\choose(1, 5),
            Generator\choose(0, 5)
        )
        ->then(function ($numAvailablePorts, $numAssignedPorts) {
            // Arrange: Create a test plan
            $planData = [
                'name' => 'Test Plan ' . bin2hex(random_bytes(4)),
                'slug' => 'test-plan-' . bin2hex(random_bytes(8)),
                'price' => 99.99,
                'currency' => 'USD',
                'billing_period_months' => 1,
                'status' => 'ACTIVE'
            ];
            
            $plan = $this->planModel->create($planData);
            $this->assertNotFalse($plan);
            
            $createdPorts = [];
            
            // Create available ports
            for ($i = 0; $i < $numAvailablePorts; $i++) {
                $portData = [
                    'instance_url' => 'https://available-' . bin2hex(random_bytes(8)) . '.karyalay.com',
                    'port_number' => 8000 + $i,
                    'plan_id' => $plan['id'],
                    'status' => 'AVAILABLE'
                ];
                $port = $this->portModel->create($portData);
                $this->assertNotFalse($port);
                $createdPorts[] = $port;
            }
            
            // Create assigned ports (without foreign key references for testing)
            for ($i = 0; $i < $numAssignedPorts; $i++) {
                $portData = [
                    'instance_url' => 'https://assigned-' . bin2hex(random_bytes(8)) . '.karyalay.com',
                    'port_number' => 9000 + $i,
                    'plan_id' => $plan['id'],
                    'status' => 'ASSIGNED',
                    'assigned_at' => date('Y-m-d H:i:s')
                ];
                $port = $this->portModel->create($portData);
                $this->assertNotFalse($port);
                $createdPorts[] = $port;
            }
            
            // Act: Check availability
            $availabilityCheck = $this->portAvailabilityService->checkAvailability($plan['id']);
            
            // Assert: Only available ports should be counted
            $this->assertEquals(
                $numAvailablePorts,
                $availabilityCheck['count'],
                "Only AVAILABLE ports should be counted, not ASSIGNED ports"
            );
            
            $this->assertEquals(
                $numAvailablePorts > 0,
                $availabilityCheck['available'],
                "Availability should be based only on AVAILABLE ports"
            );
            
            // Cleanup
            foreach ($createdPorts as $port) {
                $this->portModel->delete($port['id']);
            }
            $this->planModel->delete($plan['id']);
        });
    }

    /**
     * Property: Disabled ports should not be counted as available
     * 
     * @test
     */
    public function disabledPortsAreNotCountedAsAvailable(): void
    {
        // Arrange: Create a test plan
        $planData = [
            'name' => 'Test Plan',
            'slug' => 'test-plan-' . bin2hex(random_bytes(8)),
            'price' => 99.99,
            'currency' => 'USD',
            'billing_period_months' => 1,
            'status' => 'ACTIVE'
        ];
        
        $plan = $this->planModel->create($planData);
        $this->assertNotFalse($plan);
        
        // Create one available port
        $availablePort = $this->portModel->create([
            'instance_url' => 'https://available-' . bin2hex(random_bytes(8)) . '.karyalay.com',
            'port_number' => 8000,
            'plan_id' => $plan['id'],
            'status' => 'AVAILABLE'
        ]);
        
        // Create one disabled port
        $disabledPort = $this->portModel->create([
            'instance_url' => 'https://disabled-' . bin2hex(random_bytes(8)) . '.karyalay.com',
            'port_number' => 8001,
            'plan_id' => $plan['id'],
            'status' => 'DISABLED'
        ]);
        
        $this->assertNotFalse($availablePort);
        $this->assertNotFalse($disabledPort);
        
        // Act: Check availability
        $availabilityCheck = $this->portAvailabilityService->checkAvailability($plan['id']);
        
        // Assert: Only available port should be counted
        $this->assertEquals(1, $availabilityCheck['count']);
        $this->assertTrue($availabilityCheck['available']);
        
        // Cleanup
        $this->portModel->delete($availablePort['id']);
        $this->portModel->delete($disabledPort['id']);
        $this->planModel->delete($plan['id']);
    }

    /**
     * Property: Ports for different plans should not affect availability
     * 
     * @test
     */
    public function portsForDifferentPlansDoNotAffectAvailability(): void
    {
        // Arrange: Create two test plans
        $plan1Data = [
            'name' => 'Plan 1',
            'slug' => 'plan-1-' . bin2hex(random_bytes(8)),
            'price' => 49.99,
            'currency' => 'USD',
            'billing_period_months' => 1,
            'status' => 'ACTIVE'
        ];
        
        $plan2Data = [
            'name' => 'Plan 2',
            'slug' => 'plan-2-' . bin2hex(random_bytes(8)),
            'price' => 99.99,
            'currency' => 'USD',
            'billing_period_months' => 1,
            'status' => 'ACTIVE'
        ];
        
        $plan1 = $this->planModel->create($plan1Data);
        $plan2 = $this->planModel->create($plan2Data);
        
        $this->assertNotFalse($plan1);
        $this->assertNotFalse($plan2);
        
        // Create ports for plan 1
        $plan1Port = $this->portModel->create([
            'instance_url' => 'https://plan1-' . bin2hex(random_bytes(8)) . '.karyalay.com',
            'port_number' => 8000,
            'plan_id' => $plan1['id'],
            'status' => 'AVAILABLE'
        ]);
        
        // Create ports for plan 2
        $plan2Port1 = $this->portModel->create([
            'instance_url' => 'https://plan2-a-' . bin2hex(random_bytes(8)) . '.karyalay.com',
            'port_number' => 8001,
            'plan_id' => $plan2['id'],
            'status' => 'AVAILABLE'
        ]);
        
        $plan2Port2 = $this->portModel->create([
            'instance_url' => 'https://plan2-b-' . bin2hex(random_bytes(8)) . '.karyalay.com',
            'port_number' => 8002,
            'plan_id' => $plan2['id'],
            'status' => 'AVAILABLE'
        ]);
        
        $this->assertNotFalse($plan1Port);
        $this->assertNotFalse($plan2Port1);
        $this->assertNotFalse($plan2Port2);
        
        // Act: Check availability for each plan
        $plan1Availability = $this->portAvailabilityService->checkAvailability($plan1['id']);
        $plan2Availability = $this->portAvailabilityService->checkAvailability($plan2['id']);
        
        // Assert: Each plan should only see its own ports
        $this->assertEquals(1, $plan1Availability['count'], 'Plan 1 should have 1 available port');
        $this->assertEquals(2, $plan2Availability['count'], 'Plan 2 should have 2 available ports');
        
        // Cleanup
        $this->portModel->delete($plan1Port['id']);
        $this->portModel->delete($plan2Port1['id']);
        $this->portModel->delete($plan2Port2['id']);
        $this->planModel->delete($plan1['id']);
        $this->planModel->delete($plan2['id']);
    }

    /**
     * Property: validateCheckout should block checkout when no ports available
     * 
     * @test
     */
    public function validateCheckoutBlocksWhenNoPortsAvailable(): void
    {
        // Arrange: Create a plan with no ports
        $planData = [
            'name' => 'Test Plan',
            'slug' => 'test-plan-' . bin2hex(random_bytes(8)),
            'price' => 99.99,
            'currency' => 'USD',
            'billing_period_months' => 1,
            'status' => 'ACTIVE'
        ];
        
        $plan = $this->planModel->create($planData);
        $this->assertNotFalse($plan);
        
        // Act: Validate checkout
        $validation = $this->portAvailabilityService->validateCheckout($plan['id']);
        
        // Assert: Checkout should not be allowed
        $this->assertFalse(
            $validation['can_proceed'],
            'Checkout should not be allowed when no ports are available'
        );
        $this->assertArrayHasKey('message', $validation);
        $this->assertNotEmpty($validation['message']);
        
        // Cleanup
        $this->planModel->delete($plan['id']);
    }

    /**
     * Property: validateCheckout should allow checkout when ports are available
     * 
     * @test
     */
    public function validateCheckoutAllowsWhenPortsAvailable(): void
    {
        // Arrange: Create a plan with available ports
        $planData = [
            'name' => 'Test Plan',
            'slug' => 'test-plan-' . bin2hex(random_bytes(8)),
            'price' => 99.99,
            'currency' => 'USD',
            'billing_period_months' => 1,
            'status' => 'ACTIVE'
        ];
        
        $plan = $this->planModel->create($planData);
        $this->assertNotFalse($plan);
        
        // Create an available port
        $port = $this->portModel->create([
            'instance_url' => 'https://instance-' . bin2hex(random_bytes(8)) . '.karyalay.com',
            'port_number' => 8000,
            'plan_id' => $plan['id'],
            'status' => 'AVAILABLE'
        ]);
        $this->assertNotFalse($port);
        
        // Act: Validate checkout
        $validation = $this->portAvailabilityService->validateCheckout($plan['id']);
        
        // Assert: Checkout should be allowed
        $this->assertTrue(
            $validation['can_proceed'],
            'Checkout should be allowed when ports are available'
        );
        $this->assertArrayHasKey('available_ports', $validation);
        $this->assertGreaterThan(0, $validation['available_ports']);
        
        // Cleanup
        $this->portModel->delete($port['id']);
        $this->planModel->delete($plan['id']);
    }
}
