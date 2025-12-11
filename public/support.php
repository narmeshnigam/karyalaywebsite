<?php

/**
 * SellerPortal System
 * Support/FAQ Page
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

use Karyalay\Models\Faq;

// Load FAQs from database
$faqModel = new Faq();
$faqsByCategory = $faqModel->findAllGroupedByCategory();

// Set page variables
$page_title = 'Support & FAQ';
$page_description = 'Find answers to common questions and get help';

// Include header
include_header($page_title, $page_description);
?>

<!-- Hero Section -->
<section class="support-hero">
    <div class="container">
        <div class="support-hero-content">
            <h1 class="support-hero-title">Support & FAQ</h1>
            <p class="support-hero-subtitle">
                Find answers to common questions or get in touch with our support team
            </p>
        </div>
    </div>
</section>

<!-- Quick Links -->
<section class="support-links-section">
    <div class="container">
        <div class="support-links-grid">
            <div class="support-link-card">
                <div class="support-link-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                    </svg>
                </div>
                <h3 class="support-link-title">Contact Support</h3>
                <p class="support-link-description">Get help from our support team</p>
                <a href="<?php echo get_base_url(); ?>/contact.php" class="support-link-btn">Contact Us</a>
            </div>
            
            <div class="support-link-card">
                <div class="support-link-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <h3 class="support-link-title">Documentation</h3>
                <p class="support-link-description">Browse our comprehensive guides</p>
                <a href="<?php echo get_base_url(); ?>/modules.php" class="support-link-btn">View Modules</a>
            </div>
            
            <?php if (isLoggedIn()): ?>
                <div class="support-link-card">
                    <div class="support-link-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                        </svg>
                    </div>
                    <h3 class="support-link-title">Create Ticket</h3>
                    <p class="support-link-description">Submit a support ticket</p>
                    <a href="<?php echo get_app_base_url(); ?>/app/support/tickets/new.php" class="support-link-btn support-link-btn-primary">New Ticket</a>
                </div>
            <?php else: ?>
                <div class="support-link-card">
                    <div class="support-link-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                        </svg>
                    </div>
                    <h3 class="support-link-title">Raise Ticket</h3>
                    <p class="support-link-description">Submit your support request</p>
                    <a href="<?php echo get_base_url(); ?>/contact.php" class="support-link-btn support-link-btn-primary">Submit Request</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="support-faq-section">
    <div class="container">
        <h2 class="support-faq-title">Frequently Asked Questions</h2>
        
        <?php if (empty($faqsByCategory)): ?>
            <div class="support-faq-empty">
                <p>No FAQs available at the moment. Please check back later or contact our support team for assistance.</p>
                <a href="<?php echo get_base_url(); ?>/contact.php" class="support-link-btn support-link-btn-primary">Contact Support</a>
            </div>
        <?php else: ?>
            <div class="support-faq-grid">
                <?php foreach ($faqsByCategory as $category => $faqs): ?>
                    <div class="support-faq-category">
                        <h3 class="support-faq-category-title"><?php echo htmlspecialchars($category); ?></h3>
                        
                        <?php foreach ($faqs as $faq): ?>
                            <div class="support-faq-item">
                                <button class="support-faq-question" aria-expanded="false">
                                    <span><?php echo htmlspecialchars($faq['question']); ?></span>
                                    <svg class="support-faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div class="support-faq-answer">
                                    <p><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- CTA Section -->
<?php
$cta_title = "Still Have Questions?";
$cta_subtitle = "Our support team is here to help you with any questions or concerns. Get in touch and we'll respond quickly";
$cta_source = "support-page";
include __DIR__ . '/../templates/cta-form.php';
?>

<style>
/* Support Page Styles */
.support-hero {
    background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 50%, #a5b4fc 100%);
    padding: var(--spacing-16) 0;
    position: relative;
    overflow: hidden;
}

.support-hero::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: 
        radial-gradient(circle at 30% 40%, rgba(99, 102, 241, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 70% 70%, rgba(79, 70, 229, 0.08) 0%, transparent 50%);
    pointer-events: none;
}

.support-hero-content {
    position: relative;
    z-index: 1;
    text-align: center;
    max-width: 700px;
    margin: 0 auto;
}

.support-hero-title {
    font-size: var(--font-size-4xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-4) 0;
    line-height: 1.2;
}

.support-hero-subtitle {
    font-size: var(--font-size-lg);
    color: var(--color-gray-600);
    margin: 0;
    line-height: 1.6;
}

/* Support Links Section */
.support-links-section {
    padding: var(--spacing-16) 0;
    background: var(--color-white);
}

.support-links-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--spacing-8);
    max-width: 1000px;
    margin: 0 auto;
}

.support-link-card {
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: var(--radius-xl);
    border: 1px solid var(--color-gray-200);
    padding: var(--spacing-8);
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.support-link-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.02) 0%, rgba(79, 70, 229, 0.02) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.support-link-card:hover {
    transform: translateY(-4px);
    border-color: var(--color-primary);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.support-link-card:hover::before {
    opacity: 1;
}

.support-link-icon {
    width: 48px;
    height: 48px;
    margin: 0 auto var(--spacing-4) auto;
    color: var(--color-primary);
    position: relative;
    z-index: 1;
}

.support-link-title {
    font-size: var(--font-size-xl);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-3) 0;
    position: relative;
    z-index: 1;
}

