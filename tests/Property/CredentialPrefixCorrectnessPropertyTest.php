<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Services\EnvironmentConfigManager;
use Karyalay\Services\InstallationService;

/**
 * Property-based tests for credential prefix correctness
 * 
 * **Feature: dual-environment-setup, Property 4: Credential Prefix Correctness**
 * **Validates: Requirements 1.4, 8.2, 8.3**
 */
class CredentialPrefixCorrectnessPropertyTest extends TestCase
{
    use TestTrait;

    private string $tempEnvPath;
    private EnvironmentConfigManager $configManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a temporary .env file for testing
        $this->tempEnvPath = sys_get_temp_dir() . '/test_env_' . uniqid() . '.env';
        
        // Create a mock InstallationService that returns 'localhost' by default
        $mockInstallationService = $this->createMock(InstallationService::class);
        $mockInstallationService->method('detectEnvironment')->willReturn('localhost');
        
        $this->configManager = new EnvironmentConfigManager($this->tempEnvPath, $mockInstallationService);
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
     * Property 4: Credential Prefix Correctness
     * 
     * For any credentials written to the .env file, localhost credentials SHALL use 
     * the DB_LOCAL_ prefix and live credentials SHALL use the DB_LIVE_ prefix for 
     * all database variables (HOST, PORT, NAME, USER, PASS, UNIX_SOCKET).
     * 
     * **Feature: dual-environment-setup, Property 4: Credential Prefix Correctness**
     * **Validates: Requirements 1.4, 8.2, 8.3**
     * 
     * @test
     */
    public function credentialsAreWrittenWithCorrectPrefixes(): void
    {
        $this->forAll(
            $this->credentialsGenerator(),
            $this->credentialsGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($localCredentials, $liveCredentials) {
            // Write dual config
            $result = $this->configManager->writeDualConfig($localCredentials, $liveCredentials);
            
            $this->assertTrue($result, 'writeDualConfig should succeed');
            
            // Read the .env file content
            $envContent = file_get_contents($this->tempEnvPath);
            
            // Define the expected credential keys
            $credentialKeys = ['HOST', 'PORT', 'NAME', 'USER', 'PASS', 'UNIX_SOCKET'];
            
            // Verify localhost credentials use DB_LOCAL_ prefix
            foreach ($credentialKeys as $key) {
                $localKey = EnvironmentConfigManager::LOCAL_PREFIX . $key;
                $this->assertStringContainsString(
                    $localKey . '=',
                    $envContent,
                    "Localhost credential key {$localKey} should be present in .env file"
                );
            }
            
            // Verify live credentials use DB_LIVE_ prefix
            foreach ($credentialKeys as $key) {
                $liveKey = EnvironmentConfigManager::LIVE_PREFIX . $key;
                $this->assertStringContainsString(
                    $liveKey . '=',
                    $envContent,
                    "Live credential key {$liveKey} should be present in .env file"
                );
            }
            
            // Verify active credentials use DB_ prefix (without LOCAL_ or LIVE_)
            foreach ($credentialKeys as $key) {
                $activeKey = EnvironmentConfigManager::ACTIVE_PREFIX . $key;
                $this->assertStringContainsString(
                    $activeKey . '=',
                    $envContent,
                    "Active credential key {$activeKey} should be present in .env file"
                );
            }
            
            // Verify the values are correctly associated with their prefixes
            $this->assertCredentialValuesMatchPrefix($envContent, $localCredentials, EnvironmentConfigManager::LOCAL_PREFIX, true);
            $this->assertCredentialValuesMatchPrefix($envContent, $liveCredentials, EnvironmentConfigManager::LIVE_PREFIX, true);
        });
    }

