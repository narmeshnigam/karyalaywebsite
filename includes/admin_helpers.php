<?php
/**
 * Admin Helper Functions
 * Provides utility functions for admin panel templates
 */

// Include template helpers for URL functions
require_once __DIR__ . '/template_helpers.php';

use Karyalay\Services\RoleService;

/**
 * Include admin header template
 * 
 * @param string $page_title Page title
 * @param array $additional_css Optional array of additional CSS files
 */
function include_admin_header($page_title = '', $additional_css = []) {
    // Make variables available to the header template
    if (!empty($page_title)) {
        $GLOBALS['page_title'] = $page_title;
    }
    if (!empty($additional_css)) {
        $GLOBALS['additional_css'] = $additional_css;
    }
    
    require_once __DIR__ . '/../templates/admin-header.php';
}

/**
 * Include admin footer template
 * 
 * @param array $additional_js Optional array of additional JavaScript files
 */
function include_admin_footer($additional_js = []) {
    if (!empty($additional_js)) {
        $GLOBALS['additional_js'] = $additional_js;
    }
    
    require_once __DIR__ . '/../templates/admin-footer.php';
}

/**
 * Require admin authentication (legacy - allows any admin panel access)
 * Redirects to login if not authenticated or doesn't have admin.access permission
 * 
 * @return void
 */
function require_admin() {
    // Ensure auth helpers are loaded
    if (!function_exists('isAuthenticated')) {
        require_once __DIR__ . '/auth_helpers.php';
    }
    
    // Start secure session if not already started
    if (!function_exists('startSecureSession')) {
        require_once __DIR__ . '/auth_helpers.php';
    }
    startSecureSession();
    
    if (!isset($_SESSION['user_id'])) {
        // Get base URL for proper redirect
        if (!function_exists('get_base_url')) {
            require_once __DIR__ . '/template_helpers.php';
        }
        $baseUrl = get_base_url();
        header('Location: ' . $baseUrl . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    
    // Check if user can access admin panel using RoleService
    if (!RoleService::canAccessAdmin($_SESSION['user_id'])) {
        http_response_code(403);
        die('Access denied. Admin panel access required.');
    }
}

/**
 * Require specific permission(s) for admin page access
 * 
 * @param string|array $permissions Required permission(s)
 * @param bool $requireAll If true, requires all permissions; if false, requires any
 * @return void
 */
function require_permission($permissions, $requireAll = false) {
    // Ensure auth helpers are loaded
    if (!function_exists('startSecureSession')) {
        require_once __DIR__ . '/auth_helpers.php';
    }
    startSecureSession();
    
    if (!isset($_SESSION['user_id'])) {
        if (!function_exists('get_base_url')) {
            require_once __DIR__ . '/template_helpers.php';
        }
        $baseUrl = get_base_url();
        header('Location: ' . $baseUrl . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    $permissions = is_array($permissions) ? $permissions : [$permissions];
    
    $hasAccess = false;
    
    if ($requireAll) {
        $hasAccess = RoleService::userHasAllPermissions($userId, $permissions);
    } else {
        $hasAccess = RoleService::userHasAnyPermission($userId, $permissions);
    }
    
    if (!$hasAccess) {
        http_response_code(403);
        die('Access denied. You do not have permission to access this page.');
    }
}

/**
 * Check if current user has a specific permission
 * 
 * @param string $permission Permission to check
 * @return bool True if user has permission
 */
function has_permission($permission) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    return RoleService::userHasPermission($_SESSION['user_id'], $permission);
}

/**
 * Check if current user has any of the specified permissions
 * 
 * @param array $permissions Permissions to check
 * @return bool True if user has any of the permissions
 */
function has_any_permission($permissions) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    return RoleService::userHasAnyPermission($_SESSION['user_id'], $permissions);
}

/**
 * Check if current user has all of the specified permissions
 * 
 * @param array $permissions Permissions to check
 * @return bool True if user has all permissions
 */
function has_all_permissions($permissions) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    return RoleService::userHasAllPermissions($_SESSION['user_id'], $permissions);
}

/**
 * Check if current user has a specific role
 * 
 * @param string $role Role to check
 * @return bool True if user has role
 */
function has_role($role) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    return RoleService::userHasRole($_SESSION['user_id'], $role);
}

