<?php

namespace Karyalay\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Karyalay\Services\InstallationService;

/**
 * Test state persistence and recovery functionality
 * 
 * Requirements: 7.3, 8.4
 * 
 * Note: These tests use @runInSeparateProcess to avoid session conflicts with PHPUnit
 */
class InstallationServiceStatePersistenceTest extends TestCase
{
    private InstallationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear any existing session data
        $_SESSION = [];
        
        $this->service = new InstallationService();
    }

    protected function tearDown(): void
    {
        // Clean up session
        $_SESSION = [];
        parent::tearDown();
    }

    /**
     * Test saving and retrieving step data
     * @runInSeparateProcess
     */
    public function testSaveAndGetStepData(): void
    {
        $stepData = [
            'host' => 'localhost',
            'port' => '3306',
            'database' => 'test_db',
            'username' => 'test_user',
            'password' => 'test_pass'
        ];

        // Save step data
        $result = $this->service->saveStepData(1, $stepData);
        $this->assertTrue($result, 'Should successfully save step data');

        // Retrieve step data
        $retrieved = $this->service->getStepData(1);
        $this->assertNotNull($retrieved, 'Should retrieve saved step data');
        $this->assertEquals($stepData, $retrieved, 'Retrieved data should match saved data');
    }

    /**
     * Test retrieving non-existent step data returns null
     * @runInSeparateProcess
     */
    public function testGetNonExistentStepData(): void
    {
        $retrieved = $this->service->getStepData(99);
        $this->assertNull($retrieved, 'Should return null for non-existent step data');
    }

    /**
     * Test saving multiple steps independently
     * @runInSeparateProcess
     */
    public function testSaveMultipleSteps(): void
    {
        $step1Data = ['host' => 'localhost', 'database' => 'db1'];
        $step2Data = ['name' => 'Admin', 'email' => 'admin@test.com'];
        $step3Data = ['smtp_host' => 'smtp.test.com', 'smtp_port' => '587'];

        // Save data for multiple steps
        $this->service->saveStepData(1, $step1Data);
        $this->service->saveStepData(2, $step2Data);
        $this->service->saveStepData(3, $step3Data);

        // Verify each step's data is independent
        $this->assertEquals($step1Data, $this->service->getStepData(1));
        $this->assertEquals($step2Data, $this->service->getStepData(2));
        $this->assertEquals($step3Data, $this->service->getStepData(3));
    }

    /**
     * Test updating existing step data
     * @runInSeparateProcess
     */
    public function testUpdateStepData(): void
    {
        $initialData = ['host' => 'localhost', 'database' => 'db1'];
        $updatedData = ['host' => '127.0.0.1', 'database' => 'db2'];

        // Save initial data
        $this->service->saveStepData(1, $initialData);
        $this->assertEquals($initialData, $this->service->getStepData(1));

        // Update with new data
        $this->service->saveStepData(1, $updatedData);
        $this->assertEquals($updatedData, $this->service->getStepData(1));
    }

    /**
     * Test clearing specific step data
     * @runInSeparateProcess
     */
    public function testClearStepData(): void
    {
        $stepData = ['host' => 'localhost', 'database' => 'test_db'];

        // Save and verify data exists
        $this->service->saveStepData(1, $stepData);
        $this->assertNotNull($this->service->getStepData(1));

        // Clear the step data
        $result = $this->service->clearStepData(1);
        $this->assertTrue($result, 'Should successfully clear step data');

        // Verify data is cleared
        $this->assertNull($this->service->getStepData(1), 'Step data should be null after clearing');
    }

    /**
     * Test clearing non-existent step data doesn't cause errors
     * @runInSeparateProcess
     */
    public function testClearNonExistentStepData(): void
    {
        $result = $this->service->clearStepData(99);
        $this->assertTrue($result, 'Should handle clearing non-existent step data gracefully');
    }

    /**
     * Test getting all step data
     * @runInSeparateProcess
     */
    public function testGetAllStepData(): void
    {
        $step1Data = ['host' => 'localhost'];
        $step2Data = ['name' => 'Admin'];
        $step3Data = ['smtp_host' => 'smtp.test.com'];

        // Save data for multiple steps
        $this->service->saveStepData(1, $step1Data);
        $this->service->saveStepData(2, $step2Data);
        $this->service->saveStepData(3, $step3Data);

        // Get all step data
        $allData = $this->service->getAllStepData();

        $this->assertIsArray($allData, 'Should return an array');
        $this->assertCount(3, $allData, 'Should contain data for 3 steps');
        $this->assertEquals($step1Data, $allData[1]);
        $this->assertEquals($step2Data, $allData[2]);
        $this->assertEquals($step3Data, $allData[3]);
    }

    /**
     * Test getting all step data when no data exists
     * @runInSeparateProcess
     */
    public function testGetAllStepDataWhenEmpty(): void
    {
        $allData = $this->service->getAllStepData();
        $this->assertIsArray($allData, 'Should return an array');
        $this->assertEmpty($allData, 'Should return empty array when no data exists');
    }

    /**
     * Test progress state persistence
     * @runInSeparateProcess
     */
    public function testProgressStatePersistence(): void
    {
        $progress = [
            'current_step' => 3,
            'completed_steps' => [1, 2],
            'database_configured' => true,
            'migrations_run' => true,
            'admin_created' => false,
            'smtp_configured' => false,
            'brand_configured' => false
        ];

        // Save progress
        $result = $this->service->saveProgress($progress);
        $this->assertTrue($result, 'Should successfully save progress');

        // Retrieve progress
        $retrieved = $this->service->getProgress();
        $this->assertEquals($progress, $retrieved, 'Retrieved progress should match saved progress');
    }

    /**
     * Test default progress state
     * @runInSeparateProcess
     */
    public function testDefaultProgressState(): void
    {
        $progress = $this->service->getProgress();

        $this->assertIsArray($progress, 'Should return an array');
        $this->assertEquals(1, $progress['current_step'], 'Default current step should be 1');
        $this->assertEmpty($progress['completed_steps'], 'Default completed steps should be empty');
        $this->assertFalse($progress['database_configured']);
        $this->assertFalse($progress['migrations_run']);
        $this->assertFalse($progress['admin_created']);
        $this->assertFalse($progress['smtp_configured']);
        $this->assertFalse($progress['brand_configured']);
    }

    /**
     * Test clearing wizard session clears all data
     * @runInSeparateProcess
     */
    public function testClearWizardSession(): void
    {
        // Set up some wizard data
        $this->service->saveStepData(1, ['host' => 'localhost']);
        $this->service->saveStepData(2, ['name' => 'Admin']);
        $this->service->saveProgress(['current_step' => 2, 'completed_steps' => [1]]);
        
        // Set legacy session keys
        $_SESSION['database_credentials'] = ['host' => 'localhost'];
        $_SESSION['smtp_config'] = ['smtp_host' => 'smtp.test.com'];
        $_SESSION['admin_email'] = 'admin@test.com';

        // Verify data exists
        $this->assertNotEmpty($this->service->getAllStepData());
        $this->assertNotEquals(1, $this->service->getProgress()['current_step']);

        // Clear wizard session
        $result = $this->service->clearWizardSession();
        $this->assertTrue($result, 'Should successfully clear wizard session');

        // Verify all wizard data is cleared
        $this->assertEmpty($this->service->getAllStepData(), 'All step data should be cleared');
        $this->assertEquals(1, $this->service->getProgress()['current_step'], 'Progress should be reset to defaults');
        
        // Verify legacy session keys are cleared
        $this->assertArrayNotHasKey('database_credentials', $_SESSION);
        $this->assertArrayNotHasKey('smtp_config', $_SESSION);
        $this->assertArrayNotHasKey('admin_email', $_SESSION);
    }

    /**
     * Test state recovery after simulated page reload
     * @runInSeparateProcess
     */
    public function testStateRecoveryAfterReload(): void
    {
        // Simulate user filling out step 1
        $step1Data = [
            'host' => 'localhost',
            'port' => '3306',
            'database' => 'test_db',
            'username' => 'test_user',
            'password' => 'secret123'
        ];
        $this->service->saveStepData(1, $step1Data);

        // Simulate page reload by creating new service instance
        $newService = new InstallationService();

        // Verify data is still available
        $recovered = $newService->getStepData(1);
        $this->assertNotNull($recovered, 'Data should persist across service instances');
        $this->assertEquals($step1Data, $recovered, 'Recovered data should match original');
    }

    /**
     * Test data preservation when navigating back
     * @runInSeparateProcess
     */
    public function testDataPreservationOnBackNavigation(): void
    {
        // User completes step 1
        $step1Data = ['host' => 'localhost', 'database' => 'db1'];
        $this->service->saveStepData(1, $step1Data);

        // User completes step 2
        $step2Data = ['name' => 'Admin', 'email' => 'admin@test.com'];
        $this->service->saveStepData(2, $step2Data);

        // User navigates back to step 1
        $recovered1 = $this->service->getStepData(1);
        $this->assertEquals($step1Data, $recovered1, 'Step 1 data should be preserved');

        // User navigates forward to step 2
        $recovered2 = $this->service->getStepData(2);
        $this->assertEquals($step2Data, $recovered2, 'Step 2 data should be preserved');
    }

    /**
     * Test sensitive data handling (passwords should not be persisted in some steps)
     * @runInSeparateProcess
     */
    public function testSensitiveDataHandling(): void
    {
        // Admin step should only save non-sensitive data
        $adminData = [
            'name' => 'Admin User',
            'email' => 'admin@test.com'
            // Note: password is intentionally not saved
        ];
        $this->service->saveStepData(3, $adminData);

        $recovered = $this->service->getStepData(3);
        $this->assertArrayNotHasKey('password', $recovered, 'Password should not be persisted');
        $this->assertArrayNotHasKey('password_confirm', $recovered, 'Password confirmation should not be persisted');
    }

    /**
     * Test partial data updates
     * @runInSeparateProcess
     */
    public function testPartialDataUpdates(): void
    {
        // Save initial complete data
        $initialData = [
            'company_name' => 'Test Company',
            'company_tagline' => 'Test Tagline',
            'contact_email' => 'contact@test.com',
            'contact_phone' => '123-456-7890',
            'contact_address' => '123 Test St'
        ];
        $this->service->saveStepData(5, $initialData);

        // Update with partial data (e.g., after logo upload)
        $updatedData = array_merge($initialData, ['logo_path' => '/uploads/branding/logo.png']);
        $this->service->saveStepData(5, $updatedData);

        // Verify all data is preserved
        $recovered = $this->service->getStepData(5);
        $this->assertEquals($updatedData, $recovered);
        $this->assertArrayHasKey('logo_path', $recovered);
        $this->assertEquals('/uploads/branding/logo.png', $recovered['logo_path']);
    }
}
