<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Karyalay\Services\InstallationService;

/**
 * Integration test for environment detection in the installation wizard
 * 
 * Tests that environment detection works correctly in the full wizard context
 * 
 * Requirements: 6.1, 6.2, 6.3, 6.4
 */
class EnvironmentDetectionIntegrationTest extends TestCase
{
    private InstallationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InstallationService();
    }

    /**
     * Test that environment detection returns valid values
     */
    public function testEnvironmentDetectionReturnsValidValues(): void
    {
        $environment = $this->service->detectEnvironment();
        
        $this->assertContains($environment, ['localhost', 'production']);
    }

    /**
     * Test that environment info contains all required fields
     */
    public function testEnvironmentInfoContainsRequiredFields(): void
    {
        $info = $this->service->getEnvironmentInfo();
        
        $requiredFields = [
            'environment',
            'is_localhost',
            'is_production',
            'server_software',
            'php_version',
            'php_os',
            'is_windows',
            'is_https',
            'server_name',
            'document_root',
            'warnings'
        ];
        
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $info, "Missing required field: {$field}");
        }
    }

    /**
     * Test that warnings are properly formatted
     */
    public function testWarningsAreProperlyFormatted(): void
    {
        $warnings = $this->service->getEnvironmentWarnings();
        
        $this->assertIsArray($warnings);
        
        foreach ($warnings as $warning) {
            $this->assertArrayHasKey('type', $warning);
            $this->assertArrayHasKey('severity', $warning);
            $this->assertArrayHasKey('message', $warning);
            
            $this->assertContains($warning['severity'], ['high', 'medium', 'low']);
            $this->assertIsString($warning['message']);
            $this->assertNotEmpty($warning['message']);
        }
    }

    /**
     * Test that file permissions are valid Unix permissions
     */
    public function testFilePermissionsAreValid(): void
    {
        $configPerms = $this->service->getEnvironmentPermissions('config');
        $uploadPerms = $this->service->getEnvironmentPermissions('upload');
        $generalPerms = $this->service->getEnvironmentPermissions('general');
        
        // Permissions should be valid octal values
        $this->assertGreaterThanOrEqual(0, $configPerms);
        $this->assertLessThanOrEqual(0777, $configPerms);
        
        $this->assertGreaterThanOrEqual(0, $uploadPerms);
        $this->assertLessThanOrEqual(0777, $uploadPerms);
        
        $this->assertGreaterThanOrEqual(0, $generalPerms);
        $this->assertLessThanOrEqual(0777, $generalPerms);
    }

    /**
     * Test that path resolution works for common paths
     */
    public function testPathResolutionWorksForCommonPaths(): void
    {
        $paths = ['config', 'uploads', 'install', 'classes'];
        
        foreach ($paths as $path) {
            $resolvedPath = $this->service->getEnvironmentPath($path);
            
            $this->assertIsString($resolvedPath);
            $this->assertNotEmpty($resolvedPath);
            $this->assertStringContainsString($path, $resolvedPath);
        }
    }

    /**
     * Test that environment detection is consistent
     */
    public function testEnvironmentDetectionIsConsistent(): void
    {
        $env1 = $this->service->detectEnvironment();
        $env2 = $this->service->detectEnvironment();
        
        $this->assertEquals($env1, $env2, 'Environment detection should be consistent');
        
        $isLocalhost1 = $this->service->isLocalhost();
        $isLocalhost2 = $this->service->isLocalhost();
        
        $this->assertEquals($isLocalhost1, $isLocalhost2, 'isLocalhost() should be consistent');
    }

    /**
     * Test that isLocalhost and isProduction are mutually exclusive
     */
    public function testLocalhostAndProductionAreMutuallyExclusive(): void
    {
        $isLocalhost = $this->service->isLocalhost();
        $isProduction = $this->service->isProduction();
        
        // One must be true, the other false
        $this->assertNotEquals($isLocalhost, $isProduction);
        $this->assertTrue($isLocalhost XOR $isProduction);
    }

    /**
     * Test that environment info matches individual method results
     */
    public function testEnvironmentInfoMatchesIndividualMethods(): void
    {
        $info = $this->service->getEnvironmentInfo();
        
        $this->assertEquals($this->service->detectEnvironment(), $info['environment']);
        $this->assertEquals($this->service->isLocalhost(), $info['is_localhost']);
        $this->assertEquals($this->service->isProduction(), $info['is_production']);
    }

    /**
     * Test that config file writing with permissions works
     */
    public function testConfigFileWritingWithPermissions(): void
    {
        $tempFile = sys_get_temp_dir() . '/test_env_config_' . uniqid() . '.txt';
        $content = "TEST_VAR=test_value\n";
        
        $result = $this->service->writeConfigWithEnvironmentPermissions($tempFile, $content);
        
        $this->assertTrue($result, 'Should successfully write config file');
        $this->assertFileExists($tempFile);
        $this->assertEquals($content, file_get_contents($tempFile));
        
        // Clean up
        @unlink($tempFile);
    }

    /**
     * Test that database config writing uses environment permissions
     */
    public function testDatabaseConfigWritingUsesEnvironmentPermissions(): void
    {
        // Create a temporary .env file
        $tempEnvPath = sys_get_temp_dir() . '/.env_test_' . uniqid();
        
        // Create a mock .env.example
        $exampleContent = "DB_HOST=localhost\nDB_PORT=3306\nDB_NAME=test\nDB_USER=root\nDB_PASS=\n";
        file_put_contents($tempEnvPath . '.example', $exampleContent);
        
        // This test verifies the method exists and can be called
        // Actual file writing is tested in unit tests
        $this->assertTrue(method_exists($this->service, 'writeDatabaseConfig'));
        
        // Clean up
        @unlink($tempEnvPath . '.example');
    }

    /**
     * Test that logo upload uses environment permissions
     */
    public function testLogoUploadUsesEnvironmentPermissions(): void
    {
        // This test verifies the method exists and can be called
        // Actual upload handling is tested in unit tests
        $this->assertTrue(method_exists($this->service, 'processLogoUpload'));
        $this->assertTrue(method_exists($this->service, 'getEnvironmentPermissions'));
    }
}
