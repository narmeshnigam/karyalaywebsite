<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Services\CsrfService;
use Karyalay\Middleware\CsrfMiddleware;

/**
 * Property-based tests for CSRF token validation
 * 
 * Feature: karyalay-portal-system, Property 44: CSRF Token Validation
 * Validates: Requirements 13.4
 */
class CsrfTokenValidationPropertyTest extends TestCase
{
    use TestTrait;

    private CsrfService $csrfService;
    private CsrfMiddleware $csrfMiddleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->csrfService = new CsrfService();
        $this->csrfMiddleware = new CsrfMiddleware();
        
        // Start session for tests
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    protected function tearDown(): void
    {
        // Clean up session
        if (isset($_SESSION['csrf_token'])) {
            unset($_SESSION['csrf_token']);
        }
        parent::tearDown();
    }

    /**
     * Property 44: CSRF Token Validation
     * 
     * For any form submission, when the form is submitted, the CSRF token 
     * should be validated before processing the request.
     * 
     * Validates: Requirements 13.4
     * 
     * @test
     * @runInSeparateProcess
     */
    public function validCsrfTokenIsAccepted(): void
    {
        $this->forAll(
            Generator\nat()
        )
        ->then(function ($iteration) {
            // Generate a valid token
            $token = $this->csrfService->generateToken();
            
            // Validate the same token
            $isValid = $this->csrfService->validateToken($token);
            
            // Assert: Valid token should be accepted
            $this->assertTrue(
                $isValid,
                'Valid CSRF token should be accepted'
            );
        });
    }

    /**
     * Property: Invalid CSRF tokens are rejected
     * 
     * @test
     * @runInSeparateProcess
     */
    public function invalidCsrfTokensAreRejected(): void
    {
        $this->forAll(
            Generator\string()
        )
        ->when(function ($randomString) {
            // Only test with non-empty strings
            return strlen($randomString) > 0;
        })
        ->then(function ($randomString) {
            // Generate a valid token first
            $validToken = $this->csrfService->generateToken();
            
            // Try to validate a different token
            $isValid = $this->csrfService->validateToken($randomString);
            
            // Assert: Random token should be rejected (unless by extreme chance it matches)
            if ($randomString !== $validToken) {
                $this->assertFalse(
                    $isValid,
                    'Invalid CSRF token should be rejected'
                );
            }
        });
    }

    /**
     * Property: Null or empty tokens are rejected
     * 
     * @test
     * @runInSeparateProcess
     */
    public function nullOrEmptyTokensAreRejected(): void
    {
        // Generate a valid token first
        $this->csrfService->generateToken();
        
        // Test null token
        $this->assertFalse(
            $this->csrfService->validateToken(null),
            'Null token should be rejected'
        );
        
        // Test empty string token
        $this->assertFalse(
            $this->csrfService->validateToken(''),
            'Empty token should be rejected'
        );
    }

    /**
     * Property: Token validation is idempotent
     * 
     * For any valid token, multiple validation attempts should return
     * the same result (token remains valid).
     * 
     * @test
     * @runInSeparateProcess
     */
    public function tokenValidationIsIdempotent(): void
    {
        $this->forAll(
            Generator\nat()
        )
        ->then(function ($iteration) {
            // Generate a valid token
            $token = $this->csrfService->generateToken();
            
            // Validate multiple times
            $result1 = $this->csrfService->validateToken($token);
            $result2 = $this->csrfService->validateToken($token);
            $result3 = $this->csrfService->validateToken($token);
            
            // Assert: All validations should return same result
            $this->assertEquals(
                $result1,
                $result2,
                'Multiple validations should return same result'
            );
            $this->assertEquals(
                $result2,
                $result3,
                'Multiple validations should return same result'
            );
            $this->assertTrue($result1, 'Valid token should remain valid');
        });
    }

    /**
     * Property: Token generation produces unique tokens
     * 
     * @test
     * @runInSeparateProcess
     */
    public function tokenGenerationProducesUniqueTokens(): void
    {
        $tokens = [];
        
        // Generate multiple tokens
        for ($i = 0; $i < 100; $i++) {
            // Clear session to force new token generation
            unset($_SESSION['csrf_token']);
            $token = $this->csrfService->generateToken();
            $tokens[] = $token;
        }
        
        // Assert: All tokens should be unique
        $uniqueTokens = array_unique($tokens);
        $this->assertCount(
            100,
            $uniqueTokens,
            'Generated tokens should be unique'
        );
    }

    /**
     * Property: Token has sufficient entropy
     * 
     * @test
     * @runInSeparateProcess
     */
    public function tokenHasSufficientEntropy(): void
    {
        $this->forAll(
            Generator\nat()
        )
        ->then(function ($iteration) {
            // Clear session to force new token generation
            unset($_SESSION['csrf_token']);
            
            $token = $this->csrfService->generateToken();
            
            // Assert: Token should be at least 32 characters (16 bytes hex-encoded)
            $this->assertGreaterThanOrEqual(
                32,
                strlen($token),
                'CSRF token should have sufficient length for security'
            );
            
            // Assert: Token should be hexadecimal
            $this->assertMatchesRegularExpression(
                '/^[a-f0-9]+$/i',
                $token,
                'CSRF token should be hexadecimal'
            );
        });
    }

