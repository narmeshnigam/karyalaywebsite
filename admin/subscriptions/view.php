<?php
/**
 * Admin Subscription Detail View Page
 * Displays comprehensive subscription information including customer details,
 * renewal dates, assigned ports, and port allocation history
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

use Karyalay\Models\Subscription;
use Karyalay\Models\Plan;
use Karyalay\Models\User;
use Karyalay\Models\Order;
use Karyalay\Models\Port;
use Karyalay\Models\PortAllocationLog;

// Start secure session
startSecureSession();

// Require admin authentication and subscriptions.view_details permission
require_admin();
require_permission('subscriptions.view_details');

// Get subscription ID from URL
$subscription_id = $_GET['id'] ?? '';

if (empty($subscription_id)) {
    header('Location: ' . get_app_base_url() . '/admin/subscriptions.php');
    exit;
}

// Get database connection
$db = \Karyalay\Database\Connection::getInstance();

// Initialize models
$subscriptionModel = new Subscription();
$planModel = new Plan();
$userModel = new User();
$orderModel = new Order();
$portModel = new Port();
$portAllocationLogModel = new PortAllocationLog();

// Fetch subscription with all related data
try {
    $sql = "SELECT s.*, 
            p.name as plan_name,
            p.net_price as plan_price,
            p.mrp as plan_mrp,
            p.discounted_price as plan_discounted_price,
            p.currency as plan_currency,
            p.billing_period_months,
            p.number_of_users as plan_users,
            p.allowed_storage_gb as plan_storage,
            p.features_html as plan_features,
            u.name as customer_name,
            u.email as customer_email,
            u.phone as customer_phone,
            u.created_at as customer_joined,
            port.instance_url as port_url,
            port.db_name as port_db_name,
            port.status as port_status,
            port.created_at as port_created,
            o.amount as order_amount,
            o.status as order_status,
            o.payment_method,
            o.created_at as order_date
            FROM subscriptions s
            LEFT JOIN plans p ON s.plan_id = p.id
            LEFT JOIN users u ON s.customer_id = u.id
            LEFT JOIN ports port ON s.assigned_port_id = port.id
            LEFT JOIN orders o ON s.order_id = o.id
            WHERE s.id = :id";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $subscription_id]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$subscription) {
        header('Location: ' . get_app_base_url() . '/admin/subscriptions.php?error=not_found');
        exit;
    }
} catch (PDOException $e) {
    error_log("Subscription view error: " . $e->getMessage());
    header('Location: ' . get_app_base_url() . '/admin/subscriptions.php?error=database');
    exit;
}

// Get port allocation history for this subscription
try {
    $port_history_sql = "SELECT pal.*, 
                        p.instance_url,
                        p.db_name,
                        p.status as port_status,
                        admin.name as admin_name
                        FROM port_allocation_logs pal
                        LEFT JOIN ports p ON pal.port_id = p.id
                        LEFT JOIN users admin ON pal.performed_by = admin.id
                        WHERE pal.subscription_id = :subscription_id
                        ORDER BY pal.timestamp DESC";
    
    $stmt = $db->prepare($port_history_sql);
    $stmt->execute([':subscription_id' => $subscription_id]);
    $port_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Port history error: " . $e->getMessage());
    $port_history = [];
}


// Get all orders for this customer to show renewal history
try {
    $orders_sql = "SELECT o.*, s.id as subscription_id, s.start_date, s.end_date
                   FROM orders o
                   LEFT JOIN subscriptions s ON o.id = s.order_id
                   WHERE o.customer_id = :customer_id
                   ORDER BY o.created_at DESC";
    
    $stmt = $db->prepare($orders_sql);
    $stmt->execute([':customer_id' => $subscription['customer_id']]);
    $customer_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Customer orders error: " . $e->getMessage());
    $customer_orders = [];
}

// Calculate subscription metrics
$days_remaining = 0;
$is_expired = false;
$is_expiring_soon = false;

if ($subscription['end_date']) {
    $end_date = strtotime($subscription['end_date']);
    $now = time();
    $days_remaining = ceil(($end_date - $now) / (24 * 60 * 60));
    $is_expired = $end_date < $now;
    $is_expiring_soon = !$is_expired && $days_remaining <= 7;
}

// Include admin header
include_admin_header('Subscription Details');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <div class="admin-breadcrumb">
            <a href="<?php echo get_app_base_url(); ?>/admin/subscriptions.php" class="breadcrumb-link">Subscriptions</a>
            <span class="breadcrumb-separator">›</span>
            <span class="breadcrumb-current"><?php echo htmlspecialchars(substr($subscription['id'], 0, 8)); ?></span>
        </div>
        <h1 class="admin-page-title">Subscription Details</h1>
        <p class="admin-page-description">Complete subscription information and history</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/subscriptions.php" class="btn btn-secondary">
            ← Back to Subscriptions
        </a>
    </div>
</div>

<!-- Subscription Status Card -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Subscription Status</h2>
        <div class="admin-card-actions">
            <?php echo get_status_badge($subscription['status']); ?>
        </div>
    </div>
    <div class="admin-card-content">
        <div class="subscription-status-grid">
            <div class="status-item">
                <div class="status-label">Subscription ID</div>
                <div class="status-value">
                    <code class="code-inline"><?php echo htmlspecialchars($subscription['id']); ?></code>
                </div>
            </div>
            <div class="status-item">
                <div class="status-label">Start Date</div>
                <div class="status-value"><?php echo date('M j, Y', strtotime($subscription['start_date'])); ?></div>
            </div>
            <div class="status-item">
                <div class="status-label">End Date</div>
                <div class="status-value <?php echo $is_expired ? 'text-danger' : ($is_expiring_soon ? 'text-warning' : ''); ?>">
                    <?php echo date('M j, Y', strtotime($subscription['end_date'])); ?>
                    <?php if (!$is_expired): ?>
                        <div class="status-sublabel">
                            <?php echo $days_remaining; ?> days remaining
                        </div>
                    <?php else: ?>
                        <div class="status-sublabel text-danger">
                            Expired <?php echo abs($days_remaining); ?> days ago
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="status-item">
                <div class="status-label">Created</div>
                <div class="status-value"><?php echo date('M j, Y g:i A', strtotime($subscription['created_at'])); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Customer Information -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Customer Information</h2>
        <div class="admin-card-actions">
            <a href="<?php echo get_app_base_url(); ?>/admin/customers/view.php?id=<?php echo urlencode($subscription['customer_id']); ?>" class="btn btn-text">
                View Customer Profile →
            </a>
        </div>
    </div>
    <div class="admin-card-content">
        <div class="customer-info-grid">
            <div class="info-item">
                <div class="info-label">Name</div>
                <div class="info-value"><?php echo htmlspecialchars($subscription['customer_name'] ?? 'N/A'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Email</div>
                <div class="info-value">
                    <?php if ($subscription['customer_email']): ?>
                    <a href="mailto:<?php echo htmlspecialchars($subscription['customer_email']); ?>" class="link">
                        <?php echo htmlspecialchars($subscription['customer_email']); ?>
                    </a>
                    <?php else: ?>
                    <span class="text-muted">N/A</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($subscription['customer_phone']): ?>
            <div class="info-item">
                <div class="info-label">Phone</div>
                <div class="info-value">
                    <a href="tel:<?php echo htmlspecialchars($subscription['customer_phone']); ?>" class="link">
                        <?php echo htmlspecialchars($subscription['customer_phone']); ?>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            <div class="info-item">
                <div class="info-label">Customer Since</div>
                <div class="info-value"><?php echo $subscription['customer_joined'] ? date('M j, Y', strtotime($subscription['customer_joined'])) : 'N/A'; ?></div>
            </div>
        </div>
    </div>
</div>


<!-- Plan Information -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Plan Details</h2>
        <div class="admin-card-actions">
            <a href="<?php echo get_app_base_url(); ?>/admin/plans/view.php?id=<?php echo urlencode($subscription['plan_id']); ?>" class="btn btn-text">
                View Plan Details →
            </a>
        </div>
    </div>
    <div class="admin-card-content">
        <div class="plan-info-grid">
            <div class="info-item">
                <div class="info-label">Plan Name</div>
                <div class="info-value"><?php echo htmlspecialchars($subscription['plan_name'] ?? 'N/A'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Billing Period</div>
                <div class="info-value"><?php echo $subscription['billing_period_months'] ?? 'N/A'; ?> month<?php echo ($subscription['billing_period_months'] ?? 0) != 1 ? 's' : ''; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Price</div>
                <div class="info-value">
                    <?php echo $subscription['plan_price'] ? format_price($subscription['plan_price']) : 'N/A'; ?>
                    <?php if ($subscription['plan_mrp'] && $subscription['plan_mrp'] != $subscription['plan_price']): ?>
                        <span class="price-original"><?php echo format_price($subscription['plan_mrp']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Users Allowed</div>
                <div class="info-value"><?php echo $subscription['plan_users'] ? number_format($subscription['plan_users']) : 'N/A'; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Storage</div>
                <div class="info-value"><?php echo $subscription['plan_storage'] ? number_format($subscription['plan_storage'], 1) . ' GB' : 'N/A'; ?></div>
            </div>
        </div>
        
        <?php if ($subscription['plan_features']): ?>
        <div class="plan-features">
            <div class="info-label">Features</div>
            <div class="features-content">
                <?php echo $subscription['plan_features']; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Port Assignment -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Port Assignment</h2>
        <?php if ($subscription['assigned_port_id']): ?>
        <div class="admin-card-actions">
            <a href="<?php echo get_app_base_url(); ?>/admin/ports/view.php?id=<?php echo urlencode($subscription['assigned_port_id']); ?>" class="btn btn-text">
                View Port Details →
            </a>
        </div>
        <?php endif; ?>
    </div>
    <div class="admin-card-content">
        <?php if ($subscription['assigned_port_id']): ?>
        <div class="port-info-grid">
            <div class="info-item">
                <div class="info-label">Instance URL</div>
                <div class="info-value">
                    <?php if ($subscription['port_url']): ?>
                    <a href="<?php echo htmlspecialchars($subscription['port_url']); ?>" target="_blank" class="link">
                        <?php echo htmlspecialchars($subscription['port_url']); ?> ↗
                    </a>
                    <?php else: ?>
                    <span class="text-muted">Not configured</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Database Name</div>
                <div class="info-value">
                    <?php if ($subscription['port_db_name']): ?>
                    <code class="code-inline"><?php echo htmlspecialchars($subscription['port_db_name']); ?></code>
                    <?php else: ?>
                    <span class="text-muted">Not configured</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Port Status</div>
                <div class="info-value"><?php echo get_status_badge($subscription['port_status']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Port Created</div>
                <div class="info-value"><?php echo $subscription['port_created'] ? date('M j, Y g:i A', strtotime($subscription['port_created'])) : 'N/A'; ?></div>
            </div>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">🔌</div>
            <div class="empty-state-title">No Port Assigned</div>
            <div class="empty-state-description">This subscription does not have a port assigned yet.</div>
        </div>
        <?php endif; ?>
    </div>
</div>


<!-- Port Allocation History -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Port Allocation History</h2>
        <div class="admin-card-subtitle">All ports ever allocated to this subscription</div>
    </div>
    <div class="admin-card-content">
        <?php if (!empty($port_history)): ?>
        <div class="history-timeline">
            <?php foreach ($port_history as $log): ?>
            <div class="timeline-item">
                <div class="timeline-marker <?php echo strtolower($log['action']); ?>"></div>
                <div class="timeline-content">
                    <div class="timeline-header">
                        <div class="timeline-action">
                            <?php 
                            $action_labels = [
                                'ASSIGNED' => 'Port Assigned',
                                'ALLOCATED' => 'Port Allocated',
                                'DEALLOCATED' => 'Port Deallocated',
                                'REASSIGNED' => 'Port Reassigned',
                                'RELEASED' => 'Port Released',
                                'EXPIRED' => 'Port Expired',
                                'CANCELLED' => 'Port Cancelled'
                            ];
                            echo $action_labels[$log['action']] ?? $log['action'];
                            ?>
                        </div>
                        <div class="timeline-date">
                            <?php echo date('M j, Y g:i A', strtotime($log['timestamp'])); ?>
                        </div>
                    </div>
                    <div class="timeline-details">
                        <?php if ($log['instance_url']): ?>
                        <div class="timeline-detail">
                            <strong>Instance:</strong> 
                            <a href="<?php echo htmlspecialchars($log['instance_url']); ?>" target="_blank" class="link">
                                <?php echo htmlspecialchars($log['instance_url']); ?> ↗
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if ($log['db_name']): ?>
                        <div class="timeline-detail">
                            <strong>Database:</strong> 
                            <code class="code-inline"><?php echo htmlspecialchars($log['db_name']); ?></code>
                        </div>
                        <?php endif; ?>
                        <?php if ($log['admin_name']): ?>
                        <div class="timeline-detail">
                            <strong>Performed by:</strong> <?php echo htmlspecialchars($log['admin_name']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">📋</div>
            <div class="empty-state-title">No Port History</div>
            <div class="empty-state-description">No port allocation history found for this subscription.</div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Order Information -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Order Information</h2>
        <div class="admin-card-actions">
            <a href="<?php echo get_app_base_url(); ?>/admin/orders/view.php?id=<?php echo urlencode($subscription['order_id']); ?>" class="btn btn-text">
                View Order Details →
            </a>
        </div>
    </div>
    <div class="admin-card-content">
        <div class="order-info-grid">
            <div class="info-item">
                <div class="info-label">Order ID</div>
                <div class="info-value">
                    <code class="code-inline"><?php echo htmlspecialchars($subscription['order_id']); ?></code>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Amount</div>
                <div class="info-value"><?php echo $subscription['order_amount'] ? format_price($subscription['order_amount']) : 'N/A'; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Payment Method</div>
                <div class="info-value"><?php echo htmlspecialchars($subscription['payment_method'] ?: 'Not specified'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Order Status</div>
                <div class="info-value"><?php echo get_status_badge($subscription['order_status']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Order Date</div>
                <div class="info-value"><?php echo $subscription['order_date'] ? date('M j, Y g:i A', strtotime($subscription['order_date'])) : 'N/A'; ?></div>
            </div>
        </div>
    </div>
</div>


<!-- Customer Order History -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Customer Order History</h2>
        <div class="admin-card-subtitle">All orders from this customer</div>
    </div>
    <div class="admin-card-content">
        <?php if (!empty($customer_orders)): ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Subscription Period</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customer_orders as $order): ?>
                    <tr <?php echo $order['subscription_id'] === $subscription_id ? 'class="current-subscription"' : ''; ?>>
                        <td>
                            <code class="code-inline"><?php echo htmlspecialchars(substr($order['id'], 0, 8)); ?></code>
                            <?php if ($order['subscription_id'] === $subscription_id): ?>
                                <span class="current-badge">Current</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo format_price($order['amount']); ?></td>
                        <td><?php echo get_status_badge($order['status']); ?></td>
                        <td>
                            <?php if ($order['start_date'] && $order['end_date']): ?>
                                <?php echo date('M j, Y', strtotime($order['start_date'])); ?> - 
                                <?php echo date('M j, Y', strtotime($order['end_date'])); ?>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                        <td>
                            <a href="<?php echo get_app_base_url(); ?>/admin/orders/view.php?id=<?php echo urlencode($order['id']); ?>" class="btn btn-text btn-sm">
                                View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <div class="empty-state-title">No Orders Found</div>
            <div class="empty-state-description">No order history found for this customer.</div>
        </div>
        <?php endif; ?>
    </div>
</div>


<style>
/* Page Header */
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

