<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Services\EnvironmentConfigManager;
use Karyalay\Services\InstallationService;

/**
 * Property-based tests for config file update safety
 * 
 * **Feature: dual-environment-setup, Property 8: Config File Update Safety**
 * **Validates: Requirements 4.4**
 * 
 * For any credential update operation on an existing .env file, the system SHALL 
 * preserve all non-database configuration values and maintain file integrity.
 */
class ConfigFileUpdateSafetyPropertyTest extends TestCase
{
    use TestTrait;

    private string $tempEnvPath;
    private EnvironmentConfigManager $configManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a temporary .env file for testing
        $this->tempEnvPath = sys_get_temp_dir() . '/test_env_safety_' . uniqid() . '.env';
        
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
     * Property 8: Config File Update Safety
     * 
     * For any credential update operation on an existing .env file, the system SHALL 
     * preserve all non-database configuration values and maintain file integrity.
     * 
     * **Feature: dual-environment-setup, Property 8: Config File Update Safety**
     * **Validates: Requirements 4.4**
     * 
     * @test
     */
    public function nonDatabaseConfigurationsArePreservedOnCredentialUpdate(): void
    {
        $this->forAll(
            $this->nonDatabaseConfigGenerator(),
            $this->credentialsGenerator(),
            $this->credentialsGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($nonDbConfig, $initialCredentials, $updatedCredentials) {
            // Step 1: Create initial .env file with non-database configurations
            $initialEnvContent = $this->buildEnvFileWithNonDbConfig($nonDbConfig);
            file_put_contents($this->tempEnvPath, $initialEnvContent);
            
            // Step 2: Write initial credentials
            $result1 = $this->configManager->writeDualConfig($initialCredentials, null);
            $this->assertTrue($result1, 'Initial writeDualConfig should succeed');
            
            // Step 3: Read the file and verify non-database configs are preserved
            $contentAfterInitial = file_get_contents($this->tempEnvPath);
            $this->assertNonDatabaseConfigsPreserved($nonDbConfig, $contentAfterInitial, 'after initial write');
            
            // Step 4: Update credentials (simulating a credential update operation)
            $result2 = $this->configManager->writeDualConfig($updatedCredentials, $initialCredentials);
            $this->assertTrue($result2, 'Update writeDualConfig should succeed');
            
            // Step 5: Verify non-database configs are still preserved after update
            $contentAfterUpdate = file_get_contents($this->tempEnvPath);
            $this->assertNonDatabaseConfigsPreserved($nonDbConfig, $contentAfterUpdate, 'after credential update');
        });
    }

    /**
     * Property: Multiple credential updates preserve non-database configurations
     * 
     * For any sequence of credential updates, non-database configurations should
     * remain intact throughout all operations.
     * 
     * **Feature: dual-environment-setup, Property 8: Config File Update Safety**
     * **Validates: Requirements 4.4**
     * 
     * @test
     */
    public function multipleCredentialUpdatesPreserveNonDatabaseConfigs(): void
    {
        $this->forAll(
            $this->nonDatabaseConfigGenerator(),
            $this->credentialsGenerator(),
            $this->credentialsGenerator(),
            $this->credentialsGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($nonDbConfig, $credentials1, $credentials2, $credentials3) {
            // Create initial .env file with non-database configurations
            $initialEnvContent = $this->buildEnvFileWithNonDbConfig($nonDbConfig);
            file_put_contents($this->tempEnvPath, $initialEnvContent);
            
            $credentialsList = [$credentials1, $credentials2, $credentials3];
            
            // Perform multiple credential updates
            foreach ($credentialsList as $index => $credentials) {
                $result = $this->configManager->writeDualConfig($credentials, null);
                $this->assertTrue($result, "writeDualConfig should succeed on iteration {$index}");
                
                // Verify non-database configs are preserved after each update
                $currentContent = file_get_contents($this->tempEnvPath);
                $this->assertNonDatabaseConfigsPreserved(
                    $nonDbConfig, 
                    $currentContent, 
                    "after update iteration {$index}"
                );
            }
        });
    }

    /**
     * Property: File integrity is maintained (no corruption)
     * 
     * After any credential update, the .env file should be valid and parseable
     * with no corrupted lines or malformed entries.
     * 
     * **Feature: dual-environment-setup, Property 8: Config File Update Safety**
     * **Validates: Requirements 4.4**
     * 
     * @test
     */
    public function fileIntegrityIsMaintainedAfterUpdate(): void
    {
        $this->forAll(
            $this->nonDatabaseConfigGenerator(),
            $this->credentialsGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($nonDbConfig, $credentials) {
            // Create initial .env file
            $initialEnvContent = $this->buildEnvFileWithNonDbConfig($nonDbConfig);
            file_put_contents($this->tempEnvPath, $initialEnvContent);
            
            // Write credentials
            $result = $this->configManager->writeDualConfig($credentials, null);
            $this->assertTrue($result, 'writeDualConfig should succeed');
            
            // Read the file and verify integrity
            $content = file_get_contents($this->tempEnvPath);
            
            // File should not be empty
            $this->assertNotEmpty($content, 'File should not be empty');
            
            // File should be valid UTF-8
            $this->assertTrue(
                mb_check_encoding($content, 'UTF-8'),
                'File content should be valid UTF-8'
            );
            
            // Each non-empty, non-comment line should be a valid key=value pair
            $lines = explode("\n", $content);
            foreach ($lines as $lineNum => $line) {
                $trimmedLine = trim($line);
                
                // Skip empty lines and comments
                if (empty($trimmedLine) || strpos($trimmedLine, '#') === 0) {
                    continue;
                }
                
                // Line should contain an equals sign
                $this->assertStringContainsString(
                    '=',
                    $trimmedLine,
                    "Line {$lineNum} should be a valid key=value pair: {$trimmedLine}"
                );
                
                // Key should be a valid environment variable name
                $parts = explode('=', $trimmedLine, 2);
                $key = $parts[0];
                $this->assertMatchesRegularExpression(
                    '/^[A-Z][A-Z0-9_]*$/',
                    $key,
                    "Key on line {$lineNum} should be a valid env var name: {$key}"
                );
            }
        });
    }

