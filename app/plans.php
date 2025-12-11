<?php
/**
 * Customer Plans Page
 * Display available plans with current plan highlighted
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/template_helpers.php';

use Karyalay\Models\Plan;
use Karyalay\Models\Subscription;
use Karyalay\Services\PlanService;

$user = guardCustomerPortal();
$userId = $user['id'];

$subscriptionModel = new Subscription();
$activeSubscription = $subscriptionModel->findActiveByCustomerId($userId);
$currentPlanId = $activeSubscription ? $activeSubscription['plan_id'] : null;

$planService = new PlanService();
$selectedDuration = $_GET['duration'] ?? 'all';

$filters = ['status' => 'ACTIVE'];
if ($selectedDuration !== 'all') {
    $filters['duration'] = $selectedDuration;
}
$plans = $planService->findAll($filters);
$plansByDuration = $planService->getPlansByDuration();

$page_title = 'Plans';
require_once __DIR__ . '/../templates/customer-header.php';
?>

<div class="section-header">
    <h2 class="section-title">Available Plans</h2>
    <p style="color: #666; margin-top: 0.5rem;">Choose the plan that best fits your needs</p>
</div>

<!-- Duration Filter -->
<div class="duration-filter">
    <a href="?duration=all" class="filter-tab <?php echo $selectedDuration === 'all' ? 'active' : ''; ?>">All</a>
    <a href="?duration=monthly" class="filter-tab <?php echo $selectedDuration === 'monthly' ? 'active' : ''; ?>">Monthly</a>
    <a href="?duration=quarterly" class="filter-tab <?php echo $selectedDuration === 'quarterly' ? 'active' : ''; ?>">Quarterly</a>
    <a href="?duration=annual" class="filter-tab <?php echo $selectedDuration === 'annual' ? 'active' : ''; ?>">Annual</a>
</div>

<?php if (empty($plans)): ?>
    <div class="info-box">
        <p style="text-align: center; padding: 2rem 0;">No plans available for the selected duration.</p>
    </div>
<?php else: ?>
    <div class="plans-grid">
        <?php foreach ($plans as $plan): ?>
            <?php 
            $isCurrentPlan = ($currentPlanId === $plan['id']);
            $hasDiscount = $planService->hasDiscount($plan);
            $discountPct = $planService->getDiscountPercentage($plan);
            $effectivePrice = $planService->getEffectivePrice($plan);
            $months = (int)$plan['billing_period_months'];
            $durationLabel = match($months) {
                1 => 'month',
                3 => 'quarter',
                6 => '6 months',
                12 => 'year',
                default => $months . ' months'
            };
            ?>
            <div class="plan-card <?php echo $isCurrentPlan ? 'current-plan' : ''; ?>">
                <?php if ($isCurrentPlan): ?>
                    <div class="plan-badge current">Current Plan</div>
                <?php endif; ?>
                
                <?php if ($hasDiscount && !$isCurrentPlan): ?>
                    <div class="plan-badge discount"><?php echo $discountPct; ?>% OFF</div>
                <?php endif; ?>
                
                <div class="plan-header">
                    <h3 class="plan-name"><?php echo htmlspecialchars($plan['name']); ?></h3>
                    <?php if (!empty($plan['description'])): ?>
                        <p class="plan-description"><?php echo htmlspecialchars($plan['description']); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="plan-price">
                    <?php if ($hasDiscount): ?>
                        <span class="price-mrp"><?php echo format_price($plan['mrp'], false); ?></span>
                    <?php endif; ?>
                    <span class="price-amount"><?php echo format_price($effectivePrice, false); ?></span>
                    <span class="price-period">/ <?php echo $durationLabel; ?></span>
                </div>

                <!-- Plan Limits -->
                <div class="plan-limits">
                    <div class="limit-item">
                        <span class="limit-icon">👥</span>
                        <span><?php echo !empty($plan['number_of_users']) ? $plan['number_of_users'] . ' Users' : 'Unlimited Users'; ?></span>
                    </div>
                    <div class="limit-item">
                        <span class="limit-icon">💾</span>
                        <span><?php echo !empty($plan['allowed_storage_gb']) ? $plan['allowed_storage_gb'] . ' GB' : 'Unlimited Storage'; ?></span>
                    </div>
                </div>
                
                <?php if (!empty($plan['features_html'])): ?>
                    <div class="plan-features rich-features">
                        <?php echo $plan['features_html']; ?>
                    </div>
                <?php elseif (!empty($plan['features']) && is_array($plan['features'])): ?>
                    <div class="plan-features">
                        <ul>
                            <?php foreach ($plan['features'] as $feature): ?>
                                <li>✓ <?php echo htmlspecialchars($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="plan-actions">
                    <?php if ($isCurrentPlan): ?>
                        <a href="<?php echo get_app_base_url(); ?>/app/subscription/renew.php?subscription_id=<?php echo urlencode($activeSubscription['id']); ?>" class="btn btn-primary btn-block">Renew Plan</a>
                        <a href="<?php echo get_app_base_url(); ?>/app/subscription.php" class="btn btn-outline btn-block">View Details</a>
                    <?php elseif ($activeSubscription): ?>
                        <a href="<?php echo get_base_url(); ?>/select-plan.php?plan=<?php echo htmlspecialchars($plan['slug']); ?>" class="btn btn-primary btn-block">Upgrade</a>
                    <?php else: ?>
                        <a href="<?php echo get_base_url(); ?>/select-plan.php?plan=<?php echo htmlspecialchars($plan['slug']); ?>" class="btn btn-primary btn-block">Get Started</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
.duration-filter {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.filter-tab {
    padding: 0.5rem 1.25rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
    color: #666;
    background: #f0f0f0;
    text-decoration: none;
    transition: all 0.3s ease;
}

.filter-tab:hover {
    background: #e0e0e0;
    color: #333;
}

.filter-tab.active {
    background: var(--primary-color, #007bff);
    color: white;
}

.plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-top: 1rem;
    justify-items: center;
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
}

.plan-card {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 2rem;
    position: relative;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    width: 100%;
    max-width: 380px;
}

.plan-card:hover {
    border-color: var(--primary-color, #007bff);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    transform: translateY(-4px);
}

.plan-card.current-plan {
    border-color: var(--success-color, #28a745);
    background: #f8fff9;
}

.plan-badge {
    position: absolute;
    top: -12px;
    padding: 0.25rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.plan-badge.current {
    right: 20px;
    background: var(--success-color, #28a745);
    color: white;
}

.plan-badge.discount {
    left: 20px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.plan-header {
    margin-bottom: 1.5rem;
}

.plan-name {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
    color: #333;
}

.plan-description {
    color: #666;
    font-size: 0.9rem;
    margin: 0;
}

.plan-price {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e0e0e0;
    text-align: center;
}

.price-mrp {
    display: block;
    font-size: 1rem;
    color: #999;
    text-decoration: line-through;
    margin-bottom: 0.25rem;
}

.price-amount {
    font-size: 2.25rem;
    font-weight: 700;
    color: #333;
}

.price-period {
    color: #666;
    font-size: 0.9rem;
}

.plan-limits {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.limit-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: #555;
}

.limit-icon {
    font-size: 1rem;
}

.plan-features {
    flex: 1;
    margin-bottom: 1.5rem;
}

.plan-features ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.plan-features li {
    padding: 0.5rem 0;
    color: #555;
    font-size: 0.9rem;
}

.rich-features {
    line-height: 1.6;
}

.rich-features ul {
    padding-left: 1.25rem;
}

.rich-features li {
    margin-bottom: 0.5rem;
}

.plan-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-top: auto;
}

.btn-block {
    width: 100%;
    text-align: center;
}

@media (max-width: 1024px) {
    .plans-grid {
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .plans-grid {
        grid-template-columns: 1fr;
        justify-items: stretch;
    }
    
    .plan-card {
        max-width: 100%;
    }
    
    .plan-limits {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>

<?php require_once __DIR__ . '/../templates/customer-footer.php'; ?>