.admin-page-header-actions {
    display: flex;
    gap: var(--spacing-3);
}

.admin-breadcrumb {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    margin-bottom: var(--spacing-2);
    font-size: var(--font-size-sm);
}

.breadcrumb-link {
    color: var(--color-primary);
    text-decoration: none;
}

.breadcrumb-link:hover {
    text-decoration: underline;
}

.breadcrumb-separator {
    color: var(--color-gray-400);
}

.breadcrumb-current {
    color: var(--color-gray-600);
    font-weight: var(--font-weight-semibold);
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

/* Card Layouts */
.admin-card {
    background: white;
    border: 1px solid var(--color-gray-200);
    border-radius: var(--radius-lg);
    margin-bottom: var(--spacing-6);
}

.admin-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: var(--spacing-4);
    border-bottom: 1px solid var(--color-gray-200);
}

.admin-card-title {
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin: 0;
}

.admin-card-subtitle {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin-top: var(--spacing-1);
}

.admin-card-actions {
    display: flex;
    gap: var(--spacing-2);
}

.admin-card-content {
    padding: var(--spacing-4);
}

/* Grid Layouts */
.subscription-status-grid,
.customer-info-grid,
.plan-info-grid,
.port-info-grid,
.order-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-4);
}

.status-item,
.info-item {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-1);
}