.support-link-description {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
    margin: 0 0 var(--spacing-6) 0;
    line-height: 1.6;
    position: relative;
    z-index: 1;
}

.support-link-btn {
    display: inline-block;
    padding: var(--spacing-3) var(--spacing-6);
    border-radius: var(--radius-lg);
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-medium);
    text-decoration: none;
    transition: all 0.3s ease;
    border: 1px solid var(--color-gray-300);
    background: var(--color-white);
    color: var(--color-gray-700);
    position: relative;
    z-index: 1;
}

.support-link-btn:hover {
    border-color: var(--color-primary);
    color: var(--color-primary);
    transform: translateY(-1px);
}

.support-link-btn-primary {
    background: var(--color-primary);
    color: var(--color-white);
    border-color: var(--color-primary);
}

.support-link-btn-primary:hover {
    background: var(--color-primary-dark);
    border-color: var(--color-primary-dark);
    color: var(--color-white);
}

/* FAQ Section */
.support-faq-section {
    padding: var(--spacing-16) 0;
    background: var(--color-gray-50);
}

.support-faq-title {
    font-size: var(--font-size-3xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    text-align: center;
    margin: 0 0 var(--spacing-12) 0;
}

.support-faq-empty {
    text-align: center;
    padding: var(--spacing-16);
    max-width: 600px;
    margin: 0 auto;
}

.support-faq-empty p {
    font-size: var(--font-size-lg);
    color: var(--color-gray-600);
    margin: 0 0 var(--spacing-6) 0;
    line-height: 1.6;
}

.support-faq-grid {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-10);
    max-width: 900px;
    margin: 0 auto;
}

.support-faq-category {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-4);
}

.support-faq-category-title {
    font-size: var(--font-size-2xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-4) 0;
    padding-bottom: var(--spacing-3);
    border-bottom: 3px solid var(--color-primary);
}

.support-faq-item {
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border-radius: var(--radius-xl);
    border: 1px solid var(--color-gray-200);
    overflow: hidden;
    transition: all 0.3s ease;
}

.support-faq-item:hover {
    border-color: var(--color-primary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.support-faq-item.active {
    border-color: var(--color-primary);
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.15);
}

.support-faq-question {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--spacing-4);
    padding: var(--spacing-5);
    background: transparent;
    border: none;
    text-align: left;
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    cursor: pointer;
    transition: color 0.3s ease;
}

.support-faq-question:hover {
    color: var(--color-primary);
}

.support-faq-icon {
    width: 24px;
    height: 24px;
    flex-shrink: 0;
    color: var(--color-gray-500);
    transition: transform 0.3s ease, color 0.3s ease;
}

.support-faq-item.active .support-faq-icon {
    transform: rotate(180deg);
    color: var(--color-primary);
}

.support-faq-answer {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease, padding 0.3s ease;
}

.support-faq-item.active .support-faq-answer {
    max-height: 1000px;
    padding: 0 var(--spacing-5) var(--spacing-5) var(--spacing-5);
}

.support-faq-answer p {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
    line-height: 1.7;
    margin: 0;
}



/* Responsive Design */
@media (max-width: 768px) {
    .support-hero {
        padding: var(--spacing-12) 0;
    }
    
    .support-hero-title {
        font-size: var(--font-size-3xl);
    }
    
    .support-hero-subtitle {
        font-size: var(--font-size-base);
    }
    
    .support-links-section {
        padding: var(--spacing-12) 0;
    }
    
    .support-links-grid {
        grid-template-columns: 1fr;
        gap: var(--spacing-6);
    }
    
    .support-link-card {
        padding: var(--spacing-6);
    }
    
    .support-faq-section {
        padding: var(--spacing-12) 0;
    }
    
    .support-faq-grid {
        gap: var(--spacing-8);
    }
    
    .support-faq-category-title {
        font-size: var(--font-size-xl);
    }
    
    .support-faq-question {
        font-size: var(--font-size-base);
        padding: var(--spacing-4);
    }
    
    .support-faq-item.active .support-faq-answer {
        padding: 0 var(--spacing-4) var(--spacing-4) var(--spacing-4);
    }
    

}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const faqItems = document.querySelectorAll('.support-faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.support-faq-question');
        
        question.addEventListener('click', function() {
            const isActive = item.classList.contains('active');
            
            // Close all other FAQ items
            faqItems.forEach(otherItem => {
                if (otherItem !== item) {
                    otherItem.classList.remove('active');
                    otherItem.querySelector('.support-faq-question').setAttribute('aria-expanded', 'false');
                }
            });
            
            // Toggle current item
            if (isActive) {
                item.classList.remove('active');
                question.setAttribute('aria-expanded', 'false');
            } else {
                item.classList.add('active');
                question.setAttribute('aria-expanded', 'true');
            }
        });
    });
});
</script>

<?php
// Include footer
include_footer();
?>