    /**
     * Property: GET requests don't require CSRF validation
     * 
     * @test
     * @runInSeparateProcess
     */
    public function getRequestsDontRequireCsrfValidation(): void
    {
        // Simulate GET request
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        // Should validate successfully even without token
        $isValid = $this->csrfMiddleware->validate('GET');
        
        $this->assertTrue(
            $isValid,
            'GET requests should not require CSRF validation'
        );
    }

    /**
     * Property: POST requests require CSRF validation
     * 
     * @test
     * @runInSeparateProcess
     */
    public function postRequestsRequireCsrfValidation(): void
    {
        // Simulate POST request without token
        $_SERVER['REQUEST_METHOD'] = 'POST';
        unset($_POST['csrf_token']);
        
        // Should fail validation
        $isValid = $this->csrfMiddleware->validate('POST');
        
        $this->assertFalse(
            $isValid,
            'POST requests without valid CSRF token should fail validation'
        );
    }

    /**
     * Property: POST requests with valid token pass validation
     * 
     * @test
     * @runInSeparateProcess
     */
    public function postRequestsWithValidTokenPassValidation(): void
    {
        $this->forAll(
            Generator\nat()
        )
        ->then(function ($iteration) {
            // Generate valid token
            $token = $this->csrfService->generateToken();
            
            // Simulate POST request with valid token
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST['csrf_token'] = $token;
            
            // Should pass validation
            $isValid = $this->csrfMiddleware->validate('POST');
            
            $this->assertTrue(
                $isValid,
                'POST requests with valid CSRF token should pass validation'
            );
            
            // Clean up
            unset($_POST['csrf_token']);
        });
    }

    /**
     * Property: Token can be validated from headers
     * 
     * @test
     * @runInSeparateProcess
     */
    public function tokenCanBeValidatedFromHeaders(): void
    {
        // Generate valid token
        $token = $this->csrfService->generateToken();
        
        // Simulate POST request with token in header
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
        unset($_POST['csrf_token']);
        
        // Should pass validation
        $isValid = $this->csrfService->validateRequest();
        
        $this->assertTrue(
            $isValid,
            'CSRF token in X-CSRF-TOKEN header should be validated'
        );
        
        // Clean up
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
    }

    /**
     * Property: Token regeneration creates new valid token
     * 
     * @test
     * @runInSeparateProcess
     */
    public function tokenRegenerationCreatesNewValidToken(): void
    {
        $this->forAll(
            Generator\nat()
        )
        ->then(function ($iteration) {
            // Generate initial token
            $token1 = $this->csrfService->generateToken();
            
            // Regenerate token
            $token2 = $this->csrfService->regenerateToken();
            
            // Assert: Tokens should be different
            $this->assertNotEquals(
                $token1,
                $token2,
                'Regenerated token should be different from original'
            );
            
            // Assert: Old token should no longer be valid
            $this->assertFalse(
                $this->csrfService->validateToken($token1),
                'Old token should be invalid after regeneration'
            );
            
            // Assert: New token should be valid
            $this->assertTrue(
                $this->csrfService->validateToken($token2),
                'New token should be valid after regeneration'
            );
        });
    }

    /**
     * Property: Token field HTML is properly escaped
     * 
     * @test
     * @runInSeparateProcess
     */
    public function tokenFieldHtmlIsProperlyEscaped(): void
    {
        $token = $this->csrfService->generateToken();
        $field = $this->csrfService->getTokenField();
        
        // Assert: Field should be a hidden input
        $this->assertStringContainsString(
            '<input type="hidden"',
            $field,
            'Token field should be a hidden input'
        );
        
        // Assert: Field should have name="csrf_token"
        $this->assertStringContainsString(
            'name="csrf_token"',
            $field,
            'Token field should have name="csrf_token"'
        );
        
        // Assert: Field should contain the token value
        $this->assertStringContainsString(
            $token,
            $field,
            'Token field should contain the token value'
        );
        
        // Assert: No script tags (XSS protection)
        $this->assertStringNotContainsString(
            '<script',
            $field,
            'Token field should not contain script tags'
        );
    }

    /**
     * Property: Token meta tag HTML is properly escaped
     * 
     * @test
     * @runInSeparateProcess
     */
    public function tokenMetaHtmlIsProperlyEscaped(): void
    {
        $token = $this->csrfService->generateToken();
        $meta = $this->csrfService->getTokenMeta();
        
        // Assert: Meta should be a meta tag
        $this->assertStringContainsString(
            '<meta',
            $meta,
            'Token meta should be a meta tag'
        );
        
        // Assert: Meta should have name="csrf-token"
        $this->assertStringContainsString(
            'name="csrf-token"',
            $meta,
            'Token meta should have name="csrf-token"'
        );
        
        // Assert: Meta should contain the token value
        $this->assertStringContainsString(
            $token,
            $meta,
            'Token meta should contain the token value'
        );
        
        // Assert: No script tags (XSS protection)
        $this->assertStringNotContainsString(
            '<script',
            $meta,
            'Token meta should not contain script tags'
        );
    }
}
