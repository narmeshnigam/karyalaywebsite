<?php
/**
 * Solution Detail Page - Modern ERP Office Management
 * A beautifully designed page showcasing solution details with linked features
 */

require_once __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/app.php';

if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

require_once __DIR__ . '/../includes/auth_helpers.php';
startSecureSession();
require_once __DIR__ . '/../includes/template_helpers.php';

use Karyalay\Models\Solution;

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: ' . get_base_url() . '/solutions.php');
    exit;
}

try {
    $solutionModel = new Solution();
    $solution = $solutionModel->findBySlug($slug);
    
    if (!$solution || $solution['status'] !== 'PUBLISHED') {
        header('HTTP/1.0 404 Not Found');
        $page_title = 'Solution Not Found';
        $page_description = 'The requested solution could not be found';
        include_header($page_title, $page_description);
        ?>
        <section class="section">
            <div class="container">
                <div class="not-found-card">
                    <div class="not-found-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h1>Solution Not Found</h1>
                    <p>The solution you're looking for doesn't exist or is no longer available.</p>
                    <a href="<?php echo get_base_url(); ?>/solutions.php" class="btn btn-primary">View All Solutions</a>
                </div>
            </div>
        </section>
        <?php
        include_footer();
        exit;
    }

    // Get linked features
    $linkedFeatures = $solutionModel->getLinkedFeatures($solution['id']);
    
    // Get related solutions
    $relatedSolutions = $solutionModel->getRelatedSolutions($solution['id'], 3);
    
} catch (Exception $e) {
    error_log('Error fetching solution: ' . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    $page_title = 'Error';
    include_header($page_title, '');
    ?>
    <section class="section">
        <div class="container">
            <div class="not-found-card">
                <h1>Error</h1>
                <p>An error occurred while loading the solution. Please try again later.</p>
                <a href="<?php echo get_base_url(); ?>/solutions.php" class="btn btn-primary">View All Solutions</a>
            </div>
        </div>
    </section>
    <?php
    include_footer();
    exit;
}

$page_title = htmlspecialchars($solution['name']);
$page_description = htmlspecialchars($solution['description'] ?? '');
$colorTheme = $solution['color_theme'] ?? '#667eea';

include_header($page_title, $page_description);
?>

<!-- Hero Section with Animated Background -->
<section class="solution-hero" style="--theme-color: <?php echo htmlspecialchars($colorTheme); ?>">
    <div class="hero-bg-pattern"></div>
    <div class="hero-gradient-orb hero-orb-1"></div>
    <div class="hero-gradient-orb hero-orb-2"></div>
    
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo get_base_url(); ?>/">Home</a>
            <svg class="breadcrumb-sep" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
            <a href="<?php echo get_base_url(); ?>/solutions.php">Solutions</a>
            <svg class="breadcrumb-sep" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
            <span aria-current="page"><?php echo htmlspecialchars($solution['name']); ?></span>
        </nav>
        
        <div class="hero-content">
            <div class="hero-text animate-fade-up">
                <?php if (!empty($solution['icon_image'])): ?>
                    <div class="hero-icon">
                        <img src="<?php echo htmlspecialchars($solution['icon_image']); ?>" alt="">
                    </div>
                <?php else: ?>
                    <div class="hero-icon hero-icon-default">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($solution['tagline'])): ?>
                    <p class="hero-tagline"><?php echo htmlspecialchars($solution['tagline']); ?></p>
                <?php endif; ?>
                
                <h1 class="hero-title"><?php echo htmlspecialchars($solution['name']); ?></h1>
                
                <?php if (!empty($solution['description'])): ?>
                    <p class="hero-description"><?php echo htmlspecialchars($solution['description']); ?></p>
                <?php endif; ?>
                
                <div class="hero-actions">
                    <a href="#contact-form" class="btn btn-primary btn-lg">
                        Get Started
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                    <a href="#features" class="btn btn-outline-light btn-lg">
                        Explore Features
                    </a>
                </div>
            </div>
            
            <?php if (!empty($solution['hero_image'])): ?>
                <div class="hero-visual animate-fade-up" style="animation-delay: 0.2s">
                    <img src="<?php echo htmlspecialchars($solution['hero_image']); ?>" alt="<?php echo htmlspecialchars($solution['name']); ?>">
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
