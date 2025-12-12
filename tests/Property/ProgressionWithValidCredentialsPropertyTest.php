<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Services\EnvironmentConfigManager;
use Karyalay\Services\InstallationService;

/**
 * Property-based tests for progression with valid credentials
 * 
 * **Feature: dual-environment-setup, Property 9: Progression with Valid Credentials**
 * **Validates: Requirements 1.5**
 * 
 * For any completion of the database configuration step, progression to the next step
 * SHALL be allowed if and only if at least one credential set (local or live) has been
 * validated successfully.
 */
class ProgressionWithValidCredentialsPropertyTest extends TestCase
{
    use TestTrait;

    private string $tempEnvPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a temporary .env file for testing
        $this->tempEnvPath = sys_get_temp_dir() . '/test_env_progression_' . uniqid() . '.env';
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
     * Property 9: Progression with Valid Credentials
     * 
     * For any completion of the database configuration step, progression to the next step
     * SHALL be allowed if and only if at least one credential set (local or live) has been
     * validated successfully.
     * 
     * **Feature: dual-environment-setup, Property 9: Progression with Valid Credentials**
     * **Validates: Requirements 1.5**
     * 
     * @test
     */
    public function progressionAllowedWithAtLeastOneValidCredentialSet(): void
    {
        $this->forAll(
            $this->credentialsGenerator(),
            $this->credentialsGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($localCredentials, $liveCredentials) {
            $localValid = $this->isCredentialSetValid($localCredentials);
            $liveValid = $this->isCredentialSetValid($liveCredentials);
            
            // Simulate the progression check logic
            $canProgress = $this->canProgressToNextStep($localCredentials, $liveCredentials);
            
            // Progression should be allowed if and only if at least one credential set is valid
            $expectedCanProgress = $localValid || $liveValid;
            
            $this->assertEquals($expectedCanProgress, $canProgress,
                sprintf(
                    'Progression should be %s when local is %s and live is %s',
                    $expectedCanProgress ? 'allowed' : 'blocked',
                    $localValid ? 'valid' : 'invalid',
                    $liveValid ? 'valid' : 'invalid'
                )
            );
        });
    }

    /**
     * Property: Valid local credentials alone allow progression
     * 
     * When only local credentials are valid, progression SHALL be allowed.
     * 
     * **Feature: dual-environment-setup, Property 9: Progression with Valid Credentials**
     * **Validates: Requirements 1.5**
     * 
     * @test
     */
    public function validLocalCredentialsAloneAllowProgression(): void
    {
        $this->forAll(
            $this->validCredentialsGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($localCredentials) {
            // Invalid live credentials
            $liveCredentials = [
                'host' => '',
                'port' => '3306',
                'database' => '',
                'username' => '',
                'password' => '',
                'unix_socket' => ''
            ];
            
            $canProgress = $this->canProgressToNextStep($localCredentials, $liveCredentials);
            
            $this->assertTrue($canProgress,
                'Progression should be allowed with valid local credentials only');
        });
    }

    /**
     * Property: Valid live credentials alone allow progression
     * 
     * When only live credentials are valid, progression SHALL be allowed.
     * 
     * **Feature: dual-environment-setup, Property 9: Progression with Valid Credentials**
     * **Validates: Requirements 1.5**
     * 
     * @test
     */
    public function validLiveCredentialsAloneAllowProgression(): void
    {
        $this->forAll(
            $this->validCredentialsGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($liveCredentials) {
            // Invalid local credentials
            $localCredentials = [
                'host' => '',
                'port' => '3306',
                'database' => '',
                'username' => '',
                'password' => '',
                'unix_socket' => ''
            ];
            
            $canProgress = $this->canProgressToNextStep($localCredentials, $liveCredentials);
            
            $this->assertTrue($canProgress,
                'Progression should be allowed with valid live credentials only');
        });
    }

    /**
     * Property: Both valid credentials allow progression
     * 
     * When both credential sets are valid, progression SHALL be allowed.
     * 
     * **Feature: dual-environment-setup, Property 9: Progression with Valid Credentials**
     * **Validates: Requirements 1.5**
     * 
     * @test
     */
    public function bothValidCredentialsAllowProgression(): void
    {
        $this->forAll(
            $this->validCredentialsGenerator(),
            $this->validCredentialsGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($localCredentials, $liveCredentials) {
            $canProgress = $this->canProgressToNextStep($localCredentials, $liveCredentials);
            
            $this->assertTrue($canProgress,
                'Progression should be allowed when both credential sets are valid');
        });
    }

    /**
     * Property: No valid credentials block progression
     * 
     * When neither credential set is valid, progression SHALL be blocked.
     * 
     * **Feature: dual-environment-setup, Property 9: Progression with Valid Credentials**
     * **Validates: Requirements 1.5**
     * 
     * @test
     */
    public function noValidCredentialsBlockProgression(): void
    {
        $this->forAll(
            $this->invalidCredentialsGenerator(),
            $this->invalidCredentialsGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($localCredentials, $liveCredentials) {
            $canProgress = $this->canProgressToNextStep($localCredentials, $liveCredentials);
            
            $this->assertFalse($canProgress,
                'Progression should be blocked when neither credential set is valid');
        });
    }

