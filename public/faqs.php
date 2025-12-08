<?php

/**
 * Karyalay Portal System
 * FAQs Page
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

// Set page variables
$page_title = 'Frequently Asked Questions';
$page_description = 'Find answers to common questions about Karyalay business management system';

// Include header
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
        <div class="faqs-grid">
            <!-- General Questions -->
            <div class="faqs-category">
                <h2 class="faqs-category-title">General Questions</h2>
                
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>What is Karyalay?</span>
                        <svg class="faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="faq-answer">
                        <p>Karyalay is a comprehensive business management system designed to streamline your operations, manage customer relationships, handle subscriptions, and provide excellent support. Our platform brings all your business tools together in one place.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>Who can benefit from using Karyalay?</span>
                        <svg class="faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="faq-answer">
                        <p>Karyalay is perfect for businesses of all sizes - from startups to enterprises. Whether you're managing a small team or a large organization, our platform scales with your needs and helps you stay organized and efficient.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>Is there a free trial available?</span>
                        <svg class="faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="faq-answer">
                        <p>Yes! We offer a demo of our platform. Contact us to schedule a personalized demo where we'll walk you through all the features and show you how Karyalay can benefit your business.</p>
                    </div>
                </div>
            </div>
            
            <!-- Pricing & Plans -->
            <div class="faqs-category">
                <h2 class="faqs-category-title">Pricing & Plans</h2>
                
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>What payment methods do you accept?</span>
                        <svg class="faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="faq-answer">
                        <p>We accept all major credit cards, debit cards, and online payment methods through our secure payment gateway. All transactions are encrypted and secure.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>Can I change my plan later?</span>
                        <svg class="faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="faq-answer">
                        <p>Absolutely! You can upgrade or downgrade your plan at any time. Changes will be reflected in your next billing cycle, and we'll prorate any differences.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>Do you offer custom plans?</span>
                        <svg class="faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="faq-answer">
                        <p>Yes! If none of our standard plans fit your needs, contact us to discuss a custom solution tailored to your specific requirements and budget.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>What happens if I cancel my subscription?</span>
                        <svg class="faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="faq-answer">
                        <p>You can cancel your subscription at any time. You'll continue to have access until the end of your current billing period. We also provide data export options so you can take your information with you.</p>
                    </div>
                </div>
            </div>
            
            <!-- Features & Functionality -->
            <div class="faqs-category">
                <h2 class="faqs-category-title">Features & Functionality</h2>
                
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>What features are included in all plans?</span>
                        <svg class="faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="faq-answer">
                        <p>All plans include our core features: customer management, subscription handling, support ticketing, reporting and analytics, and 24/7 customer support. Higher-tier plans offer additional features and increased limits.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>Can I integrate Karyalay with other tools?</span>
                        <svg class="faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="faq-answer">
                        <p>Yes! Karyalay offers API access and integrations with popular business tools. Contact our team to discuss specific integration requirements for your business.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>Is my data secure?</span>
                        <svg class="faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="faq-answer">
                        <p>Absolutely. We use enterprise-grade security measures including SSL encryption, regular backups, and secure data centers. Your data is protected with industry-standard security protocols.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>Can I export my data?</span>
                        <svg class="faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="faq-answer">
                        <p>Yes, you can export your data at any time in standard formats like CSV and JSON. We believe your data belongs to you, and we make it easy to access and export whenever you need it.</p>
                    </div>
                </div>
            </div>
            
            <!-- Support & Training -->
            <div class="faqs-category">
                <h2 class="faqs-category-title">Support & Training</h2>
                
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>What kind of support do you offer?</span>
                        <svg class="faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="faq-answer">
                        <p>We offer 24/7 customer support via email and chat. Higher-tier plans include priority support and dedicated account managers. We're here to help you succeed!</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>Do you provide training for new users?</span>
                        <svg class="faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="faq-answer">
                        <p>Yes! We provide comprehensive documentation, video tutorials, and onboarding sessions to help you get started. Our support team is always available to answer questions and provide guidance.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>How quickly can I get started?</span>
                        <svg class="faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="faq-answer">
                        <p>You can get started immediately! Sign up, choose your plan, and you'll have instant access to your dashboard. Our intuitive interface makes it easy to start managing your business right away.</p>
                    </div>
                </div>
            </div>
        </div>
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
/* FAQs Hero */
.faqs-hero {
    background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 50%, #a5b4fc 100%);
    padding: var(--spacing-16) 0;
    position: relative;
    overflow: hidden;
}

.faqs-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
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

/* FAQs Section */
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

/* FAQ Item */
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

.faq-question:hover {
    color: var(--color-primary);
}

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
    max-height: 500px;
    padding: 0 var(--spacing-5) var(--spacing-5) var(--spacing-5);
}

.faq-answer p {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
    line-height: 1.7;
    margin: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .faqs-grid {
        gap: var(--spacing-8);
    }
    
    .faqs-hero {
        padding: var(--spacing-12) 0;
    }
    
    .faqs-hero-title {
        font-size: var(--font-size-3xl);
    }
    
    .faqs-hero-subtitle {
        font-size: var(--font-size-base);
    }
    
    .faqs-section {
        padding: var(--spacing-12) 0;
    }
    
    .faqs-category-title {
        font-size: var(--font-size-xl);
    }
    
    .faq-question {
        font-size: var(--font-size-base);
        padding: var(--spacing-4);
    }
    
    .faq-item.active .faq-answer {
        padding: 0 var(--spacing-4) var(--spacing-4) var(--spacing-4);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        
        question.addEventListener('click', function() {
            const isActive = item.classList.contains('active');
            
            // Close all other items
            faqItems.forEach(otherItem => {
                if (otherItem !== item) {
                    otherItem.classList.remove('active');
                    otherItem.querySelector('.faq-question').setAttribute('aria-expanded', 'false');
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