    /**
     * Property: Localhost credentials are readable with correct prefix
     * 
     * For any localhost credentials written, reading them back should return
     * the same values using the DB_LOCAL_ prefix.
     * 
     * **Feature: dual-environment-setup, Property 4: Credential Prefix Correctness**
     * **Validates: Requirements 1.4, 8.2**
     * 
     * @test
     */
    public function localhostCredentialsRoundTrip(): void
    {
        $this->forAll(
            $this->credentialsGenerator()
        )
        ->withMaxSize(100)
        ->when(function ($credentials) {
            // Only test credentials with non-empty host and database
            return !empty($credentials['host']) && !empty($credentials['database']);
        })
        ->then(function ($localCredentials) {
            // Write only local credentials
            $result = $this->configManager->writeDualConfig($localCredentials, null);
            
            $this->assertTrue($result, 'writeDualConfig should succeed');
            
            // Read back the local credentials
            $readCredentials = $this->configManager->readEnvironmentCredentials('local');
            
            $this->assertNotNull($readCredentials, 'Local credentials should be readable');
            
            // Verify the values match
            $this->assertEquals($localCredentials['host'], $readCredentials['host'], 'Host should match');
            $this->assertEquals($localCredentials['port'] ?: '3306', $readCredentials['port'], 'Port should match');
            $this->assertEquals($localCredentials['database'], $readCredentials['database'], 'Database should match');
            $this->assertEquals($localCredentials['username'], $readCredentials['username'], 'Username should match');
            $this->assertEquals($localCredentials['password'], $readCredentials['password'], 'Password should match');
            $this->assertEquals($localCredentials['unix_socket'], $readCredentials['unix_socket'], 'Unix socket should match');
        });
    }

    /**
     * Property: Live credentials are readable with correct prefix
     * 
     * For any live credentials written, reading them back should return
     * the same values using the DB_LIVE_ prefix.
     * 
     * **Feature: dual-environment-setup, Property 4: Credential Prefix Correctness**
     * **Validates: Requirements 1.4, 8.3**
     * 
     * @test
     */
    public function liveCredentialsRoundTrip(): void
    {
        $this->forAll(
            $this->credentialsGenerator()
        )
        ->withMaxSize(100)
        ->when(function ($credentials) {
            // Only test credentials with non-empty host and database
            return !empty($credentials['host']) && !empty($credentials['database']);
        })
        ->then(function ($liveCredentials) {
            // Write only live credentials
            $result = $this->configManager->writeDualConfig(null, $liveCredentials);
            
            $this->assertTrue($result, 'writeDualConfig should succeed');
            
            // Read back the live credentials
            $readCredentials = $this->configManager->readEnvironmentCredentials('live');
            
            $this->assertNotNull($readCredentials, 'Live credentials should be readable');
            
            // Verify the values match
            $this->assertEquals($liveCredentials['host'], $readCredentials['host'], 'Host should match');
            $this->assertEquals($liveCredentials['port'] ?: '3306', $readCredentials['port'], 'Port should match');
            $this->assertEquals($liveCredentials['database'], $readCredentials['database'], 'Database should match');
            $this->assertEquals($liveCredentials['username'], $readCredentials['username'], 'Username should match');
            $this->assertEquals($liveCredentials['password'], $readCredentials['password'], 'Password should match');
            $this->assertEquals($liveCredentials['unix_socket'], $readCredentials['unix_socket'], 'Unix socket should match');
        });
    }

    /**
     * Property: Prefix constants are correctly defined
     * 
     * The prefix constants should be exactly as specified in the requirements.
     * 
     * **Feature: dual-environment-setup, Property 4: Credential Prefix Correctness**
     * **Validates: Requirements 8.2, 8.3**
     * 
     * @test
     */
    public function prefixConstantsAreCorrectlyDefined(): void
    {
        $this->assertEquals('DB_LOCAL_', EnvironmentConfigManager::LOCAL_PREFIX, 'LOCAL_PREFIX should be DB_LOCAL_');
        $this->assertEquals('DB_LIVE_', EnvironmentConfigManager::LIVE_PREFIX, 'LIVE_PREFIX should be DB_LIVE_');
        $this->assertEquals('DB_', EnvironmentConfigManager::ACTIVE_PREFIX, 'ACTIVE_PREFIX should be DB_');
    }

