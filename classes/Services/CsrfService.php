<?php

namespace Karyalay\Services;

/**
 * CSRF Protection Service
 * 
 * Provides CSRF token generation and validation
 */
class CsrfService
{
    private const TOKEN_LENGTH = 32;
    private const SESSION_KEY = 'csrf_token';

    /**
     * Generate a new CSRF token
     * 
     * @return string The generated CSRF token
     */
    public function generateToken(): string
    {
        $this->ensureSessionStarted();
        
        // Generate a cryptographically secure random token
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        
        // Store token in session
        $_SESSION[self::SESSION_KEY] = $token;
        
        return $token;
    }

    /**
     * Get the current CSRF token (or generate if not exists)
     * 
     * @return string The current CSRF token
     */
    public function getToken(): string
    {
        $this->ensureSessionStarted();
        
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return $this->generateToken();
        }
        
        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Validate a CSRF token
     * 
     * @param string|null $token The token to validate
     * @return bool True if valid, false otherwise
     */
    public function validateToken(?string $token): bool
    {
        $this->ensureSessionStarted();
        
        if (!$token) {
            return false;
        }
        
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return false;
        }
        
        // Use hash_equals to prevent timing attacks
        return hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    /**
     * Validate CSRF token from request
     * Checks POST data, headers, and query parameters
     * 
     * @return bool True if valid, false otherwise
     */
    public function validateRequest(): bool
    {
        // Check POST data first
        $token = $_POST['csrf_token'] ?? null;
        
        // Check headers if not in POST
        if (!$token) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        }
        
        // Check query parameters as fallback
        if (!$token) {
            $token = $_GET['csrf_token'] ?? null;
        }
        
        return $this->validateToken($token);
    }

    /**
     * Regenerate CSRF token
     * Useful after sensitive operations
     * 
     * @return string The new CSRF token
     */
    public function regenerateToken(): string
    {
        $this->ensureSessionStarted();
        
        // Clear old token
        unset($_SESSION[self::SESSION_KEY]);
        
        // Generate new token
        return $this->generateToken();
    }

    /**
     * Get CSRF token as hidden input field HTML
     * 
     * @return string HTML for hidden input field
     */
    public function getTokenField(): string
    {
        $token = $this->getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Get CSRF token as meta tag HTML
     * Useful for AJAX requests
     * 
     * @return string HTML for meta tag
     */
    public function getTokenMeta(): string
    {
        $token = $this->getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Ensure session is started
     * 
     * @return void
     */
    private function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
