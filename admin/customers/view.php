<?php
/**
 * Admin Customer Detail Page
 * Displays customer profile, all subscriptions, all orders, and all tickets
 * Requirements: 10.2
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Models\User;
use Karyalay\Models\Subscription;
use Karyalay\Models\Order;
use Karyalay\Models\Ticket;
use Karyalay\Services\InvoiceService;

// Start secure session
startSecureSession();

// Require admin authentication and customers.view_details permission
require_admin();
require_permission('customers.view_details');

// Get customer ID from query parameter
$customer_id = $_GET['id'] ?? '';

if (empty($customer_id)) {
    header('Location: ' . get_app_base_url() . '/admin/customers.php');
    exit;
}

// Get database connection
$db = \Karyalay\Database\Connection::getInstance();

// Initialize models
$userModel = new User();
$subscriptionModel = new Subscription();
$orderModel = new Order();
$ticketModel = new Ticket();

// Fetch customer details
try {
    $customer = $userModel->findById($customer_id);
    
    if (!$customer || $customer['role'] !== 'CUSTOMER') {
        header('Location: ' . get_app_base_url() . '/admin/customers.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Customer fetch error: " . $e->getMessage());
    header('Location: ' . get_app_base_url() . '/admin/customers.php');
    exit;
}

// Fetch all subscriptions for this customer
try {
    $subscriptions_sql = "SELECT s.*, 
                          p.name as plan_name, 
                          p.mrp as plan_mrp,
                          p.discounted_price as plan_discounted_price,
                          p.currency as plan_currency,
                          p.billing_period_months as plan_billing_period,
                          port.instance_url as port_url,
                          port.status as port_status,
                          port.db_host as port_db_host,
                          port.db_name as port_db_name
                          FROM subscriptions s
                          LEFT JOIN plans p ON s.plan_id = p.id
                          LEFT JOIN ports port ON s.assigned_port_id = port.id
                          WHERE s.customer_id = :customer_id
                          ORDER BY s.created_at DESC";
    $subscriptions_stmt = $db->prepare($subscriptions_sql);
    $subscriptions_stmt->execute([':customer_id' => $customer_id]);
    $subscriptions = $subscriptions_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Subscriptions fetch error: " . $e->getMessage());
    $subscriptions = [];
}

// Fetch all orders for this customer
try {
    $orders_sql = "SELECT o.*, 
                   p.name as plan_name
                   FROM orders o
                   LEFT JOIN plans p ON o.plan_id = p.id
                   WHERE o.customer_id = :customer_id
                   ORDER BY o.created_at DESC";
    $orders_stmt = $db->prepare($orders_sql);
    $orders_stmt->execute([':customer_id' => $customer_id]);
    $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Orders fetch error: " . $e->getMessage());
    $orders = [];
}

// Fetch all tickets for this customer
try {
    $tickets_sql = "SELECT t.*,
                    a.name as assignee_name
                    FROM tickets t
                    LEFT JOIN users a ON t.assigned_to = a.id
                    WHERE t.customer_id = :customer_id
                    ORDER BY t.created_at DESC";
    $tickets_stmt = $db->prepare($tickets_sql);
    $tickets_stmt->execute([':customer_id' => $customer_id]);
    $tickets = $tickets_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Tickets fetch error: " . $e->getMessage());
    $tickets = [];
}

// Calculate statistics
$total_orders = count($orders);
$total_revenue = array_sum(array_column(array_filter($orders, function($o) {
    return $o['status'] === 'SUCCESS';
}), 'amount'));
$active_subscriptions = count(array_filter($subscriptions, function($s) {
    return $s['status'] === 'ACTIVE';
}));
$open_tickets = count(array_filter($tickets, function($t) {
    return in_array($t['status'], ['OPEN', 'IN_PROGRESS', 'WAITING_ON_CUSTOMER']);
}));

// Include admin header
include_admin_header('Customer Details');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <div class="breadcrumb">
            <a href="<?php echo get_app_base_url(); ?>/admin/customers.php" class="breadcrumb-link">Customers</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?php echo htmlspecialchars($customer['name']); ?></span>
        </div>
        <h1 class="admin-page-title"><?php echo htmlspecialchars($customer['name']); ?></h1>
        <p class="admin-page-description">Customer since <?php echo date('F j, Y', strtotime($customer['created_at'])); ?></p>
    </div>
</div>

<!-- Customer Profile Card -->
<div class="admin-card">
    <div class="card-header">
        <h2 class="card-title">Customer Profile</h2>
    </div>
    <div class="card-body">
        <div class="customer-profile-grid">
            <div class="profile-field">
                <label class="profile-label">Full Name</label>
                <p class="profile-value"><?php echo htmlspecialchars($customer['name']); ?></p>
            </div>
            <div class="profile-field">
                <label class="profile-label">Email Address</label>
                <p class="profile-value">
                    <a href="mailto:<?php echo htmlspecialchars($customer['email']); ?>" class="profile-link">
                        <?php echo htmlspecialchars($customer['email']); ?>
                    </a>
                </p>
            </div>
            <div class="profile-field">
                <label class="profile-label">Phone Number</label>
                <p class="profile-value">
                    <?php if (!empty($customer['phone'])): ?>
                        <a href="tel:<?php echo htmlspecialchars($customer['phone']); ?>" class="profile-link">
                            <?php echo htmlspecialchars($customer['phone']); ?>
                        </a>
                    <?php else: ?>
                        <span class="text-muted">Not provided</span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="profile-field">
                <label class="profile-label">Business Name</label>
                <p class="profile-value">
                    <?php if (!empty($customer['business_name'])): ?>
                        <?php echo htmlspecialchars($customer['business_name']); ?>
                    <?php else: ?>
                        <span class="text-muted">Not provided</span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="profile-field">
                <label class="profile-label">Email Verified</label>
                <p class="profile-value">
                    <?php if ($customer['email_verified']): ?>
                        <span class="badge badge-success">Verified</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Not Verified</span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="profile-field">
                <label class="profile-label">Registration Date</label>
                <p class="profile-value"><?php echo date('F j, Y g:i A', strtotime($customer['created_at'])); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <?php 
    render_admin_card(
        'Active Subscriptions',
        format_number($active_subscriptions),
        'Currently active subscriptions',
        ''
    );
    
    render_admin_card(
        'Total Orders',
        format_number($total_orders),
        'All-time order count',
        '🛒'
    );
    
    render_admin_card(
        'Total Revenue',
        format_currency($total_revenue),
        'Revenue from successful orders',
        ''
    );
    
    render_admin_card(
        'Open Tickets',
        format_number($open_tickets),
        'Tickets requiring attention',
        ''
    );
    ?>
</div>

<!-- Subscriptions Section -->
<div class="admin-card">
    <div class="card-header">
        <h2 class="card-title">Subscriptions (<?php echo count($subscriptions); ?>)</h2>
        <?php if (!empty($subscriptions)): ?>
        <a href="<?php echo get_app_base_url(); ?>/admin/subscriptions.php?customer=<?php echo urlencode($customer_id); ?>" 
           class="btn btn-sm btn-secondary">
            View All in Subscriptions
        </a>
        <?php endif; ?>
    </div>
    
    <?php if (empty($subscriptions)): ?>
        <div class="card-body">
            <div class="empty-state-small">
                <p class="empty-state-text">No subscriptions found</p>
            </div>
        </div>
    <?php else: ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Assigned Port</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subscriptions as $subscription): ?>
                        <tr>
                            <td>
                                <div class="table-cell-primary">
                                    <?php echo htmlspecialchars($subscription['plan_name'] ?? 'Unknown Plan'); ?>
                                </div>
                                <?php 
                                // Calculate effective price (discounted_price if available, otherwise mrp)
                                $effective_price = !empty($subscription['plan_discounted_price']) && $subscription['plan_discounted_price'] > 0 
                                    ? $subscription['plan_discounted_price'] 
                                    : $subscription['plan_mrp'];
                                ?>
                                <?php if ($effective_price): ?>
                                    <div class="table-cell-secondary">
                                        <?php echo format_currency($effective_price, $subscription['plan_currency'] ?? 'USD'); ?>
                                        / <?php echo $subscription['plan_billing_period']; ?> month<?php echo $subscription['plan_billing_period'] > 1 ? 's' : ''; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo get_status_badge($subscription['status']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($subscription['start_date'])); ?></td>
                            <td>
                                <?php 
                                $end_date = strtotime($subscription['end_date']);
                                $now = time();
                                if ($end_date < $now && $subscription['status'] === 'ACTIVE') {
                                    echo '<span class="text-danger">' . date('M j, Y', $end_date) . '</span>';
                                } else {
                                    echo date('M j, Y', $end_date);
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($subscription['port_url']): ?>
                                    <div class="table-cell-primary">
                                        <a href="<?php echo get_app_base_url(); ?>/admin/ports/view.php?id=<?php echo urlencode($subscription['assigned_port_id']); ?>" class="profile-link">
                                            <?php echo htmlspecialchars($subscription['port_url']); ?>
                                        </a>
                                    </div>
                                    <div class="table-cell-secondary">
                                        Status: <?php echo get_status_badge($subscription['port_status']); ?>
                                        <?php if ($subscription['port_db_name']): ?>
                                            | DB: <?php echo htmlspecialchars($subscription['port_db_name']); ?>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">Not assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (has_permission('subscriptions.view_details')): ?>
                                <a href="<?php echo get_app_base_url(); ?>/admin/subscriptions/view.php?id=<?php echo urlencode($subscription['id']); ?>" 
                                   class="btn btn-sm btn-secondary"
                                   title="View subscription">
                                    View
                                </a>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Orders Section -->
<div class="admin-card">
    <div class="card-header">
        <h2 class="card-title">Orders (<?php echo count($orders); ?>)</h2>
        <?php if (!empty($orders)): ?>
        <a href="<?php echo get_app_base_url(); ?>/admin/orders.php?customer=<?php echo urlencode($customer_id); ?>" 
           class="btn btn-sm btn-secondary">
            View All in Orders
        </a>
        <?php endif; ?>
    </div>
    
    <?php if (empty($orders)): ?>
        <div class="card-body">
            <div class="empty-state-small">
                <p class="empty-state-text">No orders found</p>
            </div>
        </div>
    <?php else: ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment Method</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <code class="code-inline"><?php echo htmlspecialchars(substr($order['id'], 0, 8)); ?></code>
                            </td>
                            <td><?php echo htmlspecialchars($order['plan_name'] ?? 'Unknown Plan'); ?></td>
                            <td><?php echo format_currency($order['amount'], $order['currency'] ?? 'USD'); ?></td>
                            <td><?php echo get_status_badge($order['status']); ?></td>
                            <td>
                                <?php if ($order['payment_method']): ?>
                                    <?php echo htmlspecialchars($order['payment_method']); ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo get_relative_time($order['created_at']); ?></td>
                            <td>
                                <?php if (has_permission('orders.view_details')): ?>
                                <a href="<?php echo get_app_base_url(); ?>/admin/orders/view.php?id=<?php echo urlencode($order['id']); ?>" 
                                   class="btn btn-sm btn-secondary"
                                   title="View order">
                                    View
                                </a>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Invoices Section (successful orders only) -->
<?php 
$successful_orders = array_filter($orders, function($o) {
    return $o['status'] === 'SUCCESS';
});
?>
<div class="admin-card">
    <div class="card-header">
        <h2 class="card-title">Invoices (<?php echo count($successful_orders); ?>)</h2>
    </div>
    
    <?php if (empty($successful_orders)): ?>
        <div class="card-body">
            <div class="empty-state-small">
                <p class="empty-state-text">No invoices found (invoices are generated for successful orders)</p>
            </div>
        </div>
    <?php else: ?>
        <?php
        $invoiceService = new InvoiceService();
        ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($successful_orders as $order): ?>
                        <?php 
                        $invoice_number = $order['invoice_id'] ?? $invoiceService->generateInvoiceNumber($order);
                        ?>
                        <tr>
                            <td>
                                <code class="code-inline"><?php echo htmlspecialchars($invoice_number); ?></code>
                            </td>
                            <td><?php echo htmlspecialchars($order['plan_name'] ?? 'Unknown Plan'); ?></td>
                            <td><?php echo format_currency($order['amount'], $order['currency'] ?? 'USD'); ?></td>
                            <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                            <td>
                                <a href="<?php echo get_app_base_url(); ?>/admin/invoices/view.php?order_id=<?php echo urlencode($order['id']); ?>" 
                                   class="btn btn-sm btn-primary"
                                   title="View invoice">
                                    View Invoice
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Tickets Section -->
<div class="admin-card">
    <div class="card-header">
        <h2 class="card-title">Support Tickets (<?php echo count($tickets); ?>)</h2>
        <?php if (!empty($tickets)): ?>
        <a href="<?php echo get_app_base_url(); ?>/admin/support/tickets.php?customer=<?php echo urlencode($customer_id); ?>" 
           class="btn btn-sm btn-secondary">
            View All in Tickets
        </a>
        <?php endif; ?>
    </div>
    
    <?php if (empty($tickets)): ?>
        <div class="card-body">
            <div class="empty-state-small">
                <p class="empty-state-text">No support tickets found</p>
            </div>
        </div>
    <?php else: ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Category</th>
                        <th>Assignee</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td>
                                <code class="code-inline"><?php echo htmlspecialchars(substr($ticket['id'], 0, 8)); ?></code>
                            </td>
                            <td>
                                <div class="table-cell-primary">
                                    <?php echo htmlspecialchars($ticket['subject']); ?>
                                </div>
                            </td>
                            <td><?php echo get_status_badge($ticket['status']); ?></td>
                            <td>
                                <?php 
                                $priority_config = [
                                    'LOW' => 'secondary',
                                    'MEDIUM' => 'info',
                                    'HIGH' => 'warning',
                                    'URGENT' => 'danger'
                                ];
                                echo get_status_badge($ticket['priority'], $priority_config); 
                                ?>
                            </td>
                            <td>
                                <?php if ($ticket['category']): ?>
                                    <?php echo htmlspecialchars($ticket['category']); ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($ticket['assignee_name']): ?>
                                    <?php echo htmlspecialchars($ticket['assignee_name']); ?>
                                <?php else: ?>
                                    <span class="text-muted">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo get_relative_time($ticket['created_at']); ?></td>
                            <td>
                                <?php if (has_permission('tickets.view_details')): ?>
                                <a href="<?php echo get_app_base_url(); ?>/admin/support/tickets/view.php?id=<?php echo urlencode($ticket['id']); ?>" 
                                   class="btn btn-sm btn-primary"
                                   title="View ticket">
                                    View
                                </a>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
.admin-page-header {
    margin-bottom: var(--spacing-6);
}

.admin-card {
    margin-bottom: var(--spacing-6);
}

.admin-card:last-child {
    margin-bottom: 0;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    margin-bottom: var(--spacing-3);
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

.customer-profile-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-6);
}

.profile-field {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
}

.profile-label {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.profile-value {
    font-size: var(--font-size-base);
    color: var(--color-gray-900);
    margin: 0;
}

.profile-link {
    color: var(--color-primary);
    text-decoration: none;
}

.profile-link:hover {
    text-decoration: underline;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-6);
}

.empty-state-small {
    padding: var(--spacing-8) var(--spacing-4);
    text-align: center;
}

.empty-state-text {
    color: var(--color-gray-500);
    font-style: italic;
    margin: 0;
}

.table-cell-primary {
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
}

.table-cell-secondary {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin-top: var(--spacing-1);
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
    font-style: italic;
}

.text-danger {
    color: var(--color-red-600);
    font-weight: var(--font-weight-semibold);
}

@media (max-width: 768px) {
    .customer-profile-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .admin-table-container {
        overflow-x: auto;
    }
}
</style>

<?php include_admin_footer(); ?>
