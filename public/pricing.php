<?php
/**
 * SellerPortal System
 * Pricing Page with Duration Filtering
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

use Karyalay\Services\PlanService;

$planService = new PlanService();
$selectedDuration = $_GET['duration'] ?? 'all';

try {
    $filters = ['status' => 'ACTIVE'];
    if ($selectedDuration !== 'all') {
        $filters['duration'] = $selectedDuration;
    }
    $plans = $planService->findAll($filters);
    $plansByDuration = $planService->getPlansByDuration();
} catch (Exception $e) {
    error_log('Error fetching plans: ' . $e->getMessage());
    $plans = [];
    $plansByDuration = ['monthly' => [], 'quarterly' => [], 'annual' => [], 'other' => []];
}

$page_title = 'Pricing';
$page_description = 'Choose the perfect plan for your business needs';
include_header($page_title, $page_description);
?>

<!-- Hero Section -->
<section class="pricing-hero">
    <div class="container">
        <div class="pricing-hero-content">
            <h1 class="pricing-hero-title">Simple, Transparent Pricing</h1>
            <p class="pricing-hero-subtitle">Choose the plan that fits your business needs. All plans include our core features and 24/7 support.</p>
        </div>
    </div>
</section>

<!-- Duration Filter Tabs -->
<section class="pricing-filter-section">
    <div class="container">
        <div class="pricing-duration-tabs">
            <a href="?duration=all" class="duration-tab <?php echo $selectedDuration === 'all' ? 'active' : ''; ?>">
                All Plans
                <span class="tab-count"><?php echo count($plansByDuration['monthly']) + count($plansByDuration['quarterly']) + count($plansByDuration['annual']) + count($plansByDuration['other']); ?></span>
            </a>
            <a href="?duration=monthly" class="duration-tab <?php echo $selectedDuration === 'monthly' ? 'active' : ''; ?>">
                Monthly
                <?php if (count($plansByDuration['monthly']) > 0): ?><span class="tab-count"><?php echo count($plansByDuration['monthly']); ?></span><?php endif; ?>
            </a>
            <a href="?duration=quarterly" class="duration-tab <?php echo $selectedDuration === 'quarterly' ? 'active' : ''; ?>">
                Quarterly
                <?php if (count($plansByDuration['quarterly']) > 0): ?><span class="tab-count"><?php echo count($plansByDuration['quarterly']); ?></span><?php endif; ?>
            </a>
            <a href="?duration=annual" class="duration-tab <?php echo $selectedDuration === 'annual' ? 'active' : ''; ?>">
                Annual
                <?php if (count($plansByDuration['annual']) > 0): ?><span class="tab-count"><?php echo count($plansByDuration['annual']); ?></span><?php endif; ?>
            </a>
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
                <h3>No Plans Available</h3>
                <p>No plans found for the selected duration. Try selecting a different category.</p>
                <div class="pricing-empty-actions">
                    <a href="?duration=all" class="btn btn-primary">View All Plans</a>
                </div>
            </div>
        <?php else: ?>
            <div class="pricing-grid">
                <?php foreach ($plans as $index => $plan): ?>
                    <?php 
                    $hasDiscount = $planService->hasDiscount($plan);
                    $discountPct = $planService->getDiscountPercentage($plan);
                    $effectivePrice = $planService->getEffectivePrice($plan);
                    $isFeatured = $index === 1 && count($plans) > 2;
                    $months = (int)$plan['billing_period_months'];
                    $durationLabel = match($months) {
                        1 => 'month',
                        3 => 'quarter',
                        6 => '6 months',
                        12 => 'year',
                        default => $months . ' months'
                    };
                    ?>
                    <article class="pricing-card <?php echo $isFeatured ? 'pricing-card-featured' : ''; ?>">
                        <?php if ($isFeatured): ?>
                            <div class="pricing-card-badge">Most Popular</div>
                        <?php endif; ?>
                        
                        <?php if ($hasDiscount): ?>
                            <div class="pricing-discount-ribbon"><?php echo $discountPct; ?>% OFF</div>
                        <?php endif; ?>
                        
                        <div class="pricing-card-header">
                            <h3 class="pricing-card-title"><?php echo htmlspecialchars($plan['name']); ?></h3>
                            <?php if (!empty($plan['description'])): ?>
                                <p class="pricing-card-description"><?php echo htmlspecialchars($plan['description']); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="pricing-card-price">
                            <?php if ($hasDiscount): ?>
                                <div class="pricing-mrp">
                                    <span><?php echo format_price($plan['mrp'], false); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="pricing-price-wrapper">
                                <span class="pricing-currency"><?php echo get_currency_symbol(); ?></span>
                                <span class="pricing-amount"><?php echo number_format($effectivePrice, 0); ?></span>
                            </div>
                            <span class="pricing-period">/ <?php echo $durationLabel; ?></span>
                        </div>

                        <!-- Plan Limits -->
                        <div class="pricing-limits">
                            <div class="limit-item">
                                <svg class="limit-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                <span><?php echo !empty($plan['number_of_users']) ? $plan['number_of_users'] . ' Users' : 'Unlimited Users'; ?></span>
                            </div>
                            <div class="limit-item">
                                <svg class="limit-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path></svg>
                                <span><?php echo !empty($plan['allowed_storage_gb']) ? $plan['allowed_storage_gb'] . ' GB Storage' : 'Unlimited Storage'; ?></span>
                            </div>
                        </div>
                        
                        <?php if (!empty($plan['features_html'])): ?>
                            <div class="pricing-card-features pricing-features-rich">
                                <?php echo $plan['features_html']; ?>
                            </div>
                        <?php elseif (!empty($plan['features']) && is_array($plan['features'])): ?>
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
                                <form method="POST" action="<?php echo get_base_url(); ?>/select-plan.php">
                                    <input type="hidden" name="plan_slug" value="<?php echo htmlspecialchars($plan['slug']); ?>">
                                    <button type="submit" class="btn btn-primary btn-block">Buy Now</button>
                                </form>
                            <?php else: ?>
                                <a href="<?php echo get_base_url(); ?>/register.php?plan=<?php echo urlencode($plan['slug']); ?>" class="btn btn-primary btn-block">Get Started</a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
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
                <div class="pricing-faq-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg></div>
                <h3 class="pricing-faq-question">Can I change my plan later?</h3>
                <p class="pricing-faq-answer">Yes, you can upgrade or downgrade your plan at any time. Changes will be reflected in your next billing cycle.</p>
            </div>
            <div class="pricing-faq-item">
                <div class="pricing-faq-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg></div>
                <h3 class="pricing-faq-question">What payment methods do you accept?</h3>
                <p class="pricing-faq-answer">We accept all major credit cards, debit cards, and online payment methods through our secure payment gateway.</p>
            </div>
            <div class="pricing-faq-item">
                <div class="pricing-faq-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
                <h3 class="pricing-faq-question">Is there a free trial?</h3>
                <p class="pricing-faq-answer">Contact us to discuss trial options for your business. We're happy to provide a demo of our platform.</p>
            </div>
            <div class="pricing-faq-item">
                <div class="pricing-faq-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg></div>
                <h3 class="pricing-faq-question">Do you offer custom plans?</h3>
                <p class="pricing-faq-answer">Yes! Contact us to discuss a custom solution tailored to your specific requirements and budget.</p>
            </div>
        </div>
    </div>
</section>

<?php
$cta_title = "Still Have Questions?";
$cta_subtitle = "Our team is here to help you choose the right plan";
$cta_source = "pricing-page";
include __DIR__ . '/../templates/cta-form.php';
?>

<style>
.pricing-hero{background:linear-gradient(135deg,#fef3c7 0%,#fde68a 50%,#fcd34d 100%);padding:var(--spacing-16) 0;position:relative}
.pricing-hero-content{text-align:center;max-width:700px;margin:0 auto}
.pricing-hero-title{font-size:var(--font-size-4xl);font-weight:var(--font-weight-bold);color:var(--color-gray-900);margin:0 0 var(--spacing-4) 0}
.pricing-hero-subtitle{font-size:var(--font-size-lg);color:var(--color-gray-600);margin:0}

.pricing-filter-section{background:var(--color-white);padding:var(--spacing-6) 0;border-bottom:1px solid var(--color-gray-200);position:sticky;top:0;z-index:100}
.pricing-duration-tabs{display:flex;justify-content:center;gap:var(--spacing-2);flex-wrap:wrap}
.duration-tab{display:inline-flex;align-items:center;gap:var(--spacing-2);padding:var(--spacing-3) var(--spacing-5);border-radius:var(--radius-full);font-size:var(--font-size-base);font-weight:var(--font-weight-medium);color:var(--color-gray-600);background:var(--color-gray-100);text-decoration:none;transition:all 0.3s ease}
.duration-tab:hover{background:var(--color-gray-200);color:var(--color-gray-900)}
.duration-tab.active{background:var(--color-primary);color:var(--color-white)}
.tab-count{font-size:var(--font-size-sm);background:rgba(255,255,255,0.2);padding:2px 8px;border-radius:var(--radius-full)}
.duration-tab.active .tab-count{background:rgba(255,255,255,0.3)}

.pricing-cards-section{padding:var(--spacing-16) 0;background:linear-gradient(180deg,#f8fafc 0%,#f1f5f9 100%)}
.pricing-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:var(--spacing-8);max-width:1200px;margin:0 auto;justify-items:center}
.pricing-grid::after{content:'';grid-column:span 1}
.pricing-grid:has(.pricing-card:nth-child(3))::after{display:none}

.pricing-card{background:var(--color-white);border-radius:var(--radius-xl);padding:var(--spacing-8);box-shadow:0 4px 20px rgba(0,0,0,0.08);transition:all 0.3s ease;border:2px solid var(--color-gray-100);position:relative;display:flex;flex-direction:column;width:100%;max-width:380px}
.pricing-card:hover{transform:translateY(-8px);box-shadow:0 12px 40px rgba(0,0,0,0.15);border-color:var(--color-primary)}
.pricing-card-featured{border-color:var(--color-primary);box-shadow:0 8px 30px rgba(139,92,246,0.2)}
.pricing-card-badge{position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,var(--color-primary) 0%,#7c3aed 100%);color:var(--color-white);padding:var(--spacing-2) var(--spacing-4);border-radius:var(--radius-full);font-size:var(--font-size-sm);font-weight:var(--font-weight-semibold)}
.pricing-discount-ribbon{position:absolute;top:16px;right:-8px;background:linear-gradient(135deg,#10b981,#059669);color:white;padding:6px 16px 6px 12px;font-size:var(--font-size-sm);font-weight:var(--font-weight-bold);clip-path:polygon(0 0,100% 0,100% 100%,0 100%,8px 50%)}

.pricing-card-header{text-align:center;margin-bottom:var(--spacing-6)}
.pricing-card-title{font-size:var(--font-size-2xl);font-weight:var(--font-weight-bold);color:var(--color-gray-900);margin:0 0 var(--spacing-3) 0}
.pricing-card-description{font-size:var(--font-size-base);color:var(--color-gray-600);margin:0}

.pricing-card-price{text-align:center;padding:var(--spacing-6) 0;margin-bottom:var(--spacing-4);border-bottom:2px solid var(--color-gray-100)}
.pricing-mrp{margin-bottom:var(--spacing-2)}
.pricing-mrp span{font-size:var(--font-size-lg);color:var(--color-gray-500);text-decoration:line-through}
.pricing-price-wrapper{display:flex;align-items:flex-start;justify-content:center;gap:var(--spacing-1)}
.pricing-currency{font-size:var(--font-size-xl);font-weight:var(--font-weight-semibold);color:var(--color-gray-700);margin-top:var(--spacing-2)}
.pricing-amount{font-size:3.5rem;font-weight:var(--font-weight-bold);color:var(--color-gray-900);line-height:1}
.pricing-period{font-size:var(--font-size-base);color:var(--color-gray-600);display:block;margin-top:var(--spacing-2)}

.pricing-limits{display:flex;justify-content:center;gap:var(--spacing-6);padding:var(--spacing-4);background:var(--color-gray-50);border-radius:var(--radius-lg);margin-bottom:var(--spacing-6)}
.limit-item{display:flex;align-items:center;gap:var(--spacing-2);font-size:var(--font-size-sm);color:var(--color-gray-700)}
.limit-icon{width:18px;height:18px;color:var(--color-primary)}

.pricing-card-features{flex:1;margin-bottom:var(--spacing-6)}
.pricing-features-label{font-size:var(--font-size-sm);font-weight:var(--font-weight-semibold);color:var(--color-gray-700);text-transform:uppercase;letter-spacing:0.05em;margin:0 0 var(--spacing-4) 0}
.pricing-features-list{list-style:none;padding:0;margin:0}
.pricing-feature-item{display:flex;align-items:flex-start;gap:var(--spacing-3);font-size:var(--font-size-base);color:var(--color-gray-700);margin-bottom:var(--spacing-3)}
.pricing-feature-icon{width:20px;height:20px;flex-shrink:0;color:#10b981;margin-top:2px}
.pricing-features-rich{line-height:1.6}
.pricing-features-rich ul{padding-left:var(--spacing-4);margin:var(--spacing-2) 0}
.pricing-features-rich li{margin-bottom:var(--spacing-2)}

.pricing-card-action{margin-top:auto}
.pricing-card-action form{margin:0}

.pricing-empty{text-align:center;padding:var(--spacing-16) var(--spacing-8);background:var(--color-white);border-radius:var(--radius-xl);max-width:600px;margin:0 auto}
.pricing-empty-icon{color:var(--color-gray-400);margin-bottom:var(--spacing-4)}
.pricing-empty h3{font-size:var(--font-size-xl);color:var(--color-gray-900);margin:0 0 var(--spacing-2) 0}
.pricing-empty p{color:var(--color-gray-600);margin:0 0 var(--spacing-6) 0}

.pricing-section-label{display:inline-block;font-size:var(--font-size-sm);font-weight:var(--font-weight-semibold);color:var(--color-primary);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:var(--spacing-2)}
.pricing-section-title{font-size:var(--font-size-3xl);font-weight:var(--font-weight-bold);color:var(--color-gray-900);margin:0}

.pricing-faq-section{padding:var(--spacing-16) 0;background:var(--color-white)}
.pricing-faq-header{text-align:center;margin-bottom:var(--spacing-12)}
.pricing-faq-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:var(--spacing-6);max-width:1100px;margin:0 auto}
.pricing-faq-item{background:linear-gradient(180deg,#f8fafc 0%,#f1f5f9 100%);border-radius:var(--radius-xl);padding:var(--spacing-6);border:1px solid var(--color-gray-100);transition:all 0.3s ease}
.pricing-faq-item:hover{transform:translateY(-4px);box-shadow:0 8px 30px rgba(0,0,0,0.1)}
.pricing-faq-icon{width:48px;height:48px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--color-primary) 0%,#7c3aed 100%);border-radius:var(--radius-lg);color:var(--color-white);margin-bottom:var(--spacing-4)}
.pricing-faq-icon svg{width:24px;height:24px}
.pricing-faq-question{font-size:var(--font-size-lg);font-weight:var(--font-weight-semibold);color:var(--color-gray-900);margin:0 0 var(--spacing-3) 0}
.pricing-faq-answer{font-size:var(--font-size-base);color:var(--color-gray-600);line-height:1.6;margin:0}

@media(max-width:1200px){.pricing-grid{grid-template-columns:repeat(auto-fit,minmax(300px,1fr))}}
@media(max-width:1024px){.pricing-faq-grid{grid-template-columns:1fr}}
@media(max-width:768px){
    .pricing-hero{padding:var(--spacing-12) 0}
    .pricing-hero-title{font-size:var(--font-size-3xl)}
    .pricing-cards-section,.pricing-faq-section{padding:var(--spacing-12) 0}
    .pricing-grid{grid-template-columns:1fr;padding:0 var(--spacing-4);justify-items:stretch}
    .pricing-card{max-width:100%}
    .pricing-amount{font-size:3rem}
    .pricing-limits{flex-direction:column;gap:var(--spacing-2)}
    .pricing-duration-tabs{gap:var(--spacing-1)}
    .duration-tab{padding:var(--spacing-2) var(--spacing-4);font-size:var(--font-size-sm)}
}
</style>

<?php include_footer(); ?>
