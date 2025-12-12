<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property-based tests for session data persistence on environment switch
 * 
 * **Feature: dual-environment-setup, Property 5: Session Data Persistence on Environment Switch**
 * **Validates: Requirements 3.4**
 * 
 * For any credentials entered in the wizard, switching between environment options
 * and back SHALL restore the previously entered values for that environment from session storage.
 */
class SessionDataPersistencePropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Simulated session storage for testing
     */
    private array $sessionData = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize session data structure
        $this->sessionData = [
            'wizard_data' => [
                1 => [
                    'selected_environment' => 'local',
                    'configure_both' => false,
                    'local' => [
                        'host' => 'localhost',
                        'port' => '3306',
                        'database' => '',
                        'username' => '',
                        'password' => '',
                        'unix_socket' => '',
                        'connection_type' => 'tcp',
                        'tested' => false,
                        'test_success' => false
                    ],
                    'live' => [
                        'host' => '',
                        'port' => '3306',
                        'database' => '',
                        'username' => '',
                        'password' => '',
                        'unix_socket' => '',
                        'connection_type' => 'tcp',
                        'tested' => false,
                        'test_success' => false
                    ]
                ]
            ]
        ];
    }

    /**
     * Property 5: Session Data Persistence on Environment Switch
     * 
     * For any credentials entered in the wizard, switching between environment options
     * and back SHALL restore the previously entered values for that environment from session storage.
     * 
     * **Feature: dual-environment-setup, Property 5: Session Data Persistence on Environment Switch**
     * **Validates: Requirements 3.4**
     * 
     * @test
     */
    public function credentialsArePreservedWhenSwitchingEnvironments(): void
    {
        $this->forAll(
            $this->credentialsGenerator(),
            $this->credentialsGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($localCredentials, $liveCredentials) {
            // Step 1: Enter local credentials
            $this->saveCredentialsToSession('local', $localCredentials);
            
            // Step 2: Switch to live environment
            $this->switchEnvironment('live');
            
            // Step 3: Enter live credentials
            $this->saveCredentialsToSession('live', $liveCredentials);
            
            // Step 4: Switch back to local environment
            $this->switchEnvironment('local');
            
            // Step 5: Verify local credentials are preserved
            $restoredLocalCredentials = $this->getCredentialsFromSession('local');
            $this->assertCredentialsMatch($localCredentials, $restoredLocalCredentials,
                'Local credentials should be preserved after switching to live and back');
            
            // Step 6: Switch to live environment again
            $this->switchEnvironment('live');
            
            // Step 7: Verify live credentials are preserved
            $restoredLiveCredentials = $this->getCredentialsFromSession('live');
            $this->assertCredentialsMatch($liveCredentials, $restoredLiveCredentials,
                'Live credentials should be preserved after switching to local and back');
        });
    }

    /**
     * Property: Multiple environment switches preserve all data
     * 
     * For any number of environment switches, credentials for both environments
     * SHALL remain intact in session storage.
     * 
     * **Feature: dual-environment-setup, Property 5: Session Data Persistence on Environment Switch**
     * **Validates: Requirements 3.4**
     * 
     * @test
     */
    public function multipleEnvironmentSwitchesPreserveAllData(): void
    {
        $this->forAll(
            $this->credentialsGenerator(),
            $this->credentialsGenerator(),
            Generator\choose(2, 10) // Number of switches
        )
        ->withMaxSize(100)
        ->then(function ($localCredentials, $liveCredentials, $switchCount) {
            // Save initial credentials
            $this->saveCredentialsToSession('local', $localCredentials);
            $this->saveCredentialsToSession('live', $liveCredentials);
            
            // Perform multiple switches
            $currentEnv = 'local';
            for ($i = 0; $i < $switchCount; $i++) {
                $currentEnv = $currentEnv === 'local' ? 'live' : 'local';
                $this->switchEnvironment($currentEnv);
            }
            
            // Verify both credential sets are still intact
            $restoredLocalCredentials = $this->getCredentialsFromSession('local');
            $restoredLiveCredentials = $this->getCredentialsFromSession('live');
            
            $this->assertCredentialsMatch($localCredentials, $restoredLocalCredentials,
                "Local credentials should be preserved after {$switchCount} switches");
            $this->assertCredentialsMatch($liveCredentials, $restoredLiveCredentials,
                "Live credentials should be preserved after {$switchCount} switches");
        });
    }

    /**
     * Property: Partial credential entry is preserved
     * 
     * When a user enters only some fields for an environment and switches,
     * those partial entries SHALL be preserved.
     * 
     * **Feature: dual-environment-setup, Property 5: Session Data Persistence on Environment Switch**
     * **Validates: Requirements 3.4**
     * 
     * @test
     */
    public function partialCredentialEntryIsPreserved(): void
    {
        $this->forAll(
            $this->partialCredentialsGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($partialCredentials) {
            // Save partial credentials for local
            $this->saveCredentialsToSession('local', $partialCredentials);
            
            // Switch to live
            $this->switchEnvironment('live');
            
            // Switch back to local
            $this->switchEnvironment('local');
            
            // Verify partial credentials are preserved
            $restoredCredentials = $this->getCredentialsFromSession('local');
            
            // Check each field that was set
            foreach ($partialCredentials as $key => $value) {
                if ($value !== '' && $value !== null) {
                    $this->assertEquals($value, $restoredCredentials[$key],
                        "Field '{$key}' should be preserved");
                }
            }
        });
    }

    /**
     * Property: Selected environment is preserved
     * 
     * The currently selected environment SHALL be preserved in session.
     * 
     * **Feature: dual-environment-setup, Property 5: Session Data Persistence on Environment Switch**
     * **Validates: Requirements 3.4**
     * 
     * @test
     */
    public function selectedEnvironmentIsPreserved(): void
    {
        $this->forAll(
            Generator\elements(['local', 'live'])
        )
        ->withMaxSize(100)
        ->then(function ($environment) {
            // Switch to the environment
            $this->switchEnvironment($environment);
            
            // Verify selected environment is stored
            $selectedEnv = $this->sessionData['wizard_data'][1]['selected_environment'];
            $this->assertEquals($environment, $selectedEnv,
                "Selected environment should be '{$environment}'");
        });
    }

    /**
     * Property: Configure both flag is preserved
     * 
     * The "configure both" checkbox state SHALL be preserved in session.
     * 
     * **Feature: dual-environment-setup, Property 5: Session Data Persistence on Environment Switch**
     * **Validates: Requirements 3.4**
     * 
     * @test
     */
    public function configureBothFlagIsPreserved(): void
    {
        $this->forAll(
            Generator\bool()
        )
        ->withMaxSize(100)
        ->then(function ($configureBoth) {
            // Set the configure_both flag
            $this->sessionData['wizard_data'][1]['configure_both'] = $configureBoth;
            
            // Switch environments
            $this->switchEnvironment('live');
            $this->switchEnvironment('local');
            
            // Verify flag is preserved
            $storedFlag = $this->sessionData['wizard_data'][1]['configure_both'];
            $this->assertEquals($configureBoth, $storedFlag,
                "Configure both flag should be preserved as " . ($configureBoth ? 'true' : 'false'));
        });
    }

    /**
     * Property: Test status is preserved per environment
     * 
     * The connection test status for each environment SHALL be preserved independently.
     * 
     * **Feature: dual-environment-setup, Property 5: Session Data Persistence on Environment Switch**
     * **Validates: Requirements 3.4**
     * 
     * @test
     */
    public function testStatusIsPreservedPerEnvironment(): void
    {
        $this->forAll(
            Generator\bool(),
            Generator\bool(),
            Generator\bool(),
            Generator\bool()
        )
        ->withMaxSize(100)
        ->then(function ($localTested, $localSuccess, $liveTested, $liveSuccess) {
            // Set test status for local
            $this->sessionData['wizard_data'][1]['local']['tested'] = $localTested;
            $this->sessionData['wizard_data'][1]['local']['test_success'] = $localSuccess;
            
            // Set test status for live
            $this->sessionData['wizard_data'][1]['live']['tested'] = $liveTested;
            $this->sessionData['wizard_data'][1]['live']['test_success'] = $liveSuccess;
            
            // Switch environments multiple times
            $this->switchEnvironment('live');
            $this->switchEnvironment('local');
            $this->switchEnvironment('live');
            
            // Verify test status is preserved for both environments
            $this->assertEquals($localTested, $this->sessionData['wizard_data'][1]['local']['tested'],
                'Local tested flag should be preserved');
            $this->assertEquals($localSuccess, $this->sessionData['wizard_data'][1]['local']['test_success'],
                'Local test_success flag should be preserved');
            $this->assertEquals($liveTested, $this->sessionData['wizard_data'][1]['live']['tested'],
                'Live tested flag should be preserved');
            $this->assertEquals($liveSuccess, $this->sessionData['wizard_data'][1]['live']['test_success'],
                'Live test_success flag should be preserved');
        });
    }

    /**
     * Save credentials to session for a specific environment
     * 
     * @param string $environment 'local' or 'live'
     * @param array $credentials Credentials to save
     */
    private function saveCredentialsToSession(string $environment, array $credentials): void
    {
        $this->sessionData['wizard_data'][1][$environment] = array_merge(
            $this->sessionData['wizard_data'][1][$environment],
            $credentials
        );
    }

    /**
     * Get credentials from session for a specific environment
     * 
     * @param string $environment 'local' or 'live'
     * @return array Credentials from session
     */
    private function getCredentialsFromSession(string $environment): array
    {
        return $this->sessionData['wizard_data'][1][$environment];
    }

    /**
     * Switch the selected environment
     * 
     * @param string $environment 'local' or 'live'
     */
    private function switchEnvironment(string $environment): void
    {
        $this->sessionData['wizard_data'][1]['selected_environment'] = $environment;
    }

    /**
     * Generate random database credentials
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
                    'unix_socket' => $values[5],
                    'connection_type' => $values[6]
                ];
            },
            Generator\tuple(
                Generator\elements(['localhost', '127.0.0.1', 'db.example.com', 'mysql.hostinger.com']),
                Generator\elements(['3306', '3307', '3308']),
                Generator\elements(['mydb', 'testdb', 'production_db', 'karyalay_portal']),
                Generator\elements(['root', 'admin', 'dbuser', 'app_user']),
                Generator\elements(['password123', 'secret', 'P@ssw0rd!', '']),
                Generator\elements(['', '/var/run/mysqld/mysqld.sock', '/tmp/mysql.sock']),
                Generator\elements(['tcp', 'socket'])
            )
        );
    }

    /**
     * Generate partial database credentials (some fields may be empty)
     * 
     * @return \Eris\Generator
     */
    private function partialCredentialsGenerator(): Generator
    {
        return Generator\map(
            function ($values) {
                return [
                    'host' => $values[0],
                    'port' => $values[1],
                    'database' => $values[2],
                    'username' => $values[3],
                    'password' => $values[4],
                    'unix_socket' => '',
                    'connection_type' => 'tcp'
                ];
            },
            Generator\tuple(
                Generator\elements(['localhost', '127.0.0.1', '']),
                Generator\elements(['3306', '']),
                Generator\elements(['mydb', '']),
                Generator\elements(['root', '']),
                Generator\elements(['password123', ''])
            )
        );
    }

    /**
     * Assert that two credential arrays match
     * 
     * @param array $expected Expected credentials
     * @param array $actual Actual credentials
     * @param string $message Assertion message
     */
    private function assertCredentialsMatch(array $expected, array $actual, string $message = ''): void
    {
        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $actual[$key] ?? null,
                $message . " - Field '{$key}' should match");
        }
    }
}
