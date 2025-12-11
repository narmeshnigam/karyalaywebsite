<?php
/**
 * Admin Tickets List Page
 * Displays all tickets from all customers with filters
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

use Karyalay\Services\TicketService;
use Karyalay\Models\User;

// Start secure session
startSecureSession();

// Require admin authentication and tickets.view permission
require_admin();
require_permission('tickets.view');

// Get database connection
$db = \Karyalay\Database\Connection::getInstance();

// Initialize services and models
$ticketService = new TicketService();
$userModel = new User();

// Get filters from query parameters
$status_filter = $_GET['status'] ?? '';
$priority_filter = $_GET['priority'] ?? '';
$assignee_filter = $_GET['assignee'] ?? '';
$category_filter = $_GET['category'] ?? '';
$customer_filter = $_GET['customer'] ?? '';
$search_query = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Fetch all admin users for assignee filter
$admin_users_sql = "SELECT id, name, email FROM users WHERE role = 'ADMIN' ORDER BY name ASC";
$admin_users_stmt = $db->query($admin_users_sql);
$admin_users = $admin_users_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all customers for customer filter
$customers_sql = "SELECT id, name, email FROM users WHERE role = 'CUSTOMER' ORDER BY name ASC LIMIT 100";
$customers_stmt = $db->query($customers_sql);
$customers = $customers_stmt->fetchAll(PDO::FETCH_ASSOC);

// Build query for counting total
$count_sql = "SELECT COUNT(*) FROM tickets WHERE 1=1";
$count_params = [];

if (!empty($status_filter) && in_array($status_filter, ['OPEN', 'IN_PROGRESS', 'WAITING_ON_CUSTOMER', 'RESOLVED', 'CLOSED'])) {
    $count_sql .= " AND status = :status";
    $count_params[':status'] = $status_filter;
}

if (!empty($priority_filter) && in_array($priority_filter, ['LOW', 'MEDIUM', 'HIGH', 'URGENT'])) {
    $count_sql .= " AND priority = :priority";
    $count_params[':priority'] = $priority_filter;
}

if (!empty($assignee_filter)) {
    if ($assignee_filter === 'unassigned') {
        $count_sql .= " AND assigned_to IS NULL";
    } else {
        $count_sql .= " AND assigned_to = :assigned_to";
        $count_params[':assigned_to'] = $assignee_filter;
    }
}

if (!empty($category_filter)) {
    $count_sql .= " AND category = :category";
    $count_params[':category'] = $category_filter;
}

if (!empty($customer_filter)) {
    $count_sql .= " AND customer_id = :customer_id";
    $count_params[':customer_id'] = $customer_filter;
}

if (!empty($search_query)) {
    $count_sql .= " AND (subject LIKE :search OR id LIKE :search)";
    $count_params[':search'] = '%' . $search_query . '%';
}

try {
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($count_params);
    $total_tickets = $count_stmt->fetchColumn();
    $total_pages = ceil($total_tickets / $per_page);
} catch (PDOException $e) {
    error_log("Tickets count error: " . $e->getMessage());
    $total_tickets = 0;
    $total_pages = 0;
}

// Build query for fetching tickets with joins
$sql = "SELECT t.*, 
        u.name as customer_name, 
        u.email as customer_email,
        a.name as assignee_name,
        s.plan_id as subscription_plan_id,
        p.name as plan_name
        FROM tickets t
        LEFT JOIN users u ON t.customer_id = u.id
        LEFT JOIN users a ON t.assigned_to = a.id
        LEFT JOIN subscriptions s ON t.subscription_id = s.id
        LEFT JOIN plans p ON s.plan_id = p.id
        WHERE 1=1";
$params = [];

if (!empty($status_filter)) {
    $sql .= " AND t.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($priority_filter)) {
    $sql .= " AND t.priority = :priority";
    $params[':priority'] = $priority_filter;
}

if (!empty($assignee_filter)) {
    if ($assignee_filter === 'unassigned') {
        $sql .= " AND t.assigned_to IS NULL";
    } else {
        $sql .= " AND t.assigned_to = :assigned_to";
        $params[':assigned_to'] = $assignee_filter;
    }
}

if (!empty($category_filter)) {
    $sql .= " AND t.category = :category";
    $params[':category'] = $category_filter;
}

if (!empty($customer_filter)) {
    $sql .= " AND t.customer_id = :customer_id";
    $params[':customer_id'] = $customer_filter;
}

if (!empty($search_query)) {
    $sql .= " AND (t.subject LIKE :search OR t.id LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

$sql .= " ORDER BY t.updated_at DESC LIMIT :limit OFFSET :offset";

try {
    $stmt = $db->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Tickets list error: " . $e->getMessage());
    $tickets = [];
}

// Include admin header
include_admin_header('Support Tickets');

// Include export button helper
require_once __DIR__ . '/../../includes/export_button_helper.php';
?>

<?php render_export_button_styles(); ?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Support Tickets</h1>
        <p class="admin-page-description">Manage customer support tickets and inquiries</p>
    </div>
    <div class="admin-page-header-actions">
        <?php render_export_button(get_app_base_url() . '/admin/api/export-tickets.php'); ?>
    </div>
</div>

<!-- Filters and Search -->
<div class="admin-filters-section">
    <form method="GET" action="/admin/support/tickets.php" class="admin-filters-form">
        <div class="admin-filter-group">
            <label for="search" class="admin-filter-label">Search</label>
            <input 
                type="text" 
                id="search" 
                name="search" 
                class="admin-filter-input" 
                placeholder="Search by subject or ticket ID..."
                value="<?php echo htmlspecialchars($search_query); ?>"
            >
        </div>
        
        <div class="admin-filter-group">
            <label for="status" class="admin-filter-label">Status</label>
            <select id="status" name="status" class="admin-filter-select">
                <option value="">All Statuses</option>
                <option value="OPEN" <?php echo $status_filter === 'OPEN' ? 'selected' : ''; ?>>Open</option>
                <option value="IN_PROGRESS" <?php echo $status_filter === 'IN_PROGRESS' ? 'selected' : ''; ?>>In Progress</option>
                <option value="WAITING_ON_CUSTOMER" <?php echo $status_filter === 'WAITING_ON_CUSTOMER' ? 'selected' : ''; ?>>Waiting on Customer</option>
                <option value="RESOLVED" <?php echo $status_filter === 'RESOLVED' ? 'selected' : ''; ?>>Resolved</option>
                <option value="CLOSED" <?php echo $status_filter === 'CLOSED' ? 'selected' : ''; ?>>Closed</option>
            </select>
        </div>
        
        <div class="admin-filter-group">
            <label for="priority" class="admin-filter-label">Priority</label>
            <select id="priority" name="priority" class="admin-filter-select">
                <option value="">All Priorities</option>
                <option value="LOW" <?php echo $priority_filter === 'LOW' ? 'selected' : ''; ?>>Low</option>
                <option value="MEDIUM" <?php echo $priority_filter === 'MEDIUM' ? 'selected' : ''; ?>>Medium</option>
                <option value="HIGH" <?php echo $priority_filter === 'HIGH' ? 'selected' : ''; ?>>High</option>
                <option value="URGENT" <?php echo $priority_filter === 'URGENT' ? 'selected' : ''; ?>>Urgent</option>
            </select>
        </div>
        
        <div class="admin-filter-group">
            <label for="assignee" class="admin-filter-label">Assignee</label>
            <select id="assignee" name="assignee" class="admin-filter-select">
                <option value="">All Assignees</option>
                <option value="unassigned" <?php echo $assignee_filter === 'unassigned' ? 'selected' : ''; ?>>Unassigned</option>
                <?php foreach ($admin_users as $admin): ?>
                    <option value="<?php echo htmlspecialchars($admin['id']); ?>" 
                            <?php echo $assignee_filter === $admin['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($admin['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="admin-filter-group">
            <label for="category" class="admin-filter-label">Category</label>
            <select id="category" name="category" class="admin-filter-select">
                <option value="">All Categories</option>
                <option value="Technical" <?php echo $category_filter === 'Technical' ? 'selected' : ''; ?>>Technical</option>
                <option value="Billing" <?php echo $category_filter === 'Billing' ? 'selected' : ''; ?>>Billing</option>
                <option value="General" <?php echo $category_filter === 'General' ? 'selected' : ''; ?>>General</option>
                <option value="Feature Request" <?php echo $category_filter === 'Feature Request' ? 'selected' : ''; ?>>Feature Request</option>
                <option value="Bug Report" <?php echo $category_filter === 'Bug Report' ? 'selected' : ''; ?>>Bug Report</option>
            </select>
        </div>
        
        <div class="admin-filter-group">
            <label for="customer" class="admin-filter-label">Customer</label>
            <select id="customer" name="customer" class="admin-filter-select">
                <option value="">All Customers</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?php echo htmlspecialchars($customer['id']); ?>" 
                            <?php echo $customer_filter === $customer['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($customer['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="admin-filter-actions">
            <button type="submit" class="btn btn-secondary">Apply Filters</button>
            <a href="<?php echo get_app_base_url(); ?>/admin/support/tickets.php" class="btn btn-text">Clear</a>
        </div>
    </form>
</div>

<!-- Priority Legend -->
<div class="priority-legend">
    <div class="legend-title">Priority Guide:</div>
    <div class="legend-items">
        <div class="legend-item">
            <span class="badge badge-danger">Urgent</span>
            <span class="legend-desc">Requires immediate attention</span>
        </div>
        <div class="legend-item">
            <span class="badge badge-warning">High</span>
            <span class="legend-desc">Important, needs quick response</span>
        </div>
        <div class="legend-item">
            <span class="badge badge-info">Medium</span>
            <span class="legend-desc">Standard priority</span>
        </div>
        <div class="legend-item">
            <span class="badge badge-secondary">Low</span>
            <span class="legend-desc">Can be addressed later</span>
        </div>
    </div>
</div>

<!-- Tickets Table -->
<div class="admin-card">
    <?php if (empty($tickets)): ?>
        <?php 
        render_empty_state(
            'No tickets found',
            'No support tickets match your current filters',
            '',
            ''
        );
        ?>
    <?php else: ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Subject</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Category</th>
                        <th>Assignee</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr class="ticket-row <?php 
                            if ($ticket['priority'] === 'URGENT') echo 'ticket-urgent';
                            elseif ($ticket['priority'] === 'HIGH') echo 'ticket-high';
                            if ($ticket['status'] === 'OPEN') echo ' ticket-open';
                        ?>">
                            <td>
                                <code class="code-inline"><?php echo htmlspecialchars(substr($ticket['id'], 0, 8)); ?></code>
                            </td>
                            <td>
                                <div class="table-cell-primary">
                                    <?php echo htmlspecialchars($ticket['subject']); ?>
                                </div>
                                <?php if (!empty($ticket['plan_name'])): ?>
                                    <div class="table-cell-secondary">
                                        Plan: <?php echo htmlspecialchars($ticket['plan_name']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($ticket['customer_name']): ?>
                                    <div class="table-cell-primary">
                                        <?php echo htmlspecialchars($ticket['customer_name']); ?>
                                    </div>
                                    <div class="table-cell-secondary">
                                        <?php echo htmlspecialchars($ticket['customer_email']); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">Unknown</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $status_config = [
                                    'OPEN' => 'danger',
                                    'IN_PROGRESS' => 'warning',
                                    'WAITING_ON_CUSTOMER' => 'info',
                                    'RESOLVED' => 'success',
                                    'CLOSED' => 'secondary'
                                ];
                                echo get_status_badge($ticket['status'], $status_config); 
                                ?>
                            </td>
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
                            <td><?php echo get_relative_time($ticket['updated_at']); ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?php echo get_app_base_url(); ?>/admin/support/tickets/view.php?id=<?php echo urlencode($ticket['id']); ?>" 
                                       class="btn btn-sm btn-primary"
                                       title="View ticket">
                                        View
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="admin-card-footer">
                <?php 
                $base_url = '/admin/support/tickets.php';
                $query_params = [];
                if (!empty($status_filter)) {
                    $query_params[] = 'status=' . urlencode($status_filter);
                }
                if (!empty($priority_filter)) {
                    $query_params[] = 'priority=' . urlencode($priority_filter);
                }
                if (!empty($assignee_filter)) {
                    $query_params[] = 'assignee=' . urlencode($assignee_filter);
                }
                if (!empty($category_filter)) {
                    $query_params[] = 'category=' . urlencode($category_filter);
                }
                if (!empty($customer_filter)) {
                    $query_params[] = 'customer=' . urlencode($customer_filter);
                }
                if (!empty($search_query)) {
                    $query_params[] = 'search=' . urlencode($search_query);
                }
                if (!empty($query_params)) {
                    $base_url .= '?' . implode('&', $query_params);
                }
                render_pagination($page, $total_pages, $base_url);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-card-footer">
            <p class="admin-card-footer-text">
                Showing <?php echo count($tickets); ?> of <?php echo $total_tickets; ?> ticket<?php echo $total_tickets !== 1 ? 's' : ''; ?>
            </p>
        </div>
    <?php endif; ?>
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

.admin-filters-section {
    background: white;
    border: 1px solid var(--color-gray-200);
    border-radius: var(--radius-lg);
    padding: var(--spacing-4);
    margin-bottom: var(--spacing-6);
}

.priority-legend {
    background: white;
    border: 1px solid var(--color-gray-200);
    border-radius: var(--radius-lg);
    padding: var(--spacing-4);
    margin-bottom: var(--spacing-6);
}

.legend-title {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    margin-bottom: var(--spacing-3);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--color-gray-700);
}

.legend-items {
    display: flex;
    gap: var(--spacing-4);
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
}

.legend-desc {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
}

.admin-filters-form {
    display: flex;
    gap: var(--spacing-4);
    align-items: flex-end;
    flex-wrap: wrap;
}

.admin-filter-group {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
    flex: 1;
    min-width: 180px;
}

.admin-filter-label {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
}

.admin-filter-input,
.admin-filter-select {
    padding: var(--spacing-2) var(--spacing-3);
    border: 1px solid var(--color-gray-300);
    border-radius: var(--radius-md);
    font-size: var(--font-size-base);
    color: var(--color-gray-900);
}

.admin-filter-input:focus,
.admin-filter-select:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.admin-filter-actions {
    display: flex;
    gap: var(--spacing-2);
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

.table-actions {
    display: flex;
    gap: var(--spacing-2);
}

.admin-card-footer-text {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin: 0;
}

/* Enhanced Badge Styling for Better Differentiation */
.badge {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    line-height: 1;
    border-radius: 9999px;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    white-space: nowrap;
}

