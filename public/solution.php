<?php

/**
 * Karyalay Portal System
 * Solution Detail Page
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
                <div class="card">
                    <div class="card-body text-center">
                        <h1 class="text-3xl font-bold mb-4">Solution Not Found</h1>
                        <p class="text-gray-600 mb-6">The solution you're looking for doesn't exist or is no longer available.</p>
                        <a href="<?php echo get_base_url(); ?>/solutions.php" class="btn btn-primary">View All Solutions</a>
                    </div>
                </div>
            </div>
        </section>
        <?php
        include_footer();
        exit;
    }
    
} catch (Exception $e) {
    error_log('Error fetching solution: ' . $e->getMessage());
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
                    <p class="text-gray-600 mb-6">An error occurred while loading the solution. Please try again later.</p>
                    <a href="<?php echo get_base_url(); ?>/solutions.php" class="btn btn-primary">View All Solutions</a>
                </div>
            </div>
        </div>
    </section>
    <?php
    include_footer();
    exit;
}

$page_title = htmlspecialchars($solution['name']);
$page_description = htmlspecialchars($solution['description'] ?? '');

include_header($page_title, $page_description);
?>

<!-- Solution Hero Section -->
<section class="solution-hero">
    <div class="container">
        <nav class="breadcrumb">
            <a href="<?php echo get_base_url(); ?>/">Home</a>
            <span class="breadcrumb-separator">›</span>
            <a href="<?php echo get_base_url(); ?>/solutions.php">Solutions</a>
            <span class="breadcrumb-separator">›</span>
            <span><?php echo htmlspecialchars($solution['name']); ?></span>
        </nav>
        
        <div class="solution-hero-content">
            <?php if (!empty($solution['icon_image'])): ?>
                <div class="solution-icon">
                    <img src="<?php echo htmlspecialchars($solution['icon_image']); ?>" 
                         alt="<?php echo htmlspecialchars($solution['name']); ?>">
                </div>
            <?php endif; ?>
            
            <h1 class="solution-title"><?php echo htmlspecialchars($solution['name']); ?></h1>
            
            <?php if (!empty($solution['description'])): ?>
                <p class="solution-description">
                    <?php echo htmlspecialchars($solution['description']); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if (!empty($solution['benefits']) && is_array($solution['benefits'])): ?>
<!-- Key Benefits Section -->
<section class="section solution-benefits-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Key Benefits</h2>
            <p class="section-subtitle">Why choose this solution</p>
        </div>
        
        <div class="benefits-grid">
            <?php foreach ($solution['benefits'] as $index => $benefit): ?>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="benefit-title"><?php echo htmlspecialchars($benefit['title'] ?? ''); ?></h3>
                    <p class="benefit-text"><?php echo htmlspecialchars($benefit['description'] ?? $benefit); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($solution['features']) && is_array($solution['features'])): ?>
<!-- Features Section -->
<section class="section bg-gray-50 solution-features-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Key Features</h2>
            <p class="section-subtitle">Everything you need to succeed</p>
        </div>
        
        <div class="features-grid">
            <?php foreach ($solution['features'] as $index => $feature): ?>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <p class="feature-text"><?php echo htmlspecialchars($feature); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($solution['screenshots']) && is_array($solution['screenshots'])): ?>
<!-- Screenshots Section -->
<section class="section solution-screenshots-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">See It In Action</h2>
            <p class="section-subtitle">Visual overview of the solution</p>
        </div>
        
        <div class="screenshots-grid">
            <?php foreach ($solution['screenshots'] as $screenshot): ?>
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

<?php if (!empty($solution['faqs']) && is_array($solution['faqs'])): ?>
<!-- FAQs Section -->
<section class="section bg-gray-50 solution-faqs-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Frequently Asked Questions</h2>
            <p class="section-subtitle">Get answers to common questions</p>
        </div>
        
        <div class="faqs-container">
            <?php foreach ($solution['faqs'] as $index => $faq): ?>
                <?php if (isset($faq['question']) && isset($faq['answer'])): ?>
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3><?php echo htmlspecialchars($faq['question']); ?></h3>
                            <span class="faq-icon">+</span>
                        </div>
                        <div class="faq-answer">
                            <p><?php echo htmlspecialchars($faq['answer']); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA Form Section -->
<?php
$cta_title = "Interested in " . htmlspecialchars($solution['name']) . "?";
$cta_subtitle = "Get in touch with us to learn more about how this solution can benefit your business";
$cta_source = "solution-" . htmlspecialchars($solution['slug']);
include __DIR__ . '/../templates/cta-form.php';
?>

<style>
/* Solution Hero Section */
.solution-hero {
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

.solution-hero-content {
    text-align: center;
    max-width: 800px;
    margin: 0 auto;
}

.solution-icon {
    width: 100px;
    height: 100px;
    margin: 0 auto var(--spacing-6);
    background: var(--color-white);
    border-radius: var(--radius-xl);
    padding: var(--spacing-4);
    box-shadow: var(--shadow-md);
    border: 1px solid var(--color-gray-200);
}

.solution-icon img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.solution-title {
    font-size: var(--font-size-5xl);
    font-weight: var(--font-weight-bold);
    margin-bottom: var(--spacing-4);
    color: var(--color-gray-900);
}

.solution-description {
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
.solution-benefits-section {
    padding: var(--spacing-16) 0;
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--spacing-6);
}

.benefit-card {
    background: var(--color-white);
    border: 1px solid var(--color-gray-200);
    border-radius: var(--radius-xl);
    padding: var(--spacing-6);
    text-align: center;
    transition: all 0.3s ease;
}

.benefit-card:hover {
    border-color: var(--color-primary);
    box-shadow: var(--shadow-lg);
    transform: translateY(-4px);
}

.benefit-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: var(--radius-full);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto var(--spacing-4);
}