    /**
     * Property: getPrefixForEnvironment returns correct prefix
     * 
     * For any environment ('local' or 'live'), getPrefixForEnvironment should
     * return the correct prefix constant.
     * 
     * **Feature: dual-environment-setup, Property 4: Credential Prefix Correctness**
     * **Validates: Requirements 8.2, 8.3**
     * 
     * @test
     */
    public function getPrefixForEnvironmentReturnsCorrectPrefix(): void
    {
        $this->forAll(
            Generator\elements(['local', 'live'])
        )
        ->then(function ($environment) {
            $prefix = EnvironmentConfigManager::getPrefixForEnvironment($environment);
            
            if ($environment === 'local') {
                $this->assertEquals(
                    EnvironmentConfigManager::LOCAL_PREFIX,
                    $prefix,
                    'getPrefixForEnvironment("local") should return LOCAL_PREFIX'
                );
            } else {
                $this->assertEquals(
                    EnvironmentConfigManager::LIVE_PREFIX,
                    $prefix,
                    'getPrefixForEnvironment("live") should return LIVE_PREFIX'
                );
            }
        });
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
                    'unix_socket' => $values[5]
                ];
            },
            Generator\tuple(
                Generator\elements(['localhost', '127.0.0.1', 'db.example.com', 'mysql.hostinger.com']),
                Generator\elements(['3306', '3307', '3308', '']),
                Generator\suchThat(
                    function ($s) { return preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $s); },
                    Generator\string()
                ),
                Generator\suchThat(
                    function ($s) { return preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $s); },
                    Generator\string()
                ),
                Generator\string(),
                Generator\elements(['', '/var/run/mysqld/mysqld.sock', '/tmp/mysql.sock', '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock'])
            )
        );
    }

    /**
     * Assert that credential values in .env content match the expected values for a given prefix
     * 
     * @param string $envContent The .env file content
     * @param array $credentials The expected credentials
     * @param string $prefix The prefix to check
     * @param bool $applyDefaultPort Whether to apply default port logic (true for written values)
     */
    private function assertCredentialValuesMatchPrefix(string $envContent, array $credentials, string $prefix, bool $applyDefaultPort = false): void
    {
        $lines = explode("\n", $envContent);
        $foundValues = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            if (strpos($line, $prefix) === 0 && strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $credKey = substr($key, strlen($prefix));
                $foundValues[$credKey] = $this->unquoteValue(trim($value));
            }
        }
        
        // Map credential array keys to .env keys
        $keyMapping = [
            'host' => 'HOST',
            'port' => 'PORT',
            'database' => 'NAME',
            'username' => 'USER',
            'password' => 'PASS',
            'unix_socket' => 'UNIX_SOCKET'
        ];
        
        foreach ($keyMapping as $credKey => $envKey) {
            // The code uses null coalescing (??) which only applies default when key is null/not set
            // If the key exists with empty string, it uses the empty string
            $expectedValue = $credentials[$credKey] ?? '';
            
            // Apply default port logic only when the port key is null (not set)
            // The code uses: $credentials['port'] ?? '3306'
            // This means default is only applied when key doesn't exist, not when it's empty string
            if ($applyDefaultPort && $credKey === 'port' && !isset($credentials['port'])) {
                $expectedValue = '3306';
            }
            
            $this->assertArrayHasKey(
                $envKey,
                $foundValues,
                "Key {$prefix}{$envKey} should be present in .env file"
            );
            
            $this->assertEquals(
                $expectedValue,
                $foundValues[$envKey],
                "Value for {$prefix}{$envKey} should match expected value"
            );
        }
    }

    /**
     * Remove quotes from an environment variable value
     * 
     * @param string $value Quoted or unquoted value
     * @return string Unquoted value
     */
    private function unquoteValue(string $value): string
    {
        // Handle double-quoted strings
        if (preg_match('/^"(.*)"$/s', $value, $matches)) {
            return str_replace(['\\"', '\\\\'], ['"', '\\'], $matches[1]);
        }
        
        // Handle single-quoted strings
        if (preg_match("/^'(.*)'$/s", $value, $matches)) {
            return $matches[1];
        }
        
        return $value;
    }
}
