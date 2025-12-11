<?php

/**
 * Property Test: Plan Display Completeness
 * Feature: karyalay-portal-system, Property 3: Plan Display Completeness
 * Validates: Requirements 1.5
 * 
 * For any set of active plans in the database, when the pricing page is accessed,
 * all active plans should be displayed with name, price, billing period, and key inclusions.
 */

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Models\Plan;

class PlanDisplayCompletenessPropertyTest extends TestCase
{
    use TestTrait;

    private Plan $planModel;
    private array $createdPlanIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->planModel = new Plan();
    }

    protected function tearDown(): void
    {
        // Clean up created plans
        foreach ($this->createdPlanIds as $id) {
            try {
                $this->planModel->delete($id);
            } catch (\Exception $e) {
                // Ignore errors during cleanup
            }
        }
        $this->createdPlanIds = [];
        parent::tearDown();
    }

    /**
     * Property: For any set of active plans, all should be retrieved with complete data
     * 
     * @test
     */
    public function allActivePlansAreRetrievedWithCompleteData(): void
    {
        $this->forAll(
            Generator\choose(1, 5), // Number of active plans
            Generator\choose(0, 2)  // Number of inactive plans
        )
        ->then(function ($activeCount, $inactiveCount) {
            $createdActivePlans = [];
            
            // Create random plans with ACTIVE status
            for ($i = 0; $i < $activeCount; $i++) {
                $planData = $this->generateRandomPlan('ACTIVE');
                $created = $this->planModel->create($planData);
                $this->assertNotFalse($created, 'Plan creation should succeed');
                $this->createdPlanIds[] = $created['id'];
                $createdActivePlans[] = $created;
            }
            
            // Also create some INACTIVE plans that should NOT be retrieved
            for ($i = 0; $i < $inactiveCount; $i++) {
                $inactiveData = $this->generateRandomPlan('INACTIVE');
                $created = $this->planModel->create($inactiveData);
                $this->assertNotFalse($created, 'Inactive plan creation should succeed');
                $this->createdPlanIds[] = $created['id'];
            }
            
            // Act: Retrieve all active plans
            $retrievedPlans = $this->planModel->findAll(['status' => 'ACTIVE']);
            
            // Assert: All created ACTIVE plans should be in the retrieved set
            foreach ($createdActivePlans as $createdPlan) {
                $found = false;
                foreach ($retrievedPlans as $retrievedPlan) {
                    if ($retrievedPlan['id'] === $createdPlan['id']) {
                        $found = true;
                        
                        // Verify all required fields are present
                        $this->assertArrayHasKey('name', $retrievedPlan, 
                            'Plan must have name field');
                        $this->assertArrayHasKey('price', $retrievedPlan, 
                            'Plan must have price field');
                        $this->assertArrayHasKey('billing_period_months', $retrievedPlan, 
                            'Plan must have billing_period_months field');
                        $this->assertArrayHasKey('currency', $retrievedPlan, 
                            'Plan must have currency field');
                        $this->assertArrayHasKey('features', $retrievedPlan, 
                            'Plan must have features field');
                        
                        // Verify field values match
                        $this->assertEquals($createdPlan['name'], $retrievedPlan['name'], 
                            'Plan name must match');
                        $this->assertEquals($createdPlan['mrp'], $retrievedPlan['mrp'], 
                            'Plan MRP must match');
                        $this->assertEquals($createdPlan['billing_period_months'], $retrievedPlan['billing_period_months'], 
                            'Plan billing period must match');
                        $this->assertEquals($createdPlan['currency'], $retrievedPlan['currency'], 
                            'Plan currency must match');
                        
                        // Verify features is an array
                        $this->assertIsArray($retrievedPlan['features'], 
                            'Plan features must be an array');
                        
                        break;
                    }
                }
                
                $this->assertTrue($found, 
                    "Active plan '{$createdPlan['name']}' should be retrieved");
            }
            
            // Clean up for next iteration
            foreach ($this->createdPlanIds as $id) {
                try {
                    $this->planModel->delete($id);
                } catch (\Exception $e) {
                    // Ignore errors during cleanup
                }
            }
            $this->createdPlanIds = [];
        });
    }

    /**
     * Property: Plan display includes all required pricing information
     * 
     * @test
     */
    public function planDisplayIncludesAllRequiredPricingInformation(): void
    {
        $this->forAll(
            Generator\string(),
            Generator\choose(1, 10000), // Price in cents
            Generator\choose(1, 12),    // Billing period in months
            Generator\elements(['USD', 'EUR', 'GBP', 'INR'])
        )
        ->when(function ($name, $price, $billingPeriod, $currency) {
            return strlen($name) >= 1 && strlen($name) <= 255;
        })
        ->then(function ($name, $price, $billingPeriod, $currency) {
            // Create a plan with specific pricing information
            $planData = [
                'name' => $name,
                'slug' => 'test-plan-' . bin2hex(random_bytes(8)),
                'description' => 'Test plan description',
                'price' => $price / 100, // Convert cents to dollars
                'currency' => $currency,
                'billing_period_months' => $billingPeriod,
                'features' => ['Feature 1', 'Feature 2', 'Feature 3'],
                'status' => 'ACTIVE'
            ];
            
            $created = $this->planModel->create($planData);
            $this->assertNotFalse($created, 'Plan creation should succeed');
            $this->createdPlanIds[] = $created['id'];
            
            // Retrieve the plan
            $retrieved = $this->planModel->findById($created['id']);
            $this->assertNotFalse($retrieved, 'Plan should be retrievable');
            
            // Assert: All pricing information is present and correct
            $this->assertEquals($name, $retrieved['name']);
            $this->assertEquals($planData['mrp'], $retrieved['mrp']);
            $this->assertEquals($currency, $retrieved['currency']);
            $this->assertEquals($billingPeriod, $retrieved['billing_period_months']);
            $this->assertIsArray($retrieved['features']);
            $this->assertNotEmpty($retrieved['features']);
            
            // Clean up
            $this->planModel->delete($created['id']);
            $this->createdPlanIds = array_diff($this->createdPlanIds, [$created['id']]);
        });
    }

    /**
     * Property: Plans with empty features array are still valid
     * 
     * @test
     */
    public function plansWithEmptyFeaturesArrayAreStillValid(): void
    {
        // Create a plan with empty features
        $planData = [
            'name' => 'Test Plan ' . bin2hex(random_bytes(4)),
            'slug' => 'test-plan-' . bin2hex(random_bytes(8)),
            'description' => 'Test plan',
            'price' => 99.99,
            'currency' => 'USD',
            'billing_period_months' => 1,
            'features' => [], // Empty features array
            'status' => 'ACTIVE'
        ];
        
        $created = $this->planModel->create($planData);
        $this->assertNotFalse($created, 'Plan with empty features should be created');
        $this->createdPlanIds[] = $created['id'];
        
        // Retrieve and verify
        $retrieved = $this->planModel->findById($created['id']);
        $this->assertNotFalse($retrieved);
        $this->assertIsArray($retrieved['features']);
        $this->assertEmpty($retrieved['features']);
        
        // Clean up
        $this->planModel->delete($created['id']);
        $this->createdPlanIds = array_diff($this->createdPlanIds, [$created['id']]);
    }

    /**
     * Generate random plan data
     */
    private function generateRandomPlan(string $status = 'ACTIVE'): array
    {
        $randomId = bin2hex(random_bytes(8));
        $name = "Test Plan " . $randomId;
        $slug = "test-plan-" . $randomId;
        
        return [
            'name' => $name,
            'slug' => $slug,
            'description' => "Description for " . $name,
            'price' => rand(10, 1000) / 10, // Random price between 1.0 and 100.0
            'currency' => ['USD', 'EUR', 'GBP'][rand(0, 2)],
            'billing_period_months' => [1, 3, 6, 12][rand(0, 3)],
            'features' => [
                'Feature ' . rand(1, 100),
                'Feature ' . rand(1, 100),
                'Feature ' . rand(1, 100)
            ],
            'status' => $status
        ];
    }
}
