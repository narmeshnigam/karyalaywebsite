<?php

/**
 * Karyalay Portal System
 * Pricing Page
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
use Karyalay\Models\Plan;

try {
    $planModel = new Plan();
    
    // Fetch all active plans
    $plans = $planModel->findAll(['status' => 'ACTIVE']);
    
} catch (Exception $e) {
    error_log('Error fetching plans: ' . $e->getMessage());
    $plans = [];
}

// Set page variables
$page_title = 'Pricing';
$page_description = 'Choose the perfect plan for your business needs';

// Include header
include_header($page_title, $page_description);
?>

<!-- Hero Section -->
<section class="pricing-hero">
    <div class="container">
        <div class="pricing-hero-content">
            <h1 class="pricing-hero-title">Simple, Transparent Pricing</h1>
            <p class="pricing-hero-subtitle">
                Choose the plan that fits your business needs. All plans include our core features and 24/7 support.
            </p>
        </div>
    </div>
</section>

<!-- Pricing Cards Section -->
<section class="pricing-cards-section">
    <div class="container">
        <?php if (empty($plans)): ?>
            <div class="pricing-empty">
                <div class="pricing-empty-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="64" height="64">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                </div>
                <h3>No Pricing Plans Available</h3>
                <p>Please check back later or contact us for custom pricing tailored to your needs.</p>
                <div class="pricing-empty-actions">
                    <a href="/karyalayportal/contact.php" class="btn btn-primary">Contact Us</a>
                </div>
            </div>
        <?php else: ?>
            <div class="pricing-carousel-wrapper">
                <?php if (count($plans) > 3): ?>
                    <button class="pricing-carousel-nav pricing-carousel-prev" aria-label="Previous plans">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                <?php endif; ?>
                
                <div class="pricing-carousel">
                    <div class="pricing-carousel-track">
                        <?php foreach ($plans as $index => $plan): ?>
                            <article class="pricing-card <?php echo $index === 1 ? 'pricing-card-featured' : ''; ?>">
                                <?php if ($index === 1): ?>
                                    <div class="pricing-card-badge">Most Popular</div>
                                <?php endif; ?>
                                
                                <div class="pricing-card-header">
                                    <h3 class="pricing-card-title">
                                        <?php echo htmlspecialchars($plan['name']); ?>
                                    </h3>
                                    
                                    <?php if (!empty($plan['description'])): ?>
                                        <p class="pricing-card-description">
                                            <?php echo htmlspecialchars($plan['description']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="pricing-card-price">
                                    <div class="pricing-price-wrapper">
                                        <span class="pricing-currency"><?php echo htmlspecialchars($plan['currency']); ?></span>
                                        <span class="pricing-amount"><?php echo number_format($plan['price'], 0); ?></span>
                                    </div>
                                    <span class="pricing-period">
                                        / <?php echo $plan['billing_period_months']; ?> 
                                        <?php echo $plan['billing_period_months'] == 1 ? 'month' : 'months'; ?>
                                    </span>
                                </div>
                                
                                <?php if (!empty($plan['features']) && is_array($plan['features'])): ?>
                                    <div class="pricing-card-features">
                                        <p class="pricing-features-label">What's included:</p>
                                        <ul class="pricing-features-list">
                                            <?php foreach ($plan['features'] as $feature): ?>
                                                <li class="pricing-feature-item">
                                                    <svg class="pricing-feature-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                    <span><?php echo htmlspecialchars($feature); ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="pricing-card-action">
                                    <?php if (isAuthenticated()): ?>
                                        <form method="POST" action="/karyalayportal/select-plan.php">
                                            <input type="hidden" name="plan_slug" value="<?php echo htmlspecialchars($plan['slug']); ?>">
                                            <button type="submit" class="btn btn-primary btn-block">
                                                Buy Now
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <a href="/karyalayportal/register.php?plan=<?php echo urlencode($plan['slug']); ?>" 
                                           class="btn btn-primary btn-block">
                                            Get Started
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php if (count($plans) > 3): ?>
                    <button class="pricing-carousel-nav pricing-carousel-next" aria-label="Next plans">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                <?php endif; ?>
            </div>
            
            <?php if (count($plans) > 3): ?>
                <div class="pricing-carousel-dots">
                    <?php for ($i = 0; $i < count($plans); $i++): ?>
                        <button class="pricing-carousel-dot <?php echo $i === 0 ? 'active' : ''; ?>" 
                                data-slide="<?php echo $i; ?>"
                                aria-label="Go to slide <?php echo $i + 1; ?>"></button>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<!-- FAQ Section -->
<section class="pricing-faq-section">
    <div class="container">
        <div class="pricing-faq-header">
            <span class="pricing-section-label">Have Questions?</span>
            <h2 class="pricing-section-title">Frequently Asked Questions</h2>
        </div>
        
        <div class="pricing-faq-grid">
            <div class="pricing-faq-item">
                <div class="pricing-faq-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                </div>
                <h3 class="pricing-faq-question">Can I change my plan later?</h3>
                <p class="pricing-faq-answer">
                    Yes, you can upgrade or downgrade your plan at any time. Changes will be reflected in your next billing cycle, and we'll prorate any differences.
                </p>
            </div>
            
            <div class="pricing-faq-item">
                <div class="pricing-faq-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                </div>
                <h3 class="pricing-faq-question">What payment methods do you accept?</h3>
                <p class="pricing-faq-answer">
                    We accept all major credit cards, debit cards, and online payment methods through our secure payment gateway. All transactions are encrypted and secure.
                </p>
            </div>
            
            <div class="pricing-faq-item">
                <div class="pricing-faq-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="pricing-faq-question">Is there a free trial?</h3>
                <p class="pricing-faq-answer">
                    Contact us to discuss trial options for your business. We're happy to provide a demo of our platform and explore the best solution for your needs.
                </p>
            </div>
            
            <div class="pricing-faq-item">
                <div class="pricing-faq-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                    </svg>
                </div>
                <h3 class="pricing-faq-question">Do you offer custom plans?</h3>
                <p class="pricing-faq-answer">
                    Yes! If none of our standard plans fit your needs, contact us to discuss a custom solution tailored to your specific requirements and budget.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<?php
$cta_title = "Still Have Questions?";
$cta_subtitle = "Our team is here to help you choose the right plan for your business and answer any questions you may have";
$cta_source = "pricing-page";
include __DIR__ . '/../templates/cta-form.php';
?>

<style>
/* Pricing Hero */
.pricing-hero {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 50%, #fcd34d 100%);
    padding: var(--spacing-16) 0;
    position: relative;
    overflow: hidden;
}

