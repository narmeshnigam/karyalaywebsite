<?php
/**
 * Admin Header Template
 * Displays the admin panel header with sidebar navigation
 */

// Load template helpers for get_base_url()
if (!function_exists('get_base_url')) {
    require_once __DIR__ . '/../includes/template_helpers.php';
}

// Get base URL for proper path generation
$base_url = get_base_url();

// Ensure user is authenticated and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'ADMIN') {
    header('Location: ' . $base_url . '/login.php');
    exit;
}

// Get current page for active nav highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_section = '';

// Determine current section based on URL path
$request_uri = $_SERVER['REQUEST_URI'];
if (strpos($request_uri, '/admin/solutions') !== false || strpos($request_uri, '/admin/features') !== false || 
    strpos($request_uri, '/admin/blog') !== false || strpos($request_uri, '/admin/case-studies') !== false || 
    strpos($request_uri, '/admin/media-library') !== false || strpos($request_uri, '/admin/hero-slides') !== false ||
    strpos($request_uri, '/admin/why-choose-cards') !== false) {
    $current_section = 'content';
} elseif (strpos($request_uri, '/admin/plans') !== false || strpos($request_uri, '/admin/orders') !== false) {
    $current_section = 'billing';
} elseif (strpos($request_uri, '/admin/ports') !== false) {
    $current_section = 'ports';
} elseif (strpos($request_uri, '/admin/customers') !== false || strpos($request_uri, '/admin/subscriptions') !== false) {
    $current_section = 'customers';
} elseif (strpos($request_uri, '/admin/support') !== false) {
    $current_section = 'support';
} elseif (strpos($request_uri, '/admin/leads') !== false) {
    $current_section = 'leads';
} elseif (strpos($request_uri, '/admin/settings') !== false || strpos($request_uri, '/admin/users-and-roles') !== false) {
    $current_section = 'settings';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - Admin' : 'Admin Panel'; ?> - Karyalay</title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo css_url('main.css'); ?>">
    <link rel="stylesheet" href="<?php echo css_url('admin.css'); ?>">
    
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css_file): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($css_file); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="admin-body">
    <div class="admin-wrapper">
        <!-- Admin Sidebar -->
        <aside class="admin-sidebar" role="navigation" aria-label="Admin Navigation">
            <div class="admin-sidebar-header">
                <a href="<?php echo $base_url; ?>/admin/dashboard.php" class="admin-logo">
                    <span class="admin-logo-text">Karyalay</span>
                    <span class="admin-logo-badge">Admin</span>
                </a>
            </div>

            <nav class="admin-nav">
                <ul class="admin-nav-list">
                    <!-- Dashboard -->
                    <li class="admin-nav-item">
                        <a href="<?php echo $base_url; ?>/admin/dashboard.php" class="admin-nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                            <span class="admin-nav-icon">📊</span>
                            <span class="admin-nav-text">Dashboard</span>
                        </a>
                    </li>

                    <!-- Content Management -->
                    <li class="admin-nav-item admin-nav-section">
                        <span class="admin-nav-section-title">Content</span>
                    </li>
                    <li class="admin-nav-item">
                        <a href="<?php echo $base_url; ?>/admin/solutions.php" class="admin-nav-link <?php echo $current_section === 'content' && strpos($request_uri, '/solutions') !== false ? 'active' : ''; ?>">
                            <span class="admin-nav-icon">📦</span>
                            <span class="admin-nav-text">Solutions</span>
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="<?php echo $base_url; ?>/admin/features.php" class="admin-nav-link <?php echo $current_section === 'content' && strpos($request_uri, '/features') !== false ? 'active' : ''; ?>">
                            <span class="admin-nav-icon">⭐</span>
                            <span class="admin-nav-text">Features</span>
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="<?php echo $base_url; ?>/admin/blog.php" class="admin-nav-link <?php echo $current_section === 'content' && strpos($request_uri, '/blog') !== false ? 'active' : ''; ?>">
                            <span class="admin-nav-icon">📝</span>
                            <span class="admin-nav-text">Blog Posts</span>
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="<?php echo $base_url; ?>/admin/case-studies.php" class="admin-nav-link <?php echo $current_section === 'content' && strpos($request_uri, '/case-studies') !== false ? 'active' : ''; ?>">
                            <span class="admin-nav-icon">📄</span>
                            <span class="admin-nav-text">Case Studies</span>
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="<?php echo $base_url; ?>/admin/media-library.php" class="admin-nav-link <?php echo $current_section === 'content' && strpos($request_uri, '/media-library') !== false ? 'active' : ''; ?>">
                            <span class="admin-nav-icon">🖼️</span>
                            <span class="admin-nav-text">Media Library</span>
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="<?php echo $base_url; ?>/admin/hero-slides.php" class="admin-nav-link <?php echo $current_section === 'content' && strpos($request_uri, '/hero-slides') !== false ? 'active' : ''; ?>">
                            <span class="admin-nav-icon">🎠</span>
                            <span class="admin-nav-text">Hero Slides</span>
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="<?php echo $base_url; ?>/admin/why-choose-cards.php" class="admin-nav-link <?php echo $current_section === 'content' && strpos($request_uri, '/why-choose-cards') !== false ? 'active' : ''; ?>">
                            <span class="admin-nav-icon">💡</span>
                            <span class="admin-nav-text">Why Choose Cards</span>
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="<?php echo $base_url; ?>/admin/testimonials.php" class="admin-nav-link <?php echo $current_section === 'content' && strpos($request_uri, '/testimonials') !== false ? 'active' : ''; ?>">
                            <span class="admin-nav-icon">⭐</span>
                            <span class="admin-nav-text">Testimonials</span>
                        </a>
                    </li>

                    <!-- Pages -->
                    <li class="admin-nav-item admin-nav-section">
                        <span class="admin-nav-section-title">Pages</span>
                    </li>
                    <li class="admin-nav-item">
                        <a href="<?php echo $base_url; ?>/admin/about-page.php" class="admin-nav-link <?php echo strpos($request_uri, '/about-page') !== false ? 'active' : ''; ?>">
                            <span class="admin-nav-icon">📄</span>
                            <span class="admin-nav-text">About Page</span>
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="<?php echo $base_url; ?>/admin/legal.php" class="admin-nav-link <?php echo strpos($request_uri, '/legal') !== false ? 'active' : ''; ?>">
                            <span class="admin-nav-icon">⚖️</span>
                            <span class="admin-nav-text">Legal</span>
                        </a>
                    </li>

                    <!-- Leads -->
                    <li class="admin-nav-item admin-nav-section">
                        <span class="admin-nav-section-title">Marketing</span>
                    </li>
                    <li class="admin-nav-item">
                        <a href="<?php echo $base_url; ?>/admin/leads.php" class="admin-nav-link <?php echo $current_section === 'leads' ? 'active' : ''; ?>">
                            <span class="admin-nav-icon">📧</span>
                            <span class="admin-nav-text">Leads</span>
                        </a>
                    </li>

                    <!-- Plans & Billing -->
                    <li class="admin-nav-item admin-nav-section">
                        <span class="admin-nav-section-title">Billing</span>
                    </li>
                    <li class="admin-nav-item">
                        <a href="<?php echo $base_url; ?>/admin/plans.php" class="admin-nav-link <?php echo $current_section === 'billing' && strpos($request_uri, '/plans') !== false ? 'active' : ''; ?>">
                            <span class="admin-nav-icon">💳</span>
                            <span class="admin-nav-text">Plans</span>
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="<?php echo $base_url; ?>/admin/orders.php" class="admin-nav-link <?php echo $current_section === 'billing' && strpos($request_uri, '/orders') !== false ? 'active' : ''; ?>">
                            <span class="admin-nav-icon">🧾</span>
                            <span class="admin-nav-text">Orders</span>
                        </a>
                    </li>

                    <!-- Port Management -->
                    <li class="admin-nav-item admin-nav-section">
                        <span class="admin-nav-section-title">Infrastructure</span>
                    </li>
                    <li class="admin-nav-item">
                        <a href="<?php echo $base_url; ?>/admin/ports.php" class="admin-nav-link <?php echo $current_section === 'ports' ? 'active' : ''; ?>">
                            <span class="admin-nav-icon">🔌</span>
                            <span class="admin-nav-text">Ports</span>
                        </a>
                    </li>

                    <!-- Customer Management -->
                    <li class="admin-nav-item admin-nav-section">
                        <span class="admin-nav-section-title">Customers</span>
                    </li>
                    <li class="admin-nav-item">
                        <a href="<?php echo $base_url; ?>/admin/customers.php" class="admin-nav-link <?php echo $current_section === 'customers' && strpos($request_uri, '/customers') !== false ? 'active' : ''; ?>">
                            <span class="admin-nav-icon">👥</span>
                            <span class="admin-nav-text">Customers</span>
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="<?php echo $base_url; ?>/admin/subscriptions.php" class="admin-nav-link <?php echo $current_section === 'customers' && strpos($request_uri, '/subscriptions') !== false ? 'active' : ''; ?>">
                            <span class="admin-nav-icon">📅</span>
                            <span class="admin-nav-text">Subscriptions</span>
                        </a>
                    </li>

                    <!-- Support -->
                    <li class="admin-nav-item admin-nav-section">
                        <span class="admin-nav-section-title">Support</span>
                    </li>
                    <li class="admin-nav-item">
                        <a href="<?php echo $base_url; ?>/admin/support/tickets.php" class="admin-nav-link <?php echo $current_section === 'support' ? 'active' : ''; ?>">
                            <span class="admin-nav-icon">🎫</span>
                            <span class="admin-nav-text">Tickets</span>
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="<?php echo $base_url; ?>/admin/leads.php" class="admin-nav-link <?php echo $current_section === 'leads' ? 'active' : ''; ?>">
                            <span class="admin-nav-icon">📧</span>
                            <span class="admin-nav-text">Leads</span>
                        </a>
                    </li>

                    <!-- Settings -->
                    <li class="admin-nav-item admin-nav-section">
                        <span class="admin-nav-section-title">System</span>
                    </li>
                    <li class="admin-nav-item">
                        <a href="<?php echo $base_url; ?>/admin/users-and-roles.php" class="admin-nav-link <?php echo $current_section === 'settings' && strpos($request_uri, '/users-and-roles') !== false ? 'active' : ''; ?>">
                            <span class="admin-nav-icon">🔐</span>
                            <span class="admin-nav-text">Users & Roles</span>
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="<?php echo $base_url; ?>/admin/settings/general.php" class="admin-nav-link <?php echo $current_section === 'settings' && strpos($request_uri, '/settings') !== false ? 'active' : ''; ?>">
                            <span class="admin-nav-icon">⚙️</span>
                            <span class="admin-nav-text">Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="admin-sidebar-footer">
                <a href="<?php echo $base_url; ?>/" class="admin-sidebar-link" target="_blank">
                    <span class="admin-nav-icon">🌐</span>
                    <span class="admin-nav-text">View Site</span>
                </a>
            </div>
        </aside>

        <!-- Admin Main Content -->
        <div class="admin-main">
            <!-- Admin Top Bar -->
            <header class="admin-topbar">
                <div class="admin-topbar-left">
                    <button class="admin-sidebar-toggle" aria-label="Toggle sidebar">
                        <span class="hamburger-icon">
                            <span></span>
                            <span></span>
                            <span></span>
                        </span>
                    </button>
                    
                    <?php if (isset($page_title)): ?>
                        <h1 class="admin-page-title"><?php echo htmlspecialchars($page_title); ?></h1>
                    <?php endif; ?>
                </div>

                <div class="admin-topbar-right">
                    <div class="admin-user-menu">
                        <button class="admin-user-button" aria-label="User menu" aria-expanded="false">
                            <span class="admin-user-avatar">
                                <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)); ?>
                            </span>
                            <span class="admin-user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                            <span class="admin-user-arrow">▼</span>
                        </button>
                        
                        <div class="admin-user-dropdown">
                            <a href="<?php echo $base_url; ?>/app/profile.php" class="admin-user-dropdown-item">Profile</a>
                            <a href="<?php echo $base_url; ?>/app/security.php" class="admin-user-dropdown-item">Security</a>
                            <div class="admin-user-dropdown-divider"></div>
                            <a href="<?php echo $base_url; ?>/app/dashboard.php" class="admin-user-dropdown-item">
                                <span class="dropdown-icon">👤</span>
                                Customer Portal
                            </a>
                            <div class="admin-user-dropdown-divider"></div>
                            <a href="<?php echo $base_url; ?>/logout.php" class="admin-user-dropdown-item">Logout</a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Admin Content Area -->
            <main class="admin-content" role="main">
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($_SESSION['flash_type'] ?? 'info'); ?>" role="alert">
                        <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
                    </div>
                    <?php 
                    unset($_SESSION['flash_message']);
                    unset($_SESSION['flash_type']);
                    ?>
                <?php endif; ?>
