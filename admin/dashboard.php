<?php
/**
 * Admin Dashboard
 * Displays overview cards with key metrics
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

// Start secure session
startSecureSession();

// Require admin authentication and dashboard.view permission
require_admin();
require_permission('dashboard.view');

// Get database connection
$db = \Karyalay\Database\Connection::getInstance();

// Fetch dashboard statistics
try {
    // Total customers
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'CUSTOMER'");
    $total_customers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Active subscriptions
    $stmt = $db->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'ACTIVE'");
    $active_subscriptions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total revenue (successful orders)
    $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM orders WHERE status = 'SUCCESS'");
    $total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Available ports
    $stmt = $db->query("SELECT COUNT(*) as count FROM ports WHERE status = 'AVAILABLE'");
    $available_ports = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Open tickets
    $stmt = $db->query("SELECT COUNT(*) as count FROM tickets WHERE status IN ('OPEN', 'IN_PROGRESS')");
    $open_tickets = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // New leads (last 30 days)
    $stmt = $db->query("SELECT COUNT(*) as count FROM leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $new_leads = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Pending orders
    $stmt = $db->query("SELECT COUNT(*) as count FROM orders WHERE status = 'PENDING'");
    $pending_orders = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Expiring subscriptions (next 30 days)
    $stmt = $db->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'ACTIVE' AND end_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)");
    $expiring_subscriptions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total invoices (successful orders)
    $stmt = $db->query("SELECT COUNT(*) as count FROM orders WHERE status = 'SUCCESS'");
    $total_invoices = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Recent activity - latest orders
    $stmt = $db->prepare("
        SELECT o.id, o.amount, o.status, o.created_at, u.name as customer_name, p.name as plan_name
        FROM orders o
        JOIN users u ON o.customer_id = u.id
        JOIN plans p ON o.plan_id = p.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent tickets
    $stmt = $db->prepare("
        SELECT t.id, t.subject, t.status, t.priority, t.created_at, u.name as customer_name
        FROM tickets t
        JOIN users u ON t.customer_id = u.id
        ORDER BY t.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recent_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $total_customers = 0;
    $active_subscriptions = 0;
    $total_revenue = 0;
    $available_ports = 0;
    $open_tickets = 0;
    $new_leads = 0;
    $pending_orders = 0;
    $expiring_subscriptions = 0;
    $total_invoices = 0;
    $recent_orders = [];
    $recent_tickets = [];
}

// Include admin header
include_admin_header('Dashboard');
?>

<div class="admin-dashboard">
    <!-- Overview Cards -->
    <div class="admin-dashboard-grid">
        <?php 
        $base = get_app_base_url();
        
        render_admin_card(
            'Total Customers',
            format_number($total_customers),
            'Registered customer accounts',
            '',
            $base . '/admin/customers.php',
            'View all customers'
        );
        
        render_admin_card(
            'Active Subscriptions',
            format_number($active_subscriptions),
            'Currently active subscriptions',
            '',
            $base . '/admin/subscriptions.php',
            'View subscriptions'
        );
        
        render_admin_card(
            'Total Revenue',
            format_currency($total_revenue),
            'All-time revenue from orders',
            '',
            $base . '/admin/orders.php',
            'View orders'
        );
        
        render_admin_card(
            'Available Ports',
            format_number($available_ports),
            'Ports ready for allocation',
            '',
            $base . '/admin/ports.php',
            'Manage ports'
        );
        
        render_admin_card(
            'Open Tickets',
            format_number($open_tickets),
            'Tickets requiring attention',
            '',
            $base . '/admin/support/tickets.php',
            'View tickets'
        );
        
        render_admin_card(
            'New Leads',
            format_number($new_leads),
            'Leads in the last 30 days',
            '',
            $base . '/admin/leads.php',
            'View leads'
        );
        ?>
    </div>

    <!-- Alerts Section -->
    <?php if ($pending_orders > 0 || $expiring_subscriptions > 0 || $available_ports < 10): ?>
        <div class="admin-alerts-section">
            <h2 class="admin-section-title">Alerts & Notifications</h2>
            <div class="admin-alerts-grid">
                <?php if ($pending_orders > 0): ?>
                    <div class="alert alert-warning">
                        <strong>Pending Orders:</strong> <?php echo $pending_orders; ?> order(s) are pending payment confirmation.
                        <a href="<?php echo get_app_base_url(); ?>/admin/orders.php?status=PENDING">View pending orders →</a>
                    </div>
                <?php endif; ?>
                
                <?php if ($expiring_subscriptions > 0): ?>
                    <div class="alert alert-info">
                        <strong>Expiring Soon:</strong> <?php echo $expiring_subscriptions; ?> subscription(s) will expire in the next 30 days.
                        <a href="<?php echo get_app_base_url(); ?>/admin/subscriptions.php?expiring=30">View expiring subscriptions →</a>
                    </div>
                <?php endif; ?>
                
                <?php if ($available_ports < 10): ?>
                    <div class="alert alert-danger">
                        <strong>Low Port Availability:</strong> Only <?php echo $available_ports; ?> port(s) available. Consider adding more ports.
                        <a href="<?php echo get_app_base_url(); ?>/admin/ports.php">Manage ports →</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recent Activity -->
    <div class="admin-activity-section">
        <div class="admin-activity-grid">
            <!-- Recent Orders -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">Recent Orders</h3>
                    <a href="<?php echo get_app_base_url(); ?>/admin/orders.php" class="admin-card-link">View all →</a>
                </div>
                
                <?php if (empty($recent_orders)): ?>
                    <p class="admin-card-description">No recent orders</p>
                <?php else: ?>
                    <div class="admin-table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Plan</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr data-href="<?php echo get_app_base_url(); ?>/admin/orders/<?php echo $order['id']; ?>">
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($order['plan_name']); ?></td>
                                        <td><?php echo format_currency($order['amount']); ?></td>
                                        <td><?php echo get_status_badge($order['status']); ?></td>
                                        <td><?php echo get_relative_time($order['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Tickets -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">Recent Tickets</h3>
                    <a href="<?php echo get_app_base_url(); ?>/admin/support/tickets.php" class="admin-card-link">View all →</a>
                </div>
                
                <?php if (empty($recent_tickets)): ?>
                    <p class="admin-card-description">No recent tickets</p>
                <?php else: ?>
                    <div class="admin-table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Customer</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_tickets as $ticket): ?>
                                    <tr data-href="<?php echo get_app_base_url(); ?>/admin/support/tickets/<?php echo $ticket['id']; ?>">
                                        <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                        <td><?php echo htmlspecialchars($ticket['customer_name']); ?></td>
                                        <td><?php echo get_status_badge($ticket['priority']); ?></td>
                                        <td><?php echo get_status_badge($ticket['status']); ?></td>
                                        <td><?php echo get_relative_time($ticket['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.admin-section-title {
    font-size: var(--font-size-xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-4) 0;
}

.admin-alerts-section {
    margin-bottom: var(--spacing-8);
}

.admin-alerts-grid {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-4);
}

.alert a {
    color: inherit;
    text-decoration: underline;
    font-weight: var(--font-weight-semibold);
    margin-left: var(--spacing-2);
}

.admin-activity-section {
    margin-bottom: var(--spacing-8);
}

.admin-activity-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: var(--spacing-6);
}

.admin-table-container {
    overflow-x: auto;
    margin-top: var(--spacing-4);
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    font-size: var(--font-size-sm);
}

.admin-table thead {
    background-color: var(--color-gray-50);
}

.admin-table th {
    padding: var(--spacing-3);
    text-align: left;
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
    border-bottom: 2px solid var(--color-gray-200);
}

.admin-table td {
    padding: var(--spacing-3);
    border-bottom: 1px solid var(--color-gray-200);
    color: var(--color-gray-900);
}

.admin-table tbody tr {
    transition: background-color var(--transition-fast);
}

.admin-table tbody tr:hover {
    background-color: var(--color-gray-50);
}

.badge {
    display: inline-block;
    padding: var(--spacing-1) var(--spacing-2);
    border-radius: var(--radius-md);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-semibold);
    text-transform: uppercase;
}

.badge-success {
    background-color: #d1fae5;
    color: #065f46;
}

.badge-warning {
    background-color: #fef3c7;
    color: #92400e;
}

.badge-danger {
    background-color: #fee2e2;
    color: #991b1b;
}

.badge-info {
    background-color: #dbeafe;
    color: #1e40af;
}

.badge-secondary {
    background-color: var(--color-gray-200);
    color: var(--color-gray-700);
}

@media (max-width: 768px) {
    .admin-activity-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include_admin_footer(); ?>
