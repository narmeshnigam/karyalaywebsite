<?php

/**
 * SellerPortal System
 * Case Studies Index Page
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

// Database connection
use Karyalay\Models\CaseStudy;

try {
    $caseStudyModel = new CaseStudy();
    
    // Fetch all published case studies
    $caseStudies = $caseStudyModel->findAll(['status' => 'PUBLISHED']);
    
} catch (Exception $e) {
    error_log('Error fetching case studies: ' . $e->getMessage());
    $caseStudies = [];
}

// Set page variables
$page_title = 'Case Studies';
$page_description = 'See how businesses are succeeding with Karyalay';

// Include header
include_header($page_title, $page_description);
?>

<!-- Hero Section -->
<section class="case-studies-hero">
    <div class="container">
        <div class="case-studies-hero-content">
            <h1 class="case-studies-hero-title">Success Stories</h1>
            <p class="case-studies-hero-subtitle">
                Discover how businesses like yours are transforming their operations and achieving remarkable results with Karyalay
            </p>
        </div>
    </div>
</section>

<!-- Case Studies Grid Section -->
<section class="case-studies-section">
    <div class="container">
        <?php if (empty($caseStudies)): ?>
            <div class="case-studies-empty">
                <div class="case-studies-empty-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="64" height="64">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3>No Case Studies Available</h3>
                <p>Check back soon for inspiring success stories from businesses using Karyalay!</p>
            </div>
        <?php else: ?>
            <div class="case-studies-grid">
                <?php foreach ($caseStudies as $caseStudy): ?>
                    <article class="case-study-card">
                        <?php if (!empty($caseStudy['cover_image'])): ?>
                            <div class="case-study-card-image">
                                <img src="<?php echo htmlspecialchars($caseStudy['cover_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($caseStudy['title']); ?>"
                                     loading="lazy">
                                <div class="case-study-card-overlay"></div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="case-study-card-content">
                            <div class="case-study-card-meta">
                                <?php if (!empty($caseStudy['industry'])): ?>
                                    <span class="case-study-card-industry">
                                        <?php echo htmlspecialchars($caseStudy['industry']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <h3 class="case-study-card-title">
                                <?php echo htmlspecialchars($caseStudy['title']); ?>
                            </h3>
                            
                            <p class="case-study-card-client">
                                <svg class="case-study-card-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                <?php echo htmlspecialchars($caseStudy['client_name']); ?>
                            </p>
                            
                            <?php if (!empty($caseStudy['challenge'])): ?>
                                <div class="case-study-card-challenge">
                                    <strong class="case-study-card-label">Challenge:</strong>
                                    <p><?php echo htmlspecialchars(substr($caseStudy['challenge'], 0, 120)); ?>
                                    <?php echo strlen($caseStudy['challenge']) > 120 ? '...' : ''; ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <a href="<?php echo get_base_url(); ?>/case-study/<?php echo urlencode($caseStudy['slug']); ?>" 
                               class="case-study-card-link">
                                Read Full Story
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
$cta_title = "Ready to Write Your Success Story?";
$cta_subtitle = "Join these successful businesses and transform your operations with Karyalay";
$cta_source = "case-studies-page";
include __DIR__ . '/../templates/cta-form.php';
?>

<style>
/* Case Studies Hero */
.case-studies-hero {
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 50%, #86efac 100%);
    padding: var(--spacing-16) 0;
    position: relative;
    overflow: hidden;
}

.case-studies-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 30% 40%, rgba(34, 197, 94, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 70% 70%, rgba(22, 163, 74, 0.08) 0%, transparent 50%);
    pointer-events: none;
}

.case-studies-hero-content {
    position: relative;
    z-index: 1;
    text-align: center;
    max-width: 700px;
    margin: 0 auto;
}

.case-studies-hero-title {
    font-size: var(--font-size-4xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-4) 0;
    line-height: 1.2;
}

.case-studies-hero-subtitle {
    font-size: var(--font-size-lg);
    color: var(--color-gray-600);
    margin: 0;
    line-height: 1.6;
}

/* Case Studies Section */
.case-studies-section {
    padding: var(--spacing-16) 0;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
}

/* Case Studies Grid */
.case-studies-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: var(--spacing-8);
    max-width: 1200px;
    margin: 0 auto;
}

/* Case Study Card */
.case-study-card {
    background: var(--color-white);
    border-radius: var(--radius-xl);
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 2px solid var(--color-gray-100);
    display: flex;
    flex-direction: column;
}

.case-study-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
    border-color: var(--color-primary);
}

.case-study-card-image {
    position: relative;
    width: 100%;
    height: 200px;
    overflow: hidden;
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
}

.case-study-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.case-study-card:hover .case-study-card-image img {
    transform: scale(1.05);
}

.case-study-card-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(180deg, transparent 0%, rgba(0, 0, 0, 0.3) 100%);
}

.case-study-card-content {
    padding: var(--spacing-6);
    display: flex;
    flex-direction: column;
    flex: 1;
}

.case-study-card-meta {
    margin-bottom: var(--spacing-3);
}

.case-study-card-industry {
    display: inline-block;
    padding: var(--spacing-2) var(--spacing-3);
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #1e40af;
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    border-radius: var(--radius-full);
}

.case-study-card-title {
    font-size: var(--font-size-xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-3) 0;
    line-height: 1.3;
}

.case-study-card-client {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
    margin: 0 0 var(--spacing-4) 0;
}

.case-study-card-icon {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
    color: var(--color-gray-400);
}

.case-study-card-challenge {
    margin-bottom: var(--spacing-5);
    flex: 1;
}

.case-study-card-label {
    display: block;
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: var(--spacing-2);
}

.case-study-card-challenge p {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    line-height: 1.6;
    margin: 0;
}

.case-study-card-link {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-semibold);
    color: var(--color-primary);
    text-decoration: none;
    transition: all 0.2s;
    margin-top: auto;
}

.case-study-card-link:hover {
    color: var(--color-primary-dark);
    gap: var(--spacing-3);
}

.case-study-card-link svg {
    transition: transform 0.2s;
}

.case-study-card-link:hover svg {
    transform: translateX(4px);
}

/* Empty State */
.case-studies-empty {
    text-align: center;
    padding: var(--spacing-16) var(--spacing-8);
    background: var(--color-white);
    border-radius: var(--radius-xl);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    max-width: 600px;
    margin: 0 auto;
}

.case-studies-empty-icon {
    color: var(--color-gray-400);
    margin-bottom: var(--spacing-4);
}

.case-studies-empty h3 {
    font-size: var(--font-size-xl);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-2) 0;
}

.case-studies-empty p {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
    margin: 0;
}

/* Responsive */
@media (max-width: 1024px) {
    .case-studies-grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: var(--spacing-6);
    }
}

@media (max-width: 768px) {
    .case-studies-hero {
        padding: var(--spacing-12) 0;
    }
    
    .case-studies-hero-title {
        font-size: var(--font-size-3xl);
    }
    
    .case-studies-hero-subtitle {
        font-size: var(--font-size-base);
    }
    
    .case-studies-section {
        padding: var(--spacing-12) 0;
    }
    
    .case-studies-grid {
        grid-template-columns: 1fr;
    }
    
    .case-study-card-content {
        padding: var(--spacing-5);
    }
}
</style>

<?php
// Include footer
include_footer();
?>
