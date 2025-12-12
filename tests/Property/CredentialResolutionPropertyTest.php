<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Services\EnvironmentConfigManager;
use Karyalay\Services\InstallationService;

/**
 * Property-based tests for credential resolution based on environment
 * 
 * **Feature: dual-environment-setup, Property 3: Credential Resolution Based on Environment**
 * **Validates: Requirements 2.2, 2.3, 2.4, 5.1, 5.3, 8.4**
 * 
 * For any combination of environment detection result and available credentials:
 * - If detected as production AND live credentials are valid → use live credentials
 * - If detected as production AND live credentials are empty/invalid AND local credentials are valid → use local credentials
 * - If detected as localhost AND local credentials are valid → use local credentials
 * - If detected as localhost AND local credentials are empty/invalid AND live credentials are valid → use live credentials
 */
class CredentialResolutionPropertyTest extends TestCase
{
    use TestTrait;

    private string $tempEnvPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a temporary .env file for testing
        $this->tempEnvPath = sys_get_temp_dir() . '/test_env_resolution_' . uniqid() . '.env';
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
     * Property 3: Credential Resolution Based on Environment
     * 
     * For any combination of environment detection result and available credentials,
     * the system SHALL resolve credentials according to the specified rules.
     * 
     * **Feature: dual-environment-setup, Property 3: Credential Resolution Based on Environment**
     * **Validates: Requirements 2.2, 2.3, 2.4, 5.1, 5.3, 8.4**
     * 
     * @test
     */
    public function credentialResolutionFollowsEnvironmentRules(): void
    {
        $this->forAll(
            Generator\elements(['localhost', 'production']),
            $this->credentialsGenerator(),
            $this->credentialsGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($detectedEnvironment, $localCredentials, $liveCredentials) {
            // Create mock InstallationService that returns the specified environment
            $mockInstallationService = $this->createMock(InstallationService::class);
            $mockInstallationService->method('detectEnvironment')->willReturn($detectedEnvironment);
            
            $configManager = new EnvironmentConfigManager($this->tempEnvPath, $mockInstallationService);
            
            // Write both credential sets
            $result = $configManager->writeDualConfig($localCredentials, $liveCredentials);
            $this->assertTrue($result, 'writeDualConfig should succeed');
            
            // Resolve credentials
            $resolved = $configManager->resolveCredentials();
            
            // Determine validity of each credential set
            $localValid = $this->isCredentialSetValid($localCredentials);
            $liveValid = $this->isCredentialSetValid($liveCredentials);
            
            // Verify resolution follows the rules
            if ($detectedEnvironment === 'production') {
                // Production environment: prefer live credentials
                if ($liveValid) {
                    // Rule: If detected as production AND live credentials are valid → use live credentials
                    $this->assertEquals('live', $resolved['environment'], 
                        'Production with valid live credentials should use live credentials');
                    $this->assertCredentialsMatch($liveCredentials, $resolved['credentials']);
                } elseif ($localValid) {
                    // Rule: If detected as production AND live credentials are empty/invalid AND local credentials are valid → use local credentials
                    $this->assertEquals('local', $resolved['environment'],
                        'Production with invalid live but valid local credentials should use local credentials');
                    $this->assertCredentialsMatch($localCredentials, $resolved['credentials']);
                } else {
                    // Neither credential set is valid
                    $this->assertNull($resolved['credentials'],
                        'Production with no valid credentials should return null credentials');
                    $this->assertNull($resolved['environment'],
                        'Production with no valid credentials should return null environment');
                }
            } else {
                // Localhost environment: prefer local credentials
                if ($localValid) {
                    // Rule: If detected as localhost AND local credentials are valid → use local credentials
                    $this->assertEquals('local', $resolved['environment'],
                        'Localhost with valid local credentials should use local credentials');
                    $this->assertCredentialsMatch($localCredentials, $resolved['credentials']);
                } elseif ($liveValid) {
                    // Rule: If detected as localhost AND local credentials are empty/invalid AND live credentials are valid → use live credentials
                    $this->assertEquals('live', $resolved['environment'],
                        'Localhost with invalid local but valid live credentials should use live credentials');
                    $this->assertCredentialsMatch($liveCredentials, $resolved['credentials']);
                } else {
                    // Neither credential set is valid
                    $this->assertNull($resolved['credentials'],
                        'Localhost with no valid credentials should return null credentials');
                    $this->assertNull($resolved['environment'],
                        'Localhost with no valid credentials should return null environment');
                }
            }
            
            // Verify detected_environment is correctly reported
            $this->assertEquals($detectedEnvironment, $resolved['detected_environment'],
                'Detected environment should be correctly reported');
            
            // Verify availability flags are correct
            $this->assertEquals($localValid, $resolved['local_available'],
                'local_available flag should match actual validity');
            $this->assertEquals($liveValid, $resolved['live_available'],
                'live_available flag should match actual validity');
        });
    }

