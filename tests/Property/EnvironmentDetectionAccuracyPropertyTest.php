<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Services\InstallationService;

/**
 * Property-based tests for environment detection accuracy
 * 
 * **Feature: dual-environment-setup, Property 2: Environment Detection Accuracy**
 * **Validates: Requirements 2.1**
 * 
 * For any server configuration (server name, IP address, domain), the system SHALL 
 * correctly classify it as either 'localhost' or 'production' based on the detection 
 * criteria (localhost indicators: 127.0.0.1, ::1, localhost, .local, .test, .dev domains).
 */
class EnvironmentDetectionAccuracyPropertyTest extends TestCase
{
    use TestTrait;

    private array $originalServer;

    protected function setUp(): void
    {
        parent::setUp();
        
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
     * Property 2: Environment Detection Accuracy
     * 
     * For any server configuration, the system SHALL correctly classify it as either 
     * 'localhost' or 'production' based on the detection criteria.
     * 
     * **Feature: dual-environment-setup, Property 2: Environment Detection Accuracy**
     * **Validates: Requirements 2.1**
     * 
     * @test
     */
    public function environmentDetectionIsAccurate(): void
    {
        $this->forAll(
            $this->serverConfigurationGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($serverConfig) {
            // Set up $_SERVER with the generated configuration
            $_SERVER['SERVER_NAME'] = $serverConfig['server_name'];
            $_SERVER['SERVER_ADDR'] = $serverConfig['server_addr'];
            $_SERVER['REMOTE_ADDR'] = $serverConfig['remote_addr'];
            $_SERVER['SERVER_SOFTWARE'] = $serverConfig['server_software'];
            
            $installationService = new InstallationService();
            $detected = $installationService->detectEnvironment();
            
            // Verify the detection matches expected result
            $expectedEnvironment = $this->determineExpectedEnvironment($serverConfig);
            
            $this->assertEquals(
                $expectedEnvironment,
                $detected,
                sprintf(
                    'Environment detection failed for config: SERVER_NAME=%s, SERVER_ADDR=%s, REMOTE_ADDR=%s, SERVER_SOFTWARE=%s',
                    $serverConfig['server_name'],
                    $serverConfig['server_addr'],
                    $serverConfig['remote_addr'],
                    $serverConfig['server_software']
                )
            );
        });
    }

    /**
     * Property: Localhost server names are detected as localhost
     * 
     * For any server name that is 'localhost', '127.0.0.1', or '::1',
     * the system SHALL detect it as localhost environment.
     * 
     * **Feature: dual-environment-setup, Property 2: Environment Detection Accuracy**
     * **Validates: Requirements 2.1**
     * 
     * @test
     */
    public function localhostServerNamesAreDetectedAsLocalhost(): void
    {
        $this->forAll(
            Generator\elements(['localhost', '127.0.0.1', '::1', 'localhost.localdomain'])
        )
        ->withMaxSize(100)
        ->then(function ($serverName) {
            $_SERVER['SERVER_NAME'] = $serverName;
            $_SERVER['SERVER_ADDR'] = '';
            $_SERVER['REMOTE_ADDR'] = '';
            $_SERVER['SERVER_SOFTWARE'] = 'Apache';
            
            $installationService = new InstallationService();
            $detected = $installationService->detectEnvironment();
            
            $this->assertEquals('localhost', $detected,
                "Server name '{$serverName}' should be detected as localhost");
        });
    }

    /**
     * Property: Local development domains are detected as localhost
     * 
     * For any domain ending with .local, .test, or .dev,
     * the system SHALL detect it as localhost environment.
     * 
     * **Feature: dual-environment-setup, Property 2: Environment Detection Accuracy**
     * **Validates: Requirements 2.1**
     * 
     * @test
     */
    public function localDevelopmentDomainsAreDetectedAsLocalhost(): void
    {
        $this->forAll(
            $this->localDomainGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($domain) {
            $_SERVER['SERVER_NAME'] = $domain;
            $_SERVER['SERVER_ADDR'] = '192.168.1.100'; // Non-localhost IP
            $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
            $_SERVER['SERVER_SOFTWARE'] = 'Apache';
            
            $installationService = new InstallationService();
            $detected = $installationService->detectEnvironment();
            
            $this->assertEquals('localhost', $detected,
                "Domain '{$domain}' should be detected as localhost");
        });
    }

    /**
     * Property: Localhost IP addresses are detected as localhost
     * 
     * For any server with localhost IP addresses (127.0.0.1, ::1, 0.0.0.0),
     * the system SHALL detect it as localhost environment.
     * 
     * **Feature: dual-environment-setup, Property 2: Environment Detection Accuracy**
     * **Validates: Requirements 2.1**
     * 
     * @test
     */
    public function localhostIpAddressesAreDetectedAsLocalhost(): void
    {
        $this->forAll(
            Generator\elements(['127.0.0.1', '::1', '0.0.0.0'])
        )
        ->withMaxSize(100)
        ->then(function ($ipAddress) {
            $_SERVER['SERVER_NAME'] = 'example.com'; // Production-like server name
            $_SERVER['SERVER_ADDR'] = $ipAddress;
            $_SERVER['REMOTE_ADDR'] = '';
            $_SERVER['SERVER_SOFTWARE'] = 'Apache';
            
            $installationService = new InstallationService();
            $detected = $installationService->detectEnvironment();
            
            $this->assertEquals('localhost', $detected,
                "Server address '{$ipAddress}' should be detected as localhost");
        });
    }

    /**
     * Property: XAMPP/MAMP/WAMP servers are detected as localhost
     * 
     * For any server running XAMPP, MAMP, or WAMP software,
     * the system SHALL detect it as localhost environment.
     * 
     * **Feature: dual-environment-setup, Property 2: Environment Detection Accuracy**
     * **Validates: Requirements 2.1**
     * 
     * @test
     */
    public function developmentServerSoftwareIsDetectedAsLocalhost(): void
    {
        $this->forAll(
            Generator\elements([
                'Apache/2.4.51 (Unix) PHP/8.1.0 XAMPP',
                'Apache/2.4.46 (Unix) PHP/7.4.12 MAMP',
                'Apache/2.4.46 (Win64) PHP/7.4.12 WAMP',
                'XAMPP Apache/2.4.51',
                'MAMP PRO/6.0'
            ])
        )
        ->withMaxSize(100)
        ->then(function ($serverSoftware) {
            $_SERVER['SERVER_NAME'] = 'example.com'; // Production-like server name
            $_SERVER['SERVER_ADDR'] = '192.168.1.100'; // Non-localhost IP
            $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
            $_SERVER['SERVER_SOFTWARE'] = $serverSoftware;
            
            $installationService = new InstallationService();
            $detected = $installationService->detectEnvironment();
            
            $this->assertEquals('localhost', $detected,
                "Server software '{$serverSoftware}' should be detected as localhost");
        });
    }

    /**
     * Property: Production domains are detected as production
     * 
     * For any server with production-like configuration (public domain, 
     * non-localhost IP, standard server software), the system SHALL 
     * detect it as production environment.
     * 
     * **Feature: dual-environment-setup, Property 2: Environment Detection Accuracy**
     * **Validates: Requirements 2.1**
     * 
     * @test
     */
    public function productionDomainsAreDetectedAsProduction(): void
    {
        $this->forAll(
            $this->productionDomainGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($domain) {
            $_SERVER['SERVER_NAME'] = $domain;
            $_SERVER['SERVER_ADDR'] = '203.0.113.50'; // Public IP
            $_SERVER['REMOTE_ADDR'] = '198.51.100.1'; // Public IP
            $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.4.51 (Ubuntu)';
            
            $installationService = new InstallationService();
            $detected = $installationService->detectEnvironment();
            
            $this->assertEquals('production', $detected,
                "Domain '{$domain}' should be detected as production");
        });
    }

    /**
     * Property: getActiveEnvironment maps correctly
     * 
     * For any detected environment, getActiveEnvironment SHALL return
     * 'local' for localhost and 'live' for production.
     * 
     * **Feature: dual-environment-setup, Property 2: Environment Detection Accuracy**
     * **Validates: Requirements 2.1**
     * 
     * @test
     */
    public function getActiveEnvironmentMapsCorrectly(): void
    {
        $this->forAll(
            $this->serverConfigurationGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($serverConfig) {
            $_SERVER['SERVER_NAME'] = $serverConfig['server_name'];
            $_SERVER['SERVER_ADDR'] = $serverConfig['server_addr'];
            $_SERVER['REMOTE_ADDR'] = $serverConfig['remote_addr'];
            $_SERVER['SERVER_SOFTWARE'] = $serverConfig['server_software'];
            
            $installationService = new InstallationService();
            $detected = $installationService->detectEnvironment();
            $active = $installationService->getActiveEnvironment();
            
            // Verify mapping
            if ($detected === 'localhost') {
                $this->assertEquals('local', $active,
                    'getActiveEnvironment should return "local" when detectEnvironment returns "localhost"');
            } else {
                $this->assertEquals('live', $active,
                    'getActiveEnvironment should return "live" when detectEnvironment returns "production"');
            }
        });
    }

    /**
     * Generate random server configurations
     * 
     * @return \Eris\Generator
     */
    private function serverConfigurationGenerator(): Generator
    {
        return Generator\map(
            function ($values) {
                return [
                    'server_name' => $values[0],
                    'server_addr' => $values[1],
                    'remote_addr' => $values[2],
                    'server_software' => $values[3]
                ];
            },
            Generator\tuple(
                Generator\elements([
                    'localhost', '127.0.0.1', '::1', 'localhost.localdomain',
                    'myapp.local', 'dev.test', 'project.dev',
                    'example.com', 'mysite.org', 'app.hostinger.com'
                ]),
                Generator\elements([
                    '127.0.0.1', '::1', '0.0.0.0',
                    '192.168.1.100', '10.0.0.1',
                    '203.0.113.50', '198.51.100.1'
                ]),
                Generator\elements([
                    '127.0.0.1', '::1',
                    '192.168.1.1', '10.0.0.1',
                    '203.0.113.1', '198.51.100.1'
                ]),
                Generator\elements([
                    'Apache/2.4.51 (Unix) PHP/8.1.0 XAMPP',
                    'Apache/2.4.46 (Unix) PHP/7.4.12 MAMP',
                    'Apache/2.4.46 (Win64) PHP/7.4.12 WAMP',
                    'Apache/2.4.51 (Ubuntu)',
                    'nginx/1.21.0',
                    'LiteSpeed'
                ])
            )
        );
    }

    /**
     * Generate local development domains
     * 
     * @return \Eris\Generator
     */
    private function localDomainGenerator(): Generator
    {
        return Generator\elements([
            'myapp.local',
            'project.local',
            'dev.local',
            'test.local',
            'mysite.test',
            'app.test',
            'project.test',
            'myapp.dev',
            'site.dev',
            'karyalay.local'
        ]);
    }

    /**
     * Generate production domains
     * 
     * @return \Eris\Generator
     */
    private function productionDomainGenerator(): Generator
    {
        return Generator\elements([
            'example.com',
            'mysite.org',
            'app.hostinger.com',
            'portal.example.net',
            'www.mycompany.com',
            'api.service.io',
            'karyalay.com',
            'subdomain.example.org'
        ]);
    }

    /**
     * Determine expected environment based on server configuration
     * 
     * This mirrors the logic in InstallationService::detectEnvironment()
     * 
     * @param array $serverConfig Server configuration
     * @return string 'localhost' or 'production'
     */
    private function determineExpectedEnvironment(array $serverConfig): string
    {
        $serverName = $serverConfig['server_name'];
        $serverAddr = $serverConfig['server_addr'];
        $remoteAddr = $serverConfig['remote_addr'];
        $serverSoftware = $serverConfig['server_software'];
        
        // Common localhost identifiers
        $localhostNames = ['localhost', '127.0.0.1', '::1', 'localhost.localdomain'];
        $localhostIPs = ['127.0.0.1', '::1', '0.0.0.0'];
        
        // Check server name
        if (in_array(strtolower($serverName), $localhostNames)) {
            return 'localhost';
        }
        
        // Check server address
        if (in_array($serverAddr, $localhostIPs)) {
            return 'localhost';
        }
        
        // Check remote address
        if (in_array($remoteAddr, $localhostIPs)) {
            return 'localhost';
        }
        
        // Check for common local development domains
        if (preg_match('/\.(local|test|dev)$/i', $serverName)) {
            return 'localhost';
        }
        
        // Check for XAMPP/MAMP/WAMP indicators
        if (stripos($serverSoftware, 'xampp') !== false ||
            stripos($serverSoftware, 'mamp') !== false ||
            stripos($serverSoftware, 'wamp') !== false) {
            return 'localhost';
        }
        
        return 'production';
    }
}