.pricing-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 30% 40%, rgba(245, 158, 11, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 70% 70%, rgba(217, 119, 6, 0.08) 0%, transparent 50%);
    pointer-events: none;
}

.pricing-hero-content {
    position: relative;
    z-index: 1;
    text-align: center;
    max-width: 700px;
    margin: 0 auto;
}

.pricing-hero-title {
    font-size: var(--font-size-4xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-4) 0;
    line-height: 1.2;
}

.pricing-hero-subtitle {
    font-size: var(--font-size-lg);
    color: var(--color-gray-600);
    margin: 0;
    line-height: 1.6;
}

/* Section Labels & Titles */
.pricing-section-label {
    display: inline-block;
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-primary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: var(--spacing-2);
}

.pricing-section-title {
    font-size: var(--font-size-3xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0;
}

/* Pricing Cards Section */
.pricing-cards-section {
    padding: var(--spacing-16) 0;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    overflow: visible;
}

/* Pricing Carousel Wrapper */
.pricing-carousel-wrapper {
    position: relative;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 60px 40px 60px;
}

/* Pricing Carousel */
.pricing-carousel {
    overflow-x: auto;
    overflow-y: visible;
    position: relative;
    padding: 20px 0 20px 0;
    margin: -20px 0;
    scroll-behavior: smooth;
    scrollbar-width: thin;
    scrollbar-color: var(--color-gray-300) transparent;
    -webkit-overflow-scrolling: touch;
    scroll-snap-type: x mandatory;
}

.pricing-carousel::-webkit-scrollbar {
    height: 8px;
}

.pricing-carousel::-webkit-scrollbar-track {
    background: transparent;
    margin: 0 20px;
}

.pricing-carousel::-webkit-scrollbar-thumb {
    background: var(--color-gray-300);
    border-radius: var(--radius-full);
}

.pricing-carousel::-webkit-scrollbar-thumb:hover {
    background: var(--color-gray-400);
}

.pricing-carousel-track {
    display: flex;
    gap: var(--spacing-8);
    transition: transform 0.5s ease-in-out;
    padding: 0 4px;
}

/* Pricing Card */
.pricing-card {
    background: var(--color-white);
    border-radius: var(--radius-xl);
    padding: var(--spacing-8);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 2px solid var(--color-gray-100);
    position: relative;
    display: flex;
    flex-direction: column;
    flex: 0 0 auto;
    min-width: 360px;
    max-width: 380px;
    width: 360px;
    scroll-snap-align: start;
    scroll-snap-stop: always;
}

.pricing-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
    border-color: var(--color-primary);
}

