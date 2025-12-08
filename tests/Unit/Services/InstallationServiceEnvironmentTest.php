<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Karyalay\Services\InstallationService;

/**
 * Unit tests for InstallationService environment detection
 * 
 * Tests environment detection, path adaptation, and permission handling
 * 
 * Requirements: 6.1, 6.2, 6.3, 6.4
 */
class InstallationServiceEnvironmentTest extends TestCase
{
    private InstallationService $service;
    private array $originalServer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InstallationService();
        
        // Save original $_SERVER values
        $this->originalServer = $_SERVER;
    }

    protected function tearDown(): void
    {
        // Restore original $_SERVER values
        $_SERVER = $this->originalServer;
        parent::tearDown();
    }

    /**
     * Test localhost detection with localhost server name
     */
    public function testDetectEnvironmentWithLocalhostServerName(): void
    {
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_ADDR'] = '127.0.0.1';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        
        $environment = $this->service->detectEnvironment();
        
        $this->assertEquals('localhost', $environment);
        $this->assertTrue($this->service->isLocalhost());
        $this->assertFalse($this->service->isProduction());
    }

    /**
     * Test localhost detection with 127.0.0.1
     */
    public function testDetectEnvironmentWith127001(): void
    {
        $_SERVER['SERVER_NAME'] = '127.0.0.1';
        $_SERVER['SERVER_ADDR'] = '127.0.0.1';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        
        $environment = $this->service->detectEnvironment();
        
        $this->assertEquals('localhost', $environment);
        $this->assertTrue($this->service->isLocalhost());
    }

    /**
     * Test localhost detection with IPv6 localhost
     */
    public function testDetectEnvironmentWithIPv6Localhost(): void
    {
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_ADDR'] = '::1';
        $_SERVER['REMOTE_ADDR'] = '::1';
        
        $environment = $this->service->detectEnvironment();
        
        $this->assertEquals('localhost', $environment);
        $this->assertTrue($this->service->isLocalhost());
    }

    /**
     * Test localhost detection with .local domain
     */
    public function testDetectEnvironmentWithLocalDomain(): void
    {
        $_SERVER['SERVER_NAME'] = 'myapp.local';
        $_SERVER['SERVER_ADDR'] = '192.168.1.100';
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        
        $environment = $this->service->detectEnvironment();
        
        $this->assertEquals('localhost', $environment);
        $this->assertTrue($this->service->isLocalhost());
    }

    /**
     * Test localhost detection with .test domain
     */
    public function testDetectEnvironmentWithTestDomain(): void
    {
        $_SERVER['SERVER_NAME'] = 'myapp.test';
        $_SERVER['SERVER_ADDR'] = '192.168.1.100';
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        
        $environment = $this->service->detectEnvironment();
        
        $this->assertEquals('localhost', $environment);
        $this->assertTrue($this->service->isLocalhost());
    }

    /**
     * Test localhost detection with .dev domain
     */
    public function testDetectEnvironmentWithDevDomain(): void
    {
        $_SERVER['SERVER_NAME'] = 'myapp.dev';
        $_SERVER['SERVER_ADDR'] = '192.168.1.100';
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        
        $environment = $this->service->detectEnvironment();
        
        $this->assertEquals('localhost', $environment);
        $this->assertTrue($this->service->isLocalhost());
    }

    /**
     * Test localhost detection with XAMPP
     */
    public function testDetectEnvironmentWithXAMPP(): void
    {
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['SERVER_ADDR'] = '192.168.1.100';
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.4.54 (Win64) OpenSSL/1.1.1q PHP/8.1.12 (XAMPP)';
        
        $environment = $this->service->detectEnvironment();
        
        $this->assertEquals('localhost', $environment);
        $this->assertTrue($this->service->isLocalhost());
    }

    /**
     * Test production detection with production domain
     */
    public function testDetectEnvironmentWithProductionDomain(): void
    {
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['SERVER_ADDR'] = '203.0.113.1';
        $_SERVER['REMOTE_ADDR'] = '198.51.100.1';
        unset($_SERVER['SERVER_SOFTWARE']);
        
        $environment = $this->service->detectEnvironment();
        
        $this->assertEquals('production', $environment);
        $this->assertFalse($this->service->isLocalhost());
        $this->assertTrue($this->service->isProduction());
    }

    /**
     * Test environment path resolution
     */
    public function testGetEnvironmentPath(): void
    {
        $configPath = $this->service->getEnvironmentPath('config');
        
        $this->assertIsString($configPath);
        $this->assertStringContainsString('config', $configPath);
    }

    /**
     * Test environment path with leading slash
     */
    public function testGetEnvironmentPathWithLeadingSlash(): void
    {
        $path1 = $this->service->getEnvironmentPath('config');
        $path2 = $this->service->getEnvironmentPath('/config');
        
        // Both should resolve to the same path
        $this->assertEquals($path1, $path2);
    }

    /**
     * Test environment path with backslashes
     */
    public function testGetEnvironmentPathWithBackslashes(): void
    {
        $path = $this->service->getEnvironmentPath('config\\database.php');
        
        $this->assertIsString($path);
        // Should not contain backslashes (normalized to forward slashes)
        $this->assertStringNotContainsString('\\', $path);
    }

    /**
     * Test config file permissions on localhost
     */
    public function testGetEnvironmentPermissionsConfigOnLocalhost(): void
    {
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_ADDR'] = '127.0.0.1';
        
        $permissions = $this->service->getEnvironmentPermissions('config');
        
        // On Windows, should return 0777 (ignored)
        // On Unix localhost, should return 0644
        $this->assertIsInt($permissions);
        $this->assertGreaterThan(0, $permissions);
    }

    /**
     * Test upload directory permissions
     */
    public function testGetEnvironmentPermissionsUpload(): void
    {
        $permissions = $this->service->getEnvironmentPermissions('upload');
        
        $this->assertIsInt($permissions);
        $this->assertEquals(0755, $permissions);
    }

    /**
     * Test general file permissions
     */
    public function testGetEnvironmentPermissionsGeneral(): void
    {
        $permissions = $this->service->getEnvironmentPermissions('general');
        
        $this->assertIsInt($permissions);
        $this->assertEquals(0644, $permissions);
    }

    /**
     * Test environment info structure
     */
    public function testGetEnvironmentInfo(): void
    {
        $info = $this->service->getEnvironmentInfo();
        
        $this->assertIsArray($info);
        $this->assertArrayHasKey('environment', $info);
        $this->assertArrayHasKey('is_localhost', $info);
        $this->assertArrayHasKey('is_production', $info);
        $this->assertArrayHasKey('server_software', $info);
        $this->assertArrayHasKey('php_version', $info);
        $this->assertArrayHasKey('php_os', $info);
        $this->assertArrayHasKey('is_windows', $info);
        $this->assertArrayHasKey('is_https', $info);
        $this->assertArrayHasKey('server_name', $info);
        $this->assertArrayHasKey('document_root', $info);
        $this->assertArrayHasKey('warnings', $info);
        
        $this->assertIsBool($info['is_localhost']);
        $this->assertIsBool($info['is_production']);
        $this->assertIsBool($info['is_windows']);
        $this->assertIsBool($info['is_https']);
        $this->assertIsArray($info['warnings']);
    }

    /**
     * Test environment warnings on production without HTTPS
     */
    public function testGetEnvironmentWarningsProductionWithoutHTTPS(): void
    {
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['SERVER_ADDR'] = '203.0.113.1';
        $_SERVER['REMOTE_ADDR'] = '198.51.100.1';
        $_SERVER['HTTPS'] = 'off';
        unset($_SERVER['SERVER_SOFTWARE']);
        
        $warnings = $this->service->getEnvironmentWarnings();
        
        $this->assertIsArray($warnings);
        $this->assertNotEmpty($warnings);
        
        // Should have HTTPS warning
        $hasHttpsWarning = false;
        foreach ($warnings as $warning) {
            if (stripos($warning['message'], 'HTTPS') !== false) {
                $hasHttpsWarning = true;
                $this->assertEquals('high', $warning['severity']);
                $this->assertEquals('security', $warning['type']);
            }
        }
        
        $this->assertTrue($hasHttpsWarning, 'Should have HTTPS warning on production without HTTPS');
    }

    /**
     * Test environment warnings on localhost
     */
    public function testGetEnvironmentWarningsLocalhost(): void
    {
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_ADDR'] = '127.0.0.1';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        
        $warnings = $this->service->getEnvironmentWarnings();
        
        $this->assertIsArray($warnings);
        
        // Should have info message about localhost
        $hasLocalhostInfo = false;
        foreach ($warnings as $warning) {
            if (stripos($warning['message'], 'local development') !== false) {
                $hasLocalhostInfo = true;
                $this->assertEquals('low', $warning['severity']);
                $this->assertEquals('info', $warning['type']);
            }
        }
        
        $this->assertTrue($hasLocalhostInfo, 'Should have localhost info message');
    }

    /**
     * Test HTTPS detection with HTTPS on
     */
    public function testEnvironmentInfoDetectsHTTPS(): void
    {
        $_SERVER['HTTPS'] = 'on';
        
        $info = $this->service->getEnvironmentInfo();
        
        $this->assertTrue($info['is_https']);
    }

    /**
     * Test HTTPS detection with port 443
     */
    public function testEnvironmentInfoDetectsHTTPSWithPort443(): void
    {
        unset($_SERVER['HTTPS']);
        $_SERVER['SERVER_PORT'] = 443;
        
        $info = $this->service->getEnvironmentInfo();
        
        $this->assertTrue($info['is_https']);
    }

    /**
     * Test HTTPS detection without HTTPS
     */
    public function testEnvironmentInfoDetectsNoHTTPS(): void
    {
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['SERVER_PORT'] = 80;
        
        $info = $this->service->getEnvironmentInfo();
        
        $this->assertFalse($info['is_https']);
    }

    /**
     * Test write config with environment permissions
     */
    public function testWriteConfigWithEnvironmentPermissions(): void
    {
        $tempFile = sys_get_temp_dir() . '/test_config_' . uniqid() . '.txt';
        $content = 'test content';
        
        $result = $this->service->writeConfigWithEnvironmentPermissions($tempFile, $content);
        
        $this->assertTrue($result);
        $this->assertFileExists($tempFile);
        $this->assertEquals($content, file_get_contents($tempFile));
        
        // Clean up
        @unlink($tempFile);
    }

    /**
     * Test write config with environment permissions failure
     */
    public function testWriteConfigWithEnvironmentPermissionsFailure(): void
    {
        // Try to write to an invalid path
        $invalidPath = '/invalid/path/that/does/not/exist/config.txt';
        $content = 'test content';
        
        $result = $this->service->writeConfigWithEnvironmentPermissions($invalidPath, $content);
        
        $this->assertFalse($result);
    }
}
