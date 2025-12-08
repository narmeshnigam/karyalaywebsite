<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Services\InputSanitizationService;

/**
 * Property-based tests for input sanitization
 * 
 * Feature: karyalay-portal-system, Property 45: Input Sanitization
 * Validates: Requirements 13.5
 */
class InputSanitizationPropertyTest extends TestCase
{
    use TestTrait;

    private InputSanitizationService $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new InputSanitizationService();
    }

    /**
     * Property 45: Input Sanitization
     * 
     * For any user input, when it is processed, it should be sanitized 
     * to prevent XSS and SQL injection attacks.
     * 
     * Validates: Requirements 13.5
     * 
     * @test
     */
    public function sanitizedStringDoesNotContainScriptTags(): void
    {
        $this->forAll(
            Generator\string()
        )
        ->then(function ($input) {
            $sanitized = $this->sanitizer->sanitizeString($input);
            
            // Assert: Sanitized output should not contain script tags
            $this->assertStringNotContainsString(
                '<script',
                strtolower($sanitized),
                'Sanitized string should not contain script tags'
            );
            
            $this->assertStringNotContainsString(
                '</script>',
                strtolower($sanitized),
                'Sanitized string should not contain script tags'
            );
        });
    }

    /**
     * Property: Sanitized string does not contain HTML tags
     * 
     * @test
     */
    public function sanitizedStringDoesNotContainHtmlTags(): void
    {
        $this->forAll(
            Generator\string()
        )
        ->then(function ($input) {
            $sanitized = $this->sanitizer->sanitizeString($input);
            
            // Assert: Sanitized output should not contain HTML tags
            $this->assertStringNotContainsString(
                '<',
                $sanitized,
                'Sanitized string should not contain < character'
            );
            
            $this->assertStringNotContainsString(
                '>',
                $sanitized,
                'Sanitized string should not contain > character'
            );
        });
    }

    /**
     * Property: XSS payloads are neutralized
     * 
     * @test
     */
    public function xssPayloadsAreNeutralized(): void
    {
        $xssPayloads = [
            '<script>alert("XSS")</script>',
            '<img src=x onerror=alert("XSS")>',
            '<svg onload=alert("XSS")>',
            'javascript:alert("XSS")',
            '<iframe src="javascript:alert(\'XSS\')">',
            '<body onload=alert("XSS")>',
            '<input onfocus=alert("XSS") autofocus>',
            '<select onfocus=alert("XSS") autofocus>',
            '<textarea onfocus=alert("XSS") autofocus>',
            '<a href="javascript:alert(\'XSS\')">Click</a>',
            '<div style="background:url(javascript:alert(\'XSS\'))">',
        ];
        
        foreach ($xssPayloads as $payload) {
            $sanitized = $this->sanitizer->sanitizeString($payload);
            
            // Assert: Sanitized output should not contain dangerous patterns
            $this->assertStringNotContainsString(
                '<script',
                strtolower($sanitized),
                'XSS payload should be neutralized: ' . $payload
            );
            
            $this->assertStringNotContainsString(
                'javascript:',
                strtolower($sanitized),
                'JavaScript protocol should be removed: ' . $payload
            );
            
            $this->assertStringNotContainsString(
                'onerror',
                strtolower($sanitized),
                'Event handlers should be removed: ' . $payload
            );
            
            $this->assertStringNotContainsString(
                'onload',
                strtolower($sanitized),
                'Event handlers should be removed: ' . $payload
            );
        }
    }

    /**
     * Property: SQL injection patterns are escaped
     * 
     * @test
     */
    public function sqlInjectionPatternsAreEscaped(): void
    {
        $sqlPayloads = [
            "' OR '1'='1",
            "'; DROP TABLE users--",
            "1' UNION SELECT * FROM users--",
            "admin'--",
            "' OR 1=1--",
        ];
        
        foreach ($sqlPayloads as $payload) {
            $sanitized = $this->sanitizer->sanitizeString($payload);
            
            // Assert: Single quotes should be escaped or removed
            $this->assertStringNotContainsString(
                "'",
                $sanitized,
                'Single quotes should be escaped: ' . $payload
            );
        }
    }

    /**
     * Property: Email sanitization preserves valid emails
     * 
     * @test
     */
    public function emailSanitizationPreservesValidEmails(): void
    {
        $validEmails = [
            'test@example.com',
            'user.name@example.com',
            'user+tag@example.co.uk',
            'user_name@example-domain.com',
        ];
        
        foreach ($validEmails as $email) {
            $sanitized = $this->sanitizer->sanitizeEmail($email);
            
            // Assert: Valid email should be preserved
            $this->assertEquals(
                $email,
                $sanitized,
                'Valid email should be preserved: ' . $email
            );
        }
    }

    /**
     * Property: Email sanitization removes invalid characters
     * 
     * @test
     */
    public function emailSanitizationRemovesInvalidCharacters(): void
    {
        $invalidEmails = [
            'test<script>@example.com',
            'test@example.com<script>alert("XSS")</script>',
            'test"@example.com',
            "test'@example.com",
        ];
        
        foreach ($invalidEmails as $email) {
            $sanitized = $this->sanitizer->sanitizeEmail($email);
            
            // Assert: Dangerous characters should be removed
            $this->assertStringNotContainsString(
                '<',
                $sanitized,
                'Invalid characters should be removed from email'
            );
            
            $this->assertStringNotContainsString(
                '>',
                $sanitized,
                'Invalid characters should be removed from email'
            );
            
            $this->assertStringNotContainsString(
                '"',
                $sanitized,
                'Invalid characters should be removed from email'
            );
        }
    }

    /**
     * Property: URL sanitization preserves valid URLs
     * 
     * @test
     */
    public function urlSanitizationPreservesValidUrls(): void
    {
        $validUrls = [
            'https://example.com',
            'http://example.com/path',
            'https://example.com/path?query=value',
            'https://subdomain.example.com',
        ];
        
        foreach ($validUrls as $url) {
            $sanitized = $this->sanitizer->sanitizeUrl($url);
            
            // Assert: Valid URL should be preserved
            $this->assertEquals(
                $url,
                $sanitized,
                'Valid URL should be preserved: ' . $url
            );
        }
    }

    /**
     * Property: URL sanitization removes javascript protocol
     * 
     * @test
     */
    public function urlSanitizationRemovesJavascriptProtocol(): void
    {
        $dangerousUrls = [
            'javascript:alert("XSS")',
            'javascript:void(0)',
            'data:text/html,<script>alert("XSS")</script>',
        ];
        
        foreach ($dangerousUrls as $url) {
            $sanitized = $this->sanitizer->sanitizeUrl($url);
            
            // Assert: JavaScript protocol should be removed or URL should be empty
            $this->assertStringNotContainsString(
                'javascript:',
                strtolower($sanitized),
                'JavaScript protocol should be removed from URL'
            );
        }
    }

    /**
     * Property: Integer sanitization returns integers
     * 
     * @test
     */
    public function integerSanitizationReturnsIntegers(): void
    {
        $this->forAll(
            Generator\int()
        )
        ->then(function ($input) {
            $sanitized = $this->sanitizer->sanitizeInt($input);
            
            // Assert: Result should be an integer
            $this->assertIsInt(
                $sanitized,
                'Sanitized value should be an integer'
            );
            
            // Assert: Value should match input for valid integers
            $this->assertEquals(
                $input,
                $sanitized,
                'Valid integer should be preserved'
            );
        });
    }

    /**
     * Property: Integer sanitization handles non-numeric input
     * 
     * @test
     */
    public function integerSanitizationHandlesNonNumericInput(): void
    {
        $nonNumericInputs = [
            'abc',
            '<script>alert("XSS")</script>',
            '123abc',
            'abc123',
        ];
        
        foreach ($nonNumericInputs as $input) {
            $sanitized = $this->sanitizer->sanitizeInt($input);
            
            // Assert: Result should be an integer
            $this->assertIsInt(
                $sanitized,
                'Sanitized value should be an integer even for non-numeric input'
            );
        }
    }

    /**
     * Property: Float sanitization returns floats
     * 
     * @test
     */
    public function floatSanitizationReturnsFloats(): void
    {
        $this->forAll(
            Generator\float()
        )
        ->then(function ($input) {
            $sanitized = $this->sanitizer->sanitizeFloat($input);
            
            // Assert: Result should be a float
            $this->assertIsFloat(
                $sanitized,
                'Sanitized value should be a float'
            );
        });
    }

    /**
     * Property: HTML sanitization removes dangerous attributes
     * 
     * @test
     */
    public function htmlSanitizationRemovesDangerousAttributes(): void
    {
        $dangerousHtml = [
            '<a href="javascript:alert(\'XSS\')">Click</a>',
            '<img src=x onerror=alert("XSS")>',
            '<div onclick="alert(\'XSS\')">Click</div>',
            '<p style="background:url(javascript:alert(\'XSS\'))">Text</p>',
            '<a href="data:text/html,<script>alert(\'XSS\')</script>">Click</a>',
        ];
        
        foreach ($dangerousHtml as $html) {
            $sanitized = $this->sanitizer->sanitizeHtml($html);
            
            // Assert: Dangerous attributes should be removed
            $this->assertStringNotContainsString(
                'javascript:',
                strtolower($sanitized),
                'JavaScript protocol should be removed from HTML'
            );
            
            $this->assertStringNotContainsString(
                'onerror',
                strtolower($sanitized),
                'Event handlers should be removed from HTML'
            );
            
            $this->assertStringNotContainsString(
                'onclick',
                strtolower($sanitized),
                'Event handlers should be removed from HTML'
            );
            
            $this->assertStringNotContainsString(
                'data:',
                strtolower($sanitized),
                'Data protocol should be removed from HTML'
            );
        }
    }

    /**
     * Property: HTML sanitization preserves safe tags
     * 
     * @test
     */
    public function htmlSanitizationPreservesSafeTags(): void
    {
        $safeHtml = [
            '<p>This is a paragraph</p>',
            '<strong>Bold text</strong>',
            '<em>Italic text</em>',
            '<a href="https://example.com">Link</a>',
            '<ul><li>Item 1</li><li>Item 2</li></ul>',
        ];
        
        foreach ($safeHtml as $html) {
            $sanitized = $this->sanitizer->sanitizeHtml($html);
            
            // Assert: Safe HTML should be preserved (tags may be present)
            $this->assertNotEmpty(
                $sanitized,
                'Safe HTML should not be completely removed'
            );
        }
    }

    /**
     * Property: Array sanitization sanitizes all values
     * 
     * @test
     */
    public function arraySanitizationSanitizesAllValues(): void
    {
        $dangerousArray = [
            'name' => '<script>alert("XSS")</script>',
            'email' => 'test<script>@example.com',
            'age' => '25<script>',
        ];
        
        $sanitized = $this->sanitizer->sanitizeArray($dangerousArray);
        
        // Assert: All values should be sanitized
        foreach ($sanitized as $value) {
            $this->assertStringNotContainsString(
                '<script',
                strtolower($value),
                'Array values should be sanitized'
            );
        }
    }

    /**
     * Property: Nested array sanitization works recursively
     * 
     * @test
     */
    public function nestedArraySanitizationWorksRecursively(): void
    {
        $nestedArray = [
            'user' => [
                'name' => '<script>alert("XSS")</script>',
                'profile' => [
                    'bio' => '<script>alert("XSS")</script>',
                ],
            ],
        ];
        
        $sanitized = $this->sanitizer->sanitizeArray($nestedArray);
        
        // Assert: Nested values should be sanitized
        $this->assertStringNotContainsString(
            '<script',
            strtolower($sanitized['user']['name']),
            'Nested array values should be sanitized'
        );
        
        $this->assertStringNotContainsString(
            '<script',
            strtolower($sanitized['user']['profile']['bio']),
            'Deeply nested array values should be sanitized'
        );
    }

    /**
     * Property: Output escaping prevents XSS
     * 
     * @test
     */
    public function outputEscapingPreventsXss(): void
    {
        $this->forAll(
            Generator\string()
        )
        ->then(function ($input) {
            $escaped = $this->sanitizer->escapeOutput($input);
            
            // Assert: Escaped output should not contain unescaped HTML entities
            if (strpos($input, '<') !== false) {
                $this->assertStringNotContainsString(
                    '<',
                    $escaped,
                    'Output should escape < character'
                );
            }
            
            if (strpos($input, '>') !== false) {
                $this->assertStringNotContainsString(
                    '>',
                    $escaped,
                    'Output should escape > character'
                );
            }
        });
    }

    /**
     * Property: JavaScript escaping prevents XSS in JS context
     * 
     * @test
     */
    public function javascriptEscapingPreventsXss(): void
    {
        $dangerousStrings = [
            "'; alert('XSS'); //",
            '"; alert("XSS"); //',
            '</script><script>alert("XSS")</script>',
            '\'; alert(\'XSS\'); //',
        ];
        
        foreach ($dangerousStrings as $input) {
            $escaped = $this->sanitizer->escapeJs($input);
            
            // Assert: Escaped output should not contain unescaped quotes
            $this->assertStringNotContainsString(
                "';",
                $escaped,
                'JavaScript output should escape single quotes'
            );
            
            $this->assertStringNotContainsString(
                '";',
                $escaped,
                'JavaScript output should escape double quotes'
            );
            
            $this->assertStringNotContainsString(
                '</script>',
                strtolower($escaped),
                'JavaScript output should escape script tags'
            );
        }
    }

    /**
     * Property: Filename sanitization removes dangerous characters
     * 
     * @test
     */
    public function filenameSanitizationRemovesDangerousCharacters(): void
    {
        $dangerousFilenames = [
            '../../../etc/passwd',
            'file<script>.txt',
            'file|name.txt',
            'file;name.txt',
            'file&name.txt',
        ];
        
        foreach ($dangerousFilenames as $filename) {
            $sanitized = $this->sanitizer->sanitizeFilename($filename);
            
            // Assert: Dangerous characters should be removed
            $this->assertStringNotContainsString(
                '..',
                $sanitized,
                'Path traversal should be prevented'
            );
            
            $this->assertStringNotContainsString(
                '<',
                $sanitized,
                'HTML characters should be removed from filename'
            );
            
            $this->assertStringNotContainsString(
                '|',
                $sanitized,
                'Pipe character should be removed from filename'
            );
            
            $this->assertStringNotContainsString(
                ';',
                $sanitized,
                'Semicolon should be removed from filename'
            );
        }
    }

    /**
     * Property: Sanitization is idempotent
     * 
     * For any input, sanitizing twice should produce the same result as sanitizing once.
     * 
     * @test
     */
    public function sanitizationIsIdempotent(): void
    {
        $this->forAll(
            Generator\string()
        )
        ->then(function ($input) {
            $sanitized1 = $this->sanitizer->sanitizeString($input);
            $sanitized2 = $this->sanitizer->sanitizeString($sanitized1);
            
            // Assert: Sanitizing twice should produce same result
            $this->assertEquals(
                $sanitized1,
                $sanitized2,
                'Sanitization should be idempotent'
            );
        });
    }
}