.pricing-card-featured {
    border-color: var(--color-primary);
    box-shadow: 0 8px 30px rgba(139, 92, 246, 0.2);
}

.pricing-card-featured:hover {
    box-shadow: 0 16px 50px rgba(139, 92, 246, 0.3);
}

.pricing-card-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, var(--color-primary) 0%, #7c3aed 100%);
    color: var(--color-white);
    padding: var(--spacing-2) var(--spacing-4);
    border-radius: var(--radius-full);
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
}

.pricing-card-header {
    text-align: center;
    margin-bottom: var(--spacing-6);
}

.pricing-card-title {
    font-size: var(--font-size-2xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-3) 0;
}

.pricing-card-description {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
    line-height: 1.6;
    margin: 0;
}

.pricing-card-price {
    text-align: center;
    padding: var(--spacing-6) 0;
    margin-bottom: var(--spacing-6);
    border-bottom: 2px solid var(--color-gray-100);
}

.pricing-price-wrapper {
    display: flex;
    align-items: flex-start;
    justify-content: center;
    gap: var(--spacing-2);
    margin-bottom: var(--spacing-2);
}

.pricing-currency {
    font-size: var(--font-size-xl);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
    margin-top: var(--spacing-2);
}

.pricing-amount {
    font-size: 3.5rem;
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    line-height: 1;
}

.pricing-period {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
    display: block;
}

.pricing-card-features {
    flex: 1;
    margin-bottom: var(--spacing-6);
}

.pricing-features-label {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin: 0 0 var(--spacing-4) 0;
}

.pricing-features-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.pricing-feature-item {
    display: flex;
    align-items: flex-start;
    gap: var(--spacing-3);
    font-size: var(--font-size-base);
    color: var(--color-gray-700);
    margin-bottom: var(--spacing-3);
    line-height: 1.5;
}

.pricing-feature-item:last-child {
    margin-bottom: 0;
}

.pricing-feature-icon {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
    color: #10b981;
    margin-top: 2px;
}

.pricing-card-action {
    margin-top: auto;
}

.pricing-card-action form {
    margin: 0;
}

/* Empty State */
.pricing-empty {
    text-align: center;
    padding: var(--spacing-16) var(--spacing-8);
    background: var(--color-white);
    border-radius: var(--radius-xl);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    max-width: 600px;
    margin: 0 auto;
}

.pricing-empty-icon {
    color: var(--color-gray-400);
    margin-bottom: var(--spacing-4);
}

.pricing-empty h3 {
    font-size: var(--font-size-xl);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-2) 0;
}

.pricing-empty p {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
    margin: 0 0 var(--spacing-6) 0;
}

.pricing-empty-actions {
    display: flex;
    gap: var(--spacing-4);
    justify-content: center;
}

/* FAQ Section */
.pricing-faq-section {
    padding: var(--spacing-16) 0;
    background: var(--color-white);
}

.pricing-faq-header {
    text-align: center;
    margin-bottom: var(--spacing-12);
}

.pricing-faq-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--spacing-6);
    max-width: 1100px;
    margin: 0 auto;
}

.pricing-faq-item {
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: var(--radius-xl);
    padding: var(--spacing-6);
    border: 1px solid var(--color-gray-100);
    transition: all 0.3s ease;
}

.pricing-faq-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
    border-color: var(--color-primary);
}

.pricing-faq-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--color-primary) 0%, #7c3aed 100%);
    border-radius: var(--radius-lg);
    color: var(--color-white);
    margin-bottom: var(--spacing-4);
}

.pricing-faq-icon svg {
    width: 24px;
    height: 24px;
}

.pricing-faq-question {
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-3) 0;
}

.pricing-faq-answer {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
    line-height: 1.6;
    margin: 0;
}

/* Carousel Navigation Buttons */
.pricing-carousel-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 48px;
    height: 48px;
    border-radius: var(--radius-full);
    background: var(--color-white);
    border: 2px solid var(--color-gray-200);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    z-index: 10;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.pricing-carousel-nav:hover {
    background: var(--color-primary);
    border-color: var(--color-primary);
    box-shadow: 0 6px 20px rgba(139, 92, 246, 0.3);
}

.pricing-carousel-nav:hover svg {
    color: var(--color-white);
}

.pricing-carousel-nav svg {
    width: 24px;
    height: 24px;
    color: var(--color-gray-700);
    transition: color 0.3s ease;
}

.pricing-carousel-prev {
    left: 0;
}

.pricing-carousel-next {
    right: 0;
}

.pricing-carousel-nav:disabled {
    opacity: 0.3;
    cursor: not-allowed;
    pointer-events: none;
}

