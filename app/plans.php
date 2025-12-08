<?php
/**
 * Customer Plans Page
 * Display available plans with current plan highlighted
 * 
 * Displays:
 * - Available plans with current plan highlighted
 * - Upgrade/renew/buy actions
 * 
 * Requirements: 5.2
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Include required files
require_once __DIR__ . '/../includes/auth_helpers.php';

use Karyalay\Models\Plan;
use Karyalay\Models\Subscription;

// Guard customer portal - requires authentication
$user = guardCustomerPortal();
$userId = $user['id'];

// Fetch active subscription for the customer
$subscriptionModel = new Subscription();
$activeSubscription = $subscriptionModel->findActiveByCustomerId($userId);

// Get current plan ID if exists
$currentPlanId = null;
if ($activeSubscription) {
    $currentPlanId = $activeSubscription['plan_id'];
}

// Fetch all active plans
$planModel = new Plan();
$plans = $planModel->findAll(['status' => 'ACTIVE']);

// Set page variables
$page_title = 'Plans';

// Include customer portal header
require_once __DIR__ . '/../templates/customer-header.php';
?>

<div class="section-header">
    <h2 class="section-title">Available Plans</h2>
    <p style="color: #666; margin-top: 0.5rem;">
        Choose the plan that best fits your needs
    </p>
</div>

<?php if (empty($plans)): ?>
    <div class="info-box">
        <div class="info-box-content">
            <p style="text-align: center; padding: 2rem 0;">
                No plans are currently available. Please check back later.
            </p>
        </div>
    </div>
<?php else: ?>
    <div class="plans-grid">
        <?php foreach ($plans as $plan): ?>
            <?php 
                $isCurrentPlan = ($currentPlanId === $plan['id']);
                $planName = htmlspecialchars($plan['name']);
                $planSlug = htmlspecialchars($plan['slug']);
                $planDescription = htmlspecialchars($plan['description'] ?? '');
                $planPrice = number_format($plan['price'], 2);
                $planCurrency = htmlspecialchars($plan['currency']);
                $billingPeriod = $plan['billing_period_months'];
                $planFeatures = $plan['features'] ?? [];
            ?>
            <div class="plan-card <?php echo $isCurrentPlan ? 'current-plan' : ''; ?>">
                <?php if ($isCurrentPlan): ?>
                    <div class="plan-badge">Current Plan</div>
                <?php endif; ?>
                
                <div class="plan-header">
                    <h3 class="plan-name"><?php echo $planName; ?></h3>
                    <?php if ($planDescription): ?>
                        <p class="plan-description"><?php echo $planDescription; ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="plan-price">
                    <span class="price-amount"><?php echo $planCurrency; ?> <?php echo $planPrice; ?></span>
                    <span class="price-period">/ <?php echo $billingPeriod; ?> month<?php echo $billingPeriod > 1 ? 's' : ''; ?></span>
                </div>
                
                <?php if (!empty($planFeatures)): ?>
                    <div class="plan-features">
                        <ul>
                            <?php foreach ($planFeatures as $feature): ?>
                                <li>✓ <?php echo htmlspecialchars($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="plan-actions">
                    <?php if ($isCurrentPlan): ?>
                        <a href="/app/subscription/renew.php" class="btn btn-primary btn-block">
                            Renew Plan
                        </a>
                        <a href="/app/subscription.php" class="btn btn-outline btn-block">
                            View Details
                        </a>
                    <?php elseif ($activeSubscription): ?>
                        <a href="/public/select-plan.php?plan=<?php echo $planSlug; ?>" class="btn btn-primary btn-block">
                            Upgrade to This Plan
                        </a>
                    <?php else: ?>
                        <a href="/public/select-plan.php?plan=<?php echo $planSlug; ?>" class="btn btn-primary btn-block">
                            Get Started
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
.plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.plan-card {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 2rem;
    position: relative;
    transition: all 0.3s ease;
}

.plan-card:hover {
    border-color: var(--primary-color, #007bff);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.plan-card.current-plan {
    border-color: var(--success-color, #28a745);
    background: #f8fff9;
}

.plan-badge {
    position: absolute;
    top: -12px;
    right: 20px;
    background: var(--success-color, #28a745);
    color: white;
    padding: 0.25rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
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
}

.price-amount {
    font-size: 2rem;
    font-weight: 700;
    color: #333;
}

.price-period {
    color: #666;
    font-size: 0.9rem;
}

.plan-features {
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

.plan-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.btn-block {
    width: 100%;
    text-align: center;
}

@media (max-width: 768px) {
    .plans-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
// Include customer portal footer
require_once __DIR__ . '/../templates/customer-footer.php';
?>
