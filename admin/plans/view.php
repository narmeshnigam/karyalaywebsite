<?php
/**
 * Admin View Plan Page
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Services\PlanService;

startSecureSession();
require_admin();
require_permission('plans.view_details');

$planService = new PlanService();
$planId = $_GET['id'] ?? '';

if (empty($planId)) {
    $_SESSION['admin_error'] = 'Plan ID is required.';
    header('Location: ' . get_app_base_url() . '/admin/plans.php');
    exit;
}

$plan = $planService->read($planId);

if (!$plan) {
    $_SESSION['admin_error'] = 'Plan not found.';
    header('Location: ' . get_app_base_url() . '/admin/plans.php');
    exit;
}

$features = is_array($plan['features']) ? $plan['features'] : [];
$hasDiscount = $planService->hasDiscount($plan);
$discountPercentage = $planService->getDiscountPercentage($plan);
$effectivePrice = $planService->getEffectivePrice($plan);

// Get subscription stats
$db = \Karyalay\Database\Connection::getInstance();
$stmt = $db->prepare("SELECT COUNT(*) FROM subscriptions WHERE plan_id = :plan_id");
$stmt->execute([':plan_id' => $planId]);
$subscriptionCount = $stmt->fetchColumn();

$activeStmt = $db->prepare("SELECT COUNT(*) FROM subscriptions WHERE plan_id = :plan_id AND status = 'ACTIVE'");
$activeStmt->execute([':plan_id' => $planId]);
$activeSubscriptionCount = $activeStmt->fetchColumn();

include_admin_header('View Plan');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title"><?php echo htmlspecialchars($plan['name']); ?></h1>
        <p class="admin-page-description">Plan Details</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/plans.php" class="btn btn-secondary">← Back to Plans</a>
        <a href="<?php echo get_app_base_url(); ?>/admin/plans/edit.php?id=<?php echo urlencode($planId); ?>" class="btn btn-primary">Edit Plan</a>
    </div>
</div>

<div class="plan-view-grid">
    <!-- Basic Information -->
    <div class="admin-card">
        <div class="card-header"><h2 class="card-title">Basic Information</h2></div>
        <div class="card-body">
            <div class="detail-row">
                <span class="detail-label">Plan Name</span>
                <span class="detail-value"><?php echo htmlspecialchars($plan['name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Slug</span>
                <span class="detail-value"><code><?php echo htmlspecialchars($plan['slug']); ?></code></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value"><?php echo get_status_badge($plan['status']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Description</span>
                <span class="detail-value"><?php echo !empty($plan['description']) ? htmlspecialchars($plan['description']) : '<em>No description</em>'; ?></span>
            </div>
        </div>
    </div>

    <!-- Plan Limits -->
    <div class="admin-card">
        <div class="card-header"><h2 class="card-title">Plan Limits</h2></div>
        <div class="card-body">
            <div class="detail-row">
                <span class="detail-label">Number of Users</span>
                <span class="detail-value"><?php echo !empty($plan['number_of_users']) ? $plan['number_of_users'] . ' users' : '<em>Unlimited</em>'; ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Storage Allowed</span>
                <span class="detail-value"><?php echo !empty($plan['allowed_storage_gb']) ? $plan['allowed_storage_gb'] . ' GB' : '<em>Unlimited</em>'; ?></span>
            </div>
        </div>
    </div>

    <!-- Pricing -->
    <div class="admin-card">
        <div class="card-header"><h2 class="card-title">Pricing</h2></div>
        <div class="card-body">
            <div class="price-display">
                <?php if ($hasDiscount): ?>
                    <div class="price-discount-badge"><?php echo $discountPercentage; ?>% OFF</div>
                    <span class="price-mrp"><?php echo format_price($plan['mrp']); ?></span>
                <?php endif; ?>
                <div class="price-main">
                    <span class="price-currency"><?php echo get_currency_symbol(); ?></span>
                    <span class="price-amount"><?php echo number_format($effectivePrice, 2); ?></span>
                </div>
                <span class="price-period">
                    / <?php 
                    $months = $plan['billing_period_months'];
                    echo $months == 1 ? 'month' : ($months == 3 ? 'quarter' : ($months == 12 ? 'year' : $months . ' months'));
                    ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">MRP</span>
                <span class="detail-value"><?php echo !empty($plan['mrp']) ? format_price($plan['mrp']) : '<em>Not set</em>'; ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Discounted Price</span>
                <span class="detail-value"><?php echo !empty($plan['discounted_price']) ? format_price($plan['discounted_price']) : '<em>Not set</em>'; ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Billing Period</span>
                <span class="detail-value"><?php echo $plan['billing_period_months']; ?> month<?php echo $plan['billing_period_months'] != 1 ? 's' : ''; ?></span>
            </div>
        </div>
    </div>

    <!-- Subscription Stats -->
    <div class="admin-card">
        <div class="card-header"><h2 class="card-title">Subscription Stats</h2></div>
        <div class="card-body">
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-value"><?php echo $subscriptionCount; ?></span>
                    <span class="stat-label">Total Subscriptions</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo $activeSubscriptionCount; ?></span>
                    <span class="stat-label">Active Subscriptions</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Features (Rich Text) -->
    <?php if (!empty($plan['features_html'])): ?>
    <div class="admin-card features-card">
        <div class="card-header"><h2 class="card-title">Features (Rich Text)</h2></div>
        <div class="card-body rich-content">
            <?php echo $plan['features_html']; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Timestamps -->
    <div class="admin-card">
        <div class="card-header"><h2 class="card-title">Timestamps</h2></div>
        <div class="card-body">
            <div class="detail-row">
                <span class="detail-label">Created</span>
                <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($plan['created_at'])); ?></span>
            </div>
            <?php if (!empty($plan['updated_at'])): ?>
            <div class="detail-row">
                <span class="detail-label">Last Updated</span>
                <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($plan['updated_at'])); ?></span>
            </div>
            <?php endif; ?>
            <div class="detail-row">
                <span class="detail-label">Plan ID</span>
                <span class="detail-value"><code><?php echo htmlspecialchars($plan['id']); ?></code></span>
            </div>
        </div>
    </div>
</div>

<style>
.admin-page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:var(--spacing-6);gap:var(--spacing-4)}
.admin-page-header-content{flex:1}
.admin-page-title{font-size:var(--font-size-2xl);font-weight:var(--font-weight-bold);color:var(--color-gray-900);margin:0 0 var(--spacing-2) 0}
.admin-page-description{font-size:var(--font-size-base);color:var(--color-gray-600);margin:0}
.admin-page-header-actions{display:flex;gap:var(--spacing-3)}
.plan-view-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:var(--spacing-6)}
.features-card{grid-column:span 2}
.card-header{display:flex;justify-content:space-between;align-items:center;padding:var(--spacing-4) var(--spacing-5);border-bottom:1px solid var(--color-gray-200)}
.card-title{font-size:var(--font-size-lg);font-weight:var(--font-weight-semibold);color:var(--color-gray-900);margin:0}
.card-body{padding:var(--spacing-5)}
.detail-row{display:flex;justify-content:space-between;align-items:flex-start;padding:var(--spacing-3) 0;border-bottom:1px solid var(--color-gray-100)}
.detail-row:last-child{border-bottom:none}
.detail-label{font-size:var(--font-size-sm);font-weight:var(--font-weight-medium);color:var(--color-gray-600)}
.detail-value{font-size:var(--font-size-base);color:var(--color-gray-900);text-align:right}
.detail-value code{background:var(--color-gray-100);padding:2px 6px;border-radius:var(--radius-sm);font-family:monospace;font-size:var(--font-size-sm)}
.text-muted{color:var(--color-gray-500);font-style:italic}
.price-display{text-align:center;padding:var(--spacing-6) 0;border-bottom:1px solid var(--color-gray-100);margin-bottom:var(--spacing-4);position:relative}
.price-discount-badge{position:absolute;top:0;right:0;background:linear-gradient(135deg,#10b981,#059669);color:white;padding:4px 12px;border-radius:var(--radius-full);font-size:var(--font-size-sm);font-weight:var(--font-weight-semibold)}
.price-mrp{display:block;font-size:var(--font-size-lg);color:var(--color-gray-500);text-decoration:line-through;margin-bottom:var(--spacing-2)}
.price-main{display:flex;align-items:flex-start;justify-content:center;gap:var(--spacing-1)}
.price-currency{font-size:var(--font-size-lg);font-weight:var(--font-weight-medium);color:var(--color-gray-600);margin-top:var(--spacing-2)}
.price-amount{font-size:3rem;font-weight:var(--font-weight-bold);color:var(--color-gray-900);line-height:1}
.price-period{font-size:var(--font-size-base);color:var(--color-gray-600);display:block;margin-top:var(--spacing-2)}
.stats-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:var(--spacing-4)}
.stat-item{text-align:center;padding:var(--spacing-4);background:var(--color-gray-50);border-radius:var(--radius-md)}
.stat-value{display:block;font-size:var(--font-size-2xl);font-weight:var(--font-weight-bold);color:var(--color-primary)}
.stat-label{display:block;font-size:var(--font-size-sm);color:var(--color-gray-600);margin-top:var(--spacing-1)}
.feature-count{font-size:var(--font-size-sm);color:var(--color-gray-500);background:var(--color-gray-100);padding:var(--spacing-1) var(--spacing-2);border-radius:var(--radius-full)}
.features-list{list-style:none;padding:0;margin:0;display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:var(--spacing-3)}
.feature-item{display:flex;align-items:center;gap:var(--spacing-2);padding:var(--spacing-3);background:var(--color-gray-50);border-radius:var(--radius-md);font-size:var(--font-size-base);color:var(--color-gray-800)}
.feature-icon{color:#10b981;font-weight:var(--font-weight-bold)}
.rich-content{line-height:1.6}
.rich-content ul,.rich-content ol{margin:var(--spacing-4) 0;padding-left:var(--spacing-6)}
.rich-content li{margin-bottom:var(--spacing-2)}
@media(max-width:768px){.admin-page-header{flex-direction:column}.plan-view-grid{grid-template-columns:1fr}.features-card{grid-column:span 1}.stats-grid{grid-template-columns:1fr}.features-list{grid-template-columns:1fr}}
</style>

<?php include_admin_footer(); ?>
