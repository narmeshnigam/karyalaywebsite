<?php
/**
 * Header Template
 * Displays the site header with navigation
 */

// Get current page for active nav highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Get base URL for dynamic path generation
$base_url = get_base_url();
$app_base_url = get_app_base_url();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' . htmlspecialchars(get_brand_name()) : htmlspecialchars(get_brand_name()) . ' - Business Management System'; ?></title>
    <meta name="description" content="<?php echo isset($page_description) ? htmlspecialchars($page_description) : htmlspecialchars(get_brand_name()) . ' - ' . htmlspecialchars(get_footer_company_description()); ?>">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo css_url('main.css'); ?>">
    
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css_file): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($css_file); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <div class="page-wrapper">
        <header class="site-header" role="banner">
            <div class="header-container">
                <!-- Logo -->
                <a href="<?php echo $base_url; ?>/" class="site-logo" aria-label="<?php echo htmlspecialchars(get_brand_name()); ?> Home">
                    <?php echo render_brand_logo('light_bg', 'header-logo', 40); ?>
                </a>

                <!-- Desktop Navigation -->
                <nav class="main-nav" role="navigation" aria-label="Main Navigation">
                    <ul class="main-nav-list">
                        <li>
                            <a href="<?php echo $base_url; ?>/solutions.php" class="main-nav-link <?php echo $current_page === 'solutions' ? 'active' : ''; ?>">
                                Solutions
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $base_url; ?>/features.php" class="main-nav-link <?php echo $current_page === 'features' ? 'active' : ''; ?>">
                                Features
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $base_url; ?>/pricing.php" class="main-nav-link <?php echo $current_page === 'pricing' ? 'active' : ''; ?>">
                                Pricing
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $base_url; ?>/case-studies.php" class="main-nav-link <?php echo $current_page === 'case-studies' ? 'active' : ''; ?>">
                                Case Studies
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $base_url; ?>/faqs.php" class="main-nav-link <?php echo $current_page === 'faqs' ? 'active' : ''; ?>">
                                FAQs
                            </a>
                        </li>
                    </ul>
                </nav>

                <!-- Header Actions (Desktop) -->
                <div class="header-actions">
                    <?php if (isAuthenticated()): ?>
                        <a href="<?php echo $app_base_url; ?>/app/dashboard.php" class="btn btn-outline btn-sm">Dashboard</a>
                        <a href="<?php echo $base_url; ?>/logout.php" class="btn btn-secondary btn-sm">Logout</a>
                    <?php else: ?>
                        <a href="<?php echo $base_url; ?>/login.php" class="btn btn-outline btn-sm">Login</a>
                        <a href="<?php echo $base_url; ?>/register.php" class="btn btn-primary btn-sm">Get Started</a>
                    <?php endif; ?>
                </div>

                <!-- Mobile Menu Toggle -->
                <button class="mobile-menu-toggle" aria-label="Toggle mobile menu" aria-expanded="false">
                    <span class="hamburger-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>
            </div>

            <!-- Mobile Menu -->
            <nav class="mobile-menu" role="navigation" aria-label="Mobile Navigation">
                <ul class="mobile-menu-list">
                    <li class="mobile-menu-item">
                        <a href="<?php echo $base_url; ?>/solutions.php" class="mobile-menu-link <?php echo $current_page === 'solutions' ? 'active' : ''; ?>">
                            Solutions
                        </a>
                    </li>
                    <li class="mobile-menu-item">
                        <a href="<?php echo $base_url; ?>/features.php" class="mobile-menu-link <?php echo $current_page === 'features' ? 'active' : ''; ?>">
                            Features
                        </a>
                    </li>
                    <li class="mobile-menu-item">
                        <a href="<?php echo $base_url; ?>/pricing.php" class="mobile-menu-link <?php echo $current_page === 'pricing' ? 'active' : ''; ?>">
                            Pricing
                        </a>
                    </li>
                    <li class="mobile-menu-item">
                        <a href="<?php echo $base_url; ?>/case-studies.php" class="mobile-menu-link <?php echo $current_page === 'case-studies' ? 'active' : ''; ?>">
                            Case Studies
                        </a>
                    </li>
                    <li class="mobile-menu-item">
                        <a href="<?php echo $base_url; ?>/faqs.php" class="mobile-menu-link <?php echo $current_page === 'faqs' ? 'active' : ''; ?>">
                            FAQs
                        </a>
                    </li>
                </ul>

                <div class="mobile-menu-actions">
                    <?php if (isAuthenticated()): ?>
                        <a href="<?php echo $app_base_url; ?>/app/dashboard.php" class="btn btn-outline">Dashboard</a>
                        <a href="<?php echo $base_url; ?>/logout.php" class="btn btn-secondary">Logout</a>
                    <?php else: ?>
                        <a href="<?php echo $base_url; ?>/login.php" class="btn btn-outline">Login</a>
                        <a href="<?php echo $base_url; ?>/register.php" class="btn btn-primary">Get Started</a>
                    <?php endif; ?>
                </div>
            </nav>
        </header>

        <main class="main-content" role="main">