/**
 * Check if current user has any of the specified roles
 * 
 * @param array $roles Roles to check
 * @return bool True if user has any of the roles
 */
function has_any_role($roles) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    return RoleService::userHasAnyRole($_SESSION['user_id'], $roles);
}

/**
 * Check if current user is an admin (has ADMIN role)
 * 
 * @return bool True if user is admin
 */
function is_admin_user() {
    return has_role('ADMIN');
}

/**
 * Get current user's roles
 * 
 * @return array Array of role names
 */
function get_user_roles() {
    if (!isset($_SESSION['user_id'])) {
        return [];
    }
    
    return RoleService::getUserRoles($_SESSION['user_id']);
}

/**
 * Get current user's permissions
 * 
 * @return array Array of permission names
 */
function get_user_permissions() {
    if (!isset($_SESSION['user_id'])) {
        return [];
    }
    
    return RoleService::getUserPermissions($_SESSION['user_id']);
}

/**
 * Render admin dashboard card
 * 
 * @param string $title Card title
 * @param mixed $value Card value (number or text)
 * @param string $description Card description
 * @param string $icon Card icon (emoji or HTML)
 * @param string $link Optional link URL
 * @param string $link_text Optional link text
 * @return void
 */
function render_admin_card($title, $value, $description = '', $icon = '', $link = '', $link_text = 'View all') {
    ?>
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><?php echo htmlspecialchars($title); ?></h3>
            <?php if ($icon): ?>
                <span class="admin-card-icon"><?php echo $icon; ?></span>
            <?php endif; ?>
        </div>
        <p class="admin-card-value"><?php echo htmlspecialchars($value); ?></p>
        <?php if ($description): ?>
            <p class="admin-card-description"><?php echo htmlspecialchars($description); ?></p>
        <?php endif; ?>
        <?php if ($link): ?>
            <a href="<?php echo htmlspecialchars($link); ?>" class="admin-card-link">
                <?php echo htmlspecialchars($link_text); ?> →
            </a>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Format number for display
 * 
 * @param int|float $number Number to format
 * @return string Formatted number
 */
function format_number($number) {
    return number_format($number);
}

/**
 * Format currency using centralized localisation
 * This is a wrapper around the format_price function from template_helpers.php
 * 
 * @param float $amount Amount to format
 * @param string $currency Currency code (deprecated, uses localisation settings)
 * @return string Formatted currency
 */
function format_currency($amount, $currency = 'USD') {
    // Use centralized localisation if available
    if (function_exists('format_price')) {
        return format_price($amount);
    }
    
    // Fallback to old behavior
    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'INR' => '₹',
    ];
    
    $symbol = $symbols[$currency] ?? $currency . ' ';
    return $symbol . number_format($amount, 2);
}

/**
 * Get status badge HTML
 * 
 * @param string $status Status value
 * @param array $config Optional configuration for status colors
 * @return string HTML for status badge
 */
function get_status_badge($status, $config = []) {
    $default_config = [
        'ACTIVE' => 'success',
        'PUBLISHED' => 'success',
        'SUCCESS' => 'success',
        'OPEN' => 'info',
        'IN_PROGRESS' => 'warning',
        'PENDING' => 'warning',
        'PENDING_ALLOCATION' => 'warning',
        'EXPIRED' => 'danger',
        'CANCELLED' => 'danger',
        'FAILED' => 'danger',
        'CLOSED' => 'secondary',
        'DRAFT' => 'secondary',
        'ARCHIVED' => 'secondary',
        'AVAILABLE' => 'success',
        'ASSIGNED' => 'info',
        'DISABLED' => 'danger',
    ];
    
    $config = array_merge($default_config, $config);
    $type = $config[$status] ?? 'secondary';
    
    $status_display = ucwords(strtolower(str_replace('_', ' ', $status)));
    
    return '<span class="badge badge-' . htmlspecialchars($type) . '">' . htmlspecialchars($status_display) . '</span>';
}

/**
 * Render admin pagination
 * 
 * @param int $current_page Current page number
 * @param int $total_pages Total number of pages
 * @param string $base_url Base URL for pagination links
 * @return void
 */