/* Status Badges - Clear Visual Hierarchy */
.badge-danger {
    background-color: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

.badge-warning {
    background-color: #fef3c7;
    color: #92400e;
    border: 1px solid #fcd34d;
}

.badge-success {
    background-color: #d1fae5;
    color: #065f46;
    border: 1px solid #6ee7b7;
}

.badge-info {
    background-color: #dbeafe;
    color: #1e40af;
    border: 1px solid #93c5fd;
}

.badge-secondary {
    background-color: #f3f4f6;
    color: #4b5563;
    border: 1px solid #d1d5db;
}

/* Priority-specific enhancements */
.badge-danger {
    animation: pulse-danger 2s ease-in-out infinite;
}

@keyframes pulse-danger {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
    }
    50% {
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0);
    }
}

/* Hover effects for better interactivity */
.admin-table tbody tr:hover .badge {
    transform: scale(1.05);
    transition: transform 0.2s ease;
}

/* Row highlighting for priority tickets */
.ticket-row {
    transition: all 0.2s ease;
}

.ticket-urgent {
    border-left: 4px solid #dc2626;
    background-color: #fef2f2;
}

.ticket-urgent:hover {
    background-color: #fee2e2;
}

.ticket-high {
    border-left: 4px solid #f59e0b;
    background-color: #fffbeb;
}

