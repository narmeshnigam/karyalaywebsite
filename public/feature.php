<?php

/**
 * SellerPortal System
 * Feature Detail Page
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration
$config = require __DIR__ . '/../config/app.php';

// Set error reporting based on environment
if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Load authentication helpers
require_once __DIR__ . '/../includes/auth_helpers.php';

// Start secure session
startSecureSession();

// Include template helpers
require_once __DIR__ . '/../includes/template_helpers.php';

use Karyalay\Models\Feature;
use Karyalay\Models\Solution;

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: ' . get_base_url() . '/features.php');
    exit;
}

try {
    $featureModel = new Feature();
    $feature = $featureModel->findBySlug($slug);
    
    if (!$feature || $feature['status'] !== 'PUBLISHED') {
        header('HTTP/1.0 404 Not Found');
        $page_title = 'Feature Not Found';
        $page_description = 'The requested feature could not be found';
        include_header($page_title, $page_description);
        ?>
        <section class="section">
            <div class="container">
                <div class="card">
                    <div class="card-body text-center">
                        <h1 class="text-3xl font-bold mb-4">Feature Not Found</h1>
                        <p class="text-gray-600 mb-6">The feature you're looking for doesn't exist or is no longer available.</p>
                        <a href="<?php echo get_base_url(); ?>/features.php" class="btn btn-primary">View All Features</a>
                    </div>
                </div>
            </div>
        </section>
        <?php
        include_footer();
        exit;
    }
    
    // Fetch related solutions if available
    $relatedSolutions = [];
    if (!empty($feature['related_solutions']) && is_array($feature['related_solutions'])) {
        $solutionModel = new Solution();
        foreach ($feature['related_solutions'] as $solutionSlug) {
            $solution = $solutionModel->findBySlug($solutionSlug);
            if ($solution && $solution['status'] === 'PUBLISHED') {
                $relatedSolutions[] = $solution;
            }
        }
    }
    
} catch (Exception $e) {
    error_log('Error fetching feature: ' . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    $page_title = 'Error';
    $page_description = 'An error occurred';
    include_header($page_title, $page_description);
    ?>
    <section class="section">
        <div class="container">
            <div class="card">
                <div class="card-body text-center">
                    <h1 class="text-3xl font-bold mb-4">Error</h1>
                    <p class="text-gray-600 mb-6">An error occurred while loading the feature. Please try again later.</p>
                    <a href="<?php echo get_base_url(); ?>/features.php" class="btn btn-primary">View All Features</a>
                </div>
            </div>
        </div>
    </section>
    <?php
    include_footer();
    exit;
}

$page_title = htmlspecialchars($feature['name']);
$page_description = htmlspecialchars($feature['description'] ?? '');

include_header($page_title, $page_description);
?>

<!-- Feature Hero Section -->
<section class="feature-hero">
    <div class="container">
        <nav class="breadcrumb">
            <a href="<?php echo get_base_url(); ?>/">Home</a>
            <span class="breadcrumb-separator">›</span>
            <a href="<?php echo get_base_url(); ?>/features.php">Features</a>
            <span class="breadcrumb-separator">›</span>
            <span><?php echo htmlspecialchars($feature['name']); ?></span>
        </nav>
        
        <div class="feature-hero-content">
            <?php if (!empty($feature['icon_image'])): ?>
                <div class="feature-icon">
                    <img src="<?php echo htmlspecialchars($feature['icon_image']); ?>" 
                         alt="<?php echo htmlspecialchars($feature['name']); ?>">
                </div>
            <?php endif; ?>
            
            <h1 class="feature-title"><?php echo htmlspecialchars($feature['name']); ?></h1>
            
            <?php if (!empty($feature['description'])): ?>
                <p class="feature-description">
                    <?php echo htmlspecialchars($feature['description']); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if (!empty($feature['benefits']) && is_array($feature['benefits'])): ?>
<!-- Key Benefits Section -->
<section class="section feature-benefits-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Key Benefits</h2>
            <p class="section-subtitle">Why this feature matters</p>
        </div>
        
        <div class="benefits-grid">
            <?php foreach ($feature['benefits'] as $index => $benefit): ?>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="benefit-text"><?php echo htmlspecialchars(is_array($benefit) ? ($benefit['description'] ?? $benefit['title'] ?? '') : $benefit); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($feature['screenshots']) && is_array($feature['screenshots'])): ?>
<!-- Screenshots Section -->
<section class="section bg-gray-50 feature-screenshots-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">See It In Action</h2>
            <p class="section-subtitle">Visual overview of the feature</p>
        </div>
        
        <div class="screenshots-grid">
            <?php foreach ($feature['screenshots'] as $screenshot): ?>
                <div class="screenshot-card">
                    <img src="<?php echo htmlspecialchars($screenshot); ?>" 
                         alt="Screenshot" 
                         loading="lazy">
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($relatedSolutions)): ?>
<!-- Related Solutions Section -->
<section class="section feature-related-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Related Solutions</h2>
            <p class="section-subtitle">Explore solutions that include this feature</p>
        </div>
        
        <div class="related-grid">
            <?php foreach ($relatedSolutions as $solution): ?>
                <div class="related-card">
                    <?php if (!empty($solution['icon_image'])): ?>
                        <div class="related-icon">
                            <img src="<?php echo htmlspecialchars($solution['icon_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($solution['name']); ?>">
                        </div>
                    <?php endif; ?>
                    
                    <h3 class="related-title">
                        <?php echo htmlspecialchars($solution['name']); ?>
                    </h3>
                    
                    <?php if (!empty($solution['description'])): ?>
                        <p class="related-description">
                            <?php echo htmlspecialchars($solution['description']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <a href="<?php echo get_base_url(); ?>/solution.php?slug=<?php echo urlencode($solution['slug']); ?>" 
                       class="btn btn-outline btn-sm">
                        Learn More
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA Form Section -->
<?php
$cta_title = "Interested in " . htmlspecialchars($feature['name']) . "?";
$cta_subtitle = "Get in touch with us to learn more about this feature and how it can benefit your business";
$cta_source = "feature-" . htmlspecialchars($feature['slug']);
include __DIR__ . '/../templates/cta-form.php';
?>

<style>
/* Feature Hero Section */
.feature-hero {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.08) 0%, rgba(118, 75, 162, 0.08) 100%);
    color: var(--color-gray-900);
    padding: var(--spacing-12) 0 var(--spacing-16);
    border-bottom: 1px solid var(--color-gray-200);
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    font-size: var(--font-size-sm);
    margin-bottom: var(--spacing-6);
    color: var(--color-gray-600);
}

