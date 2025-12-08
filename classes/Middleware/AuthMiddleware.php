<?php

namespace Karyalay\Middleware;

use Karyalay\Services\AuthService;
use Karyalay\Services\RoleService;

/**
 * Authentication Middleware
 * 
 * Protects routes by requiring authentication
 */
class AuthMiddleware
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * Handle authentication check
     * 
     * @param string|null $sessionToken Session token from cookie or header
     * @param string $redirectUrl URL to redirect to if not authenticated
     * @return array|null Returns user data if authenticated, null and redirects if not
     */
    public function handle(?string $sessionToken, string $redirectUrl = '/login.php'): ?array
    {
        if (!$sessionToken) {
            $this->redirectToLogin($redirectUrl);
            return null;
        }

        $result = $this->authService->validateSession($sessionToken);

        if (!$result) {
            $this->redirectToLogin($redirectUrl);
            return null;
        }

        return $result['user'];
    }

    /**
     * Check if user has required role
     * 
     * @param array $user User data
     * @param string|array $requiredRoles Required role(s)
     * @return bool Returns true if user has role, false otherwise
     */
    public function hasRole(array $user, $requiredRoles): bool
    {
        $userRole = $user['role'] ?? '';
        $roles = is_array($requiredRoles) ? $requiredRoles : [$requiredRoles];
        
        return in_array($userRole, $roles);
    }

    /**
     * Require specific role
     * 
     * @param array $user User data
     * @param string|array $requiredRoles Required role(s)
     * @return void Exits with 403 if user doesn't have role
     */
    public function requireRole(array $user, $requiredRoles): void
    {
        if (!$this->hasRole($user, $requiredRoles)) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Access denied. You do not have permission to access this resource.'
            ]);
            exit;
        }
    }

    /**
     * Redirect to login page
     * 
     * @param string $loginUrl Login page URL
     * @return void
     */
    private function redirectToLogin(string $loginUrl): void
    {
        // Store current URL for redirect after login
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
        
        header('Location: ' . $loginUrl);
        exit;
    }

    /**
     * Validate session token and return user
     * 
     * @param string $sessionToken Session token
     * @return array|false Returns user data if valid, false otherwise
     */
    public function validateSession(string $sessionToken)
    {
        $result = $this->authService->validateSession($sessionToken);
        
        if (!$result) {
            return false;
        }
        
        return $result['user'];
    }

    /**
     * Guard for customer portal routes
     * Requires authentication and CUSTOMER or ADMIN role
     * 
     * @param string|null $sessionToken Session token from cookie or header
     * @param string $redirectUrl URL to redirect to if not authenticated
     * @return array Returns authenticated user data
     */
    public function guardCustomerPortal(?string $sessionToken, string $redirectUrl = '/login.php'): array
    {
        $user = $this->handle($sessionToken, $redirectUrl);
        
        if (!$user) {
            exit;
        }

        // Allow both CUSTOMER and ADMIN roles to access customer portal
        if (!$this->hasRole($user, ['CUSTOMER', 'ADMIN'])) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Access denied. Customer account required.'
            ]);
            exit;
        }

        return $user;
    }

    /**
     * Guard for admin routes
     * Requires authentication and ADMIN role
     * 
     * @param string|null $sessionToken Session token from cookie or header
     * @param string $redirectUrl URL to redirect to if not authenticated
     * @return array Returns authenticated admin user data
     */
    public function guardAdmin(?string $sessionToken, string $redirectUrl = '/login.php'): array
    {
        $user = $this->handle($sessionToken, $redirectUrl);
        
        if (!$user) {
            exit;
        }

        // Only ADMIN role can access admin routes
        $this->requireRole($user, 'ADMIN');

        return $user;
    }

    /**
     * Guard for support staff routes
     * Requires authentication and ADMIN or SUPPORT role
     * 
     * @param string|null $sessionToken Session token from cookie or header
     * @param string $redirectUrl URL to redirect to if not authenticated
     * @return array Returns authenticated support staff user data
     */
    public function guardSupport(?string $sessionToken, string $redirectUrl = '/login.php'): array
    {
        $user = $this->handle($sessionToken, $redirectUrl);
        
        if (!$user) {
            exit;
        }

        // Allow ADMIN and SUPPORT roles
        if (!$this->hasRole($user, ['ADMIN', 'SUPPORT'])) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Access denied. Support staff access required.'
            ]);
            exit;
        }

        return $user;
    }

    /**
     * Guard for content editor routes
     * Requires authentication and ADMIN or CONTENT_EDITOR role
     * 
     * @param string|null $sessionToken Session token from cookie or header
     * @param string $redirectUrl URL to redirect to if not authenticated
     * @return array Returns authenticated content editor user data
     */
    public function guardContentEditor(?string $sessionToken, string $redirectUrl = '/login.php'): array
    {
        $user = $this->handle($sessionToken, $redirectUrl);
        
        if (!$user) {
            exit;
        }

        // Allow ADMIN and CONTENT_EDITOR roles
        if (!$this->hasRole($user, ['ADMIN', 'CONTENT_EDITOR'])) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Access denied. Content editor access required.'
            ]);
            exit;
        }

        return $user;
    }

    /**
     * Check if user has a specific permission
     * 
     * @param array $user User data
     * @param string $permission Permission to check
     * @return bool Returns true if user has permission, false otherwise
     */
    public function hasPermission(array $user, string $permission): bool
    {
        return RoleService::userHasPermission($user, $permission);
    }

    /**
     * Require specific permission
     * 
     * @param array $user User data
     * @param string $permission Required permission
     * @return void Exits with 403 if user doesn't have permission
     */
    public function requirePermission(array $user, string $permission): void
    {
        RoleService::requirePermission($user, $permission);
    }

    /**
     * Require any of the specified permissions
     * 
     * @param array $user User data
     * @param array $permissions Required permissions (any)
     * @return void Exits with 403 if user doesn't have any permission
     */
    public function requireAnyPermission(array $user, array $permissions): void
    {
        RoleService::requireAnyPermission($user, $permissions);
    }

    /**
     * Require all of the specified permissions
     * 
     * @param array $user User data
     * @param array $permissions Required permissions (all)
     * @return void Exits with 403 if user doesn't have all permissions
     */
    public function requireAllPermissions(array $user, array $permissions): void
    {
        RoleService::requireAllPermissions($user, $permissions);
    }
}