    /**
     * Property: Production environment prefers live credentials
     * 
     * When running in production environment with valid live credentials,
     * the system SHALL always use live credentials regardless of local credential validity.
     * 
     * **Feature: dual-environment-setup, Property 3: Credential Resolution Based on Environment**
     * **Validates: Requirements 2.2, 5.1**
     * 
     * @test
     */
    public function productionEnvironmentPrefersLiveCredentials(): void
    {
        $this->forAll(
            $this->validCredentialsGenerator(),
            $this->validCredentialsGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($localCredentials, $liveCredentials) {
            // Create mock InstallationService that returns production
            $mockInstallationService = $this->createMock(InstallationService::class);
            $mockInstallationService->method('detectEnvironment')->willReturn('production');
            
            $configManager = new EnvironmentConfigManager($this->tempEnvPath, $mockInstallationService);
            
            // Write both valid credential sets
            $result = $configManager->writeDualConfig($localCredentials, $liveCredentials);
            $this->assertTrue($result, 'writeDualConfig should succeed');
            
            // Resolve credentials
            $resolved = $configManager->resolveCredentials();
            
            // Production should always prefer live when both are valid
            $this->assertEquals('live', $resolved['environment'],
                'Production should prefer live credentials when both are valid');
            $this->assertCredentialsMatch($liveCredentials, $resolved['credentials']);
        });
    }

    /**
     * Property: Localhost environment prefers local credentials
     * 
     * When running in localhost environment with valid local credentials,
     * the system SHALL always use local credentials regardless of live credential validity.
     * 
     * **Feature: dual-environment-setup, Property 3: Credential Resolution Based on Environment**
     * **Validates: Requirements 2.3**
     * 
     * @test
     */
    public function localhostEnvironmentPrefersLocalCredentials(): void
    {
        $this->forAll(
            $this->validCredentialsGenerator(),
            $this->validCredentialsGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($localCredentials, $liveCredentials) {
            // Create mock InstallationService that returns localhost
            $mockInstallationService = $this->createMock(InstallationService::class);
            $mockInstallationService->method('detectEnvironment')->willReturn('localhost');
            
            $configManager = new EnvironmentConfigManager($this->tempEnvPath, $mockInstallationService);
            
            // Write both valid credential sets
            $result = $configManager->writeDualConfig($localCredentials, $liveCredentials);
            $this->assertTrue($result, 'writeDualConfig should succeed');
            
            // Resolve credentials
            $resolved = $configManager->resolveCredentials();
            
            // Localhost should always prefer local when both are valid
            $this->assertEquals('local', $resolved['environment'],
                'Localhost should prefer local credentials when both are valid');
            $this->assertCredentialsMatch($localCredentials, $resolved['credentials']);
        });
    }

    /**
     * Property: Fallback to alternative credentials when preferred are invalid
     * 
     * When the preferred credentials for an environment are invalid,
     * the system SHALL fall back to the alternative credential set.
     * 
     * **Feature: dual-environment-setup, Property 3: Credential Resolution Based on Environment**
     * **Validates: Requirements 2.4**
     * 
     * @test
     */
    public function fallbackToAlternativeCredentialsWhenPreferredInvalid(): void
    {
        $this->forAll(
            Generator\elements(['localhost', 'production']),
            $this->validCredentialsGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($detectedEnvironment, $validCredentials) {
            // Create mock InstallationService
            $mockInstallationService = $this->createMock(InstallationService::class);
            $mockInstallationService->method('detectEnvironment')->willReturn($detectedEnvironment);
            
            $configManager = new EnvironmentConfigManager($this->tempEnvPath, $mockInstallationService);
            
            // Invalid credentials (missing required fields)
            $invalidCredentials = [
                'host' => '',
                'port' => '3306',
                'database' => '',
                'username' => '',
                'password' => '',
                'unix_socket' => ''
            ];
            
            if ($detectedEnvironment === 'production') {
                // Production with invalid live, valid local
                $result = $configManager->writeDualConfig($validCredentials, $invalidCredentials);
                $this->assertTrue($result, 'writeDualConfig should succeed');
                
                $resolved = $configManager->resolveCredentials();
                
                $this->assertEquals('local', $resolved['environment'],
                    'Production with invalid live should fall back to local');
                $this->assertCredentialsMatch($validCredentials, $resolved['credentials']);
            } else {
                // Localhost with invalid local, valid live
                $result = $configManager->writeDualConfig($invalidCredentials, $validCredentials);
                $this->assertTrue($result, 'writeDualConfig should succeed');
                
                $resolved = $configManager->resolveCredentials();
                
                $this->assertEquals('live', $resolved['environment'],
                    'Localhost with invalid local should fall back to live');
                $this->assertCredentialsMatch($validCredentials, $resolved['credentials']);
            }
        });
    }

