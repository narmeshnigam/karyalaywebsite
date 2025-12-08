<?php
/**
 * Edit Admin User Page
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Models\User;

startSecureSession();
require_admin();

$db = \Karyalay\Database\Connection::getInstance();
$userModel = new User();

// Get user ID from query string
$user_id = $_GET['id'] ?? '';
if (empty($user_id)) {
    $_SESSION['admin_error'] = 'User ID is required.';
    header('Location: ' . get_base_url() . '/admin/users-and-roles.php');
    exit;
}

// Fetch user data
$user = $userModel->findById($user_id);
if (!$user || !in_array($user['role'], ['ADMIN', 'SUPPORT', 'SALES', 'CONTENT_EDITOR'])) {
    $_SESSION['admin_error'] = 'Admin user not found.';
    header('Location: ' . get_base_url() . '/admin/users-and-roles.php');
    exit;
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['admin_error'] = 'Invalid security token. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'ADMIN';
        $password = $_POST['password'] ?? '';
        
        if (empty($name) || empty($email)) {
            $_SESSION['admin_error'] = 'Name and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['admin_error'] = 'Invalid email format.';
        } elseif (!empty($password) && strlen($password) < 8) {
            $_SESSION['admin_error'] = 'Password must be at least 8 characters.';
        } elseif (!in_array($role, ['ADMIN', 'SUPPORT', 'SALES', 'CONTENT_EDITOR'])) {
            $_SESSION['admin_error'] = 'Invalid role selected.';
        } else {
            // Check if email exists for another user
            $existingUser = $userModel->findByEmail($email);
            if ($existingUser && $existingUser['id'] !== $user_id) {
                $_SESSION['admin_error'] = 'Email already exists for another user.';
            } else {
                $updateData = [
                    'name' => $name,
                    'email' => $email,
                    'role' => $role
                ];
                
                if (!empty($password)) {
                    $updateData['password'] = password_hash($password, PASSWORD_DEFAULT);
                }
                
                $result = $userModel->update($user_id, $updateData);
                
                if ($result) {
                    $_SESSION['admin_success'] = 'Admin user updated successfully!';
                    header('Location: ' . get_base_url() . '/admin/users-and-roles.php');
                    exit;
                } else {
                    $_SESSION['admin_error'] = 'Failed to update admin user. Please try again.';
                }
            }
        }
    }
    
    // Refresh user data after failed update
    $user = $userModel->findById($user_id);
}

include_admin_header('Edit Admin User');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <nav class="admin-breadcrumb">
            <a href="<?php echo get_base_url(); ?>/admin/users-and-roles.php">Users & Roles</a>
            <span class="breadcrumb-separator">/</span>
            <span>Edit Admin User</span>
        </nav>
        <h1 class="admin-page-title">Edit Admin User</h1>
        <p class="admin-page-description">Update administrator details</p>
    </div>
</div>

<?php if (isset($_SESSION['admin_error'])): ?>
    <div class="alert alert-error">
        <?php echo htmlspecialchars($_SESSION['admin_error']); ?>
        <?php unset($_SESSION['admin_error']); ?>
    </div>
<?php endif; ?>

<div class="admin-card">
    <form method="POST" action="<?php echo get_base_url(); ?>/admin/users-and-roles/edit.php?id=<?php echo urlencode($user_id); ?>" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        
        <div class="form-row">
            <div class="form-group">
                <label for="name" class="form-label">Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" class="form-input" required
                    value="<?php echo htmlspecialchars($user['name']); ?>">
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" class="form-input" required
                    value="<?php echo htmlspecialchars($user['email']); ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-input" minlength="8">
                <p class="form-help">Leave blank to keep current password. Minimum 8 characters if changing.</p>
            </div>
            
            <div class="form-group">
                <label for="role" class="form-label">Role <span class="required">*</span></label>
                <select id="role" name="role" class="form-input" required>
                    <option value="ADMIN" <?php echo $user['role'] === 'ADMIN' ? 'selected' : ''; ?>>Admin</option>
                    <option value="SUPPORT" <?php echo $user['role'] === 'SUPPORT' ? 'selected' : ''; ?>>Support</option>
                    <option value="SALES" <?php echo $user['role'] === 'SALES' ? 'selected' : ''; ?>>Sales</option>
                    <option value="CONTENT_EDITOR" <?php echo $user['role'] === 'CONTENT_EDITOR' ? 'selected' : ''; ?>>Content Editor</option>
                </select>
            </div>
        </div>
        
        <div class="role-permissions-card">
            <h3>Role Permissions</h3>
            <div class="role-permission" data-role="ADMIN">
                <strong>Admin:</strong> Full system access including user management, settings, and all administrative functions.
            </div>
            <div class="role-permission" data-role="SUPPORT">
                <strong>Support:</strong> Access to tickets, customer support features, and customer information.
            </div>
            <div class="role-permission" data-role="SALES">
                <strong>Sales:</strong> Access to leads, customers, orders, and sales-related features.
            </div>
            <div class="role-permission" data-role="CONTENT_EDITOR">
                <strong>Content Editor:</strong> Access to content management including blog, solutions, and features.
            </div>
        </div>
        
        <div class="user-info-card">
            <h3>User Information</h3>
            <div class="info-row">
                <span class="info-label">Created:</span>
                <span class="info-value"><?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Email Verified:</span>
                <span class="info-value">
                    <?php if ($user['email_verified']): ?>
                        <span class="badge badge-success">Verified</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Not Verified</span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="<?php echo get_base_url(); ?>/admin/users-and-roles.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Update User</button>
        </div>
    </form>
</div>

<style>
.admin-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    margin-bottom: 8px;
}
.admin-breadcrumb a {
    color: var(--color-primary);
    text-decoration: none;
}
.admin-breadcrumb a:hover {
    text-decoration: underline;
}
.breadcrumb-separator {
    color: var(--color-gray-400);
}
.admin-form {
    padding: 24px;
}
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 20px;
}
.form-group {
    display: flex;
    flex-direction: column;
}
.form-label {
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--color-gray-700);
}
.required {
    color: #dc2626;
}
.form-input {
    padding: 10px 12px;
    border: 1px solid var(--color-gray-300);
    border-radius: 6px;
    font-size: 14px;
}
.form-input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
.form-help {
    font-size: 12px;
    color: var(--color-gray-500);
    margin-top: 4px;
}
.role-permissions-card,
.user-info-card {
    background: var(--color-gray-50);
    border: 1px solid var(--color-gray-200);
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 24px;
}
.role-permissions-card h3,
.user-info-card h3 {
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 12px 0;
    color: var(--color-gray-700);
}
.role-permission {
    font-size: 13px;
    color: var(--color-gray-600);
    padding: 8px 0;
    border-bottom: 1px solid var(--color-gray-200);
    display: none;
}
.role-permission:last-child {
    border-bottom: none;
}
.role-permission.active {
    display: block;
}
.info-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid var(--color-gray-200);
}
.info-row:last-child {
    border-bottom: none;
}
.info-label {
    font-size: 13px;
    color: var(--color-gray-600);
}
.info-value {
    font-size: 13px;
    color: var(--color-gray-900);
}
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding-top: 16px;
    border-top: 1px solid var(--color-gray-200);
}
@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('role');
    
    function updateRolePermissions() {
        const selectedRole = roleSelect.value;
        document.querySelectorAll('.role-permission').forEach(el => {
            el.classList.toggle('active', el.dataset.role === selectedRole);
        });
    }
    
    roleSelect.addEventListener('change', updateRolePermissions);
    updateRolePermissions();
});
</script>

<?php include_admin_footer(); ?>
