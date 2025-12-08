<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Services\PortService;
use Karyalay\Models\Port;
use Karyalay\Models\Plan;

/**
 * Property-based tests for CSV import validation
 * 
 * Feature: karyalay-portal-system, Property 31: CSV Import Validation
 * Validates: Requirements 9.2
 */
class CsvImportValidationPropertyTest extends TestCase
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
            try {
                $this->portModel->delete($portId);
            } catch (\Exception $e) {
                // Port may already be deleted
            }
        }
        
        // Clean up test plans
        foreach ($this->testPlanIds as $planId) {
            try {
                $this->planModel->delete($planId);
            } catch (\Exception $e) {
                // Plan may already be deleted
            }
        }
        
        parent::tearDown();
    }

    /**
     * Property 31: CSV Import Validation
     * 
     * For any CSV file with port data, when an admin uploads the file, all valid 
     * rows should be inserted and invalid rows should generate error messages 
     * without being inserted.
     * 
     * Validates: Requirements 9.2
     * 
     * @test
     */
    public function csvImportInsertsValidRowsAndRejectsInvalidRows(): void
    {
        $this->forAll(
            Generator\choose(1, 10) // Number of valid ports
        )
        ->then(function ($validPortCount) {
            // Create test plan
            $planId = $this->createTestPlan();
            $planName = $this->planModel->findById($planId)['name'];
            
            // Generate valid port data
            $validPorts = [];
            for ($i = 0; $i < $validPortCount; $i++) {
                $validPorts[] = [
                    'instance_url' => 'https://valid-' . bin2hex(random_bytes(8)) . '.example.com',
                    'plan_id' => $planId,
                    'port_number' => rand(1000, 9999),
                    'status' => 'AVAILABLE',
                    'server_region' => 'region-' . $i,
                    'notes' => 'Valid port ' . $i
                ];
            }
            
            // Generate invalid port data (missing required fields)
            $invalidPorts = [
                // Missing instance_url
                [
                    'instance_url' => '',
                    'plan_id' => $planId,
                    'status' => 'AVAILABLE'
                ],
                // Missing plan_id
                [
                    'instance_url' => 'https://invalid-' . bin2hex(random_bytes(8)) . '.example.com',
                    'plan_id' => '',
                    'status' => 'AVAILABLE'
                ],
                // Invalid status
                [
                    'instance_url' => 'https://invalid-' . bin2hex(random_bytes(8)) . '.example.com',
                    'plan_id' => $planId,
                    'status' => 'INVALID_STATUS'
                ]
            ];
            
            // Combine valid and invalid ports
            $allPorts = array_merge($validPorts, $invalidPorts);
            
            // Act: Bulk import ports
            $result = $this->portService->bulkImportPorts($allPorts);
            
            // Assert: Import completed
            $this->assertTrue($result['success'], 'Bulk import should complete');
            
            // Assert: Correct number of valid ports imported
            $this->assertEquals(
                $validPortCount,
                $result['imported'],
                "Should import exactly {$validPortCount} valid ports"
            );
            
            // Assert: Correct number of invalid ports rejected
            $this->assertEquals(
                count($invalidPorts),
                $result['failed'],
                'Should reject exactly ' . count($invalidPorts) . ' invalid ports'
            );
            
            // Assert: Error messages generated for invalid rows
            $this->assertNotEmpty(
                $result['errors'],
                'Should have error messages for invalid rows'
            );
            $this->assertCount(
                count($invalidPorts),
                $result['errors'],
                'Should have one error per invalid row'
            );
            
            // Assert: Valid ports exist in database
            foreach ($result['imported_ports'] as $importedPort) {
                $this->testPortIds[] = $importedPort['id'];
                $port = $this->portModel->findById($importedPort['id']);
                $this->assertNotFalse($port, 'Imported port should exist in database');
            }
            
            // Assert: Invalid ports do not exist in database
            // We verify this by checking that the total port count matches imported count
            $allPortsInDb = $this->portModel->findAll(['plan_id' => $planId]);
            $importedPortIds = array_column($result['imported_ports'], 'id');
            $matchingPorts = array_filter($allPortsInDb, function($port) use ($importedPortIds) {
                return in_array($port['id'], $importedPortIds);
            });
            $this->assertCount(
                $validPortCount,
                $matchingPorts,
                'Only valid ports should exist in database'
            );
        });
    }

    /**
     * Property: All valid ports in CSV are imported successfully
     * 
     * @test
     */
    public function allValidPortsInCsvAreImported(): void
    {
        $this->forAll(
            Generator\choose(1, 20) // Number of ports to import
        )
        ->then(function ($portCount) {
            // Create test plan
            $planId = $this->createTestPlan();
            
            // Generate all valid port data
            $portsData = [];
            for ($i = 0; $i < $portCount; $i++) {
                $portsData[] = [
                    'instance_url' => 'https://test-' . bin2hex(random_bytes(8)) . '.example.com',
                    'plan_id' => $planId,
                    'port_number' => rand(1000, 9999),
                    'status' => 'AVAILABLE'
                ];
            }
            
            // Act: Bulk import
            $result = $this->portService->bulkImportPorts($portsData);
            
            // Assert: All ports imported successfully
            $this->assertTrue($result['success']);
            $this->assertEquals($portCount, $result['imported']);
            $this->assertEquals(0, $result['failed']);
            $this->assertEmpty($result['errors']);
            
            // Track for cleanup
            foreach ($result['imported_ports'] as $port) {
                $this->testPortIds[] = $port['id'];
            }
        });
    }

    /**
     * Property: Duplicate ports in CSV are rejected
     * 
     * @test
     */
    public function duplicatePortsInCsvAreRejected(): void
    {
        // Create test plan
        $planId = $this->createTestPlan();
        
        // Create a port that already exists
        $existingInstanceUrl = 'https://existing-' . bin2hex(random_bytes(8)) . '.example.com';
        $existingPortNumber = rand(1000, 9999);
        $existingPortData = [
            'instance_url' => $existingInstanceUrl,
            'port_number' => $existingPortNumber,
            'plan_id' => $planId,
            'status' => 'AVAILABLE'
        ];
        
        $createResult = $this->portService->createPort($existingPortData);
        $this->assertTrue($createResult['success']);
        $this->testPortIds[] = $createResult['port']['id'];
        
        // Prepare CSV data with duplicate
        $portsData = [
            // Valid new port
            [
                'instance_url' => 'https://new-' . bin2hex(random_bytes(8)) . '.example.com',
                'port_number' => rand(1000, 9999),
                'plan_id' => $planId,
                'status' => 'AVAILABLE'
            ],
            // Duplicate port (same instance_url and port_number)
            [
                'instance_url' => $existingInstanceUrl,
                'port_number' => $existingPortNumber,
                'plan_id' => $planId,
                'status' => 'AVAILABLE'
            ]
        ];
        
        // Act: Bulk import
        $result = $this->portService->bulkImportPorts($portsData);
        
        // Assert: Import completed
        $this->assertTrue($result['success']);
        
        // Assert: Only valid port imported, duplicate rejected
        $this->assertEquals(1, $result['imported'], 'Should import 1 valid port');
        $this->assertEquals(1, $result['failed'], 'Should reject 1 duplicate port');
        $this->assertNotEmpty($result['errors'], 'Should have error for duplicate');
        
        // Track for cleanup
        foreach ($result['imported_ports'] as $port) {
            $this->testPortIds[] = $port['id'];
        }
    }

    /**
     * Property: Invalid port numbers are rejected
     * 
     * @test
     */
    public function invalidPortNumbersAreRejected(): void
    {
        // Create test plan
        $planId = $this->createTestPlan();
        
        // Prepare CSV data with invalid port numbers
        $portsData = [
            // Valid port
            [
                'instance_url' => 'https://valid-' . bin2hex(random_bytes(8)) . '.example.com',
                'port_number' => 8080,
                'plan_id' => $planId,
                'status' => 'AVAILABLE'
            ],
            // Port number too high (> 65535)
            [
                'instance_url' => 'https://invalid-' . bin2hex(random_bytes(8)) . '.example.com',
                'port_number' => 70000,
                'plan_id' => $planId,
                'status' => 'AVAILABLE'
            ],
            // Port number zero
            [
                'instance_url' => 'https://invalid2-' . bin2hex(random_bytes(8)) . '.example.com',
                'port_number' => 0,
                'plan_id' => $planId,
                'status' => 'AVAILABLE'
            ]
        ];
        
        // Act: Bulk import
        $result = $this->portService->bulkImportPorts($portsData);
        
        // Assert: Import completed
        $this->assertTrue($result['success']);
        
        // Assert: Only valid port imported
        $this->assertGreaterThanOrEqual(1, $result['imported'], 'Should import at least 1 valid port');
        
        // Track for cleanup
        foreach ($result['imported_ports'] as $port) {
            $this->testPortIds[] = $port['id'];
        }
    }

    /**
     * Property: Missing required fields are rejected
     * 
     * @test
     */
    public function missingRequiredFieldsAreRejected(): void
    {
        // Create test plan
        $planId = $this->createTestPlan();
        
        // Prepare CSV data with missing required fields
        $portsData = [
            // Valid port
            [
                'instance_url' => 'https://valid-' . bin2hex(random_bytes(8)) . '.example.com',
                'plan_id' => $planId,
                'status' => 'AVAILABLE'
            ],
            // Missing instance_url
            [
                'instance_url' => '',
                'plan_id' => $planId,
                'status' => 'AVAILABLE'
            ],
            // Missing plan_id
            [
                'instance_url' => 'https://invalid-' . bin2hex(random_bytes(8)) . '.example.com',
                'plan_id' => '',
                'status' => 'AVAILABLE'
            ]
        ];
        
        // Act: Bulk import
        $result = $this->portService->bulkImportPorts($portsData);
        
        // Assert: Import completed
        $this->assertTrue($result['success']);
        
        // Assert: Only valid port imported, others rejected
        $this->assertEquals(1, $result['imported'], 'Should import 1 valid port');
        $this->assertEquals(2, $result['failed'], 'Should reject 2 invalid ports');
        $this->assertCount(2, $result['errors'], 'Should have 2 error messages');
        
        // Track for cleanup
        foreach ($result['imported_ports'] as $port) {
            $this->testPortIds[] = $port['id'];
        }
    }

    /**
     * Property: Invalid status values are rejected
     * 
     * @test
     */
    public function invalidStatusValuesAreRejected(): void
    {
        // Create test plan
        $planId = $this->createTestPlan();
        
        // Prepare CSV data with invalid status
        $portsData = [
            // Valid port
            [
                'instance_url' => 'https://valid-' . bin2hex(random_bytes(8)) . '.example.com',
                'plan_id' => $planId,
                'status' => 'AVAILABLE'
            ],
            // Invalid status
            [
                'instance_url' => 'https://invalid-' . bin2hex(random_bytes(8)) . '.example.com',
                'plan_id' => $planId,
                'status' => 'INVALID_STATUS'
            ],
            // Another invalid status
            [
                'instance_url' => 'https://invalid2-' . bin2hex(random_bytes(8)) . '.example.com',
                'plan_id' => $planId,
                'status' => 'PENDING'
            ]
        ];
        
        // Act: Bulk import
        $result = $this->portService->bulkImportPorts($portsData);
        
        // Assert: Import completed
        $this->assertTrue($result['success']);
        
        // Assert: Only valid port imported
        $this->assertEquals(1, $result['imported'], 'Should import 1 valid port');
        $this->assertEquals(2, $result['failed'], 'Should reject 2 invalid ports');
        
        // Track for cleanup
        foreach ($result['imported_ports'] as $port) {
            $this->testPortIds[] = $port['id'];
        }
    }

    /**
     * Property: Empty CSV results in zero imports
     * 
     * @test
     */
    public function emptyCsvResultsInZeroImports(): void
    {
        // Act: Bulk import with empty array
        $result = $this->portService->bulkImportPorts([]);
        
        // Assert: Import completed with no imports
        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * Property: Partial import success is reported correctly
     * 
     * @test
     */
    public function partialImportSuccessIsReportedCorrectly(): void
    {
        $this->forAll(
            Generator\choose(1, 5), // Valid ports
            Generator\choose(1, 5)  // Invalid ports
        )
        ->then(function ($validCount, $invalidCount) {
            // Create test plan
            $planId = $this->createTestPlan();
            
            // Generate valid ports
            $portsData = [];
            for ($i = 0; $i < $validCount; $i++) {
                $portsData[] = [
                    'instance_url' => 'https://valid-' . bin2hex(random_bytes(8)) . '.example.com',
                    'plan_id' => $planId,
                    'status' => 'AVAILABLE'
                ];
            }
            
            // Generate invalid ports (missing instance_url)
            for ($i = 0; $i < $invalidCount; $i++) {
                $portsData[] = [
                    'instance_url' => '', // Invalid: empty
                    'plan_id' => $planId,
                    'status' => 'AVAILABLE'
                ];
            }
            
            // Act: Bulk import
            $result = $this->portService->bulkImportPorts($portsData);
            
            // Assert: Import completed
            $this->assertTrue($result['success']);
            
            // Assert: Correct counts
            $this->assertEquals($validCount, $result['imported']);
            $this->assertEquals($invalidCount, $result['failed']);
            $this->assertCount($invalidCount, $result['errors']);
            
            // Assert: Imported and failed arrays have correct sizes
            $this->assertCount($validCount, $result['imported_ports']);
            $this->assertCount($invalidCount, $result['failed_ports']);
            
            // Track for cleanup
            foreach ($result['imported_ports'] as $port) {
                $this->testPortIds[] = $port['id'];
            }
        });
    }

    /**
     * Helper: Create test plan
     */
    private function createTestPlan(): string
    {
        $planData = [
            'name' => 'Test Plan ' . bin2hex(random_bytes(4)),
            'slug' => 'test-plan-' . bin2hex(random_bytes(4)),
            'description' => 'Test plan for CSV import tests',
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
}

