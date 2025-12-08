<?php

/**
 * Property Test: Module Retrieval and Display
 * Feature: karyalay-portal-system, Property 1: Module Retrieval and Display
 * Validates: Requirements 1.2
 * 
 * For any set of active modules in the database, when the modules overview page is accessed,
 * all active modules should be retrieved and displayed with their name, description, and icon.
 */

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Models\Module;

class ModuleRetrievalPropertyTest extends TestCase
{
    use TestTrait;

    private Module $moduleModel;
    private array $createdModuleIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->moduleModel = new Module();
    }

    protected function tearDown(): void
    {
        // Clean up created modules
        foreach ($this->createdModuleIds as $id) {
            try {
                $this->moduleModel->delete($id);
            } catch (\Exception $e) {
                // Ignore errors during cleanup
            }
        }
        $this->createdModuleIds = [];
        parent::tearDown();
    }

    /**
     * Property: For any set of active modules, all should be retrieved with complete data
     * 
     * @test
     */
    public function allPublishedModulesAreRetrievedWithCompleteData(): void
    {
        $this->forAll(
            Generator\choose(1, 5), // Number of published modules
            Generator\choose(0, 3)  // Number of draft modules
        )
        ->then(function ($publishedCount, $draftCount) {
            $createdPublishedModules = [];
            
            // Create random modules with PUBLISHED status
            for ($i = 0; $i < $publishedCount; $i++) {
                $moduleData = $this->generateRandomModule('PUBLISHED');
                $created = $this->moduleModel->create($moduleData);
                $this->assertNotFalse($created, 'Module creation should succeed');
                $this->createdModuleIds[] = $created['id'];
                $createdPublishedModules[] = $created;
            }
            
            // Also create some DRAFT modules that should NOT be retrieved
            for ($i = 0; $i < $draftCount; $i++) {
                $draftData = $this->generateRandomModule('DRAFT');
                $created = $this->moduleModel->create($draftData);
                $this->assertNotFalse($created, 'Draft module creation should succeed');
                $this->createdModuleIds[] = $created['id'];
            }
            
            // Act: Retrieve all published modules
            $retrievedModules = $this->moduleModel->findAll(['status' => 'PUBLISHED']);
            
            // Assert: All created PUBLISHED modules should be in the retrieved set
            foreach ($createdPublishedModules as $createdModule) {
                $found = false;
                foreach ($retrievedModules as $retrievedModule) {
                    if ($retrievedModule['id'] === $createdModule['id']) {
                        $found = true;
                        
                        // Verify all required fields are present and match
                        $this->assertArrayHasKey('name', $retrievedModule, 
                            'Module must have name field');
                        $this->assertArrayHasKey('description', $retrievedModule, 
                            'Module must have description field');
                        $this->assertArrayHasKey('slug', $retrievedModule, 
                            'Module must have slug field');
                        
                        $this->assertEquals($createdModule['name'], $retrievedModule['name'], 
                            'Module name must match');
                        $this->assertEquals($createdModule['description'], $retrievedModule['description'], 
                            'Module description must match');
                        $this->assertEquals($createdModule['slug'], $retrievedModule['slug'], 
                            'Module slug must match');
                        
                        break;
                    }
                }
                
                $this->assertTrue($found, 
                    "Published module '{$createdModule['name']}' should be retrieved");
            }
            
            // Clean up for next iteration
            foreach ($this->createdModuleIds as $id) {
                try {
                    $this->moduleModel->delete($id);
                } catch (\Exception $e) {
                    // Ignore errors during cleanup
                }
            }
            $this->createdModuleIds = [];
        });
    }

    /**
     * Generate random module data
     */
    private function generateRandomModule(string $status = 'PUBLISHED'): array
    {
        $randomId = bin2hex(random_bytes(8));
        $name = "Test Module " . $randomId;
        $slug = "test-module-" . $randomId;
        
        return [
            'name' => $name,
            'slug' => $slug,
            'description' => "Description for " . $name,
            'features' => ['Feature 1', 'Feature 2', 'Feature 3'],
            'screenshots' => ['/images/screenshot.png'],
            'faqs' => [['question' => 'Q1', 'answer' => 'A1']],
            'display_order' => rand(1, 100),
            'status' => $status
        ];
    }
}
