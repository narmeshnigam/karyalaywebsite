<?php
/**
 * Admin Users and Roles Management Page
 * Display all admin accounts and allow creating new admin users
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/template_helpers.php';

use Karyalay\Models\User;
use Karyalay\Services\RoleService;

startSecureSession();
require_admin();
require_permission('users.view');

$db = \Karyalay\Database\Connection::getInstance();
$userModel = new User();

// Get admin roles for filtering
$adminRoles = RoleService::getAdminRoles();

// Get filters from query parameters
$role_filter = $_GET['role'] ?? '';
$search_query = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query for counting total admin users (users with any admin role)
$adminRolesPlaceholders = implode(',', array_fill(0, count($adminRoles), '?'));
$count_sql = "SELECT COUNT(DISTINCT u.id) FROM users u 
              INNER JOIN user_roles ur ON u.id = ur.user_id 
              WHERE ur.role IN ($adminRolesPlaceholders)";
$count_params = $adminRoles;

if (!empty($role_filter)) {
    $count_sql .= " AND ur.role = ?";
    $count_params[] = $role_filter;
}

if (!empty($search_query)) {
    $count_sql .= " AND (u.name LIKE ? OR u.email LIKE ?)";
    $count_params[] = '%' . $search_query . '%';
    $count_params[] = '%' . $search_query . '%';
}

try {
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($count_params);
    $total_users = $count_stmt->fetchColumn();
    $total_pages = ceil($total_users / $per_page);
} catch (PDOException $e) {
    error_log("Admin users count error: " . $e->getMessage());
    // Fallback to legacy query if user_roles table doesn't exist yet
    $total_users = 0;
    $total_pages = 0;
}

// Build query for fetching admin users with their roles
$sql = "SELECT DISTINCT u.*, GROUP_CONCAT(ur.role ORDER BY ur.role SEPARATOR ',') as roles
        FROM users u 
        INNER JOIN user_roles ur ON u.id = ur.user_id 
        WHERE ur.role IN ($adminRolesPlaceholders)";
$params = $adminRoles;

if (!empty($role_filter)) {
    $sql .= " AND u.id IN (SELECT user_id FROM user_roles WHERE role = ?)";
    $params[] = $role_filter;
}

if (!empty($search_query)) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ?)";
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
}

$sql .= " GROUP BY u.id ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $admin_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Admin users list error: " . $e->getMessage());
    // Fallback to legacy query
    $admin_users = [];
}

// If user_roles table doesn't exist or is empty, fall back to legacy behavior
if (empty($admin_users) && $total_users === 0) {
    try {
        $legacy_sql = "SELECT * FROM users WHERE role IN ('ADMIN', 'SUPPORT', 'SALES', 'CONTENT_EDITOR', 'INFRASTRUCTURE', 'SALES_MANAGER', 'OPERATIONS', 'CONTENT_MANAGER')";
        $legacy_params = [];
        
        if (!empty($role_filter)) {
            $legacy_sql .= " AND role = :role";
            $legacy_params[':role'] = $role_filter;
        }
        
        if (!empty($search_query)) {
            $legacy_sql .= " AND (name LIKE :search OR email LIKE :search)";
            $legacy_params[':search'] = '%' . $search_query . '%';
        }
        
        $legacy_sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($legacy_sql);
        foreach ($legacy_params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $admin_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add roles field for legacy users
        foreach ($admin_users as &$user) {
            $user['roles'] = $user['role'];
        }
        
        // Count for legacy
        $count_sql = "SELECT COUNT(*) FROM users WHERE role IN ('ADMIN', 'SUPPORT', 'SALES', 'CONTENT_EDITOR', 'INFRASTRUCTURE', 'SALES_MANAGER', 'OPERATIONS', 'CONTENT_MANAGER')";
        $count_stmt = $db->query($count_sql);
        $total_users = $count_stmt->fetchColumn();
        $total_pages = ceil($total_users / $per_page);
    } catch (PDOException $e) {
        error_log("Legacy admin users query error: " . $e->getMessage());
    }
}

include_admin_header('Users & Roles');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Users & Roles Management</h1>
        <p class="admin-page-description">Manage admin users and their roles. Users can have multiple roles assigned.</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/users-and-roles/roles.php" class="btn btn-secondary">
            View Roles & Permissions
        </a>
        <?php if (has_permission('users.create')): ?>
        <a href="<?php echo get_app_base_url(); ?>/admin/users-and-roles/new.php" class="btn btn-primary">
            <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Create Admin User
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_SESSION['admin_success'])): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($_SESSION['admin_success']); ?>
        <?php unset($_SESSION['admin_success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['admin_error'])): ?>
    <div class="alert alert-error">
        <?php echo htmlspecialchars($_SESSION['admin_error']); ?>
        <?php unset($_SESSION['admin_error']); ?>
    </div>
<?php endif; ?>

<!-- Filters and Search -->
<div class="admin-filters-section">
    <form method="GET" action="<?php echo get_app_base_url(); ?>/admin/users-and-roles.php" class="admin-filters-form">
        <div class="admin-filter-group">
            <label for="search" class="admin-filter-label">Search</label>
            <input 
                type="text" 
                id="search" 
                name="search" 
                class="admin-filter-input" 
                placeholder="Search by name or email..."
                value="<?php echo htmlspecialchars($search_query); ?>"
            >
        </div>
        
        <div class="admin-filter-group">
            <label for="role" class="admin-filter-label">Role</label>
            <select id="role" name="role" class="admin-filter-select">
                <option value="">All Roles</option>
                <option value="ADMIN" <?php echo $role_filter === 'ADMIN' ? 'selected' : ''; ?>>Administrator</option>
                <option value="SUPPORT" <?php echo $role_filter === 'SUPPORT' ? 'selected' : ''; ?>>Support</option>
                <option value="INFRASTRUCTURE" <?php echo $role_filter === 'INFRASTRUCTURE' ? 'selected' : ''; ?>>Infrastructure</option>
                <option value="SALES" <?php echo $role_filter === 'SALES' ? 'selected' : ''; ?>>Sales</option>
                <option value="SALES_MANAGER" <?php echo $role_filter === 'SALES_MANAGER' ? 'selected' : ''; ?>>Sales Manager</option>
                <option value="OPERATIONS" <?php echo $role_filter === 'OPERATIONS' ? 'selected' : ''; ?>>Operations</option>
                <option value="CONTENT_MANAGER" <?php echo $role_filter === 'CONTENT_MANAGER' ? 'selected' : ''; ?>>Content Manager</option>
            </select>
        </div>
        
        <div class="admin-filter-actions">
            <button type="submit" class="btn btn-secondary">Apply Filters</button>
            <a href="<?php echo get_app_base_url(); ?>/admin/users-and-roles.php" class="btn btn-text">Clear</a>
        </div>
    </form>
</div>

<!-- Admin Users Table -->
<div class="admin-card">
    <?php if (empty($admin_users)): ?>
        <?php 
        render_empty_state(
            'No admin users found',
            'No admin users match your current filters',
            '',
            ''
        );
        ?>
    <?php else: ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Roles</th>
                        <th>Created</th>
                        <th>Email Verified</th>
                        <?php if (has_permission('users.edit')): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admin_users as $user): ?>
                        <tr>
                            <td>
                                <div class="table-cell-primary">
                                    <?php echo htmlspecialchars($user['name']); ?>
                                </div>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($user['email']); ?>
                            </td>
                            <td>
                                <div class="role-badges">
                                    <?php 
                                    $userRoles = isset($user['roles']) ? explode(',', $user['roles']) : [$user['role']];
                                    // Filter out CUSTOMER role for display
                                    $displayRoles = array_filter($userRoles, function($r) { return $r !== 'CUSTOMER' && !empty($r); });
                                    if (empty($displayRoles)) $displayRoles = ['CUSTOMER'];
                                    foreach ($displayRoles as $role): 
                                    ?>
                                        <?php echo get_role_badge(trim($role)); ?>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td><?php echo get_relative_time($user['created_at']); ?></td>
                            <td>
                                <?php if ($user['email_verified']): ?>
                                    <span class="badge badge-success">Verified</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Not Verified</span>
                                <?php endif; ?>
                            </td>
                            <?php if (has_permission('users.edit')): ?>
                            <td>
                                <div class="table-actions">
                                    <a href="<?php echo get_app_base_url(); ?>/admin/users-and-roles/edit.php?id=<?php echo urlencode($user['id']); ?>" 
                                       class="btn btn-sm btn-secondary">
                                        Edit
                                    </a>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="admin-card-footer">
                <?php 
                $base_url = '/admin/users-and-roles.php';
                $query_params = [];
                if (!empty($role_filter)) {
                    $query_params[] = 'role=' . urlencode($role_filter);
                }
                if (!empty($search_query)) {
                    $query_params[] = 'search=' . urlencode($search_query);
                }
                if (!empty($query_params)) {
                    $base_url .= '?' . implode('&', $query_params);
                }
                render_pagination($page, $total_pages, $base_url);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-card-footer">
            <p class="admin-card-footer-text">
                Showing <?php echo count($admin_users); ?> of <?php echo $total_users; ?> admin user<?php echo $total_users !== 1 ? 's' : ''; ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<style>
.admin-page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
    gap: 16px;
}
.admin-page-header-content {
    flex: 1;
}
.admin-page-title {
    font-size: 24px;
    font-weight: 700;
    color: var(--color-gray-900);
    margin: 0 0 8px 0;
}
.admin-page-description {
    font-size: 14px;
    color: var(--color-gray-600);
    margin: 0;
}
.admin-page-header-actions {
    display: flex;
    gap: 8px;
}
.btn-icon {
    width: 20px;
    height: 20px;
    margin-right: 8px;
}
.alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 16px;
}
.alert-success {
    background-color: #d1fae5;
    border: 1px solid #6ee7b7;
    color: #065f46;
}
.alert-error {
    background-color: #fee2e2;
    border: 1px solid #fca5a5;
    color: #991b1b;
}
.admin-filters-section {
    background: white;
    border: 1px solid var(--color-gray-200);
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 24px;
}
.admin-filters-form {
    display: flex;
    gap: 16px;
    align-items: flex-end;
    flex-wrap: wrap;
}
.admin-filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 1;
    min-width: 200px;
}
.admin-filter-label {
    font-size: 14px;
    font-weight: 600;
    color: var(--color-gray-700);
}
.admin-filter-input,
.admin-filter-select {
    padding: 8px 12px;
    border: 1px solid var(--color-gray-300);
    border-radius: 6px;
    font-size: 14px;
    color: var(--color-gray-900);
}
.admin-filter-input:focus,
.admin-filter-select:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
.admin-filter-actions {
    display: flex;
    gap: 8px;
}
.table-cell-primary {
    font-weight: 600;
    color: var(--color-gray-900);
}
.table-actions {
    display: flex;
    gap: 8px;
}
.admin-card-footer-text {
    font-size: 14px;
    color: var(--color-gray-600);
    margin: 0;
}
.role-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}
/* Badge colors for new roles */
.badge-purple {
    background-color: #8b5cf6;
    color: white;
}
.badge-teal {
    background-color: #14b8a6;
    color: white;
}
.badge-orange {
    background-color: #f97316;
    color: white;
}
@media (max-width: 768px) {
    .admin-page-header {
        flex-direction: column;
    }
    .admin-filters-form {
        flex-direction: column;
    }
    .admin-filter-group {
        width: 100%;
    }
    .admin-table-container {
        overflow-x: auto;
    }
}
</style>

<?php include_admin_footer(); ?>
