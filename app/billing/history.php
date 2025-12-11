<?php
/**
 * Billing History Page
 * View all orders and transactions
 */

// Load Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Include required files
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Services\OrderService;
use Karyalay\Models\Plan;

// Guard customer portal - requires authentication
$user = guardCustomerPortal();
$userId = $user['id'];

// Fetch all orders for the customer
$orderService = new OrderService();
$orders = $orderService->getOrdersByCustomer($userId);

// Fetch plan details for each order
$planModel = new Plan();
$ordersWithPlans = [];

foreach ($orders as $order) {
    $plan = $planModel->findById($order['plan_id']);
    $ordersWithPlans[] = [
        'order' => $order,
        'plan' => $plan
    ];
}

// Calculate statistics
$successfulOrders = array_filter($ordersWithPlans, function($item) {
    return strtoupper($item['order']['status']) === 'SUCCESS';
});
$totalSpent = array_reduce($successfulOrders, function($sum, $item) {
    return $sum + $item['order']['amount'];
}, 0);

// Use centralized localisation for currency
// The format_price() and get_currency_symbol() functions are available from template_helpers.php

// Set page variables
$page_title = 'Billing History';

// Include customer portal header
require_once __DIR__ . '/../../templates/customer-header.php';
?>

<div class="section-header">
    <h2 class="section-title">Billing History</h2>
</div>

<?php if (empty($ordersWithPlans)): ?>
    <div class="info-box">
        <div class="info-box-content">
            <p style="text-align: center; padding: 2rem 0;">

                <strong>No orders yet</strong><br>
                <span style="color: #666; font-size: 0.9rem;">Your billing history will appear here once you make a purchase.</span>
            </p>
            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="<?php echo get_app_base_url(); ?>/app/plans.php" class="btn btn-primary">View Plans</a>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Orders Table -->
    <div class="info-box">
        <h3 class="info-box-title">Payment History</h3>
        <div class="info-box-content" style="padding: 0;">
            <table class="billing-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>Payment ID</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ordersWithPlans as $item): ?>
                        <?php 
                        $order = $item['order'];
                        $plan = $item['plan'];
                        
                        $orderDate = date('M d, Y', strtotime($order['created_at']));
                        $amount = format_price($order['amount']);
                        $planName = $plan ? htmlspecialchars($plan['name']) : 'Unknown Plan';
                        $displayOrderId = '#' . strtoupper(substr($order['id'], 0, 8));
                        
                        $statusClass = 'pending';
                        switch (strtoupper($order['status'])) {
                            case 'SUCCESS':
                                $statusClass = 'active';
                                break;
                            case 'PENDING':
                                $statusClass = 'pending';
                                break;
                            case 'FAILED':
                            case 'CANCELLED':
                                $statusClass = 'expired';
                                break;
                        }
                        ?>
                        <tr>
                            <td>
                                <code class="order-id-code"><?php echo htmlspecialchars($displayOrderId); ?></code>
                            </td>
                            <td><?php echo htmlspecialchars($orderDate); ?></td>
                            <td><?php echo $planName; ?></td>
                            <td><strong><?php echo htmlspecialchars($amount); ?></strong></td>
                            <td>
                                <?php if (!empty($order['pg_payment_id'])): ?>
                                    <code class="order-id-code" style="font-size: 0.75rem;">
                                        <?php echo htmlspecialchars(substr($order['pg_payment_id'], 0, 20)); ?>
                                    </code>
                                <?php else: ?>
                                    <span style="color: #9ca3af;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="subscription-status <?php echo $statusClass; ?>">
                                    <?php echo ucfirst(strtolower($order['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (strtoupper($order['status']) === 'SUCCESS'): ?>
                                    <a href="<?php echo get_app_base_url(); ?>/app/invoice.php?order_id=<?php echo urlencode($order['id']); ?>" class="btn btn-sm btn-outline" target="_blank">
                                        📄 View Invoice
                                    </a>
                                <?php else: ?>
                                    <span style="color: #9ca3af;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="quick-actions">
        <a href="<?php echo get_app_base_url(); ?>/app/plans.php" class="btn btn-primary">View Plans</a>
        <a href="<?php echo get_app_base_url(); ?>/app/subscription.php" class="btn btn-outline">Manage Subscription</a>
    </div>
<?php endif; ?>

<style>
.billing-table {
    width: 100%;
    border-collapse: collapse;
}

.billing-table th {
    background: #f9fafb;
    padding: 0.875rem 1rem;
    text-align: left;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #6b7280;
    border-bottom: 1px solid #e5e7eb;
}

.billing-table td {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
    font-size: 0.9375rem;
    color: #374151;
}

.billing-table tbody tr:hover {
    background: #f9fafb;
}

.billing-table tbody tr:last-child td {
    border-bottom: none;
}

.order-id-code {
    font-family: 'SF Mono', 'Monaco', 'Inconsolata', monospace;
    font-size: 0.8125rem;
    background: #f3f4f6;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    color: #374151;
}

.btn-sm {
    font-size: 0.8125rem;
    padding: 0.375rem 0.75rem;
}

.btn-outline {
    background: white;
    border: 1px solid #d1d5db;
    color: #374151;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.btn-outline:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

@media (max-width: 768px) {
    .billing-table {
        display: block;
        overflow-x: auto;
    }
    
    .billing-table th,
    .billing-table td {
        white-space: nowrap;
    }
}
</style>

<?php
// Include customer portal footer
require_once __DIR__ . '/../../templates/customer-footer.php';
?>
