<?php
/**
 * Edit Admin User Page
 * Supports multiple roles per user
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Models\User;
use Karyalay\Services\RoleService;

startSecureSession();
require_admin();
require_permission('users.edit');

$db = \Karyalay\Database\Connection::getInstance();
$userModel = new User();

// Get user ID from query string
$user_id = $_GET['id'] ?? '';
if (empty($user_id)) {
    $_SESSION['admin_error'] = 'User ID is required.';
    header('Location: ' . get_app_base_url() . '/admin/users-and-roles.php');
    exit;
}

// Fetch user data
$user = $userModel->findById($user_id);
if (!$user) {
    $_SESSION['admin_error'] = 'User not found.';
    header('Location: ' . get_app_base_url() . '/admin/users-and-roles.php');
    exit;
}

// Check if user is editing themselves (cannot edit own roles)
$isEditingSelf = ($user_id === $_SESSION['user_id']);

// Get user's current roles
$userRoles = RoleService::getUserRoles($user_id);

// Get all available admin roles
$allRoles = RoleService::getRoles();
$adminRoles = RoleService::getAdminRoles();

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
        $selectedRoles = $_POST['roles'] ?? [];
        $password = $_POST['password'] ?? '';
        
        // If editing self, keep current roles (cannot change own roles)
        if ($isEditingSelf) {
            $selectedRoles = array_filter($userRoles, function($role) {
                return $role !== 'CUSTOMER'; // Remove CUSTOMER as it's auto-added
            });
        } else {
            // Ensure roles is an array
            if (!is_array($selectedRoles)) {
                $selectedRoles = [$selectedRoles];
            }
            
            // Filter to only valid roles
            $selectedRoles = array_filter($selectedRoles, function($role) use ($allRoles) {
                return isset($allRoles[$role]);
            });
        }
        
        if (empty($name) || empty($email)) {
            $_SESSION['admin_error'] = 'Name and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['admin_error'] = 'Invalid email format.';
        } elseif (!empty($password) && strlen($password) < 8) {
            $_SESSION['admin_error'] = 'Password must be at least 8 characters.';
        } elseif (empty($selectedRoles) && !$isEditingSelf) {
            $_SESSION['admin_error'] = 'At least one role must be selected.';
        } else {
            // Check if email exists for another user
            $existingUser = $userModel->findByEmail($email);
            if ($existingUser && $existingUser['id'] !== $user_id) {
                $_SESSION['admin_error'] = 'Email already exists for another user.';
            } else {
                // Determine primary role (first non-CUSTOMER role)
                $primaryRole = 'CUSTOMER';
                foreach ($selectedRoles as $role) {
                    if ($role !== 'CUSTOMER') {
                        $primaryRole = $role;
                        break;
                    }
                }
                
                $updateData = [
                    'name' => $name,
                    'email' => $email,
                    'role' => $primaryRole
                ];
                
                if (!empty($password)) {
                    $updateData['password'] = $password;
                }
                
                $result = $userModel->update($user_id, $updateData);
                
                if ($result) {
                    // Update roles in user_roles table
                    RoleService::setUserRoles($user_id, $selectedRoles, $_SESSION['user_id']);
                    
                    $_SESSION['admin_success'] = 'User updated successfully!';
                    header('Location: ' . get_app_base_url() . '/admin/users-and-roles.php');
                    exit;
                } else {
                    $_SESSION['admin_error'] = 'Failed to update user. Please try again.';
                }
            }
        }
    }
    
    // Refresh user data after failed update
    $user = $userModel->findById($user_id);
    $userRoles = RoleService::getUserRoles($user_id);
}

include_admin_header('Edit User');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <nav class="admin-breadcrumb">
            <a href="<?php echo get_app_base_url(); ?>/admin/users-and-roles.php">Users & Roles</a>
            <span class="breadcrumb-separator">/</span>
            <span>Edit User</span>
        </nav>
        <h1 class="admin-page-title">Edit User</h1>
        <p class="admin-page-description">Update user details and roles</p>
    </div>
</div>

<?php if (isset($_SESSION['admin_error'])): ?>
    <div class="alert alert-error">
        <?php echo htmlspecialchars($_SESSION['admin_error']); ?>
        <?php unset($_SESSION['admin_error']); ?>
    </div>
<?php endif; ?>

<div class="admin-card">
    <form method="POST" action="<?php echo get_app_base_url(); ?>/admin/users-and-roles/edit.php?id=<?php echo urlencode($user_id); ?>" class="admin-form">
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
        </div>
        
        <div class="form-group">
            <label class="form-label">Roles <span class="required">*</span></label>
            <?php if ($isEditingSelf): ?>
                <div class="alert alert-warning" style="margin-bottom: 12px;">
                    <strong>Note:</strong> You cannot modify your own roles. Please ask another administrator to change your roles if needed.
                </div>
            <?php else: ?>
                <p class="form-help" style="margin-bottom: 12px;">Select one or more roles. All users automatically have the CUSTOMER role.</p>
            <?php endif; ?>
            
            <div class="roles-grid <?php echo $isEditingSelf ? 'roles-disabled' : ''; ?>">
                <?php foreach ($allRoles as $roleName => $roleData): ?>
                    <?php if ($roleName === 'CUSTOMER') continue; // Skip CUSTOMER as it's automatic ?>
                    <div class="role-checkbox-card <?php echo in_array($roleName, $userRoles) ? 'selected' : ''; ?> <?php echo $isEditingSelf ? 'disabled' : ''; ?>">
                        <label class="role-checkbox-label">
                            <input type="checkbox" name="roles[]" value="<?php echo htmlspecialchars($roleName); ?>"
                                <?php echo in_array($roleName, $userRoles) ? 'checked' : ''; ?>
                                <?php echo $isEditingSelf ? 'disabled' : ''; ?>>
                            <div class="role-checkbox-content">
                                <div class="role-checkbox-header">
                                    <span class="role-checkbox-name"><?php echo htmlspecialchars($roleData['label']); ?></span>
                                    <?php echo get_role_badge($roleName); ?>
                                </div>
                                <p class="role-checkbox-desc"><?php echo htmlspecialchars($roleData['description']); ?></p>
                            </div>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="user-info-card">
            <h3>User Information</h3>
            <div class="info-row">
                <span class="info-label">User ID:</span>
                <span class="info-value"><code><?php echo htmlspecialchars($user['id']); ?></code></span>
            </div>
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
            <div class="info-row">
                <span class="info-label">Current Roles:</span>
                <span class="info-value">
                    <?php 
                    $displayRoles = array_filter($userRoles, function($r) { return $r !== 'CUSTOMER'; });
                    if (empty($displayRoles)) $displayRoles = ['CUSTOMER'];
                    foreach ($displayRoles as $role): 
                    ?>
                        <?php echo get_role_badge($role); ?>
                    <?php endforeach; ?>
                </span>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="<?php echo get_app_base_url(); ?>/admin/users-and-roles.php" class="btn btn-secondary">Cancel</a>
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
    margin-bottom: 20px;
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

/* Roles Grid */
.roles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 12px;
}