    /**
     * Property: Existing database credentials are replaced, not duplicated
     * 
     * When updating credentials, old database entries should be replaced,
     * not appended, to prevent duplicate keys.
     * 
     * **Feature: dual-environment-setup, Property 8: Config File Update Safety**
     * **Validates: Requirements 4.4**
     * 
     * @test
     */
    public function existingDatabaseCredentialsAreReplacedNotDuplicated(): void
    {
        $this->forAll(
            $this->credentialsGenerator(),
            $this->credentialsGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($initialCredentials, $updatedCredentials) {
            // Write initial credentials
            $result1 = $this->configManager->writeDualConfig($initialCredentials, null);
            $this->assertTrue($result1, 'Initial writeDualConfig should succeed');
            
            // Write updated credentials
            $result2 = $this->configManager->writeDualConfig($updatedCredentials, null);
            $this->assertTrue($result2, 'Update writeDualConfig should succeed');
            
            // Read the file
            $content = file_get_contents($this->tempEnvPath);
            
            // Count occurrences of each database key
            $dbKeys = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_UNIX_SOCKET'];
            $localKeys = ['DB_LOCAL_HOST', 'DB_LOCAL_PORT', 'DB_LOCAL_NAME', 'DB_LOCAL_USER', 'DB_LOCAL_PASS', 'DB_LOCAL_UNIX_SOCKET'];
            $liveKeys = ['DB_LIVE_HOST', 'DB_LIVE_PORT', 'DB_LIVE_NAME', 'DB_LIVE_USER', 'DB_LIVE_PASS', 'DB_LIVE_UNIX_SOCKET'];
            
            $allKeys = array_merge($dbKeys, $localKeys, $liveKeys);
            
            foreach ($allKeys as $key) {
                $count = preg_match_all('/^' . preg_quote($key, '/') . '=/m', $content);
                $this->assertEquals(
                    1,
                    $count,
                    "Key {$key} should appear exactly once in the file, found {$count} times"
                );
            }
        });
    }

    /**
     * Generate random non-database configuration entries
     * 
     * @return \Eris\Generator
     */
    private function nonDatabaseConfigGenerator(): Generator
    {
        return Generator\map(
            function ($values) {
                return [
                    'APP_ENV' => $values[0],
                    'APP_DEBUG' => $values[1],
                    'APP_URL' => $values[2],
                    'RAZORPAY_KEY_ID' => $values[3],
                    'RAZORPAY_KEY_SECRET' => $values[4],
                    'ADMIN_EMAIL' => $values[5],
                ];
            },
            Generator\tuple(
                Generator\elements(['production', 'development', 'staging', 'local']),
                Generator\elements(['true', 'false']),
                Generator\elements(['https://example.com', 'https://karyalay.in', 'http://localhost:8080']),
                Generator\suchThat(
                    function ($s) { return preg_match('/^[a-zA-Z0-9_]+$/', $s); },
                    Generator\string()
                ),
                Generator\suchThat(
                    function ($s) { return preg_match('/^[a-zA-Z0-9_]+$/', $s); },
                    Generator\string()
                ),
                Generator\elements(['admin@example.com', 'test@karyalay.in', 'support@localhost'])
            )
        );
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
                Generator\suchThat(
                    function ($s) { return preg_match('/^[a-zA-Z0-9_]*$/', $s); },
                    Generator\string()
                ),
                Generator\elements(['', '/var/run/mysqld/mysqld.sock', '/tmp/mysql.sock'])
            )
        );
    }

    /**
     * Build an .env file content with non-database configurations
     * 
     * @param array $nonDbConfig Non-database configuration values
     * @return string .env file content
     */
    private function buildEnvFileWithNonDbConfig(array $nonDbConfig): string
    {
        $lines = [];
        $lines[] = '# Application Configuration';
        $lines[] = '# =============================================================================';
        $lines[] = '';
        $lines[] = '# APPLICATION SETTINGS';
        $lines[] = '# =============================================================================';
        $lines[] = '';
        
        foreach ($nonDbConfig as $key => $value) {
            $lines[] = "{$key}={$value}";
        }
        
        $lines[] = '';
        $lines[] = '# =============================================================================';
        $lines[] = '# END OF NON-DATABASE CONFIGURATION';
        $lines[] = '# =============================================================================';
        
        return implode("\n", $lines);
    }

    /**
     * Assert that non-database configurations are preserved in the .env content
     * 
     * @param array $expectedConfig Expected non-database configuration values
     * @param string $envContent Current .env file content
     * @param string $context Context message for assertion failures
     */
    private function assertNonDatabaseConfigsPreserved(array $expectedConfig, string $envContent, string $context): void
    {
        foreach ($expectedConfig as $key => $expectedValue) {
            // Check that the key=value pair exists in the content
            $pattern = '/^' . preg_quote($key, '/') . '=' . preg_quote($expectedValue, '/') . '$/m';
            $this->assertMatchesRegularExpression(
                $pattern,
                $envContent,
                "Non-database config {$key}={$expectedValue} should be preserved {$context}"
            );
        }
    }
}
