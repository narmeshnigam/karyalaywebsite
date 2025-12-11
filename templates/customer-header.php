<?php
/**
 * Customer Portal Header Template
 * Displays the customer portal header with navigation
 */

// Load template helpers for get_base_url() and css_url()
if (!function_exists('get_base_url')) {
    require_once __DIR__ . '/../includes/template_helpers.php';
}

use Karyalay\Services\RoleService;

// Get base URL for proper path generation
$base_url = get_base_url();
$app_base_url = get_app_base_url();

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/login.php');
    exit;
}

// Get current page for active nav highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_section = '';

// Determine current section based on URL path
$request_uri = $_SERVER['REQUEST_URI'];
if (strpos($request_uri, '/app/dashboard') !== false) {
    $current_section = 'dashboard';
} elseif (strpos($request_uri, '/app/my-port') !== false) {
    $current_section = 'my-port';
} elseif (strpos($request_uri, '/app/subscription') !== false || strpos($request_uri, '/app/plans') !== false || strpos($request_uri, '/app/setup') !== false) {
    $current_section = 'subscription';
} elseif (strpos($request_uri, '/app/billing') !== false) {
    $current_section = 'billing';
} elseif (strpos($request_uri, '/app/profile') !== false || strpos($request_uri, '/app/security') !== false) {
    $current_section = 'profile';
} elseif (strpos($request_uri, '/app/support') !== false) {
    $current_section = 'support';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - Customer Portal' : 'Customer Portal'; ?> - <?php echo htmlspecialchars(get_brand_name()); ?></title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo css_url('main.css'); ?>">
    <link rel="stylesheet" href="<?php echo css_url('customer-portal.css'); ?>">
    
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css_file): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($css_file); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="customer-portal-body">
    <div class="customer-portal-wrapper">
        <!-- Customer Portal Sidebar -->
        <aside class="customer-portal-sidebar" role="navigation" aria-label="Customer Portal Navigation">
            <div class="customer-portal-sidebar-header">
                <a href="<?php echo $app_base_url; ?>/app/dashboard.php" class="customer-portal-logo">
                    <?php echo render_brand_logo('dark_bg', 'customer-portal-logo-img', 32); ?>
                </a>
            </div>

            <nav class="customer-portal-nav">
                <ul class="customer-portal-nav-list">
                    <!-- Dashboard -->
                    <li class="customer-portal-nav-item">
                        <a href="<?php echo $app_base_url; ?>/app/dashboard.php" class="customer-portal-nav-link <?php echo $current_section === 'dashboard' ? 'active' : ''; ?>">
                            <span class="customer-portal-nav-icon">📊</span>
                            <span class="customer-portal-nav-text">Dashboard</span>
                        </a>
                    </li>

                    <!-- Subscription -->
                    <li class="customer-portal-nav-item">
                        <a href="<?php echo $app_base_url; ?>/app/subscription.php" class="customer-portal-nav-link <?php echo $current_section === 'subscription' ? 'active' : ''; ?>">
                            <span class="customer-portal-nav-icon">📦</span>
                            <span class="customer-portal-nav-text">Subscription</span>
                        </a>
                    </li>

                    <!-- My Port -->
                    <li class="customer-portal-nav-item">
                        <a href="<?php echo $app_base_url; ?>/app/my-port.php" class="customer-portal-nav-link <?php echo $current_section === 'my-port' ? 'active' : ''; ?>">
                            <span class="customer-portal-nav-icon">🔌</span>
                            <span class="customer-portal-nav-text">My Port</span>
                        </a>
                    </li>

                    <!-- Billing -->
                    <li class="customer-portal-nav-item">
                        <a href="<?php echo $app_base_url; ?>/app/billing/history.php" class="customer-portal-nav-link <?php echo $current_section === 'billing' ? 'active' : ''; ?>">
                            <span class="customer-portal-nav-icon">💳</span>
                            <span class="customer-portal-nav-text">Billing</span>
                        </a>
                    </li>

                    <!-- Profile -->
                    <li class="customer-portal-nav-item">
                        <a href="<?php echo $app_base_url; ?>/app/profile.php" class="customer-portal-nav-link <?php echo $current_section === 'profile' ? 'active' : ''; ?>">
                            <span class="customer-portal-nav-icon">👤</span>
                            <span class="customer-portal-nav-text">Profile</span>
                        </a>
                    </li>

                    <!-- Support -->
                    <li class="customer-portal-nav-item">
                        <a href="<?php echo $app_base_url; ?>/app/support/tickets.php" class="customer-portal-nav-link <?php echo $current_section === 'support' ? 'active' : ''; ?>">
                            <span class="customer-portal-nav-icon">🎫</span>
                            <span class="customer-portal-nav-text">Support</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="customer-portal-sidebar-footer">
                <a href="<?php echo $base_url; ?>/" class="customer-portal-sidebar-link" target="_blank">
                    <span class="customer-portal-nav-icon">🌐</span>
                    <span class="customer-portal-nav-text">View Website</span>
                </a>
            </div>
        </aside>

        <!-- Customer Portal Main Content -->
        <div class="customer-portal-main">
            <!-- Customer Portal Top Bar -->
            <header class="customer-portal-topbar">
                <div class="customer-portal-topbar-left">
                    <button class="customer-portal-sidebar-toggle" aria-label="Toggle sidebar">
                        <span class="hamburger-icon">
                            <span></span>
                            <span></span>
                            <span></span>
                        </span>
                    </button>
                    
                    <?php if (isset($page_title)): ?>
                        <h1 class="customer-portal-page-title"><?php echo htmlspecialchars($page_title); ?></h1>
                    <?php endif; ?>
                </div>

                <div class="customer-portal-topbar-right">
                    <div class="customer-portal-user-menu">
                        <button class="customer-portal-user-button" aria-label="User menu" aria-expanded="false">
                            <span class="customer-portal-user-avatar">
                                <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                            </span>
                            <span class="customer-portal-user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                            <span class="customer-portal-user-arrow">▼</span>
                        </button>
                        
                        <div class="customer-portal-user-dropdown">
                            <a href="<?php echo $app_base_url; ?>/app/profile.php" class="customer-portal-user-dropdown-item">Profile</a>
                            <a href="<?php echo $app_base_url; ?>/app/security.php" class="customer-portal-user-dropdown-item">Security</a>
                            <?php if (isset($_SESSION['user_id']) && RoleService::canAccessAdmin($_SESSION['user_id'])): ?>
                                <div class="customer-portal-user-dropdown-divider"></div>
                                <a href="<?php echo $app_base_url; ?>/admin/dashboard.php" class="customer-portal-user-dropdown-item">
                                    <span class="dropdown-icon">⚡</span>
                                    Admin Dashboard
                                </a>
                            <?php endif; ?>
                            <div class="customer-portal-user-dropdown-divider"></div>
                            <a href="<?php echo $base_url; ?>/logout.php" class="customer-portal-user-dropdown-item">Logout</a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Customer Portal Content Area -->
            <main class="customer-portal-content" role="main">
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($_SESSION['flash_type'] ?? 'info'); ?>" role="alert">
                        <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
                    </div>
                    <?php 
                    unset($_SESSION['flash_message']);
                    unset($_SESSION['flash_type']);
                    ?>
                <?php endif; ?>
