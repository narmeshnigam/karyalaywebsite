<?php

/**
 * SellerPortal System
 * FAQs Page - Dynamic Content
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

use Karyalay\Models\Faq;

$faqModel = new Faq();
$faqsByCategory = $faqModel->findAllGroupedByCategory();

$page_title = 'Frequently Asked Questions';
$page_description = 'Find answers to common questions about Karyalay business management system';

include_header($page_title, $page_description);
?>

<!-- Hero Section -->
<section class="faqs-hero">
    <div class="container">
        <div class="faqs-hero-content">
            <h1 class="faqs-hero-title">Frequently Asked Questions</h1>
            <p class="faqs-hero-subtitle">
                Find answers to common questions about our platform, features, and services
            </p>
        </div>
    </div>
</section>

<!-- FAQs Section -->
<section class="faqs-section">
    <div class="container">
        <?php if (empty($faqsByCategory)): ?>
            <div class="empty-faqs">
                <p>No FAQs available at the moment. Please check back later.</p>
            </div>
        <?php else: ?>
            <div class="faqs-grid">
                <?php foreach ($faqsByCategory as $category => $faqs): ?>
                    <div class="faqs-category">
                        <h2 class="faqs-category-title"><?php echo htmlspecialchars($category); ?></h2>
                        
                        <?php foreach ($faqs as $faq): ?>
                            <div class="faq-item">
                                <button class="faq-question" aria-expanded="false">
                                    <span><?php echo htmlspecialchars($faq['question']); ?></span>
                                    <svg class="faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div class="faq-answer">
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
$cta_subtitle = "Can't find the answer you're looking for? Our team is here to help you with any questions";
$cta_source = "faqs-page";
include __DIR__ . '/../templates/cta-form.php';
?>

<style>
.faqs-hero {
    background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 50%, #a5b4fc 100%);
    padding: var(--spacing-16) 0;
    position: relative;
    overflow: hidden;
}

.faqs-hero::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: 
        radial-gradient(circle at 30% 40%, rgba(99, 102, 241, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 70% 70%, rgba(79, 70, 229, 0.08) 0%, transparent 50%);
    pointer-events: none;
}

.faqs-hero-content {
    position: relative;
    z-index: 1;
    text-align: center;
    max-width: 700px;
    margin: 0 auto;
}

.faqs-hero-title {
    font-size: var(--font-size-4xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-4) 0;
    line-height: 1.2;
}

.faqs-hero-subtitle {
    font-size: var(--font-size-lg);
    color: var(--color-gray-600);
    margin: 0;
    line-height: 1.6;
}

.faqs-section {
    padding: var(--spacing-16) 0;
    background: var(--color-white);
}

.faqs-grid {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-10);
    max-width: 900px;
    margin: 0 auto;
}

.faqs-category {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-4);
}

.faqs-category-title {
    font-size: var(--font-size-2xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-4) 0;
    padding-bottom: var(--spacing-3);
    border-bottom: 3px solid var(--color-primary);
}

.faq-item {
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: var(--radius-xl);
    border: 1px solid var(--color-gray-200);
    overflow: hidden;
    transition: all 0.3s ease;
}

.faq-item:hover {
    border-color: var(--color-primary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.faq-item.active {
    border-color: var(--color-primary);
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.15);
}

.faq-question {
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

.faq-question:hover { color: var(--color-primary); }

.faq-icon {
    width: 24px;
    height: 24px;
    flex-shrink: 0;
    color: var(--color-gray-500);
    transition: transform 0.3s ease, color 0.3s ease;
}

.faq-item.active .faq-icon {
    transform: rotate(180deg);
    color: var(--color-primary);
}

.faq-answer {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease, padding 0.3s ease;
}

.faq-item.active .faq-answer {
    max-height: 1000px;
    padding: 0 var(--spacing-5) var(--spacing-5) var(--spacing-5);
}

.faq-answer p {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
    line-height: 1.7;
    margin: 0;
}

.empty-faqs {
    text-align: center;
    padding: var(--spacing-16);
    color: var(--color-gray-500);
}

@media (max-width: 768px) {
    .faqs-grid { gap: var(--spacing-8); }
    .faqs-hero { padding: var(--spacing-12) 0; }
    .faqs-hero-title { font-size: var(--font-size-3xl); }
    .faqs-hero-subtitle { font-size: var(--font-size-base); }
    .faqs-section { padding: var(--spacing-12) 0; }
    .faqs-category-title { font-size: var(--font-size-xl); }
    .faq-question { font-size: var(--font-size-base); padding: var(--spacing-4); }
    .faq-item.active .faq-answer { padding: 0 var(--spacing-4) var(--spacing-4) var(--spacing-4); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        
        question.addEventListener('click', function() {
            const isActive = item.classList.contains('active');
            
            faqItems.forEach(otherItem => {
                if (otherItem !== item) {
                    otherItem.classList.remove('active');
                    otherItem.querySelector('.faq-question').setAttribute('aria-expanded', 'false');
                }
            });
            
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

<?php include_footer(); ?>