.status-label,
.info-label {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
}

.status-value,
.info-value {
    font-size: var(--font-size-base);
    color: var(--color-gray-900);
}

.status-sublabel {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin-top: var(--spacing-1);
}

/* Plan Features */
.plan-features {
    margin-top: var(--spacing-4);
    padding-top: var(--spacing-4);
    border-top: 1px solid var(--color-gray-200);
}

.features-content {
    margin-top: var(--spacing-2);
}

.features-content ul {
    margin: 0;
    padding-left: var(--spacing-4);
}

.features-content li {
    margin-bottom: var(--spacing-1);
}

/* Timeline */
.history-timeline {
    position: relative;
}

.timeline-item {
    display: flex;
    gap: var(--spacing-3);
    margin-bottom: var(--spacing-4);
    position: relative;
}

.timeline-item:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 8px;
    top: 24px;
    bottom: -16px;
    width: 2px;
    background-color: var(--color-gray-200);
}

.timeline-marker {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    flex-shrink: 0;
    margin-top: 4px;
    border: 2px solid white;
    box-shadow: 0 0 0 2px var(--color-gray-300);
    background-color: var(--color-gray-400);
}

.timeline-marker.assigned,
.timeline-marker.allocated {
    background-color: var(--color-green-500);
    box-shadow: 0 0 0 2px var(--color-green-500);
}

