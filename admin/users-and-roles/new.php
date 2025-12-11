<?php
/**
 * Create New Admin User Page
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
require_permission('users.create');

$db = \Karyalay\Database\Connection::getInstance();
$userModel = new User();

// Get all available roles
$allRoles = RoleService::getRoles();

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
        $password = $_POST['password'] ?? '';
        $selectedRoles = $_POST['roles'] ?? [];
        
        // Ensure roles is an array
        if (!is_array($selectedRoles)) {
            $selectedRoles = [$selectedRoles];
        }
        
        // Filter to only valid roles
        $selectedRoles = array_filter($selectedRoles, function($role) use ($allRoles) {
            return isset($allRoles[$role]);
        });
        
        if (empty($name) || empty($email) || empty($password)) {
            $_SESSION['admin_error'] = 'Name, email, and password are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['admin_error'] = 'Invalid email format.';
        } elseif (strlen($password) < 8) {
            $_SESSION['admin_error'] = 'Password must be at least 8 characters.';
        } elseif (empty($selectedRoles)) {
            $_SESSION['admin_error'] = 'At least one role must be selected.';
        } elseif ($userModel->emailExists($email)) {
            $_SESSION['admin_error'] = 'Email already exists.';
        } else {
            // Determine primary role (first non-CUSTOMER role)
            $primaryRole = 'CUSTOMER';
            foreach ($selectedRoles as $role) {
                if ($role !== 'CUSTOMER') {
                    $primaryRole = $role;
                    break;
                }
            }
            
            $result = $userModel->create([
                'email' => $email,
                'password' => $password,
                'name' => $name,
                'phone' => null,
                'role' => $primaryRole,
                'email_verified' => true
            ]);
            
            if ($result) {
                // Set roles in user_roles table
                RoleService::setUserRoles($result['id'], $selectedRoles, $_SESSION['user_id']);
                
                $_SESSION['admin_success'] = 'User created successfully!';
                header('Location: ' . get_app_base_url() . '/admin/users-and-roles.php');
                exit;
            } else {
                $_SESSION['admin_error'] = 'Failed to create user. Please try again.';
            }
        }
    }
}

include_admin_header('Create User');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <nav class="admin-breadcrumb">
            <a href="<?php echo get_app_base_url(); ?>/admin/users-and-roles.php">Users & Roles</a>
            <span class="breadcrumb-separator">/</span>
            <span>Create User</span>
        </nav>
        <h1 class="admin-page-title">Create User</h1>
        <p class="admin-page-description">Add a new user with admin panel access</p>
    </div>
</div>

<?php if (isset($_SESSION['admin_error'])): ?>
    <div class="alert alert-error">
        <?php echo htmlspecialchars($_SESSION['admin_error']); ?>
        <?php unset($_SESSION['admin_error']); ?>
    </div>
<?php endif; ?>

<div class="admin-card">
    <form method="POST" action="<?php echo get_app_base_url(); ?>/admin/users-and-roles/new.php" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        
        <div class="form-row">
            <div class="form-group">
                <label for="name" class="form-label">Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" class="form-input" required
                    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" class="form-input" required
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="password" class="form-label">Password <span class="required">*</span></label>
                <input type="password" id="password" name="password" class="form-input" required minlength="8">
                <p class="form-help">Minimum 8 characters</p>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Roles <span class="required">*</span></label>
            <p class="form-help" style="margin-bottom: 12px;">Select one or more roles. All users automatically have the CUSTOMER role.</p>
            
            <div class="roles-grid">
                <?php 
                $postedRoles = $_POST['roles'] ?? [];
                foreach ($allRoles as $roleName => $roleData): 
                ?>
                    <?php if ($roleName === 'CUSTOMER') continue; // Skip CUSTOMER as it's automatic ?>
                    <div class="role-checkbox-card <?php echo in_array($roleName, $postedRoles) ? 'selected' : ''; ?>">
                        <label class="role-checkbox-label">
                            <input type="checkbox" name="roles[]" value="<?php echo htmlspecialchars($roleName); ?>"
                                <?php echo in_array($roleName, $postedRoles) ? 'checked' : ''; ?>>
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
        
        <div class="form-actions">
            <a href="<?php echo get_app_base_url(); ?>/admin/users-and-roles.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Create User</button>
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