.breadcrumb a {
    color: var(--color-gray-700);
    text-decoration: none;
    transition: color 0.2s;
}

.breadcrumb a:hover {
    color: var(--color-primary);
}

.breadcrumb-separator {
    color: var(--color-gray-400);
}

.feature-hero-content {
    text-align: center;
    max-width: 800px;
    margin: 0 auto;
}

.feature-icon {
    width: 100px;
    height: 100px;
    margin: 0 auto var(--spacing-6);
    background: var(--color-white);
    border-radius: var(--radius-xl);
    padding: var(--spacing-4);
    box-shadow: var(--shadow-md);
    border: 1px solid var(--color-gray-200);
}

.feature-icon img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.feature-title {
    font-size: var(--font-size-5xl);
    font-weight: var(--font-weight-bold);
    margin-bottom: var(--spacing-4);
    color: var(--color-gray-900);
}

.feature-description {
    font-size: var(--font-size-xl);
    line-height: 1.6;
    margin-bottom: 0;
    color: var(--color-gray-600);
}

/* Section Headers */
.section-header {
    text-align: center;
    margin-bottom: var(--spacing-10);
}

.section-header .section-title {
    margin-bottom: var(--spacing-3);
}

.section-header .section-subtitle {
    margin-bottom: 0;
}

/* Benefits Section */
.feature-benefits-section {
    padding: var(--spacing-16) 0;
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--spacing-5);
}

.benefit-card {
    background: var(--color-white);
    border: 1px solid var(--color-gray-200);
    border-radius: var(--radius-lg);
    padding: var(--spacing-5);
    display: flex;
    align-items: flex-start;
    gap: var(--spacing-4);
    transition: all 0.3s ease;
}

.benefit-card:hover {
    border-color: var(--color-primary);
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.benefit-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: var(--radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.benefit-icon .icon {
    width: 24px;
    height: 24px;
    color: var(--color-white);
}

.benefit-text {
    font-size: var(--font-size-base);
    color: var(--color-gray-700);
    line-height: 1.6;
    margin: 0;
}

/* Screenshots Section */
.feature-screenshots-section {
    padding: var(--spacing-16) 0;
    background-color: var(--color-gray-50);
}

.screenshots-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--spacing-6);
}

.screenshot-card {
    background: var(--color-white);
    border-radius: var(--radius-xl);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.screenshot-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.screenshot-card img {
    width: 100%;
    height: auto;
    display: block;
}

/* Related Solutions Section */
.feature-related-section {
    padding: var(--spacing-16) 0;
    background-color: var(--color-white);
}

.related-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--spacing-6);
}

.related-card {
    background: var(--color-white);
    border: 1px solid var(--color-gray-200);
    border-radius: var(--radius-xl);
    padding: var(--spacing-6);
    text-align: center;
    transition: all 0.3s ease;
}

.related-card:hover {
    border-color: var(--color-primary);
    box-shadow: var(--shadow-lg);
    transform: translateY(-4px);
}

.related-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto var(--spacing-4);
    background: var(--color-gray-50);
    border-radius: var(--radius-lg);
    padding: var(--spacing-3);
    border: 1px solid var(--color-gray-200);
}

.related-icon img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.related-title {
    font-size: var(--font-size-xl);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin-bottom: var(--spacing-3);
}

.related-description {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    line-height: 1.6;
    margin-bottom: var(--spacing-4);
}

/* Responsive Design */
@media (max-width: 1024px) {
    .screenshots-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .related-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .feature-hero {
        padding: var(--spacing-8) 0 var(--spacing-12);
    }
    
    .feature-title {
        font-size: var(--font-size-3xl);
    }
    
    .feature-description {
        font-size: var(--font-size-lg);
    }
    
    .benefits-grid {
        grid-template-columns: 1fr;
    }
    
    .screenshots-grid {
        grid-template-columns: 1fr;
    }
    
    .related-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include_footer(); ?>