function render_admin_pagination($current_page, $total_pages, $base_url) {
    if ($total_pages <= 1) {
        return;
    }
    
    $query_separator = strpos($base_url, '?') !== false ? '&' : '?';
    
    ?>
    <nav class="pagination" aria-label="Pagination">
        <ul class="pagination-list">
            <?php if ($current_page > 1): ?>
                <li class="pagination-item">
                    <a href="<?php echo htmlspecialchars($base_url . $query_separator . 'page=' . ($current_page - 1)); ?>" class="pagination-link">
                        ← Previous
                    </a>
                </li>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i === $current_page): ?>
                    <li class="pagination-item">
                        <span class="pagination-link active"><?php echo $i; ?></span>
                    </li>
                <?php elseif ($i === 1 || $i === $total_pages || abs($i - $current_page) <= 2): ?>
                    <li class="pagination-item">
                        <a href="<?php echo htmlspecialchars($base_url . $query_separator . 'page=' . $i); ?>" class="pagination-link">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php elseif (abs($i - $current_page) === 3): ?>
                    <li class="pagination-item">
                        <span class="pagination-ellipsis">...</span>
                    </li>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($current_page < $total_pages): ?>
                <li class="pagination-item">
                    <a href="<?php echo htmlspecialchars($base_url . $query_separator . 'page=' . ($current_page + 1)); ?>" class="pagination-link">
                        Next →
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php
}

/**
 * Get relative time string
 * 
 * @param string $datetime Datetime string
 * @return string Relative time string
 */
function get_relative_time($datetime) {
    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
        return $datetime;
    }
    
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}

/**
 * Render empty state
 * 
 * @param string $title Empty state title
 * @param string $description Empty state description
 * @param string $action_url Optional action button URL
 * @param string $action_text Optional action button text
 * @return void
 */
function render_empty_state($title, $description = '', $action_url = '', $action_text = '') {
    ?>
    <div class="empty-state">
        <div class="empty-state-icon">📭</div>
        <h3 class="empty-state-title"><?php echo htmlspecialchars($title); ?></h3>
        <?php if ($description): ?>
            <p class="empty-state-description"><?php echo htmlspecialchars($description); ?></p>
        <?php endif; ?>
        <?php if ($action_url && $action_text): ?>
            <a href="<?php echo htmlspecialchars($action_url); ?>" class="btn btn-primary">
                <?php echo htmlspecialchars($action_text); ?>
            </a>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Format file size for display
 * 
 * @param int $bytes File size in bytes
 * @return string Formatted file size
 */
function format_file_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Get role badge HTML
 * 
 * @param string $role Role value
 * @return string HTML for role badge
 */
function get_role_badge($role) {
    $role_config = [
        'ADMIN' => ['label' => 'Administrator', 'type' => 'danger'],
        'SUPPORT' => ['label' => 'Support', 'type' => 'info'],
        'INFRASTRUCTURE' => ['label' => 'Infrastructure', 'type' => 'purple'],
        'SALES' => ['label' => 'Sales', 'type' => 'success'],
        'SALES_MANAGER' => ['label' => 'Sales Manager', 'type' => 'teal'],
        'OPERATIONS' => ['label' => 'Operations', 'type' => 'orange'],
        'CONTENT_MANAGER' => ['label' => 'Content Manager', 'type' => 'warning'],
        'CONTENT_EDITOR' => ['label' => 'Content Editor', 'type' => 'warning'], // Legacy
        'CUSTOMER' => ['label' => 'Customer', 'type' => 'secondary'],
    ];
    
    $config = $role_config[$role] ?? ['label' => $role, 'type' => 'secondary'];
    
    return '<span class="badge badge-' . htmlspecialchars($config['type']) . '">' . htmlspecialchars($config['label']) . '</span>';
}

/**
 * Get multiple role badges HTML
 * 
 * @param array $roles Array of role values
 * @return string HTML for role badges
 */
function get_role_badges($roles) {
    $badges = [];
    foreach ($roles as $role) {
        $badges[] = get_role_badge($role);
    }
    return implode(' ', $badges);
}
