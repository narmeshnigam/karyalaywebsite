<?php

namespace Karyalay\Middleware;

use Karyalay\Services\CsrfService;

/**
 * CSRF Protection Middleware
 * 
 * Validates CSRF tokens on state-changing requests
 */
class CsrfMiddleware
{
    private CsrfService $csrfService;
    
    /**
     * HTTP methods that require CSRF protection
     */
    private const PROTECTED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct()
    {
        $this->csrfService = new CsrfService();
    }

    /**
     * Validate CSRF token for state-changing requests
     * 
     * @param string|null $method HTTP method (defaults to $_SERVER['REQUEST_METHOD'])
     * @return bool True if valid or not required, false if invalid
     */
    public function validate(?string $method = null): bool
    {
        $method = $method ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        
        // Only validate for state-changing methods
        if (!in_array(strtoupper($method), self::PROTECTED_METHODS)) {
            return true;
        }
        
        return $this->csrfService->validateRequest();
    }

    /**
     * Require valid CSRF token or exit with 403
     * 
     * @param string|null $method HTTP method (defaults to $_SERVER['REQUEST_METHOD'])
     * @return void Exits with 403 if invalid
     */
    public function requireValidToken(?string $method = null): void
    {
        if (!$this->validate($method)) {
            $this->sendForbiddenResponse();
        }
    }

    /**
     * Get CSRF token
     * 
     * @return string The current CSRF token
     */
    public function getToken(): string
    {
        return $this->csrfService->getToken();
    }

    /**
     * Get CSRF token as hidden input field
     * 
     * @return string HTML for hidden input field
     */
    public function getTokenField(): string
    {
        return $this->csrfService->getTokenField();
    }

    /**
     * Get CSRF token as meta tag
     * 
     * @return string HTML for meta tag
     */
    public function getTokenMeta(): string
    {
        return $this->csrfService->getTokenMeta();
    }

    /**
     * Send 403 Forbidden response
     * 
     * @return void
     */
    private function sendForbiddenResponse(): void
    {
        http_response_code(403);
        
        // Check if request expects JSON
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (strpos($acceptHeader, 'application/json') !== false) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'CSRF token validation failed. Please refresh the page and try again.'
            ]);
        } else {
            echo 'CSRF token validation failed. Please refresh the page and try again.';
        }
        
        exit;
    }
}
