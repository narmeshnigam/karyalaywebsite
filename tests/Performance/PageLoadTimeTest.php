<?php

namespace Tests\Performance;

use PHPUnit\Framework\TestCase;

/**
 * Performance Test: Page Load Times
 * 
 * Tests that pages load within acceptable time limits.
 * Target: < 2.5 seconds for most pages
 */
class PageLoadTimeTest extends TestCase
{
    private const MAX_LOAD_TIME = 2.5; // seconds
    private const MAX_ADMIN_LOAD_TIME = 3.0; // seconds (admin pages can be slightly slower)
    
    private $baseUrl;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->baseUrl = getenv('APP_URL') ?: 'http://localhost';
    }
    
    /**
     * Measure page load time using cURL
     */
    private function measurePageLoadTime(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADER, true);
        
        $startTime = microtime(true);
        $response = curl_exec($ch);
        $endTime = microtime(true);
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        $connectTime = curl_getinfo($ch, CURLINFO_CONNECT_TIME);
        $downloadSize = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
        
        // curl_close is deprecated in PHP 8.5, handles are closed automatically
        
        return [
            'url' => $url,
            'http_code' => $httpCode,
            'total_time' => $totalTime,
            'connect_time' => $connectTime,
            'download_size' => $downloadSize,
            'measured_time' => $endTime - $startTime,
            'response' => $response
        ];
    }
    
    /**
     * Test home page load time
     */
    public function testHomePageLoadTime()
    {
        $result = $this->measurePageLoadTime($this->baseUrl . '/public/index.php');
        
        $this->assertEquals(200, $result['http_code'], 'Home page should return 200 OK');
        $this->assertLessThan(
            self::MAX_LOAD_TIME,
            $result['total_time'],
            sprintf(
                'Home page loaded in %.3fs, exceeds target of %.1fs',
                $result['total_time'],
                self::MAX_LOAD_TIME
            )
        );
        
        echo sprintf(
            "\n✓ Home page: %.3fs (target: %.1fs)\n",
            $result['total_time'],
            self::MAX_LOAD_TIME
        );
    }
    
    /**
     * Test pricing page load time
     */
    public function testPricingPageLoadTime()
    {
        $result = $this->measurePageLoadTime($this->baseUrl . '/public/pricing.php');
        
        $this->assertEquals(200, $result['http_code'], 'Pricing page should return 200 OK');
        $this->assertLessThan(
            self::MAX_LOAD_TIME,
            $result['total_time'],
            sprintf(
                'Pricing page loaded in %.3fs, exceeds target of %.1fs',
                $result['total_time'],
                self::MAX_LOAD_TIME
            )
        );
        
        echo sprintf(
            "\n✓ Pricing page: %.3fs (target: %.1fs)\n",
            $result['total_time'],
            self::MAX_LOAD_TIME
        );
    }
    
    /**
     * Test modules page load time
     */
    public function testModulesPageLoadTime()
    {
        $result = $this->measurePageLoadTime($this->baseUrl . '/public/modules.php');
        
        $this->assertEquals(200, $result['http_code'], 'Modules page should return 200 OK');
        $this->assertLessThan(
            self::MAX_LOAD_TIME,
            $result['total_time'],
            sprintf(
                'Modules page loaded in %.3fs, exceeds target of %.1fs',
                $result['total_time'],
                self::MAX_LOAD_TIME
            )
        );
        
        echo sprintf(
            "\n✓ Modules page: %.3fs (target: %.1fs)\n",
            $result['total_time'],
            self::MAX_LOAD_TIME
        );
    }
    
    /**
     * Test features page load time
     */
    public function testFeaturesPageLoadTime()
    {
        $result = $this->measurePageLoadTime($this->baseUrl . '/public/features.php');
        
        $this->assertEquals(200, $result['http_code'], 'Features page should return 200 OK');
        $this->assertLessThan(
            self::MAX_LOAD_TIME,
            $result['total_time'],
            sprintf(
                'Features page loaded in %.3fs, exceeds target of %.1fs',
                $result['total_time'],
                self::MAX_LOAD_TIME
            )
        );
        
        echo sprintf(
            "\n✓ Features page: %.3fs (target: %.1fs)\n",
            $result['total_time'],
            self::MAX_LOAD_TIME
        );
    }
    
    /**
     * Test blog page load time
     */
    public function testBlogPageLoadTime()
    {
        $result = $this->measurePageLoadTime($this->baseUrl . '/public/blog.php');
        
        $this->assertEquals(200, $result['http_code'], 'Blog page should return 200 OK');
        $this->assertLessThan(
            self::MAX_LOAD_TIME,
            $result['total_time'],
            sprintf(
                'Blog page loaded in %.3fs, exceeds target of %.1fs',
                $result['total_time'],
                self::MAX_LOAD_TIME
            )
        );
        
        echo sprintf(
            "\n✓ Blog page: %.3fs (target: %.1fs)\n",
            $result['total_time'],
            self::MAX_LOAD_TIME
        );
    }
    
    /**
     * Test contact page load time
     */
    public function testContactPageLoadTime()
    {
        $result = $this->measurePageLoadTime($this->baseUrl . '/public/contact.php');
        
        $this->assertEquals(200, $result['http_code'], 'Contact page should return 200 OK');
        $this->assertLessThan(
            self::MAX_LOAD_TIME,
            $result['total_time'],
            sprintf(
                'Contact page loaded in %.3fs, exceeds target of %.1fs',
                $result['total_time'],
                self::MAX_LOAD_TIME
            )
        );
        
        echo sprintf(
            "\n✓ Contact page: %.3fs (target: %.1fs)\n",
            $result['total_time'],
            self::MAX_LOAD_TIME
        );
    }
    
    /**
     * Test static asset load time (CSS)
     */
    public function testStaticAssetLoadTime()
    {
        $result = $this->measurePageLoadTime($this->baseUrl . '/assets/css/main.css');
        
        $this->assertEquals(200, $result['http_code'], 'CSS file should return 200 OK');
        $this->assertLessThan(
            1.0,
            $result['total_time'],
            sprintf(
                'Static asset loaded in %.3fs, exceeds target of 1.0s',
                $result['total_time']
            )
        );
        
        // Check cache headers
        $this->assertStringContainsString(
            'Cache-Control',
            $result['response'],
            'Static assets should have cache headers'
        );
        
        echo sprintf(
            "\n✓ Static asset (CSS): %.3fs (target: 1.0s)\n",
            $result['total_time']
        );
    }
    
    /**
     * Test multiple concurrent requests (simulating multiple users)
     */
    public function testConcurrentRequestPerformance()
    {
        $urls = [
            $this->baseUrl . '/public/index.php',
            $this->baseUrl . '/public/pricing.php',
            $this->baseUrl . '/public/modules.php',
            $this->baseUrl . '/public/features.php',
            $this->baseUrl . '/public/blog.php'
        ];
        
        $multiHandle = curl_multi_init();
        $handles = [];
        
        foreach ($urls as $url) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_multi_add_handle($multiHandle, $ch);
            $handles[] = $ch;
        }
        
        $startTime = microtime(true);
        
        // Execute all requests
        $running = null;
        do {
            curl_multi_exec($multiHandle, $running);
            curl_multi_select($multiHandle);
        } while ($running > 0);
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        // Clean up
        foreach ($handles as $ch) {
            curl_multi_remove_handle($multiHandle, $ch);
            // curl_close is deprecated in PHP 8.5, handles are closed automatically
        }
        curl_multi_close($multiHandle);
        
        // Concurrent requests should complete faster than sequential
        $this->assertLessThan(
            self::MAX_LOAD_TIME * count($urls),
            $totalTime,
            sprintf(
                'Concurrent requests took %.3fs, should be faster than sequential (%.1fs)',
                $totalTime,
                self::MAX_LOAD_TIME * count($urls)
            )
        );
        
        echo sprintf(
            "\n✓ Concurrent requests (%d pages): %.3fs\n",
            count($urls),
            $totalTime
        );
    }
}
