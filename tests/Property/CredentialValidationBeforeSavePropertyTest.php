<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Services\InstallationService;
use Karyalay\Services\EnvironmentConfigManager;

/**
 * Property-based tests for credential validation before save
 * 
 * **Feature: dual-environment-setup, Property 1: Credential Validation Before Save**
 * **Validates: Requirements 1.3, 4.3**
 * 
 * For any database credentials submitted through the installation wizard or admin settings,
 * the system SHALL test the database connection and only save credentials that pass validation.
 */
class CredentialValidationBeforeSavePropertyTest extends TestCase
{
    use TestTrait;

    private string $tempEnvPath;
    private InstallationService $installationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a temporary .env file for testing
        $this->tempEnvPath = sys_get_temp_dir() . '/test_env_validation_' . uniqid() . '.env';
        
        $this->installationService = new InstallationService();
    }

    protected function tearDown(): void
    {
        // Clean up temporary file
        if (file_exists($this->tempEnvPath)) {
            unlink($this->tempEnvPath);
        }
        
        parent::tearDown();
    }

    /**
     * Property 1: Credential Validation Before Save
     * 
     * For any database credentials submitted, the system SHALL test the database 
     * connection and only save credentials that pass validation.
     * 
     * **Feature: dual-environment-setup, Property 1: Credential Validation Before Save**
     * **Validates: Requirements 1.3, 4.3**
     * 
     * @test
     */
    public function credentialsAreValidatedBeforeSave(): void
    {
        $this->forAll(
            Generator\elements(['local', 'live']),
            $this->invalidCredentialsGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($environment, $invalidCredentials) {
            // Create a mock InstallationService that we can test
            $installationService = $this->getMockBuilder(InstallationService::class)
                ->onlyMethods(['testDatabaseConnection'])
                ->getMock();
            
            // Mock testDatabaseConnection to return failure for invalid credentials
            $installationService->method('testDatabaseConnection')
                ->willReturn([
                    'success' => false,
                    'error' => 'Connection refused or invalid credentials'
                ]);
            
            // Attempt to save invalid credentials
            $result = $installationService->saveEnvironmentCredentials($environment, $invalidCredentials);
            
            // The save should fail because connection test fails
            $this->assertFalse($result['success'], 
                'saveEnvironmentCredentials should fail when connection test fails');
            $this->assertNotNull($result['error'],
                'Error message should be provided when save fails');
            $this->assertStringContainsString('Connection test failed', $result['error'],
                'Error should indicate connection test failure');
        });
    }

    /**
     * Property: Valid credentials are saved after successful connection test
     * 
     * For any valid database credentials that pass the connection test,
     * the system SHALL save them to the configuration file.
     * 
     * **Feature: dual-environment-setup, Property 1: Credential Validation Before Save**
     * **Validates: Requirements 1.3, 4.3**
     * 
     * @test
     */
    public function validCredentialsAreSavedAfterSuccessfulConnectionTest(): void
    {
        $this->forAll(
            Generator\elements(['local', 'live']),
            $this->validCredentialsGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($environment, $validCredentials) {
            // Create a mock InstallationService
            $installationService = $this->getMockBuilder(InstallationService::class)
                ->onlyMethods(['testDatabaseConnection'])
                ->getMock();
            
            // Mock testDatabaseConnection to return success
            $installationService->method('testDatabaseConnection')
                ->willReturn([
                    'success' => true,
                    'error' => null
                ]);
            
            // Attempt to save valid credentials
            $result = $installationService->saveEnvironmentCredentials($environment, $validCredentials);
            
            // The save should succeed
            $this->assertTrue($result['success'], 
                'saveEnvironmentCredentials should succeed when connection test passes');
            $this->assertNull($result['error'],
                'No error should be present when save succeeds');
            
            // Verify credentials were actually saved
            $configManager = new EnvironmentConfigManager();
            $savedCredentials = $configManager->readEnvironmentCredentials($environment);
            
            $this->assertNotNull($savedCredentials, 
                'Saved credentials should be readable');
            $this->assertEquals($validCredentials['host'], $savedCredentials['host'],
                'Saved host should match input');
            $this->assertEquals($validCredentials['database'], $savedCredentials['database'],
                'Saved database should match input');
        });
    }

    /**
     * Property: Missing required fields are rejected before connection test
     * 
     * For any credentials missing required fields (host or database),
     * the system SHALL reject them before attempting a connection test.
     * 
     * **Feature: dual-environment-setup, Property 1: Credential Validation Before Save**
     * **Validates: Requirements 1.3**
     * 
     * @test
     */
    public function missingRequiredFieldsAreRejectedBeforeConnectionTest(): void
    {
        $this->forAll(
            Generator\elements(['local', 'live']),
            $this->credentialsWithMissingRequiredFieldsGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($environment, $incompleteCredentials) {
            // Use real InstallationService - connection test should not be called
            // because validation should fail first
            $installationService = new InstallationService();
            
            // Attempt to save incomplete credentials
            $result = $installationService->saveEnvironmentCredentials($environment, $incompleteCredentials);
            
            // The save should fail due to missing required fields
            $this->assertFalse($result['success'], 
                'saveEnvironmentCredentials should fail when required fields are missing');
            $this->assertNotNull($result['error'],
                'Error message should be provided when required fields are missing');
            
            // Error should indicate which field is missing
            $this->assertTrue(
                stripos($result['error'], 'host') !== false || 
                stripos($result['error'], 'database') !== false ||
                stripos($result['error'], 'required') !== false,
                'Error should indicate missing required field'
            );
        });
    }

    /**
     * Property: Invalid environment parameter is rejected
     * 
     * For any environment parameter that is not 'local' or 'live',
     * the system SHALL reject the save operation.
     * 
     * **Feature: dual-environment-setup, Property 1: Credential Validation Before Save**
     * **Validates: Requirements 1.3**
     * 
     * @test
     */
    public function invalidEnvironmentParameterIsRejected(): void
    {
        $this->forAll(
            Generator\elements(['production', 'dev', 'staging', 'test', '', 'LOCAL', 'LIVE']),
            $this->validCredentialsGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($invalidEnvironment, $credentials) {
            $installationService = new InstallationService();
            
            // Attempt to save with invalid environment
            $result = $installationService->saveEnvironmentCredentials($invalidEnvironment, $credentials);
            
            // The save should fail due to invalid environment
            $this->assertFalse($result['success'], 
                'saveEnvironmentCredentials should fail with invalid environment parameter');
            $this->assertNotNull($result['error'],
                'Error message should be provided for invalid environment');
            $this->assertStringContainsString('Invalid environment', $result['error'],
                'Error should indicate invalid environment');
        });
    }

    /**
     * Generate invalid database credentials (will fail connection test)
     * 
     * @return \Eris\Generator
     */
    private function invalidCredentialsGenerator(): Generator
    {
        return Generator\map(
            function ($values) {
                return [
                    'host' => $values[0],
                    'port' => $values[1],
                    'database' => $values[2],
                    'username' => $values[3],
                    'password' => $values[4],
                    'unix_socket' => ''
                ];
            },
            Generator\tuple(
                Generator\elements(['invalid.host.example', 'nonexistent.server.local', '192.168.255.255']),
                Generator\elements(['3306', '3307', '9999']),
                Generator\elements(['nonexistent_db', 'fake_database', 'invalid_db']),
                Generator\elements(['invalid_user', 'fake_admin', 'wrong_user']),
                Generator\elements(['wrong_password', 'invalid_pass', 'fake123'])
            )
        );
    }

    /**
     * Generate valid database credentials (structure is valid, but may not connect)
     * 
     * @return \Eris\Generator
     */
    private function validCredentialsGenerator(): Generator
    {
        return Generator\map(
            function ($values) {
                return [
                    'host' => $values[0],
                    'port' => $values[1],
                    'database' => $values[2],
                    'username' => $values[3],
                    'password' => $values[4],
                    'unix_socket' => $values[5]
                ];
            },
            Generator\tuple(
                Generator\elements(['localhost', '127.0.0.1', 'db.example.com']),
                Generator\elements(['3306', '3307']),
                Generator\elements(['mydb', 'testdb', 'karyalay_portal']),
                Generator\elements(['root', 'admin', 'dbuser']),
                Generator\elements(['password123', 'secret', '']),
                Generator\elements(['', '/var/run/mysqld/mysqld.sock'])
            )
        );
    }

    /**
     * Generate credentials with missing required fields
     * 
     * @return \Eris\Generator
     */
    private function credentialsWithMissingRequiredFieldsGenerator(): Generator
    {
        return Generator\map(
            function ($values) {
                // Randomly omit host or database
                $missingField = $values[0];
                
                $credentials = [
                    'host' => $missingField === 'host' ? '' : 'localhost',
                    'port' => '3306',
                    'database' => $missingField === 'database' ? '' : 'testdb',
                    'username' => 'root',
                    'password' => 'password',
                    'unix_socket' => ''
                ];
                
                return $credentials;
            },
            Generator\tuple(
                Generator\elements(['host', 'database'])
            )
        );
    }
}
