<?php
/**
 * Admin View Plan Page
 * Displays detailed plan information in read-only format
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Services\PlanService;

// Start secure session
startSecureSession();

// Require admin authentication
require_admin();

// Initialize services
$planService = new PlanService();

// Get plan ID from query parameter
$planId = $_GET['id'] ?? '';

if (empty($planId)) {
    $_SESSION['admin_error'] = 'Plan ID is required.';
    header('Location: ' . get_base_url() . '/admin/plans.php');
    exit;
}

// Fetch plan data
$plan = $planService->read($planId);

if (!$plan) {
    $_SESSION['admin_error'] = 'Plan not found.';
    header('Location: ' . get_base_url() . '/admin/plans.php');
    exit;
}

// Decode features if needed
$features = [];
if (isset($plan['features'])) {
    $features = is_array($plan['features']) ? $plan['features'] : json_decode($plan['features'], true);
}

// Get subscription count for this plan
$db = \Karyalay\Database\Connection::getInstance();
$stmt = $db->prepare("SELECT COUNT(*) FROM subscriptions WHERE plan_id = :plan_id");
$stmt->execute([':plan_id' => $planId]);
$subscriptionCount = $stmt->fetchColumn();

$activeStmt = $db->prepare("SELECT COUNT(*) FROM subscriptions WHERE plan_id = :plan_id AND status = 'ACTIVE'");
$activeStmt->execute([':plan_id' => $planId]);
$activeSubscriptionCount = $activeStmt->fetchColumn();

// Include admin header
include_admin_header('View Plan');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title"><?php echo htmlspecialchars($plan['name']); ?></h1>
        <p class="admin-page-description">Plan Details</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_base_url(); ?>/admin/plans.php" class="btn btn-secondary">
            ← Back to Plans
        </a>
        <a href="<?php echo get_base_url(); ?>/admin/plans/edit.php?id=<?php echo urlencode($planId); ?>" class="btn btn-primary">
            Edit Plan
        </a>
    </div>
</div>

<div class="plan-view-grid">
    <!-- Basic Information Card -->
    <div class="admin-card">
        <div class="card-header">
            <h2 class="card-title">Basic Information</h2>
        </div>
        <div class="card-body">
            <div class="detail-row">
                <span class="detail-label">Plan Name</span>
                <span class="detail-value"><?php echo htmlspecialchars($plan['name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Slug</span>
                <span class="detail-value"><code class="code-inline"><?php echo htmlspecialchars($plan['slug']); ?></code></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value"><?php echo get_status_badge($plan['status']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Description</span>
                <span class="detail-value"><?php echo !empty($plan['description']) ? htmlspecialchars($plan['description']) : '<em class="text-muted">No description</em>'; ?></span>
            </div>
        </div>
    </div>

    <!-- Pricing Card -->
    <div class="admin-card">
        <div class="card-header">
            <h2 class="card-title">Pricing</h2>
        </div>
        <div class="card-body">
            <div class="price-display">
                <span class="price-currency"><?php echo htmlspecialchars($plan['currency'] ?? 'USD'); ?></span>
                <span class="price-amount"><?php echo number_format($plan['price'], 2); ?></span>
                <span class="price-period">
                    / <?php 
                    $months = $plan['billing_period_months'];
                    if ($months == 1) {
                        echo 'month';
                    } elseif ($months == 12) {
                        echo 'year';
                    } else {
                        echo $months . ' months';
                    }
                    ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Billing Period</span>
                <span class="detail-value"><?php echo $plan['billing_period_months']; ?> month<?php echo $plan['billing_period_months'] != 1 ? 's' : ''; ?></span>
            </div>
        </div>
    </div>

    <!-- Subscription Stats Card -->
    <div class="admin-card">
        <div class="card-header">
            <h2 class="card-title">Subscription Stats</h2>
        </div>
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

    <!-- Features Card -->
    <div class="admin-card features-card">
        <div class="card-header">
            <h2 class="card-title">Features</h2>
            <span class="feature-count"><?php echo count($features); ?> feature<?php echo count($features) != 1 ? 's' : ''; ?></span>
        </div>
        <div class="card-body">
            <?php if (!empty($features)): ?>
                <ul class="features-list">
                    <?php foreach ($features as $feature): ?>
                        <li class="feature-item">
                            <span class="feature-icon">✓</span>
                            <?php echo htmlspecialchars($feature); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted">No features defined for this plan.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Timestamps Card -->
    <div class="admin-card">
        <div class="card-header">
            <h2 class="card-title">Timestamps</h2>
        </div>
        <div class="card-body">
            <div class="detail-row">
                <span class="detail-label">Created</span>
                <span class="detail-value">
                    <?php echo date('M j, Y \a\t g:i A', strtotime($plan['created_at'])); ?>
                    <span class="text-muted">(<?php echo get_relative_time($plan['created_at']); ?>)</span>
                </span>
            </div>
            <?php if (!empty($plan['updated_at'])): ?>
            <div class="detail-row">
                <span class="detail-label">Last Updated</span>
                <span class="detail-value">
                    <?php echo date('M j, Y \a\t g:i A', strtotime($plan['updated_at'])); ?>
                    <span class="text-muted">(<?php echo get_relative_time($plan['updated_at']); ?>)</span>
                </span>
            </div>
            <?php endif; ?>
            <div class="detail-row">
                <span class="detail-label">Plan ID</span>
                <span class="detail-value"><code class="code-inline"><?php echo htmlspecialchars($plan['id']); ?></code></span>
            </div>
        </div>
    </div>
</div>

<style>
.admin-page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: var(--spacing-6);
    gap: var(--spacing-4);
}

.admin-page-header-content {
    flex: 1;
}

.admin-page-title {
    font-size: var(--font-size-2xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-2) 0;
}

.admin-page-description {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
    margin: 0;
}

.admin-page-header-actions {
    display: flex;
    gap: var(--spacing-3);
}

.plan-view-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--spacing-6);
}

.features-card {
    grid-column: span 2;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-4) var(--spacing-5);
    border-bottom: 1px solid var(--color-gray-200);
}

.card-title {
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin: 0;
}

.card-body {
    padding: var(--spacing-5);
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: var(--spacing-3) 0;
    border-bottom: 1px solid var(--color-gray-100);
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-medium);
    color: var(--color-gray-600);
    flex-shrink: 0;
    margin-right: var(--spacing-4);
}

.detail-value {
    font-size: var(--font-size-base);
    color: var(--color-gray-900);
    text-align: right;
}

.code-inline {
    background-color: var(--color-gray-100);
    padding: 2px 6px;
    border-radius: var(--radius-sm);
    font-family: 'Courier New', monospace;
    font-size: var(--font-size-sm);
    color: var(--color-gray-800);
}

.text-muted {
    color: var(--color-gray-500);
}

.price-display {
    text-align: center;
    padding: var(--spacing-6) 0;
    border-bottom: 1px solid var(--color-gray-100);
    margin-bottom: var(--spacing-4);
}

.price-currency {
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-medium);
    color: var(--color-gray-600);
    vertical-align: top;
}

.price-amount {
    font-size: 3rem;
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    line-height: 1;
}

.price-period {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--spacing-4);
}

.stat-item {
    text-align: center;
    padding: var(--spacing-4);
    background-color: var(--color-gray-50);
    border-radius: var(--radius-md);
}

.stat-value {
    display: block;
    font-size: var(--font-size-2xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-primary);
}

.stat-label {
    display: block;
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin-top: var(--spacing-1);
}

.feature-count {
    font-size: var(--font-size-sm);
    color: var(--color-gray-500);
    background-color: var(--color-gray-100);
    padding: var(--spacing-1) var(--spacing-2);
    border-radius: var(--radius-full);
}

.features-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: var(--spacing-3);
}

.feature-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-3);
    background-color: var(--color-gray-50);
    border-radius: var(--radius-md);
    font-size: var(--font-size-base);
    color: var(--color-gray-800);
}

.feature-icon {
    color: #10b981;
    font-weight: var(--font-weight-bold);
}

@media (max-width: 768px) {
    .admin-page-header {
        flex-direction: column;
    }
    
    .plan-view-grid {
        grid-template-columns: 1fr;
    }
    
    .features-card {
        grid-column: span 1;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .features-list {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include_admin_footer(); ?>