/* Carousel Dots */
.pricing-carousel-dots {
    display: flex;
    justify-content: center;
    gap: var(--spacing-3);
    margin-top: var(--spacing-8);
}

.pricing-carousel-dot {
    width: 12px;
    height: 12px;
    border-radius: var(--radius-full);
    background: var(--color-gray-300);
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    padding: 0;
}

.pricing-carousel-dot:hover {
    background: var(--color-gray-400);
    transform: scale(1.2);
}

.pricing-carousel-dot.active {
    background: var(--color-primary);
    width: 32px;
}

/* Responsive */
@media (max-width: 1024px) {
    .pricing-carousel-wrapper {
        padding: 0 50px 40px 50px;
    }
    
    .pricing-card {
        flex: 0 0 auto;
        min-width: 320px;
        max-width: 360px;
    }
    
    .pricing-faq-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .pricing-hero {
        padding: var(--spacing-12) 0;
    }
    
    .pricing-hero-title {
        font-size: var(--font-size-3xl);
    }
    
    .pricing-hero-subtitle {
        font-size: var(--font-size-base);
    }
    
    .pricing-cards-section,
    .pricing-faq-section {
        padding: var(--spacing-12) 0;
    }
    
    .pricing-carousel-wrapper {
        padding: 0 40px 40px 40px;
    }
    
    .pricing-carousel {
        padding: 20px 0;
    }
    
    .pricing-card {
        flex: 0 0 auto;
        min-width: 280px;
        max-width: 320px;
        padding: var(--spacing-6);
    }
    
    .pricing-carousel-nav {
        width: 40px;
        height: 40px;
    }
    
    .pricing-carousel-nav svg {
        width: 20px;
        height: 20px;
    }
    
    .pricing-amount {
        font-size: 3rem;
    }
    
    .pricing-faq-item {
        padding: var(--spacing-5);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const carousel = document.querySelector('.pricing-carousel');
    const track = document.querySelector('.pricing-carousel-track');
    const cards = document.querySelectorAll('.pricing-card');
    const prevBtn = document.querySelector('.pricing-carousel-prev');
    const nextBtn = document.querySelector('.pricing-carousel-next');
    const dots = document.querySelectorAll('.pricing-carousel-dot');
    
    if (!carousel || !track || cards.length === 0) return;
    
    let currentSlide = 0;
    let isScrolling = false;
    
    // Get card width including gap
    function getCardScrollWidth() {
        const cardWidth = cards[0].offsetWidth;
        const gap = parseInt(getComputedStyle(track).gap) || 32;
        return cardWidth + gap;
    }
    
    // Update navigation buttons based on scroll position
    function updateNavigation() {
        const scrollLeft = carousel.scrollLeft;
        const maxScroll = carousel.scrollWidth - carousel.clientWidth;
        
        if (prevBtn && nextBtn) {
            prevBtn.disabled = scrollLeft <= 0;
            nextBtn.disabled = scrollLeft >= maxScroll - 5; // 5px tolerance
        }
        
        // Update current slide based on scroll position
        const cardScrollWidth = getCardScrollWidth();
        currentSlide = Math.round(scrollLeft / cardScrollWidth);
        
        // Update dots
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === currentSlide);
        });
    }
    
    // Scroll to specific card
    function scrollToCard(cardIndex) {
        const cardScrollWidth = getCardScrollWidth();
        const scrollPosition = cardIndex * cardScrollWidth;
        
        carousel.scrollTo({
            left: scrollPosition,
            behavior: 'smooth'
        });
    }
    
    // Previous card
    if (prevBtn) {
        prevBtn.addEventListener('click', function() {
            scrollToCard(Math.max(0, currentSlide - 1));
        });
    }
    
    // Next card
    if (nextBtn) {
        nextBtn.addEventListener('click', function() {
            scrollToCard(Math.min(cards.length - 1, currentSlide + 1));
        });
    }
    
    // Dot navigation
    dots.forEach((dot, index) => {
        dot.addEventListener('click', function() {
            scrollToCard(index);
        });
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowLeft' && prevBtn && !prevBtn.disabled) {
            scrollToCard(Math.max(0, currentSlide - 1));
        } else if (e.key === 'ArrowRight' && nextBtn && !nextBtn.disabled) {
            scrollToCard(Math.min(cards.length - 1, currentSlide + 1));
        }
    });
    
    // Listen to scroll events to update navigation
    let scrollTimeout;
    carousel.addEventListener('scroll', function() {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(function() {
            updateNavigation();
        }, 50);
    }, { passive: true });
    
    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            updateNavigation();
        }, 250);
    });
    
    // Initialize
    updateNavigation();
});
</script>

<?php
// Include footer
include_footer();
?>
