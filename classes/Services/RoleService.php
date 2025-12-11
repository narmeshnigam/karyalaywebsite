<?php

namespace Karyalay\Services;

use Karyalay\Database\Connection;
use PDO;
use PDOException;

/**
 * Role Service
 * 
 * Manages roles and permissions for the system
 * Supports multiple roles per user
 */
class RoleService
{
    /**
     * Define all available roles and their permissions
     * 
     * Role Hierarchy:
     * - ADMIN: Full system access
     * - SUPPORT: Tickets only
     * - INFRASTRUCTURE: Plans, Ports
     * - SALES: Leads only
     * - SALES_MANAGER: Leads, Customers (view only, no drill-down)
     * - OPERATIONS: Customers, Orders, Invoices, Subscriptions
     * - CONTENT_MANAGER: All content management sections
     * - CUSTOMER: Default role for all users (portal access)
     * 
     * @return array Array of roles with their permissions
     */
    public static function getRoles(): array
    {
        return [
            'ADMIN' => [
                'label' => 'Administrator',
                'description' => 'Full system access including all settings and user management',
                'color' => 'danger',
                'permissions' => [
                    // User Management
                    'users.view',
                    'users.create',
                    'users.edit',
                    'users.delete',
                    'roles.manage',
                    
                    // Customer Management
                    'customers.view',
                    'customers.view_details',
                    'customers.edit',
                    'customers.delete',
                    
                    // Subscription Management
                    'subscriptions.view',
                    'subscriptions.view_details',
                    'subscriptions.create',
                    'subscriptions.edit',
                    'subscriptions.delete',
                    
                    // Order Management
                    'orders.view',
                    'orders.view_details',
                    'orders.edit',
                    
                    // Invoice Management
                    'invoices.view',
                    'invoices.view_details',
                    'invoices.create',
                    
                    // Port Management
                    'ports.view',
                    'ports.view_details',
                    'ports.create',
                    'ports.edit',
                    'ports.delete',
                    'ports.assign',
                    'ports.import',
                    
                    // Plan Management
                    'plans.view',
                    'plans.view_details',
                    'plans.create',
                    'plans.edit',
                    'plans.delete',
                    
                    // Content Management
                    'content.view',
                    'content.create',
                    'content.edit',
                    'content.delete',
                    'content.publish',
                    'hero_slides.manage',
                    'solutions.manage',
                    'why_choose.manage',
                    'testimonials.manage',
                    'blog.manage',
                    'case_studies.manage',
                    'about.manage',
                    'legal.manage',
                    'faqs.manage',
                    
                    // Media Management
                    'media.view',
                    'media.upload',
                    'media.delete',
                    
                    // Support Management
                    'tickets.view',
                    'tickets.view_details',
                    'tickets.create',
                    'tickets.edit',
                    'tickets.assign',
                    'tickets.close',
                    'tickets.internal_notes',
                    
                    // Lead Management
                    'leads.view',
                    'leads.view_details',
                    'leads.edit',
                    'leads.delete',
                    
                    // Settings Management (ADMIN ONLY)
                    'settings.view',
                    'settings.edit',
                    'settings.smtp',
                    'settings.payment',
                    'settings.localisation',
                    'settings.general',
                    
                    // Reports & Dashboard
                    'dashboard.view',
                    'reports.view',
                    
                    // Admin Panel Access
                    'admin.access',
                ]
            ],
            
            'SUPPORT' => [
                'label' => 'Support',
                'description' => 'Access to support tickets management only',
                'color' => 'info',
                'permissions' => [
                    // Support Management - Full Access
                    'tickets.view',
                    'tickets.view_details',
                    'tickets.create',
                    'tickets.edit',
                    'tickets.assign',
                    'tickets.close',
                    'tickets.internal_notes',
                    
                    // Dashboard (limited)
                    'dashboard.view',
                    
                    // Admin Panel Access
                    'admin.access',
                ]
            ],
            
            'INFRASTRUCTURE' => [
                'label' => 'Infrastructure',
                'description' => 'Access to Plans and Ports management',
                'color' => 'purple',
                'permissions' => [
                    // Port Management - Full Access
                    'ports.view',
                    'ports.view_details',
                    'ports.create',
                    'ports.edit',
                    'ports.delete',
                    'ports.assign',
                    'ports.import',
                    
                    // Plan Management - Full Access
                    'plans.view',
                    'plans.view_details',
                    'plans.create',
                    'plans.edit',
                    'plans.delete',
                    
                    // Dashboard (limited)
                    'dashboard.view',
                    
                    // Admin Panel Access
                    'admin.access',
                ]
            ],
            
            'SALES' => [
                'label' => 'Sales',
                'description' => 'Access to Leads management only',
                'color' => 'success',
                'permissions' => [
                    // Lead Management - Full Access
                    'leads.view',
                    'leads.view_details',
                    'leads.edit',
                    'leads.delete',
                    
                    // Dashboard (limited)
                    'dashboard.view',
                    
                    // Admin Panel Access
                    'admin.access',
                ]
            ],
            
            'SALES_MANAGER' => [
                'label' => 'Sales Manager',
                'description' => 'Access to Leads and Customers (view only, no drill-down to related items)',
                'color' => 'teal',
                'permissions' => [
                    // Lead Management - Full Access
                    'leads.view',
                    'leads.view_details',
                    'leads.edit',
                    'leads.delete',
                    
                    // Customer Management - View Only (no drill-down)
                    'customers.view',
                    'customers.view_details',
                    // Note: Does NOT have subscriptions.view_details, orders.view_details, etc.
                    
                    // Dashboard (limited)
                    'dashboard.view',
                    
                    // Admin Panel Access
                    'admin.access',
                ]
            ],
            
            'OPERATIONS' => [
                'label' => 'Operations',
                'description' => 'Access to Customers, Orders, Invoices, and Subscriptions',
                'color' => 'orange',
                'permissions' => [
                    // Customer Management - Full Access
                    'customers.view',
                    'customers.view_details',
                    'customers.edit',
                    
                    // Order Management - Full Access
                    'orders.view',
                    'orders.view_details',
                    'orders.edit',
                    
                    // Invoice Management - Full Access
                    'invoices.view',
                    'invoices.view_details',
                    'invoices.create',
                    
                    // Subscription Management - Full Access
                    'subscriptions.view',
                    'subscriptions.view_details',
                    'subscriptions.create',
                    'subscriptions.edit',
                    
                    // Dashboard (limited)
                    'dashboard.view',
                    
                    // Admin Panel Access
                    'admin.access',
                ]
            ],
            
            'CONTENT_MANAGER' => [
                'label' => 'Content Manager',
                'description' => 'Access to all content management sections',
                'color' => 'warning',
                'permissions' => [
                    // Content Management - Full Access
                    'content.view',
                    'content.create',
                    'content.edit',
                    'content.delete',
                    'content.publish',
                    'hero_slides.manage',
                    'solutions.manage',
                    'why_choose.manage',
                    'testimonials.manage',
                    'blog.manage',
                    'case_studies.manage',
                    'about.manage',
                    'legal.manage',
                    'faqs.manage',
                    
                    // Media Management - Full Access
                    'media.view',
                    'media.upload',
                    'media.delete',
                    
                    // Dashboard (limited)
                    'dashboard.view',
                    
                    // Admin Panel Access
                    'admin.access',
                ]
            ],
            
            'CUSTOMER' => [
                'label' => 'Customer',
                'description' => 'Default role for all users - customer portal access',
                'color' => 'secondary',
                'permissions' => [
                    // Customer Portal Access
                    'portal.access',
                    'portal.subscriptions',
                    'portal.orders',
                    'portal.invoices',
                    'portal.tickets',
                    'portal.profile',
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
     * Get all roles for a user from the database
     * 
     * @param string $userId User ID
     * @return array Array of role names
     */
    public static function getUserRoles(string $userId): array
    {
        try {
            $db = Connection::getInstance();
            $stmt = $db->prepare("SELECT role FROM user_roles WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $userId]);
            $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // If no roles found in user_roles table, fall back to users.role column
            if (empty($roles)) {
                $stmt = $db->prepare("SELECT role FROM users WHERE id = :user_id");
                $stmt->execute([':user_id' => $userId]);
                $role = $stmt->fetchColumn();
                if ($role) {
                    $roles = [$role];
                    // Also ensure CUSTOMER role is included
                    if ($role !== 'CUSTOMER') {
                        $roles[] = 'CUSTOMER';
                    }
                }
            }
            
            return $roles;
        } catch (PDOException $e) {
            error_log('Failed to get user roles: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all permissions for a user based on all their roles
     * 
     * @param string $userId User ID
     * @return array Array of unique permissions
     */
    public static function getUserPermissions(string $userId): array
    {
        $roles = self::getUserRoles($userId);
        $permissions = [];
        
        foreach ($roles as $role) {
            $rolePermissions = self::getPermissions($role);
            $permissions = array_merge($permissions, $rolePermissions);
        }
        
        return array_unique($permissions);
    }

    /**
     * Check if a user has a specific permission
     * 
     * @param string $userId User ID
     * @param string $permission Permission to check
     * @return bool True if user has permission, false otherwise
     */
    public static function userHasPermission(string $userId, string $permission): bool
    {
        $permissions = self::getUserPermissions($userId);
        return in_array($permission, $permissions);
    }

    /**
     * Check if a user has any of the specified permissions
     * 
     * @param string $userId User ID
     * @param array $permissions Array of permissions to check
     * @return bool True if user has any of the permissions, false otherwise
     */
    public static function userHasAnyPermission(string $userId, array $permissions): bool
    {
        $userPermissions = self::getUserPermissions($userId);
        
        foreach ($permissions as $permission) {
            if (in_array($permission, $userPermissions)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if a user has all of the specified permissions
     * 
     * @param string $userId User ID
     * @param array $permissions Array of permissions to check
     * @return bool True if user has all permissions, false otherwise
     */
    public static function userHasAllPermissions(string $userId, array $permissions): bool
    {
        $userPermissions = self::getUserPermissions($userId);
        
        foreach ($permissions as $permission) {
            if (!in_array($permission, $userPermissions)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Check if a user has a specific role
     * 
     * @param string $userId User ID
     * @param string $role Role to check
     * @return bool True if user has role, false otherwise
     */
    public static function userHasRole(string $userId, string $role): bool
    {
        $roles = self::getUserRoles($userId);
        return in_array($role, $roles);
    }

    /**
     * Check if a user has any of the specified roles
     * 
     * @param string $userId User ID
     * @param array $roles Array of roles to check
     * @return bool True if user has any of the roles, false otherwise
     */
    public static function userHasAnyRole(string $userId, array $roles): bool
    {
        $userRoles = self::getUserRoles($userId);
        
        foreach ($roles as $role) {
            if (in_array($role, $userRoles)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Assign a role to a user
     * 
     * @param string $userId User ID
     * @param string $role Role to assign
     * @param string|null $assignedBy User ID of who assigned the role
     * @return bool True on success, false on failure
     */
    public static function assignRole(string $userId, string $role, ?string $assignedBy = null): bool
    {
        try {
            $db = Connection::getInstance();
            $stmt = $db->prepare("
                INSERT INTO user_roles (user_id, role, assigned_by)
                VALUES (:user_id, :role, :assigned_by)
                ON DUPLICATE KEY UPDATE assigned_at = CURRENT_TIMESTAMP, assigned_by = VALUES(assigned_by)
            ");
            
            return $stmt->execute([
                ':user_id' => $userId,
                ':role' => $role,
                ':assigned_by' => $assignedBy
            ]);
        } catch (PDOException $e) {
            error_log('Failed to assign role: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove a role from a user
     * 
     * @param string $userId User ID
     * @param string $role Role to remove
     * @return bool True on success, false on failure
     */
    public static function removeRole(string $userId, string $role): bool
    {
        // Cannot remove CUSTOMER role
        if ($role === 'CUSTOMER') {
            return false;
        }
        
        try {
            $db = Connection::getInstance();
            $stmt = $db->prepare("DELETE FROM user_roles WHERE user_id = :user_id AND role = :role");
            return $stmt->execute([':user_id' => $userId, ':role' => $role]);
        } catch (PDOException $e) {
            error_log('Failed to remove role: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Set all roles for a user (replaces existing roles)
     * 
     * @param string $userId User ID
     * @param array $roles Array of roles to set
     * @param string|null $assignedBy User ID of who assigned the roles
     * @return bool True on success, false on failure
     */
    public static function setUserRoles(string $userId, array $roles, ?string $assignedBy = null): bool
    {
        // Ensure CUSTOMER role is always included
        if (!in_array('CUSTOMER', $roles)) {
            $roles[] = 'CUSTOMER';
        }
        
        try {
            $db = Connection::getInstance();
            $db->beginTransaction();
            
            // Remove all existing roles
            $stmt = $db->prepare("DELETE FROM user_roles WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $userId]);
            
            // Add new roles
            $stmt = $db->prepare("
                INSERT INTO user_roles (user_id, role, assigned_by)
                VALUES (:user_id, :role, :assigned_by)
            ");
            
            foreach ($roles as $role) {
                $stmt->execute([
                    ':user_id' => $userId,
                    ':role' => $role,
                    ':assigned_by' => $assignedBy
                ]);
            }
            
            // Update primary role in users table (first non-CUSTOMER role, or CUSTOMER)
            $primaryRole = 'CUSTOMER';
            foreach ($roles as $role) {
                if ($role !== 'CUSTOMER') {
                    $primaryRole = $role;
                    break;
                }
            }
            
            $stmt = $db->prepare("UPDATE users SET role = :role WHERE id = :user_id");
            $stmt->execute([':role' => $primaryRole, ':user_id' => $userId]);
            
            $db->commit();
            return true;
        } catch (PDOException $e) {
            $db->rollBack();
            error_log('Failed to set user roles: ' . $e->getMessage());
            return false;
        }
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
     * Get admin roles (roles that can access admin panel)
     * 
     * @return array Array of admin role names
     */
    public static function getAdminRoles(): array
    {
        $roles = self::getRoles();
        $adminRoles = [];
        
        foreach ($roles as $roleName => $roleData) {
            if (in_array('admin.access', $roleData['permissions'])) {
                $adminRoles[] = $roleName;
            }
        }
        
        return $adminRoles;
    }

    /**
     * Check if user can access admin panel
     * 
     * @param string $userId User ID
     * @return bool True if user can access admin panel
     */
    public static function canAccessAdmin(string $userId): bool
    {
        return self::userHasPermission($userId, 'admin.access');
    }

    /**
     * Require permission for current user
     * Exits with 403 if user doesn't have permission
     * 
     * @param string $userId User ID
     * @param string $permission Required permission
     * @return void
     */
    public static function requirePermission(string $userId, string $permission): void
    {
        if (!self::userHasPermission($userId, $permission)) {
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
     * @param string $userId User ID
     * @param array $permissions Required permissions (any)
     * @return void
     */
    public static function requireAnyPermission(string $userId, array $permissions): void
    {
        if (!self::userHasAnyPermission($userId, $permissions)) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Access denied. You do not have permission to perform this action.',
                'required_permissions' => $permissions
            ]);
            exit;
        }
    }
}
