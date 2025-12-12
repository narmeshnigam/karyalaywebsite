<?php
/**
 * Admin Header Template
 * Displays the admin panel header with sidebar navigation
 * Menu items are shown/hidden based on user permissions
 */

// Load template helpers for get_base_url()
if (!function_exists('get_base_url')) {
    require_once __DIR__ . '/../includes/template_helpers.php';
}

// Load admin helpers for permission checking
if (!function_exists('has_permission')) {
    require_once __DIR__ . '/../includes/admin_helpers.php';
}

use Karyalay\Services\RoleService;

// Get base URL for proper path generation
$base_url = get_base_url();
$app_base_url = get_app_base_url();
$admin_base_url = $app_base_url; // Admin is outside /public, same as app

// Ensure user is authenticated and has admin panel access
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/login.php');
    exit;
}

// Check if user can access admin panel
if (!RoleService::canAccessAdmin($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/login.php');
    exit;
}

// Get user's roles and permissions for menu display
$user_roles = RoleService::getUserRoles($_SESSION['user_id']);
$user_permissions = RoleService::getUserPermissions($_SESSION['user_id']);

// Helper function to check if user has permission (local scope)
$canAccess = function($permission) use ($user_permissions) {
    return in_array($permission, $user_permissions);
};

// Get current page for active nav highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_section = '';

// Determine current section based on URL path
$request_uri = $_SERVER['REQUEST_URI'];
if (strpos($request_uri, '/admin/solutions') !== false || strpos($request_uri, '/admin/features') !== false || 
    strpos($request_uri, '/admin/blog') !== false || strpos($request_uri, '/admin/case-studies') !== false || 
    strpos($request_uri, '/admin/media-library') !== false || strpos($request_uri, '/admin/hero-slides') !== false ||
    strpos($request_uri, '/admin/why-choose-cards') !== false || strpos($request_uri, '/admin/faqs') !== false ||
    strpos($request_uri, '/admin/faq-') !== false || strpos($request_uri, '/admin/testimonials') !== false ||
    strpos($request_uri, '/admin/about-page') !== false || strpos($request_uri, '/admin/legal') !== false) {
    $current_section = 'content';
} elseif (strpos($request_uri, '/admin/plans') !== false || strpos($request_uri, '/admin/orders') !== false || strpos($request_uri, '/admin/invoices') !== false) {
    $current_section = 'billing';
} elseif (strpos($request_uri, '/admin/ports') !== false || strpos($request_uri, '/admin/port-allocation-logs') !== false) {
    $current_section = 'ports';
} elseif (strpos($request_uri, '/admin/customers') !== false || strpos($request_uri, '/admin/subscriptions') !== false) {
    $current_section = 'customers';
} elseif (strpos($request_uri, '/admin/support') !== false) {
    $current_section = 'support';
} elseif (strpos($request_uri, '/admin/leads') !== false) {
    $current_section = 'leads';
} elseif (strpos($request_uri, '/admin/settings') !== false || strpos($request_uri, '/admin/users-and-roles') !== false) {
    $current_section = 'settings';
} elseif (strpos($request_uri, '/admin/smtp-settings') !== false || strpos($request_uri, '/admin/payment-settings') !== false || strpos($request_uri, '/admin/localisation') !== false || strpos($request_uri, '/admin/database-settings') !== false) {
    $current_section = 'integrations';
}

// Define menu sections with their required permissions
$menu_sections = [
    'infrastructure' => [
        'title' => 'Infrastructure',
        'items' => [
            ['url' => '/admin/support/tickets.php', 'label' => 'Tickets', 'permission' => 'tickets.view', 'section' => 'support'],
            ['url' => '/admin/ports.php', 'label' => 'Ports', 'permission' => 'ports.view', 'section' => 'ports'],
            ['url' => '/admin/plans.php', 'label' => 'Plans', 'permission' => 'plans.view', 'section' => 'billing', 'match' => '/plans'],
        ]
    ],
    'marketing_sales' => [
        'title' => 'Marketing & Sales',
        'items' => [
            ['url' => '/admin/leads.php', 'label' => 'Leads', 'permission' => 'leads.view', 'section' => 'leads'],
            ['url' => '/admin/customers.php', 'label' => 'Customers', 'permission' => 'customers.view', 'section' => 'customers', 'match' => '/customers'],
            ['url' => '/admin/orders.php', 'label' => 'Orders', 'permission' => 'orders.view', 'section' => 'billing', 'match' => '/orders'],
            ['url' => '/admin/invoices.php', 'label' => 'Invoices', 'permission' => 'invoices.view', 'section' => 'billing', 'match' => '/invoices'],
            ['url' => '/admin/subscriptions.php', 'label' => 'Subscriptions', 'permission' => 'subscriptions.view', 'section' => 'customers', 'match' => '/subscriptions'],
        ]
    ],
    'content' => [
        'title' => 'Content',
        'items' => [
            ['url' => '/admin/hero-slides.php', 'label' => 'Hero Slides', 'permission' => 'hero_slides.manage', 'section' => 'content', 'match' => '/hero-slides'],
            ['url' => '/admin/solutions.php', 'label' => 'Solutions', 'permission' => 'solutions.manage', 'section' => 'content', 'match' => '/solutions'],
            ['url' => '/admin/features.php', 'label' => 'Features', 'permission' => 'content.view', 'section' => 'content', 'match' => '/features'],
            ['url' => '/admin/why-choose-cards.php', 'label' => 'Why Choose', 'permission' => 'why_choose.manage', 'section' => 'content', 'match' => '/why-choose-cards'],
            ['url' => '/admin/testimonials.php', 'label' => 'Testimonials', 'permission' => 'testimonials.manage', 'section' => 'content', 'match' => '/testimonials'],
            ['url' => '/admin/blog.php', 'label' => 'Blog Posts', 'permission' => 'blog.manage', 'section' => 'content', 'match' => '/blog'],
            ['url' => '/admin/case-studies.php', 'label' => 'Case Studies', 'permission' => 'case_studies.manage', 'section' => 'content', 'match' => '/case-studies'],
            ['url' => '/admin/about-page.php', 'label' => 'About', 'permission' => 'about.manage', 'match' => '/about-page'],
            ['url' => '/admin/legal.php', 'label' => 'Legal', 'permission' => 'legal.manage', 'match' => '/legal'],
            ['url' => '/admin/faqs.php', 'label' => 'FAQs', 'permission' => 'faqs.manage', 'section' => 'content', 'match' => '/faqs'],
            ['url' => '/admin/media-library.php', 'label' => 'Media Library', 'permission' => 'media.view', 'section' => 'content', 'match' => '/media-library'],
        ]
    ],
    'settings' => [
        'title' => 'Settings',
        'items' => [
            ['url' => '/admin/smtp-settings.php', 'label' => 'SMTP Integration', 'permission' => 'settings.smtp', 'section' => 'integrations', 'match' => '/smtp-settings'],
            ['url' => '/admin/payment-settings.php', 'label' => 'Payment Integration', 'permission' => 'settings.payment', 'section' => 'integrations', 'match' => '/payment-settings'],
            ['url' => '/admin/database-settings.php', 'label' => 'Database', 'permission' => 'settings.general', 'section' => 'integrations', 'match' => '/database-settings'],
            ['url' => '/admin/localisation.php', 'label' => 'Localisation', 'permission' => 'settings.localisation', 'section' => 'integrations', 'match' => '/localisation'],
            ['url' => '/admin/users-and-roles.php', 'label' => 'Users & Roles', 'permission' => 'users.view', 'section' => 'settings', 'match' => '/users-and-roles'],
            ['url' => '/admin/settings/general.php', 'label' => 'General', 'permission' => 'settings.general', 'section' => 'settings', 'match' => '/settings'],
        ]
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - Admin' : 'Admin Panel'; ?> - <?php echo htmlspecialchars(get_brand_name()); ?></title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo css_url('main.css'); ?>">
    <link rel="stylesheet" href="<?php echo css_url('admin.css'); ?>">
    
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css_file): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($css_file); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <style>
        /* Additional badge colors for new roles */
        .badge-purple { background-color: #8b5cf6; color: white; }
        .badge-teal { background-color: #14b8a6; color: white; }
        .badge-orange { background-color: #f97316; color: white; }
    </style>
</head>
<body class="admin-body">
    <div class="admin-wrapper">
        <!-- Admin Sidebar -->
        <aside class="admin-sidebar" role="navigation" aria-label="Admin Navigation">
            <div class="admin-sidebar-header">
                <a href="<?php echo $admin_base_url; ?>/admin/dashboard.php" class="admin-logo">
                    <?php echo render_brand_logo('dark_bg', 'admin-logo-img', 32); ?>
                    <span class="admin-logo-badge">Admin</span>
                </a>
            </div>

            <nav class="admin-nav">
                <ul class="admin-nav-list">
                    <!-- Dashboard - Always visible for admin panel users -->
                    <li class="admin-nav-item admin-nav-dashboard">
                        <a href="<?php echo $admin_base_url; ?>/admin/dashboard.php" class="admin-nav-link admin-nav-link-dashboard <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                            <span class="admin-nav-text">Dashboard</span>
                        </a>
                    </li>

                    <?php foreach ($menu_sections as $section_key => $section): ?>
                        <?php 
                        // Check if user has access to any item in this section
                        $hasAccessToSection = false;
                        foreach ($section['items'] as $item) {
                            if ($canAccess($item['permission'])) {
                                $hasAccessToSection = true;
                                break;
                            }
                        }
                        
                        if (!$hasAccessToSection) continue;
                        ?>
                        
                        <!-- <?php echo htmlspecialchars($section['title']); ?> -->
                        <li class="admin-nav-item admin-nav-section">
                            <span class="admin-nav-section-title"><?php echo htmlspecialchars($section['title']); ?></span>
                        </li>
                        
                        <?php foreach ($section['items'] as $item): ?>
                            <?php if (!$canAccess($item['permission'])) continue; ?>
                            
                            <?php 
                            $isActive = false;
                            if (isset($item['match'])) {
                                $isActive = strpos($request_uri, $item['match']) !== false;
                            } elseif (isset($item['section'])) {
                                $isActive = $current_section === $item['section'];
                            }
                            ?>
                            
                            <li class="admin-nav-item admin-nav-subitem">
                                <a href="<?php echo $admin_base_url . htmlspecialchars($item['url']); ?>" 
                                   class="admin-nav-link <?php echo $isActive ? 'active' : ''; ?>">
                                    <span class="admin-nav-text"><?php echo htmlspecialchars($item['label']); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <div class="admin-sidebar-footer">
                <a href="<?php echo $base_url; ?>/" class="admin-sidebar-link" target="_blank">
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
                            <div class="admin-user-dropdown-header">
                                <span class="admin-user-roles">
                                    <?php 
                                    // Show user's roles (excluding CUSTOMER for admin users)
                                    $displayRoles = array_filter($user_roles, function($r) { return $r !== 'CUSTOMER'; });
                                    if (empty($displayRoles)) $displayRoles = ['CUSTOMER'];
                                    echo implode(', ', array_map(function($r) {
                                        return ucwords(strtolower(str_replace('_', ' ', $r)));
                                    }, $displayRoles));
                                    ?>
                                </span>
                            </div>
                            <a href="<?php echo $app_base_url; ?>/app/profile.php" class="admin-user-dropdown-item">Profile</a>
                            <a href="<?php echo $app_base_url; ?>/app/security.php" class="admin-user-dropdown-item">Security</a>
                            <div class="admin-user-dropdown-divider"></div>
                            <a href="<?php echo $app_base_url; ?>/app/dashboard.php" class="admin-user-dropdown-item">
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