.benefit-icon .icon {
    width: 32px;
    height: 32px;
    color: var(--color-white);
}

.benefit-title {
    font-size: var(--font-size-xl);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin-bottom: var(--spacing-3);
}

.benefit-text {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
    line-height: 1.6;
    margin: 0;
}

/* Features Section */
.solution-features-section {
    padding: var(--spacing-16) 0;
    background-color: var(--color-gray-50);
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--spacing-5);
}

.feature-card {
    background: var(--color-white);
    border: 1px solid var(--color-gray-200);
    border-radius: var(--radius-lg);
    padding: var(--spacing-5);
    display: flex;
    align-items: flex-start;
    gap: var(--spacing-4);
    transition: all 0.3s ease;
}

.feature-card:hover {
    border-color: var(--color-primary);
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.feature-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: var(--radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.feature-icon .icon {
    width: 24px;
    height: 24px;
    color: var(--color-white);
}

.feature-text {
    font-size: var(--font-size-base);
    color: var(--color-gray-700);
    line-height: 1.6;
    margin: 0;
}

/* Screenshots Section */
.solution-screenshots-section {
    padding: var(--spacing-16) 0;
    background-color: var(--color-white);
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

/* FAQs Section */
.solution-faqs-section {
    padding: var(--spacing-16) 0;
    background-color: var(--color-gray-50);
}

.faqs-container {
    max-width: 800px;
    margin: 0 auto;
}

.faq-item {
    background: var(--color-white);
    border: 1px solid var(--color-gray-200);
    border-radius: var(--radius-lg);
    margin-bottom: var(--spacing-4);
    overflow: hidden;
}

.faq-question {
    padding: var(--spacing-5);
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    transition: background-color 0.2s;
}

.faq-question:hover {
    background-color: var(--color-gray-50);
}

.faq-question h3 {
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin: 0;
    flex: 1;
}

.faq-icon {
    font-size: var(--font-size-2xl);
    color: var(--color-primary);
    font-weight: var(--font-weight-bold);
    transition: transform 0.3s;
}

.faq-item.active .faq-icon {
    transform: rotate(45deg);
}

.faq-answer {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.faq-item.active .faq-answer {
    max-height: 500px;
}

.faq-answer p {
    padding: 0 var(--spacing-5) var(--spacing-5);
    color: var(--color-gray-600);
    line-height: 1.6;
    margin: 0;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .benefits-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .screenshots-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .solution-hero {
        padding: var(--spacing-8) 0 var(--spacing-12);
    }
    
    .solution-title {
        font-size: var(--font-size-3xl);
    }
    
    .solution-description {
        font-size: var(--font-size-lg);
    }
    
    .benefits-grid {
        grid-template-columns: 1fr;
    }
    
    .features-grid {
        grid-template-columns: 1fr;
    }
    
    .screenshots-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// FAQ Accordion functionality
document.addEventListener('DOMContentLoaded', function() {
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        
        question.addEventListener('click', () => {
            const isActive = item.classList.contains('active');
            
            // Close all FAQs
            faqItems.forEach(faq => faq.classList.remove('active'));
            
            // Open clicked FAQ if it wasn't active
            if (!isActive) {
                item.classList.add('active');
            }
        });
    });
});
</script>

<?php include_footer(); ?>
