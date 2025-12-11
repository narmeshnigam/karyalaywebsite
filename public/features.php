<?php

/**
 * SellerPortal System
 * Features Overview Page
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

use Karyalay\Models\Feature;

try {
    $featureModel = new Feature();
    $features = $featureModel->findAll(['status' => 'PUBLISHED']);
} catch (Exception $e) {
    error_log('Error fetching features: ' . $e->getMessage());
    $features = [];
}

$page_title = 'Features';
$page_description = 'Explore the powerful features that make Karyalay the perfect solution for your business';

include_header($page_title, $page_description);
?>

<!-- Hero Section -->
<section class="features-hero">
    <div class="container">
        <div class="features-hero-content">
            <h1 class="features-hero-title">Powerful Features</h1>
            <p class="features-hero-subtitle">
                Discover the tools and capabilities that drive business success and streamline your operations
            </p>
        </div>
    </div>
</section>

<!-- Features Grid Section -->
<section class="features-list-section">
    <div class="container">
        <?php if (empty($features)): ?>
            <div class="features-empty">
                <div class="features-empty-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="64" height="64">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                </div>
                <h3>No Features Available</h3>
                <p>Please check back later for our comprehensive feature list.</p>
            </div>
        <?php else: ?>
            <div class="features-grid">
                <?php foreach ($features as $index => $feature): ?>
                    <article class="feature-item <?php echo $index % 2 === 0 ? '' : 'feature-item-alt'; ?>">
                        <div class="feature-item-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <div class="feature-item-content">
                            <h3 class="feature-item-title">
                                <?php echo htmlspecialchars($feature['name']); ?>
                            </h3>
                            <p class="feature-item-description">
                                <?php echo htmlspecialchars($feature['description']); ?>
                            </p>
                            
                            <?php if (!empty($feature['benefits']) && is_array($feature['benefits'])): ?>
                                <ul class="feature-item-benefits">
                                    <?php foreach (array_slice($feature['benefits'], 0, 3) as $benefit): ?>
                                        <li>
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <span><?php echo htmlspecialchars($benefit); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            
                            <a href="<?php echo get_base_url(); ?>/feature/<?php echo urlencode($feature['slug']); ?>" 
                               class="feature-item-link">
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
$cta_title = "Ready to Experience These Features?";
$cta_subtitle = "Get started with Karyalay today and unlock all these powerful capabilities for your business";
$cta_source = "features-page";
include __DIR__ . '/../templates/cta-form.php';
?>

<style>
/* Features Hero */
.features-hero {
    background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 50%, #e9d5ff 100%);
    padding: var(--spacing-16) 0;
    position: relative;
    overflow: hidden;
}

.features-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 30% 40%, rgba(139, 92, 246, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 70% 70%, rgba(168, 85, 247, 0.08) 0%, transparent 50%);
    pointer-events: none;
}

.features-hero-content {
    position: relative;
    z-index: 1;
    text-align: center;
    max-width: 700px;
    margin: 0 auto;
}

.features-hero-title {
    font-size: var(--font-size-4xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-4) 0;
    line-height: 1.2;
}

.features-hero-subtitle {
    font-size: var(--font-size-lg);
    color: var(--color-gray-600);
    margin: 0;
    line-height: 1.6;
}

/* Features List Section */
.features-list-section {
    padding: var(--spacing-16) 0;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
}

/* Features Grid */
.features-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--spacing-6);
}

/* Feature Item */
.feature-item {
    background: var(--color-white);
    border-radius: var(--radius-xl);
    padding: var(--spacing-8);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    display: flex;
    gap: var(--spacing-6);
    border: 1px solid var(--color-gray-100);
}

.feature-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
    border-color: var(--color-primary);
}

.feature-item-alt {
    background: linear-gradient(135deg, #fafafa 0%, #f5f5f5 100%);
}

.feature-item-icon {
    width: 56px;
    height: 56px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--color-primary) 0%, #7c3aed 100%);
    border-radius: var(--radius-lg);
    color: var(--color-white);
}

.feature-item-icon svg {
    width: 28px;
    height: 28px;
}

.feature-item-content {
    flex: 1;
}

.feature-item-title {
    font-size: var(--font-size-xl);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-3) 0;
}

.feature-item-description {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
    line-height: 1.6;
    margin: 0 0 var(--spacing-4) 0;
}

.feature-item-benefits {
    list-style: none;
    padding: 0;
    margin: 0 0 var(--spacing-5) 0;
}

.feature-item-benefits li {
    display: flex;
    align-items: flex-start;
    gap: var(--spacing-2);
    font-size: var(--font-size-sm);
    color: var(--color-gray-700);
    margin-bottom: var(--spacing-2);
}

.feature-item-benefits li:last-child {
    margin-bottom: 0;
}

.feature-item-benefits svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
    color: #10b981;
    margin-top: 2px;
}

.feature-item-link {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-primary);
    text-decoration: none;
    transition: all 0.2s;
}

.feature-item-link:hover {
    color: var(--color-primary-dark);
    gap: var(--spacing-3);
}

.feature-item-link svg {
    transition: transform 0.2s;
}

.feature-item-link:hover svg {
    transform: translateX(4px);
}

/* Empty State */
.features-empty {
    text-align: center;
    padding: var(--spacing-16) var(--spacing-8);
    background: var(--color-white);
    border-radius: var(--radius-xl);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.features-empty-icon {
    color: var(--color-gray-400);
    margin-bottom: var(--spacing-4);
}

.features-empty h3 {
    font-size: var(--font-size-xl);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-2) 0;
}

.features-empty p {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
    margin: 0;
}

/* Responsive */
@media (max-width: 1024px) {
    .features-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .features-hero {
        padding: var(--spacing-12) 0;
    }
    
    .features-hero-title {
        font-size: var(--font-size-3xl);
    }
    
    .features-hero-subtitle {
        font-size: var(--font-size-base);
    }
    
    .features-list-section {
        padding: var(--spacing-12) 0;
    }
    
    .feature-item {
        flex-direction: column;
        padding: var(--spacing-6);
        gap: var(--spacing-4);
    }
    
    .feature-item-icon {
        width: 48px;
        height: 48px;
    }
    
    .feature-item-icon svg {
        width: 24px;
        height: 24px;
    }
}
</style>

<?php include_footer(); ?>