.ticket-high:hover {
    background-color: #fef3c7;
}

.ticket-open {
    font-weight: 500;
}



/* Table responsive handling */
.admin-table-container {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.admin-table {
    min-width: 100%;
    table-layout: auto;
}

/* Allow text wrapping for content columns */
.admin-table td {
    white-space: normal;
    word-wrap: break-word;
    word-break: break-word;
}

/* Set max-widths for columns that can have long content */
.admin-table td:nth-child(2) { /* Subject */
    max-width: 300px;
}

.admin-table td:nth-child(3) { /* Customer */
    max-width: 200px;
}

.admin-table td:nth-child(7) { /* Assignee */
    max-width: 150px;
}

/* Keep action buttons and badges from wrapping */
.admin-table td:nth-child(1), /* Ticket ID */
.admin-table td:nth-child(4), /* Status */
.admin-table td:nth-child(5), /* Priority */
.admin-table td:nth-child(6), /* Category */
.admin-table td:nth-child(8), /* Last Updated */
.admin-table td:nth-child(9) { /* Actions */
    white-space: nowrap;
}

@media (max-width: 1200px) {
    .admin-table td:nth-child(2) {
        max-width: 250px;
    }
    
    .admin-table td:nth-child(3) {
        max-width: 180px;
    }
    
    .admin-table td:nth-child(7) {
        max-width: 120px;
    }
}

@media (max-width: 768px) {
    .admin-page-header {
        flex-direction: column;
    }
    
    .admin-filters-form {
        flex-direction: column;
    }
    
    .admin-filter-group {
        width: 100%;
    }
    
    .admin-table th,
    .admin-table td {
        font-size: var(--font-size-sm);
        padding: var(--spacing-2);
    }
    
    .admin-table td:nth-child(2) {
        max-width: 150px;
    }
    
    .admin-table td:nth-child(3) {
        max-width: 120px;
    }
    
    .admin-table td:nth-child(7) {
        max-width: 100px;
    }
    
    .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }
    
    .legend-items {
        flex-direction: column;
        gap: var(--spacing-2);
    }
    
    .legend-desc {
        font-size: 0.7rem;
    }
    
    .table-cell-primary,
    .table-cell-secondary {
        font-size: 0.8rem;
    }
}
</style>

<?php include_admin_footer(); ?>
