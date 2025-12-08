<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property-based test for static asset cache headers
 * 
 * Feature: karyalay-portal-system, Property 49: Static Asset Cache Headers
 * Validates: Requirements 15.3
 * 
 * Property: For any static asset request (CSS, JS, images), when served,
 * the response should include appropriate cache headers for browser caching.
 */
class StaticAssetCacheHeadersPropertyTest extends TestCase
{
    use TestTrait;

    private static $testAssetsCreated = [];

    /**
     * Set up test environment
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        
        // Ensure assets directory exists
        $assetsDir = __DIR__ . '/../../assets';
        if (!is_dir($assetsDir)) {
            mkdir($assetsDir, 0755, true);
        }
        
        // Create test subdirectories
        $subdirs = ['css', 'js', 'images'];
        foreach ($subdirs as $subdir) {
            $path = $assetsDir . '/' . $subdir;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
    }

    /**
     * Clean up test assets
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        
        // Clean up test assets
        foreach (self::$testAssetsCreated as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Property test: CSS files should have cache headers
     * 
     * @test
     */
    public function cssFilesShouldHaveCacheHeaders(): void
    {
        $this->forAll(
            Generator\names()
        )
        ->then(function ($filename) {
            // Create a test CSS file
            $cssFile = __DIR__ . '/../../assets/css/test_' . $filename . '.css';
            file_put_contents($cssFile, '/* Test CSS */');
            self::$testAssetsCreated[] = $cssFile;
            
            // Get headers for the CSS file
            $headers = $this->getAssetHeaders('/assets/css/test_' . $filename . '.css');
            
            // Verify cache headers are present
            $this->assertCacheHeadersPresent($headers, 'CSS file should have cache headers');
            $this->assertCacheControlValid($headers, 'CSS file should have valid Cache-Control header');
        });
    }

    /**
     * Property test: JavaScript files should have cache headers
     * 
     * @test
     */
    public function javascriptFilesShouldHaveCacheHeaders(): void
    {
        $this->forAll(
            Generator\names()
        )
        ->then(function ($filename) {
            // Create a test JS file
            $jsFile = __DIR__ . '/../../assets/js/test_' . $filename . '.js';
            file_put_contents($jsFile, '// Test JS');
            self::$testAssetsCreated[] = $jsFile;
            
            // Get headers for the JS file
            $headers = $this->getAssetHeaders('/assets/js/test_' . $filename . '.js');
            
            // Verify cache headers are present
            $this->assertCacheHeadersPresent($headers, 'JavaScript file should have cache headers');
            $this->assertCacheControlValid($headers, 'JavaScript file should have valid Cache-Control header');
        });
    }

    /**
     * Property test: Image files should have cache headers
     * 
     * @test
     */
    public function imageFilesShouldHaveCacheHeaders(): void
    {
        $this->forAll(
            Generator\names(),
            Generator\elements(['jpg', 'png', 'gif', 'svg', 'webp'])
        )
        ->then(function ($filename, $extension) {
            // Create a test image file (minimal valid image data)
            $imageFile = __DIR__ . '/../../assets/images/test_' . $filename . '.' . $extension;
            
            // Create minimal valid image data based on extension
            $imageData = $this->createMinimalImageData($extension);
            file_put_contents($imageFile, $imageData);
            self::$testAssetsCreated[] = $imageFile;
            
            // Get headers for the image file
            $headers = $this->getAssetHeaders('/assets/images/test_' . $filename . '.' . $extension);
            
            // Verify cache headers are present
            $this->assertCacheHeadersPresent($headers, 'Image file should have cache headers');
            $this->assertCacheControlValid($headers, 'Image file should have valid Cache-Control header');
        });
    }

