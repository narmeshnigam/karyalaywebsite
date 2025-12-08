<?php
/**
 * Admin Roles and Permissions Page
 * Display all roles and their permissions
 * Requirements: 13.6
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Services\RoleService;

// Start secure session
startSecureSession();

// Require admin authentication
require_admin();

// Get all roles and their information
$roles = RoleService::getRoles();

// Include admin header
include_admin_header('Roles & Permissions');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Roles & Permissions</h1>
        <p class="admin-page-description">View role definitions and their permissions</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_base_url(); ?>/admin/users-and-roles.php" class="btn btn-secondary">
            ← Back to Users
        </a>
    </div>
</div>

<div class="roles-grid">
    <?php foreach ($roles as $role_name => $role_data): ?>
        <div class="role-card">
            <div class="role-card-header">
                <div class="role-card-title-section">
                    <h2 class="role-card-title"><?php echo htmlspecialchars($role_data['label']); ?></h2>
                    <?php echo get_role_badge($role_name); ?>
                </div>
                <p class="role-card-description"><?php echo htmlspecialchars($role_data['description']); ?></p>
            </div>
            
            <div class="role-card-body">
                <h3 class="permissions-title">Permissions (<?php echo count($role_data['permissions']); ?>)</h3>
                <div class="permissions-list">
                    <?php 
                    // Group permissions by category
                    $grouped_permissions = [];
                    foreach ($role_data['permissions'] as $permission) {
                        $parts = explode('.', $permission);
                        $category = $parts[0];
                        $action = $parts[1] ?? '';
                        
                        if (!isset($grouped_permissions[$category])) {
                            $grouped_permissions[$category] = [];
                        }
                        $grouped_permissions[$category][] = $action;
                    }
                    
                    foreach ($grouped_permissions as $category => $actions):
                    ?>
                        <div class="permission-group">
                            <h4 class="permission-category"><?php echo ucfirst($category); ?></h4>
                            <ul class="permission-actions">
                                <?php foreach ($actions as $action): ?>
                                    <li class="permission-item">
                                        <svg class="permission-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <?php echo ucfirst(str_replace('_', ' ', $action)); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<style>
.admin-page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: var(--spacing-6);
    gap: var(--spacing-4);
}

.admin-page-header-content {
    flex: 1;
}

.admin-page-title {
    font-size: var(--font-size-2xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-2) 0;
}

.admin-page-description {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
    margin: 0;
}

.admin-page-header-actions {
    display: flex;
    gap: var(--spacing-2);
}

.roles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: var(--spacing-6);
}

.role-card {
    background: white;
    border: 1px solid var(--color-gray-200);
    border-radius: var(--radius-lg);
    overflow: hidden;
}

.role-card-header {
    padding: var(--spacing-6);
    border-bottom: 1px solid var(--color-gray-200);
    background: var(--color-gray-50);
}

.role-card-title-section {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    margin-bottom: var(--spacing-2);
}

.role-card-title {
    font-size: var(--font-size-xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0;
}

.role-card-description {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin: 0;
}

.role-card-body {
    padding: var(--spacing-6);
}

.permissions-title {
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-4) 0;
}

.permissions-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-4);
}

.permission-group {
    border-left: 3px solid var(--color-primary);
    padding-left: var(--spacing-3);
}

.permission-category {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
    margin: 0 0 var(--spacing-2) 0;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.permission-actions {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: var(--spacing-1);
}

.permission-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
}

.permission-icon {
    width: 16px;
    height: 16px;
    color: var(--color-green-600);
    flex-shrink: 0;
}

@media (max-width: 768px) {
    .admin-page-header {
        flex-direction: column;
    }
    
    .roles-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include_admin_footer(); ?>