.timeline-marker.deallocated,
.timeline-marker.released {
    background-color: var(--color-red-500);
    box-shadow: 0 0 0 2px var(--color-red-500);
}

.timeline-marker.reassigned {
    background-color: var(--color-blue-500);
    box-shadow: 0 0 0 2px var(--color-blue-500);
}

.timeline-marker.expired {
    background-color: var(--color-orange-500);
    box-shadow: 0 0 0 2px var(--color-orange-500);
}

.timeline-marker.cancelled {
    background-color: var(--color-gray-500);
    box-shadow: 0 0 0 2px var(--color-gray-500);
}

.timeline-content {
    flex: 1;
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-2);
}

.timeline-action {
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
}

.timeline-date {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
}

.timeline-details {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-1);
}

.timeline-detail {
    font-size: var(--font-size-sm);
    color: var(--color-gray-700);
}
</style>

<style>
/* Empty States */
.empty-state {
    text-align: center;
    padding: var(--spacing-8) var(--spacing-4);
}

.empty-state-icon {
    font-size: 3rem;
    margin-bottom: var(--spacing-3);
}

.empty-state-title {
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin-bottom: var(--spacing-2);
}

.empty-state-description {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
}

/* Table Styles */
.admin-table-container {
    overflow-x: auto;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table th,
.admin-table td {
    padding: var(--spacing-3);
    text-align: left;
    border-bottom: 1px solid var(--color-gray-200);
}

.admin-table th {
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
    background-color: var(--color-gray-50);
}

.current-subscription {
    background-color: var(--color-blue-50);
}

.current-badge {
    display: inline-block;
    background-color: var(--color-blue-100);
    color: var(--color-blue-800);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-semibold);
    padding: 2px 6px;
    border-radius: var(--radius-sm);
    margin-left: var(--spacing-2);
}

/* Utility Classes */
.code-inline {
    background-color: var(--color-gray-100);
    padding: 2px 6px;
    border-radius: var(--radius-sm);
    font-family: 'Courier New', monospace;
    font-size: var(--font-size-sm);
    color: var(--color-gray-800);
}

.link {
    color: var(--color-primary);
    text-decoration: none;
}

.link:hover {
    text-decoration: underline;
}

.text-danger {
    color: var(--color-red-600);
}

.text-warning {
    color: var(--color-orange-600);
}

.text-muted {
    color: var(--color-gray-500);
}

.price-original {
    text-decoration: line-through;
    color: var(--color-gray-500);
    margin-left: var(--spacing-2);
    font-size: var(--font-size-sm);
}

.btn-sm {
    padding: var(--spacing-1) var(--spacing-2);
    font-size: var(--font-size-sm);
}

@media (max-width: 768px) {
    .admin-page-header {
        flex-direction: column;
    }
    
    .subscription-status-grid,
    .customer-info-grid,
    .plan-info-grid,
    .port-info-grid,
    .order-info-grid {
        grid-template-columns: 1fr;
    }
    
    .timeline-header {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--spacing-1);
    }
}
</style>

<?php include_admin_footer(); ?>
