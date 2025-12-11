<?php

/**
 * SellerPortal System
 * Solutions Overview Page
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

try {
    $solutionModel = new Solution();
    $solutions = $solutionModel->findAll(['status' => 'PUBLISHED']);
} catch (Exception $e) {
    error_log('Error fetching solutions: ' . $e->getMessage());
    $solutions = [];
}

$page_title = 'Solutions';
$page_description = 'Explore our comprehensive suite of business management solutions';

include_header($page_title, $page_description);
?>

<!-- Hero Section -->
<section class="solutions-hero">
    <div class="container">
        <div class="solutions-hero-content">
            <h1 class="solutions-hero-title">Our Solutions</h1>
            <p class="solutions-hero-subtitle">
                Discover powerful solutions designed to streamline your business operations and drive growth
            </p>
        </div>
    </div>
</section>

<!-- Solutions Grid Section -->
<section class="solutions-list-section">
    <div class="container">
        <?php if (empty($solutions)): ?>
            <div class="solutions-empty">
                <div class="solutions-empty-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="64" height="64">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <h3>No Solutions Available</h3>
                <p>Please check back later for our comprehensive business solutions.</p>
            </div>
        <?php else: ?>
            <div class="solutions-grid">
                <?php foreach ($solutions as $solution): ?>
                    <article class="solution-item">
                        <div class="solution-item-icon">
                            <?php if (!empty($solution['icon_image'])): ?>
                                <img src="<?php echo htmlspecialchars($solution['icon_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($solution['name']); ?>"
                                     loading="lazy">
                            <?php else: ?>
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="solution-item-content">
                            <h3 class="solution-item-title">
                                <?php echo htmlspecialchars($solution['name']); ?>
                            </h3>
                            <p class="solution-item-description">
                                <?php echo htmlspecialchars($solution['description']); ?>
                            </p>
                            <a href="<?php echo get_base_url(); ?>/solution/<?php echo urlencode($solution['slug']); ?>" 
                               class="solution-item-link">
                                Learn More
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- CTA Section -->
<?php
$cta_title = "Find the Right Solution for Your Business";
$cta_subtitle = "Let us help you choose the perfect combination of solutions to meet your unique needs";
$cta_source = "solutions-page";
include __DIR__ . '/../templates/cta-form.php';
?>

<style>
/* Solutions Hero */
.solutions-hero {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 50%, #bfdbfe 100%);
    padding: var(--spacing-16) 0;
    position: relative;
    overflow: hidden;
}

.solutions-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(37, 99, 235, 0.08) 0%, transparent 50%);
    pointer-events: none;
}

.solutions-hero-content {
    position: relative;
    z-index: 1;
    text-align: center;
    max-width: 700px;
    margin: 0 auto;
}

.solutions-hero-title {
    font-size: var(--font-size-4xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-4) 0;
    line-height: 1.2;
}

.solutions-hero-subtitle {
    font-size: var(--font-size-lg);
    color: var(--color-gray-600);
    margin: 0;
    line-height: 1.6;
}

/* Solutions List Section */
.solutions-list-section {
    padding: var(--spacing-16) 0;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
}

/* Solutions Grid */
.solutions-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--spacing-6);
}

/* Solution Item */
.solution-item {
    background: var(--color-white);
    border-radius: var(--radius-xl);
    padding: var(--spacing-8);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    border: 1px solid var(--color-gray-100);
}

.solution-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
    border-color: var(--color-primary);
}

.solution-item-icon {
    width: 64px;
    height: 64px;
    margin-bottom: var(--spacing-5);
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border-radius: var(--radius-lg);
    color: var(--color-primary);
}

.solution-item-icon img {
    width: 48px;
    height: 48px;
    object-fit: contain;
}

.solution-item-icon svg {
    width: 32px;
    height: 32px;
}

.solution-item-content {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.solution-item-title {
    font-size: var(--font-size-xl);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-3) 0;
}

.solution-item-description {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
    line-height: 1.6;
    margin: 0 0 var(--spacing-5) 0;
    flex: 1;
}

.solution-item-link {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-primary);
    text-decoration: none;
    transition: all 0.2s;
}

.solution-item-link:hover {
    color: var(--color-primary-dark);
    gap: var(--spacing-3);
}

.solution-item-link svg {
    transition: transform 0.2s;
}

.solution-item-link:hover svg {
    transform: translateX(4px);
}

/* Empty State */
.solutions-empty {
    text-align: center;
    padding: var(--spacing-16) var(--spacing-8);
    background: var(--color-white);
    border-radius: var(--radius-xl);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.solutions-empty-icon {
    color: var(--color-gray-400);
    margin-bottom: var(--spacing-4);
}

.solutions-empty h3 {
    font-size: var(--font-size-xl);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-2) 0;
}

.solutions-empty p {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
    margin: 0;
}

/* Responsive */
@media (max-width: 1024px) {
    .solutions-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .solutions-hero {
        padding: var(--spacing-12) 0;
    }
    
    .solutions-hero-title {
        font-size: var(--font-size-3xl);
    }
    
    .solutions-hero-subtitle {
        font-size: var(--font-size-base);
    }
    
    .solutions-list-section {
        padding: var(--spacing-12) 0;
    }
    
    .solutions-grid {
        grid-template-columns: 1fr;
        gap: var(--spacing-4);
    }
    
    .solution-item {
        padding: var(--spacing-6);
    }
}
</style>

<?php include_footer(); ?>