    /**
     * Property: Empty/commented live credentials force local usage
     * 
     * When live credentials are commented out or empty, the system SHALL
     * automatically use localhost credentials regardless of detected environment.
     * 
     * **Feature: dual-environment-setup, Property 3: Credential Resolution Based on Environment**
     * **Validates: Requirements 5.3**
     * 
     * @test
     */
    public function emptyLiveCredentialsForceLocalUsage(): void
    {
        $this->forAll(
            Generator\elements(['localhost', 'production']),
            $this->validCredentialsGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($detectedEnvironment, $localCredentials) {
            // Create mock InstallationService
            $mockInstallationService = $this->createMock(InstallationService::class);
            $mockInstallationService->method('detectEnvironment')->willReturn($detectedEnvironment);
            
            $configManager = new EnvironmentConfigManager($this->tempEnvPath, $mockInstallationService);
            
            // Write only local credentials (live is null/empty)
            $result = $configManager->writeDualConfig($localCredentials, null);
            $this->assertTrue($result, 'writeDualConfig should succeed');
            
            // Resolve credentials
            $resolved = $configManager->resolveCredentials();
            
            // Should use local credentials regardless of environment
            $this->assertEquals('local', $resolved['environment'],
                'Empty live credentials should force local usage');
            $this->assertCredentialsMatch($localCredentials, $resolved['credentials']);
            $this->assertFalse($resolved['live_available'],
                'live_available should be false when live credentials are empty');
        });
    }

    /**
     * Generate random database credentials (may be valid or invalid)
     * 
     * @return \Eris\Generator
     */
    private function credentialsGenerator(): Generator
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
                Generator\elements(['localhost', '127.0.0.1', 'db.example.com', '']),
                Generator\elements(['3306', '3307', '']),
                Generator\elements(['mydb', 'testdb', '']),
                Generator\elements(['root', 'admin', '']),
                Generator\elements(['password123', 'secret', '']),
                Generator\elements(['', '/var/run/mysqld/mysqld.sock'])
            )
        );
    }

    /**
     * Generate valid database credentials (always has required fields)
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
                Generator\elements(['localhost', '127.0.0.1', 'db.example.com', 'mysql.hostinger.com']),
                Generator\elements(['3306', '3307', '3308']),
                Generator\elements(['mydb', 'testdb', 'production_db', 'karyalay_portal']),
                Generator\elements(['root', 'admin', 'dbuser', 'app_user']),
                Generator\elements(['password123', 'secret', 'P@ssw0rd!', '']),
                Generator\elements(['', '/var/run/mysqld/mysqld.sock', '/tmp/mysql.sock'])
            )
        );
    }

    /**
     * Check if a credential set is valid (has required fields)
     * 
     * @param array|null $credentials Credentials to check
     * @return bool True if valid, false otherwise
     */
    private function isCredentialSetValid(?array $credentials): bool
    {
        if ($credentials === null) {
            return false;
        }
        
        // At minimum, we need host and database name
        return !empty($credentials['host']) && !empty($credentials['database']);
    }

    /**
     * Assert that resolved credentials match expected credentials
     * 
     * @param array $expected Expected credentials
     * @param array|null $actual Actual resolved credentials
     */
    private function assertCredentialsMatch(array $expected, ?array $actual): void
    {
        $this->assertNotNull($actual, 'Resolved credentials should not be null');
        
        $this->assertEquals($expected['host'], $actual['host'], 'Host should match');
        $this->assertEquals($expected['database'], $actual['database'], 'Database should match');
        $this->assertEquals($expected['username'], $actual['username'], 'Username should match');
        $this->assertEquals($expected['password'], $actual['password'], 'Password should match');
        $this->assertEquals($expected['unix_socket'], $actual['unix_socket'], 'Unix socket should match');
        
        // Port may have default value applied
        $expectedPort = $expected['port'] ?: '3306';
        $this->assertEquals($expectedPort, $actual['port'], 'Port should match');
    }
}