.role-checkbox-card {
    border: 2px solid var(--color-gray-200);
    border-radius: 8px;
    padding: 16px;
    transition: all 0.2s ease;
    cursor: pointer;
}

.role-checkbox-card:hover {
    border-color: var(--color-gray-300);
    background: var(--color-gray-50);
}

.role-checkbox-card.selected {
    border-color: var(--color-primary);
    background: rgba(59, 130, 246, 0.05);
}

.role-checkbox-label {
    display: flex;
    gap: 12px;
    cursor: pointer;
}

.role-checkbox-label input[type="checkbox"] {
    width: 20px;
    height: 20px;
    margin-top: 2px;
    flex-shrink: 0;
}

.role-checkbox-content {
    flex: 1;
}

.role-checkbox-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}

.role-checkbox-name {
    font-weight: 600;
    color: var(--color-gray-900);
}

.role-checkbox-desc {
    font-size: 13px;
    color: var(--color-gray-600);
    margin: 0;
    line-height: 1.4;
}

/* Disabled roles state (when editing self) */
.roles-grid.roles-disabled {
    opacity: 0.7;
}

.role-checkbox-card.disabled {
    cursor: not-allowed;
    background: var(--color-gray-100);
}

.role-checkbox-card.disabled .role-checkbox-label {
    cursor: not-allowed;
}

.role-checkbox-card.disabled input[type="checkbox"] {
    cursor: not-allowed;
}

.alert-warning {
    background-color: #fef3c7;
    border: 1px solid #f59e0b;
    color: #92400e;
    padding: 12px 16px;
    border-radius: 6px;
    font-size: 14px;
}

.user-info-card {
    background: var(--color-gray-50);
    border: 1px solid var(--color-gray-200);
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 24px;
}
.user-info-card h3 {
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 12px 0;
    color: var(--color-gray-700);
}
.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
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
.info-value code {
    background: var(--color-gray-100);
    padding: 2px 6px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 12px;
}
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding-top: 16px;
    border-top: 1px solid var(--color-gray-200);
}

/* Badge colors */
.badge-purple { background-color: #8b5cf6; color: white; }
.badge-teal { background-color: #14b8a6; color: white; }
.badge-orange { background-color: #f97316; color: white; }

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    .roles-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update card selection state when checkbox changes
    document.querySelectorAll('.role-checkbox-card input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            this.closest('.role-checkbox-card').classList.toggle('selected', this.checked);
        });
    });
});
</script>

<?php include_admin_footer(); ?>