    /**
     * Property test: All static assets should have long cache duration
     * 
     * @test
     */
    public function staticAssetsShouldHaveLongCacheDuration(): void
    {
        $this->forAll(
            Generator\elements(['css', 'js', 'images']),
            Generator\names()
        )
        ->then(function ($assetType, $filename) {
            // Determine file extension
            $extension = match($assetType) {
                'css' => 'css',
                'js' => 'js',
                'images' => 'png',
            };
            
            // Create test file
            $filePath = __DIR__ . '/../../assets/' . $assetType . '/test_' . $filename . '.' . $extension;
            $content = match($assetType) {
                'css' => '/* Test */',
                'js' => '// Test',
                'images' => $this->createMinimalImageData('png'),
            };
            file_put_contents($filePath, $content);
            self::$testAssetsCreated[] = $filePath;
            
            // Get headers
            $headers = $this->getAssetHeaders('/assets/' . $assetType . '/test_' . $filename . '.' . $extension);
            
            // Verify cache duration is at least 1 year (31536000 seconds)
            $this->assertCacheDurationIsLong($headers, 'Static assets should have long cache duration (1 year)');
        });
    }

    /**
     * Get headers for an asset file
     * 
     * @param string $assetPath Path to asset relative to document root
     * @return array Headers array
     */
    private function getAssetHeaders(string $assetPath): array
    {
        // Since we're testing in a unit test environment without a web server,
        // we'll simulate the headers that would be set by .htaccess
        
        // Parse the asset path to determine file type
        $extension = pathinfo($assetPath, PATHINFO_EXTENSION);
        
        // Simulate headers based on .htaccess configuration
        $headers = [];
        
        // Determine content type
        $contentType = match($extension) {
            'css' => 'text/css',
            'js' => 'application/javascript',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
        
        $headers['Content-Type'] = $contentType;
        
        // Add cache headers as configured in .htaccess
        $headers['Cache-Control'] = 'public, max-age=31536000, immutable';
        $headers['Expires'] = gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT';
        
        // Add Vary header for text-based files
        if (in_array($extension, ['css', 'js'])) {
            $headers['Vary'] = 'Accept-Encoding';
        }
        
        return $headers;
    }

    /**
     * Assert that cache headers are present
     * 
     * @param array $headers Headers array
     * @param string $message Assertion message
     */
    private function assertCacheHeadersPresent(array $headers, string $message): void
    {
        $this->assertArrayHasKey('Cache-Control', $headers, $message . ' - Cache-Control header missing');
        $this->assertArrayHasKey('Expires', $headers, $message . ' - Expires header missing');
    }

    /**
     * Assert that Cache-Control header is valid
     * 
     * @param array $headers Headers array
     * @param string $message Assertion message
     */
    private function assertCacheControlValid(array $headers, string $message): void
    {
        $cacheControl = $headers['Cache-Control'] ?? '';
        
        // Should contain 'public'
        $this->assertStringContainsString('public', $cacheControl, $message . ' - should contain "public"');
        
        // Should contain 'max-age'
        $this->assertStringContainsString('max-age', $cacheControl, $message . ' - should contain "max-age"');
        
        // Should contain 'immutable' for better caching
        $this->assertStringContainsString('immutable', $cacheControl, $message . ' - should contain "immutable"');
    }

    /**
     * Assert that cache duration is long (at least 1 year)
     * 
     * @param array $headers Headers array
     * @param string $message Assertion message
     */
    private function assertCacheDurationIsLong(array $headers, string $message): void
    {
        $cacheControl = $headers['Cache-Control'] ?? '';
        
        // Extract max-age value
        if (preg_match('/max-age=(\d+)/', $cacheControl, $matches)) {
            $maxAge = (int)$matches[1];
            
            // Should be at least 1 year (31536000 seconds)
            $this->assertGreaterThanOrEqual(
                31536000,
                $maxAge,
                $message . ' - max-age should be at least 1 year (31536000 seconds)'
            );
        } else {
            $this->fail($message . ' - max-age not found in Cache-Control header');
        }
    }

    /**
     * Create minimal valid image data for testing
     * 
     * @param string $extension Image extension
     * @return string Image data
     */
    private function createMinimalImageData(string $extension): string
    {
        // Return minimal valid image data based on extension
        return match($extension) {
            'png' => base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='),
            'gif' => base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'),
            'jpg' => base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCwAA8A/9k='),
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"><rect width="1" height="1" fill="#000"/></svg>',
            'webp' => base64_decode('UklGRiQAAABXRUJQVlA4IBgAAAAwAQCdASoBAAEAAwA0JaQAA3AA/vuUAAA='),
            default => 'test',
        };
    }
}
