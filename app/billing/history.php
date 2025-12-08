<?php
/**
 * Billing History Page
 * View all orders and transactions
 * 
 * Displays:
 * - All orders with date, plan, amount, status
 * 
 * Requirements: 5.3
 */

// Load Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Include required files
require_once __DIR__ . '/../../includes/auth_helpers.php';

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

// Set page variables
$page_title = 'Billing History';

// Include customer portal header
require_once __DIR__ . '/../../templates/customer-header.php';
?>

<div class="section-header">
    <h2 class="section-title">Billing History</h2>
    <p class="section-description">View all your orders and transactions</p>
</div>

<?php if (empty($ordersWithPlans)): ?>
    <div class="info-box">
        <div class="info-box-content">
            <p style="text-align: center; padding: 2rem 0;">
                <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">📋</span>
                <strong>No orders yet</strong><br>
                <span style="color: #666; font-size: 0.9rem;">Your order history will appear here once you make a purchase.</span>
            </p>
            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="/pricing.php" class="btn btn-primary">View Plans</a>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Plan</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ordersWithPlans as $item): ?>
                    <?php 
                    $order = $item['order'];
                    $plan = $item['plan'];
                    
                    // Format date
                    $orderDate = date('M d, Y', strtotime($order['created_at']));
                    
                    // Format amount
                    $currency = strtoupper($order['currency'] ?? 'USD');
                    $currencySymbol = $currency === 'USD' ? '$' : $currency . ' ';
                    $amount = $currencySymbol . number_format($order['amount'], 2);
                    
                    // Determine status badge class
                    $statusClass = 'badge-secondary';
                    switch (strtoupper($order['status'])) {
                        case 'SUCCESS':
                            $statusClass = 'badge-success';
                            break;
                        case 'PENDING':
                            $statusClass = 'badge-warning';
                            break;
                        case 'FAILED':
                        case 'CANCELLED':
                            $statusClass = 'badge-danger';
                            break;
                    }
                    
                    // Get plan name
                    $planName = $plan ? htmlspecialchars($plan['name']) : 'Unknown Plan';
                    
                    // Format order ID for display (first 8 characters)
                    $displayOrderId = '#' . strtoupper(substr($order['id'], 0, 8));
                    ?>
                    <tr>
                        <td>
                            <span class="order-id" title="<?php echo htmlspecialchars($order['id']); ?>">
                                <?php echo htmlspecialchars($displayOrderId); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($orderDate); ?></td>
                        <td><?php echo $planName; ?></td>
                        <td><strong><?php echo htmlspecialchars($amount); ?></strong></td>
                        <td>
                            <span class="badge <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars(ucfirst(strtolower($order['status']))); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Order Summary -->
    <div class="info-box" style="margin-top: 2rem;">
        <div class="info-box-content">
            <div class="info-box-row">
                <span class="info-box-label">Total Orders</span>
                <span class="info-box-value"><?php echo count($ordersWithPlans); ?></span>
            </div>
            <?php
            // Calculate statistics
            $successfulOrders = array_filter($ordersWithPlans, function($item) {
                return strtoupper($item['order']['status']) === 'SUCCESS';
            });
            $totalSpent = array_reduce($successfulOrders, function($sum, $item) {
                return $sum + $item['order']['amount'];
            }, 0);
            ?>
            <div class="info-box-row">
                <span class="info-box-label">Successful Orders</span>
                <span class="info-box-value"><?php echo count($successfulOrders); ?></span>
            </div>
            <div class="info-box-row">
                <span class="info-box-label">Total Spent</span>
                <span class="info-box-value">
                    <strong>
                        <?php 
                        $currency = !empty($ordersWithPlans) ? strtoupper($ordersWithPlans[0]['order']['currency'] ?? 'USD') : 'USD';
                        $currencySymbol = $currency === 'USD' ? '$' : $currency . ' ';
                        echo htmlspecialchars($currencySymbol . number_format($totalSpent, 2)); 
                        ?>
                    </strong>
                </span>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
// Include customer portal footer
require_once __DIR__ . '/../../templates/customer-footer.php';
?>
