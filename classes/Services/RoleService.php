<?php

namespace Karyalay\Services;

/**
 * Role Service
 * 
 * Manages roles and permissions for the system
 * Requirements: 13.6
 */
class RoleService
{
    /**
     * Define all available roles and their permissions
     * 
     * @return array Array of roles with their permissions
     */
    public static function getRoles(): array
    {
        return [
            'ADMIN' => [
                'label' => 'Admin',
                'description' => 'Full system access including user management',
                'permissions' => [
                    // User Management
                    'users.view',
                    'users.create',
                    'users.edit',
                    'users.delete',
                    'roles.manage',
                    
                    // Customer Management
                    'customers.view',
                    'customers.edit',
                    'customers.delete',
                    
                    // Subscription Management
                    'subscriptions.view',
                    'subscriptions.create',
                    'subscriptions.edit',
                    'subscriptions.delete',
                    
                    // Order Management
                    'orders.view',
                    'orders.edit',
                    
                    // Port Management
                    'ports.view',
                    'ports.create',
                    'ports.edit',
                    'ports.delete',
                    'ports.assign',
                    'ports.import',
                    
                    // Plan Management
                    'plans.view',
                    'plans.create',
                    'plans.edit',
                    'plans.delete',
                    
                    // Content Management
                    'content.view',
                    'content.create',
                    'content.edit',
                    'content.delete',
                    'content.publish',
                    
                    // Media Management
                    'media.view',
                    'media.upload',
                    'media.delete',
                    
                    // Support Management
                    'tickets.view',
                    'tickets.create',
                    'tickets.edit',
                    'tickets.assign',
                    'tickets.close',
                    'tickets.internal_notes',
                    
                    // Lead Management
                    'leads.view',
                    'leads.edit',
                    'leads.delete',
                    
                    // Settings Management
                    'settings.view',
                    'settings.edit',
                    
                    // Reports
                    'reports.view',
                ]
            ],
            'SUPPORT' => [
                'label' => 'Support',
                'description' => 'Access to tickets and customer support features',
                'permissions' => [
                    // Customer Management (Read-only)
                    'customers.view',
                    
                    // Subscription Management (Read-only)
                    'subscriptions.view',
                    
                    // Order Management (Read-only)
                    'orders.view',
                    
                    // Support Management
                    'tickets.view',
                    'tickets.create',
                    'tickets.edit',
                    'tickets.assign',
                    'tickets.close',
                    'tickets.internal_notes',
                    
                    // Lead Management (Read-only)
                    'leads.view',
                ]
            ],
            'SALES' => [
                'label' => 'Sales',
                'description' => 'Access to leads, customers, and orders',
                'permissions' => [
                    // Customer Management
                    'customers.view',
                    'customers.edit',
                    
                    // Subscription Management (Read-only)
                    'subscriptions.view',
                    
                    // Order Management
                    'orders.view',
                    'orders.edit',
                    
                    // Plan Management (Read-only)
                    'plans.view',
                    
                    // Lead Management
                    'leads.view',
                    'leads.edit',
                    'leads.delete',
                    
                    // Reports (Limited)
                    'reports.view',
                ]
            ],
            'CONTENT_EDITOR' => [
                'label' => 'Content Editor',
                'description' => 'Access to content management (blog, modules, features)',
                'permissions' => [
                    // Content Management
                    'content.view',
                    'content.create',
                    'content.edit',
                    'content.delete',
                    'content.publish',
                    
                    // Media Management
                    'media.view',
                    'media.upload',
                    'media.delete',
                ]
            ],
        ];
    }

    /**
     * Get permissions for a specific role
     * 
     * @param string $role Role name
     * @return array Array of permissions
     */
    public static function getPermissions(string $role): array
    {
        $roles = self::getRoles();
        return $roles[$role]['permissions'] ?? [];
    }

    /**
     * Check if a role has a specific permission
     * 
     * @param string $role Role name
     * @param string $permission Permission to check
     * @return bool True if role has permission, false otherwise
     */
    public static function hasPermission(string $role, string $permission): bool
    {
        $permissions = self::getPermissions($role);
        return in_array($permission, $permissions);
    }

    /**
     * Check if a user has a specific permission
     * 
     * @param array $user User data with 'role' key
     * @param string $permission Permission to check
     * @return bool True if user has permission, false otherwise
     */
    public static function userHasPermission(array $user, string $permission): bool
    {
        if (!isset($user['role'])) {
            return false;
        }
        
        return self::hasPermission($user['role'], $permission);
    }

    /**
     * Check if a user has any of the specified permissions
     * 
     * @param array $user User data with 'role' key
     * @param array $permissions Array of permissions to check
     * @return bool True if user has any of the permissions, false otherwise
     */
    public static function userHasAnyPermission(array $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::userHasPermission($user, $permission)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if a user has all of the specified permissions
     * 
     * @param array $user User data with 'role' key
     * @param array $permissions Array of permissions to check
     * @return bool True if user has all permissions, false otherwise
     */
    public static function userHasAllPermissions(array $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!self::userHasPermission($user, $permission)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get all available permissions in the system
     * 
     * @return array Array of all unique permissions
     */
    public static function getAllPermissions(): array
    {
        $roles = self::getRoles();
        $all_permissions = [];
        
        foreach ($roles as $role_data) {
            $all_permissions = array_merge($all_permissions, $role_data['permissions']);
        }
        
        return array_unique($all_permissions);
    }

    /**
     * Get role information
     * 
     * @param string $role Role name
     * @return array|null Role information or null if not found
     */
    public static function getRoleInfo(string $role): ?array
    {
        $roles = self::getRoles();
        return $roles[$role] ?? null;
    }

    /**
     * Validate if a role exists
     * 
     * @param string $role Role name
     * @return bool True if role exists, false otherwise
     */
    public static function isValidRole(string $role): bool
    {
        $roles = self::getRoles();
        return isset($roles[$role]);
    }

    /**
     * Get list of all role names
     * 
     * @return array Array of role names
     */
    public static function getRoleNames(): array
    {
        return array_keys(self::getRoles());
    }

    /**
     * Require permission for current user
     * Exits with 403 if user doesn't have permission
     * 
     * @param array $user User data
     * @param string $permission Required permission
     * @return void
     */
    public static function requirePermission(array $user, string $permission): void
    {
        if (!self::userHasPermission($user, $permission)) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Access denied. You do not have permission to perform this action.',
                'required_permission' => $permission
            ]);
            exit;
        }
    }

    /**
     * Require any of the specified permissions for current user
     * Exits with 403 if user doesn't have any of the permissions
     * 
     * @param array $user User data
     * @param array $permissions Required permissions (any)
     * @return void
     */
    public static function requireAnyPermission(array $user, array $permissions): void
    {
        if (!self::userHasAnyPermission($user, $permissions)) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Access denied. You do not have permission to perform this action.',
                'required_permissions' => $permissions
            ]);
            exit;
        }
    }

    /**
     * Require all of the specified permissions for current user
     * Exits with 403 if user doesn't have all permissions
     * 
     * @param array $user User data
     * @param array $permissions Required permissions (all)
     * @return void
     */
    public static function requireAllPermissions(array $user, array $permissions): void
    {
        if (!self::userHasAllPermissions($user, $permissions)) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Access denied. You do not have all required permissions.',
                'required_permissions' => $permissions
            ]);
            exit;
        }
    }
}