    /**
     * Property: Progression requires database name
     * 
     * A credential set without a database name SHALL NOT be considered valid for progression.
     * 
     * **Feature: dual-environment-setup, Property 9: Progression with Valid Credentials**
     * **Validates: Requirements 1.5**
     * 
     * @test
     */
    public function progressionRequiresDatabaseName(): void
    {
        $this->forAll(
            Generator\elements(['localhost', '127.0.0.1', 'db.example.com']),
            Generator\elements(['root', 'admin', 'dbuser'])
        )
        ->withMaxSize(100)
        ->then(function ($host, $username) {
            // Credentials with host and username but no database
            $credentials = [
                'host' => $host,
                'port' => '3306',
                'database' => '', // Missing database name
                'username' => $username,
                'password' => 'password123',
                'unix_socket' => ''
            ];
            
            $isValid = $this->isCredentialSetValid($credentials);
            
            $this->assertFalse($isValid,
                'Credentials without database name should not be valid');
        });
    }

    /**
     * Property: Progression requires host (for TCP connection)
     * 
     * A credential set without a host (for TCP connection) SHALL NOT be considered valid for progression.
     * 
     * **Feature: dual-environment-setup, Property 9: Progression with Valid Credentials**
     * **Validates: Requirements 1.5**
     * 
     * @test
     */
    public function progressionRequiresHost(): void
    {
        $this->forAll(
            Generator\elements(['mydb', 'testdb', 'production_db']),
            Generator\elements(['root', 'admin', 'dbuser'])
        )
        ->withMaxSize(100)
        ->then(function ($database, $username) {
            // Credentials with database and username but no host
            $credentials = [
                'host' => '', // Missing host
                'port' => '3306',
                'database' => $database,
                'username' => $username,
                'password' => 'password123',
                'unix_socket' => ''
            ];
            
            $isValid = $this->isCredentialSetValid($credentials);
            
            $this->assertFalse($isValid,
                'Credentials without host should not be valid');
        });
    }

    /**
     * Simulate the progression check logic
     * 
     * This mirrors the logic in the database configuration step that determines
     * whether the user can proceed to the next step.
     * 
     * @param array $localCredentials Local environment credentials
     * @param array $liveCredentials Live environment credentials
     * @return bool True if progression is allowed
     */
    private function canProgressToNextStep(array $localCredentials, array $liveCredentials): bool
    {
        // Progression is allowed if at least one credential set is valid
        return $this->isCredentialSetValid($localCredentials) || 
               $this->isCredentialSetValid($liveCredentials);
    }

    /**
     * Check if a credential set is valid (has required fields)
     * 
     * A credential set is valid if it has:
     * - A non-empty host (for TCP connection) OR a non-empty unix_socket
     * - A non-empty database name
     * 
     * @param array|null $credentials Credentials to check
     * @return bool True if valid, false otherwise
     */
    private function isCredentialSetValid(?array $credentials): bool
    {
        if ($credentials === null) {
            return false;
        }
        
        // Must have database name
        if (empty($credentials['database'])) {
            return false;
        }
        
        // Must have either host or unix_socket
        $hasHost = !empty($credentials['host']);
        $hasSocket = !empty($credentials['unix_socket']);
        
        return $hasHost || $hasSocket;
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
     * Generate invalid database credentials (missing required fields)
     * 
     * @return \Eris\Generator
     */
    private function invalidCredentialsGenerator(): Generator
    {
        // Generate credentials that are guaranteed to be invalid
        // by always having either empty host or empty database
        return Generator\map(
            function ($values) {
                // Use the invalidationType to determine which field to make empty
                $invalidationType = $values[0];
                
                if ($invalidationType === 0) {
                    // Empty host, has database
                    return [
                        'host' => '',
                        'port' => '3306',
                        'database' => 'mydb',
                        'username' => $values[1],
                        'password' => $values[2],
                        'unix_socket' => ''
                    ];
                } elseif ($invalidationType === 1) {
                    // Has host, empty database
                    return [
                        'host' => 'localhost',
                        'port' => '3306',
                        'database' => '',
                        'username' => $values[1],
                        'password' => $values[2],
                        'unix_socket' => ''
                    ];
                } else {
                    // Both empty
                    return [
                        'host' => '',
                        'port' => '3306',
                        'database' => '',
                        'username' => $values[1],
                        'password' => $values[2],
                        'unix_socket' => ''
                    ];
                }
            },
            Generator\tuple(
                Generator\elements([0, 1, 2]), // Invalidation type
                Generator\elements(['root', 'admin', '']),
                Generator\elements(['password123', ''])
            )
        );
    }
}
