<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Services\UrlService;

/**
 * Property-based tests for URL base resolution
 * 
 * **Feature: dual-environment-setup, Property 6: URL Base Resolution**
 * **Validates: Requirements 7.1, 7.2, 7.4**
 * 
 * For any URL generation request:
 * - If APP_URL is configured → use APP_URL as base
 * - If APP_URL is not configured → use detected request host and protocol
 * - Protocol SHALL match the detected request protocol (HTTP or HTTPS)
 */
class UrlBaseResolutionPropertyTest extends TestCase
{
    use TestTrait;

    private UrlService $urlService;
    private array $originalEnv;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->urlService = new UrlService();
        
        // Save original environment
        $this->originalEnv = [
            'APP_URL' => getenv('APP_URL')
        ];
    }

    protected function tearDown(): void
    {
        // Restore original environment
        if ($this->originalEnv['APP_URL'] !== false) {
            putenv('APP_URL=' . $this->originalEnv['APP_URL']);
        } else {
            putenv('APP_URL');
        }
        
        parent::tearDown();
    }

    /**
     * Property 6: When APP_URL is configured, it is used as the base URL
     * 
     * For any configured APP_URL, the resolved base URL SHALL use APP_URL
     * as the base (with protocol adjusted to match detected protocol).
     * 
     * **Feature: dual-environment-setup, Property 6: URL Base Resolution**
     * **Validates: Requirements 7.1**
     * 
     * @test
     */
    public function appUrlIsUsedAsBaseWhenConfigured(): void
    {
        $this->forAll(
            $this->appUrlGenerator(),
            $this->serverVarsGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($appUrl, $serverVars) {
            // Set APP_URL
            putenv('APP_URL=' . $appUrl);
            
            $resolvedUrl = $this->urlService->resolveBaseUrl($serverVars);
            
            // Parse both URLs
            $appUrlParsed = parse_url($appUrl);
            $resolvedParsed = parse_url($resolvedUrl);
            
            // The host should match APP_URL's host
            $this->assertEquals(
                $appUrlParsed['host'] ?? '',
                $resolvedParsed['host'] ?? '',
                "Host should match APP_URL when configured. APP_URL: {$appUrl}, Resolved: {$resolvedUrl}"
            );
            
            // The path should match APP_URL's path (if any)
            $expectedPath = rtrim($appUrlParsed['path'] ?? '', '/');
            $actualPath = rtrim($resolvedParsed['path'] ?? '', '/');
            
            $this->assertEquals(
                $expectedPath,
                $actualPath,
                "Path should match APP_URL when configured. APP_URL: {$appUrl}, Resolved: {$resolvedUrl}"
            );
        });
    }

    /**
     * Property 6: When APP_URL is not configured, request detection is used
     * 
     * For any request without APP_URL configured, the resolved base URL
     * SHALL be detected from the request's host and protocol.
     * 
     * **Feature: dual-environment-setup, Property 6: URL Base Resolution**
     * **Validates: Requirements 7.2**
     * 
     * @test
     */
    public function requestDetectionIsUsedWhenAppUrlNotConfigured(): void
    {
        $this->forAll(
            $this->serverVarsGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($serverVars) {
            // Clear APP_URL
            putenv('APP_URL');
            
            $resolvedUrl = $this->urlService->resolveBaseUrl($serverVars);
            
            // Parse resolved URL
            $resolvedParsed = parse_url($resolvedUrl);
            
            // The host should match the detected host
            $expectedHost = $this->getExpectedHost($serverVars);
            $actualHost = $resolvedParsed['host'] ?? '';
            
            // Handle port in host
            if (isset($resolvedParsed['port'])) {
                $actualHost .= ':' . $resolvedParsed['port'];
            }
            
            $this->assertEquals(
                $expectedHost,
                $actualHost,
                "Host should be detected from request when APP_URL not configured"
            );
        });
    }

    /**
     * Property 6: Protocol matches detected request protocol
     * 
     * For any URL generation request, the protocol SHALL match the
     * detected request protocol (HTTP or HTTPS).
     * 
     * **Feature: dual-environment-setup, Property 6: URL Base Resolution**
     * **Validates: Requirements 7.4**
     * 
     * @test
     */
    public function protocolMatchesDetectedRequestProtocol(): void
    {
        $this->forAll(
            Generator\elements([null, 'http://example.com', 'https://example.com']),
            $this->serverVarsGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($appUrl, $serverVars) {
            // Set or clear APP_URL
            if ($appUrl !== null) {
                putenv('APP_URL=' . $appUrl);
            } else {
                putenv('APP_URL');
            }
            
            $resolvedUrl = $this->urlService->resolveBaseUrl($serverVars);
            
            // Determine expected protocol from server vars
            $expectedProtocol = $this->getExpectedProtocol($serverVars);
            
            // Check that resolved URL uses the expected protocol
            $this->assertStringStartsWith(
                $expectedProtocol,
                $resolvedUrl,
                "Protocol should match detected request protocol. Expected: {$expectedProtocol}, Got: {$resolvedUrl}"
            );
        });
    }

    /**
     * Property 6: HTTPS detection via HTTPS server variable
     * 
     * When HTTPS server variable is set and not 'off', the protocol
     * SHALL be detected as HTTPS.
     * 
     * **Feature: dual-environment-setup, Property 6: URL Base Resolution**
     * **Validates: Requirements 7.4**
     * 
     * @test
     */
    public function httpsDetectedViaHttpsServerVariable(): void
    {
        $this->forAll(
            Generator\elements(['on', '1', 'true', 'ON'])
        )
        ->withMaxSize(100)
        ->then(function ($httpsValue) {
            putenv('APP_URL');
            
            $serverVars = [
                'HTTPS' => $httpsValue,
                'HTTP_HOST' => 'example.com',
                'SERVER_NAME' => 'example.com',
                'SERVER_PORT' => '443',
                'SCRIPT_NAME' => '/index.php'
            ];
            
            $protocol = $this->urlService->detectProtocol($serverVars);
            
            $this->assertEquals(
                'https://',
                $protocol,
                "HTTPS should be detected when HTTPS server variable is '{$httpsValue}'"
            );
        });
    }

    /**
     * Property 6: HTTPS detection via X-Forwarded-Proto header
     * 
     * When X-Forwarded-Proto header is 'https', the protocol
     * SHALL be detected as HTTPS (for reverse proxy scenarios).
     * 
     * **Feature: dual-environment-setup, Property 6: URL Base Resolution**
     * **Validates: Requirements 7.4**
     * 
     * @test
     */
    public function httpsDetectedViaXForwardedProtoHeader(): void
    {
        $this->forAll(
            Generator\elements(['https', 'HTTPS', 'Https'])
        )
        ->withMaxSize(100)
        ->then(function ($protoValue) {
            $serverVars = [
                'HTTP_X_FORWARDED_PROTO' => $protoValue,
                'HTTP_HOST' => 'example.com',
                'SERVER_NAME' => 'example.com',
                'SERVER_PORT' => '80',
                'SCRIPT_NAME' => '/index.php'
            ];
            
            $protocol = $this->urlService->detectProtocol($serverVars);
            
            $this->assertEquals(
                'https://',
                $protocol,
                "HTTPS should be detected when X-Forwarded-Proto is '{$protoValue}'"
            );
        });
    }

    /**
     * Property 6: HTTPS detection via port 443
     * 
     * When server port is 443, the protocol SHALL be detected as HTTPS.
     * 
     * **Feature: dual-environment-setup, Property 6: URL Base Resolution**
     * **Validates: Requirements 7.4**
     * 
     * @test
     */
    public function httpsDetectedViaPort443(): void
    {
        putenv('APP_URL');
        
        $serverVars = [
            'HTTP_HOST' => 'example.com',
            'SERVER_NAME' => 'example.com',
            'SERVER_PORT' => '443',
            'SCRIPT_NAME' => '/index.php'
        ];
        
        $protocol = $this->urlService->detectProtocol($serverVars);
        
        $this->assertEquals(
            'https://',
            $protocol,
            "HTTPS should be detected when server port is 443"
        );
    }

    /**
     * Property 6: HTTP is default when no HTTPS indicators present
     * 
     * When no HTTPS indicators are present, the protocol SHALL default to HTTP.
     * 
     * **Feature: dual-environment-setup, Property 6: URL Base Resolution**
     * **Validates: Requirements 7.4**
     * 
     * @test
     */
    public function httpIsDefaultWhenNoHttpsIndicators(): void
    {
        $serverVars = [
            'HTTP_HOST' => 'example.com',
            'SERVER_NAME' => 'example.com',
            'SERVER_PORT' => '80',
            'SCRIPT_NAME' => '/index.php'
        ];
        
        $protocol = $this->urlService->detectProtocol($serverVars);
        
        $this->assertEquals(
            'http://',
            $protocol,
            "HTTP should be default when no HTTPS indicators are present"
        );
    }

    /**
     * Property 6: Base path is correctly detected from script name
     * 
     * For any script path, the base path SHALL be correctly extracted
     * by removing known subdirectories (/install, /public, /admin, /app).
     * 
     * **Feature: dual-environment-setup, Property 6: URL Base Resolution**
     * **Validates: Requirements 7.2**
     * 
     * @test
     */
    public function basePathIsCorrectlyDetectedFromScriptName(): void
    {
        $this->forAll(
            $this->scriptNameGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($scriptData) {
            $serverVars = [
                'SCRIPT_NAME' => $scriptData['script_name']
            ];
            
            $basePath = $this->urlService->detectBasePath($serverVars);
            
            $this->assertEquals(
                $scriptData['expected_base'],
                $basePath,
                "Base path should be '{$scriptData['expected_base']}' for script '{$scriptData['script_name']}'"
            );
        });
    }

    /**
     * Property 6: Resolved URL never has trailing slash
     * 
     * For any configuration, the resolved base URL SHALL NOT have a trailing slash.
     * 
     * **Feature: dual-environment-setup, Property 6: URL Base Resolution**
     * **Validates: Requirements 7.1, 7.2**
     * 
     * @test
     */
    public function resolvedUrlNeverHasTrailingSlash(): void
    {
        $this->forAll(
            Generator\elements([
                null,
                'http://example.com',
                'http://example.com/',
                'https://example.com/app',
                'https://example.com/app/'
            ]),
            $this->serverVarsGenerator()
        )
        ->withMaxSize(100)
        ->then(function ($appUrl, $serverVars) {
            if ($appUrl !== null) {
                putenv('APP_URL=' . $appUrl);
            } else {
                putenv('APP_URL');
            }
            
            $resolvedUrl = $this->urlService->resolveBaseUrl($serverVars);
            
            $this->assertStringEndsNotWith(
                '/',
                $resolvedUrl,
                "Resolved URL should not have trailing slash: {$resolvedUrl}"
            );
        });
    }

    /**
     * Generate valid APP_URL values
     * 
     * @return \Eris\Generator
     */
    private function appUrlGenerator(): Generator
    {
        return Generator\elements([
            'http://localhost',
            'http://localhost:8080',
            'https://example.com',
            'https://example.com/karyalayportal',
            'http://myapp.local',
            'https://portal.hostinger.com',
            'http://192.168.1.100',
            'https://app.example.org/subdir'
        ]);
    }

    /**
     * Generate server variables for testing
     * 
     * @return \Eris\Generator
     */
    private function serverVarsGenerator(): Generator
    {
        return Generator\map(
            function ($values) {
                $serverVars = [
                    'HTTP_HOST' => $values[0],
                    'SERVER_NAME' => $values[0],
                    'SERVER_PORT' => $values[1],
                    'SCRIPT_NAME' => $values[2]
                ];
                
                // Add HTTPS indicator based on port or random
                if ($values[1] === '443' || $values[3]) {
                    $serverVars['HTTPS'] = 'on';
                }
                
                // Add X-Forwarded-Proto for some cases
                if ($values[4]) {
                    $serverVars['HTTP_X_FORWARDED_PROTO'] = $values[3] ? 'https' : 'http';
                }
                
                return $serverVars;
            },
            Generator\tuple(
                Generator\elements([
                    'localhost',
                    'localhost:8080',
                    'example.com',
                    'myapp.local',
                    '192.168.1.100'
                ]),
                Generator\elements(['80', '443', '8080', '3000']),
                Generator\elements([
                    '/index.php',
                    '/install/index.php',
                    '/public/index.php',
                    '/admin/dashboard.php',
                    '/karyalayportal/install/index.php',
                    '/karyalayportal/public/index.php',
                    '/app/dashboard.php'
                ]),
                Generator\bool(),
                Generator\bool()
            )
        );
    }

    /**
     * Generate script names with expected base paths
     * 
     * @return \Eris\Generator
     */
    private function scriptNameGenerator(): Generator
    {
        return Generator\elements([
            ['script_name' => '/index.php', 'expected_base' => ''],
            ['script_name' => '/install/index.php', 'expected_base' => ''],
            ['script_name' => '/public/index.php', 'expected_base' => ''],
            ['script_name' => '/admin/dashboard.php', 'expected_base' => ''],
            ['script_name' => '/app/dashboard.php', 'expected_base' => ''],
            ['script_name' => '/karyalayportal/index.php', 'expected_base' => '/karyalayportal'],
            ['script_name' => '/karyalayportal/install/index.php', 'expected_base' => '/karyalayportal'],
            ['script_name' => '/karyalayportal/public/index.php', 'expected_base' => '/karyalayportal'],
            ['script_name' => '/karyalayportal/admin/dashboard.php', 'expected_base' => '/karyalayportal'],
            ['script_name' => '/myapp/install/api/test.php', 'expected_base' => '/myapp'],
            ['script_name' => '/subdir/app/profile.php', 'expected_base' => '/subdir']
        ]);
    }

    /**
     * Get expected host from server variables
     * 
     * @param array $serverVars Server variables
     * @return string Expected host
     */
    private function getExpectedHost(array $serverVars): string
    {
        if (!empty($serverVars['HTTP_X_FORWARDED_HOST'])) {
            $hosts = explode(',', $serverVars['HTTP_X_FORWARDED_HOST']);
            return trim($hosts[0]);
        }
        
        if (!empty($serverVars['HTTP_HOST'])) {
            return $serverVars['HTTP_HOST'];
        }
        
        if (!empty($serverVars['SERVER_NAME'])) {
            $host = $serverVars['SERVER_NAME'];
            
            if (!empty($serverVars['SERVER_PORT'])) {
                $port = (int)$serverVars['SERVER_PORT'];
                if ($port !== 80 && $port !== 443) {
                    // Check if host already contains port
                    if (strpos($host, ':') === false) {
                        $host .= ':' . $port;
                    }
                }
            }
            
            return $host;
        }
        
        return 'localhost';
    }

    /**
     * Get expected protocol from server variables
     * 
     * @param array $serverVars Server variables
     * @return string Expected protocol ('http://' or 'https://')
     */
    private function getExpectedProtocol(array $serverVars): string
    {
        if (!empty($serverVars['HTTPS']) && $serverVars['HTTPS'] !== 'off') {
            return 'https://';
        }
        
        if (!empty($serverVars['HTTP_X_FORWARDED_PROTO'])) {
            if (strtolower($serverVars['HTTP_X_FORWARDED_PROTO']) === 'https') {
                return 'https://';
            }
        }
        
        if (!empty($serverVars['SERVER_PORT']) && (int)$serverVars['SERVER_PORT'] === 443) {
            return 'https://';
        }
        
        return 'http://';
    }
}
