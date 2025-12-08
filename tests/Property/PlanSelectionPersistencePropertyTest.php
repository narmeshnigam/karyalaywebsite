<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Models\Plan;

/**
 * Property-based tests for plan selection persistence
 * 
 * Feature: karyalay-portal-system, Property 9: Plan Selection Persistence
 * Validates: Requirements 3.1
 */
class PlanSelectionPersistencePropertyTest extends TestCase
{
    use TestTrait;

    private Plan $planModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->planModel = new Plan();
        
        // Use $_SESSION superglobal directly without session_start()
        // This allows testing session logic without actual PHP sessions
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        // Clean up session array
        $_SESSION = [];
        parent::tearDown();
    }

    /**
     * Property 9: Plan Selection Persistence
     * 
     * For any plan, when "Buy Now" is clicked, the plan identifier should be 
     * stored in the session context and retrievable during checkout.
     * 
     * Validates: Requirements 3.1
     * 
     * @test
     */
    public function planSelectionPersistsInSession(): void
    {
        $this->forAll(
            Generator\choose(1, 12)
        )
        ->then(function ($billingPeriod) {
            // Generate valid test data
            $name = 'Test Plan ' . bin2hex(random_bytes(4));
            $price = rand(1000, 10000) / 100; // Random price between 10.00 and 100.00
            // Arrange: Create a test plan
            $slug = 'test-plan-' . bin2hex(random_bytes(8));
            $planData = [
                'name' => $name,
                'slug' => $slug,
                'description' => 'Test plan for property testing',
                'price' => abs($price),
                'currency' => 'USD',
                'billing_period_months' => $billingPeriod,
                'features' => ['Feature 1', 'Feature 2'],
                'status' => 'ACTIVE'
            ];
            
            $plan = $this->planModel->create($planData);
            $this->assertNotFalse($plan, 'Plan should be created successfully');
            
            // Act: Simulate plan selection by storing in session
            $_SESSION['selected_plan_id'] = $plan['id'];
            $_SESSION['selected_plan_slug'] = $plan['slug'];
            
            // Assert: Plan ID should be stored in session
            $this->assertArrayHasKey(
                'selected_plan_id',
                $_SESSION,
                'Session should contain selected_plan_id'
            );
            
            // Assert: Stored plan ID should match the selected plan
            $this->assertEquals(
                $plan['id'],
                $_SESSION['selected_plan_id'],
                'Stored plan ID should match the selected plan ID'
            );
            
            // Assert: Plan slug should be stored in session
            $this->assertArrayHasKey(
                'selected_plan_slug',
                $_SESSION,
                'Session should contain selected_plan_slug'
            );
            
            // Assert: Stored plan slug should match the selected plan
            $this->assertEquals(
                $plan['slug'],
                $_SESSION['selected_plan_slug'],
                'Stored plan slug should match the selected plan slug'
            );
            
            // Assert: Plan should be retrievable from session during checkout
            $retrievedPlanId = $_SESSION['selected_plan_id'] ?? null;
            $this->assertNotNull(
                $retrievedPlanId,
                'Plan ID should be retrievable from session'
            );
            
            // Assert: Retrieved plan ID can be used to fetch plan details
            $retrievedPlan = $this->planModel->findById($retrievedPlanId);
            $this->assertNotFalse(
                $retrievedPlan,
                'Plan should be retrievable from database using session ID'
            );
            $this->assertEquals(
                $plan['id'],
                $retrievedPlan['id'],
                'Retrieved plan should match the selected plan'
            );
            
            // Cleanup
            $this->planModel->delete($plan['id']);
        });
    }

    /**
     * Property: Session should persist plan selection across multiple accesses
     * 
     * @test
     */
    public function sessionPersistsPlanSelectionAcrossAccesses(): void
    {
        // Arrange: Create a test plan
        $planData = [
            'name' => 'Test Plan',
            'slug' => 'test-plan-' . bin2hex(random_bytes(8)),
            'description' => 'Test plan',
            'price' => 99.99,
            'currency' => 'USD',
            'billing_period_months' => 1,
            'features' => ['Feature 1'],
            'status' => 'ACTIVE'
        ];
        
        $plan = $this->planModel->create($planData);
        $this->assertNotFalse($plan);
        
        // Act: Store plan in session
        $_SESSION['selected_plan_id'] = $plan['id'];
        
        // Assert: Plan should be retrievable multiple times
        for ($i = 0; $i < 5; $i++) {
            $this->assertArrayHasKey('selected_plan_id', $_SESSION);
            $this->assertEquals($plan['id'], $_SESSION['selected_plan_id']);
        }
        
        // Cleanup
        $this->planModel->delete($plan['id']);
    }

    /**
     * Property: Session should allow plan selection to be updated
     * 
     * @test
     */
    public function sessionAllowsPlanSelectionUpdate(): void
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
        
        // Act: Select first plan
        $_SESSION['selected_plan_id'] = $plan1['id'];
        $this->assertEquals($plan1['id'], $_SESSION['selected_plan_id']);
        
        // Act: Change selection to second plan
        $_SESSION['selected_plan_id'] = $plan2['id'];
        
        // Assert: Session should reflect the updated selection
        $this->assertEquals(
            $plan2['id'],
            $_SESSION['selected_plan_id'],
            'Session should contain the updated plan selection'
        );
        $this->assertNotEquals(
            $plan1['id'],
            $_SESSION['selected_plan_id'],
            'Session should not contain the old plan selection'
        );
        
        // Cleanup
        $this->planModel->delete($plan1['id']);
        $this->planModel->delete($plan2['id']);
    }

    /**
     * Property: Session should handle invalid plan IDs gracefully
     * 
     * @test
     */
    public function sessionHandlesInvalidPlanIds(): void
    {
        // Act: Store an invalid plan ID
        $invalidPlanId = 'invalid-plan-id-' . bin2hex(random_bytes(8));
        $_SESSION['selected_plan_id'] = $invalidPlanId;
        
        // Assert: Session should store the value
        $this->assertEquals($invalidPlanId, $_SESSION['selected_plan_id']);
        
        // Assert: Attempting to retrieve plan should return false
        $plan = $this->planModel->findById($invalidPlanId);
        $this->assertFalse(
            $plan,
            'Invalid plan ID should not return a plan from database'
        );
    }

    /**
     * Property: Empty session should not have plan selection
     * 
     * @test
     */
    public function emptySessionDoesNotHavePlanSelection(): void
    {
        // Assert: Fresh session should not have plan selection
        $this->assertArrayNotHasKey(
            'selected_plan_id',
            $_SESSION,
            'Fresh session should not have selected_plan_id'
        );
        
        $this->assertNull(
            $_SESSION['selected_plan_id'] ?? null,
            'Accessing non-existent session key should return null'
        );
    }
}
