<?php
/**
 * Admin Invoices List Page
 * Displays all invoices from successful orders
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

use Karyalay\Services\InvoiceService;

// Start secure session
startSecureSession();

// Require admin authentication and invoices.view permission
require_admin();
require_permission('invoices.view');

// Get database connection
$db = \Karyalay\Database\Connection::getInstance();

// Get filters from query parameters
$search_query = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query for counting total invoices (only SUCCESS orders have invoices)
$count_sql = "SELECT COUNT(*) FROM orders WHERE status = 'SUCCESS'";
$count_params = [];

if (!empty($date_from)) {
    $count_sql .= " AND DATE(created_at) >= :date_from";
    $count_params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $count_sql .= " AND DATE(created_at) <= :date_to";
    $count_params[':date_to'] = $date_to;
}

if (!empty($search_query)) {
    $count_sql .= " AND id IN (SELECT o.id FROM orders o 
                    LEFT JOIN users u ON o.customer_id = u.id 
                    WHERE (u.name LIKE :search OR u.email LIKE :search OR o.id LIKE :search OR o.invoice_id LIKE :search) AND o.status = 'SUCCESS')";
    $count_params[':search'] = '%' . $search_query . '%';
}

try {
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($count_params);
    $total_invoices = $count_stmt->fetchColumn();
    $total_pages = ceil($total_invoices / $per_page);
} catch (PDOException $e) {
    error_log("Invoices count error: " . $e->getMessage());
    $total_invoices = 0;
    $total_pages = 0;
}

// Build query for fetching invoices
$sql = "SELECT o.*, 
        p.name as plan_name,
        u.name as customer_name,
        u.email as customer_email
        FROM orders o
        LEFT JOIN plans p ON o.plan_id = p.id
        LEFT JOIN users u ON o.customer_id = u.id
        WHERE o.status = 'SUCCESS'";
$params = [];

if (!empty($date_from)) {
    $sql .= " AND DATE(o.created_at) >= :date_from";
    $params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $sql .= " AND DATE(o.created_at) <= :date_to";
    $params[':date_to'] = $date_to;
}

if (!empty($search_query)) {
    $sql .= " AND (u.name LIKE :search OR u.email LIKE :search OR o.id LIKE :search OR o.invoice_id LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

$sql .= " ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";

try {
    $stmt = $db->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Invoices list error: " . $e->getMessage());
    $invoices = [];
}

// Generate invoice numbers for orders that don't have them
$invoiceService = new InvoiceService();
foreach ($invoices as &$invoice) {
    if (empty($invoice['invoice_id'])) {
        $invoice['invoice_id'] = $invoiceService->generateInvoiceNumber($invoice);
    }
}
unset($invoice);

// Include admin header
include_admin_header('Invoices');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Invoice Management</h1>
        <p class="admin-page-description">View all invoices from successful orders</p>
    </div>
</div>

<!-- Filters and Search -->
<div class="admin-filters-section">
    <form method="GET" action="<?php echo get_app_base_url(); ?>/admin/invoices.php" class="admin-filters-form">
        <div class="admin-filter-group">
            <label for="search" class="admin-filter-label">Search</label>
            <input 
                type="text" 
                id="search" 
                name="search" 
                class="admin-filter-input" 
                placeholder="Search by customer, invoice #, or order ID..."
                value="<?php echo htmlspecialchars($search_query); ?>"
            >
        </div>
        
        <div class="admin-filter-group">
            <label for="date_from" class="admin-filter-label">Date From</label>
            <input 
                type="date" 
                id="date_from" 
                name="date_from" 
                class="admin-filter-input" 
                value="<?php echo htmlspecialchars($date_from); ?>"
            >
        </div>
        
        <div class="admin-filter-group">
            <label for="date_to" class="admin-filter-label">Date To</label>
            <input 
                type="date" 
                id="date_to" 
                name="date_to" 
                class="admin-filter-input" 
                value="<?php echo htmlspecialchars($date_to); ?>"
            >
        </div>
        
        <div class="admin-filter-actions">
            <button type="submit" class="btn btn-secondary">Apply Filters</button>
            <a href="<?php echo get_app_base_url(); ?>/admin/invoices.php" class="btn btn-text">Clear</a>
        </div>
    </form>
</div>

<!-- Invoices Table -->
<div class="admin-card">
    <?php if (empty($invoices)): ?>
        <?php 
        render_empty_state(
            'No invoices found',
            'Invoices are generated for successful orders only',
            '',
            ''
        );
        ?>
    <?php else: ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>Order ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $invoice): ?>
                        <tr>
                            <td>
                                <code class="code-inline"><?php echo htmlspecialchars($invoice['invoice_id']); ?></code>
                            </td>
                            <td>
                                <div class="table-cell-primary">
                                    <?php echo date('M j, Y', strtotime($invoice['created_at'])); ?>
                                </div>
                                <div class="table-cell-secondary">
                                    <?php echo date('g:i A', strtotime($invoice['created_at'])); ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($invoice['customer_name']): ?>
                                    <div class="table-cell-primary">
                                        <?php echo htmlspecialchars($invoice['customer_name']); ?>
                                    </div>
                                    <div class="table-cell-secondary">
                                        <?php echo htmlspecialchars($invoice['customer_email']); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">Unknown</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($invoice['plan_name']): ?>
                                    <?php echo htmlspecialchars($invoice['plan_name']); ?>
                                <?php else: ?>
                                    <span class="text-muted">No plan</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="amount-display">
                                    <span class="currency"><?php echo htmlspecialchars($invoice['currency']); ?></span>
                                    <span class="amount"><?php echo number_format($invoice['amount'], 2); ?></span>
                                </div>
                            </td>
                            <td>
                                <a href="<?php echo get_app_base_url(); ?>/admin/orders/view.php?id=<?php echo urlencode($invoice['id']); ?>" 
                                   class="code-link">
                                    <code class="code-inline"><?php echo htmlspecialchars(substr($invoice['id'], 0, 8)); ?></code>
                                </a>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?php echo get_app_base_url(); ?>/admin/invoices/view.php?order_id=<?php echo urlencode($invoice['id']); ?>" 
                                       class="btn btn-sm btn-primary"
                                       title="View invoice">
                                        View Invoice
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
                $base_url = get_app_base_url() . '/admin/invoices.php';
                $query_params = [];
                if (!empty($date_from)) {
                    $query_params[] = 'date_from=' . urlencode($date_from);
                }
                if (!empty($date_to)) {
                    $query_params[] = 'date_to=' . urlencode($date_to);
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
                Showing <?php echo count($invoices); ?> of <?php echo $total_invoices; ?> invoice<?php echo $total_invoices !== 1 ? 's' : ''; ?>
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

.admin-filter-input {
    padding: var(--spacing-2) var(--spacing-3);
    border: 1px solid var(--color-gray-300);
    border-radius: var(--radius-md);
    font-size: var(--font-size-base);
    color: var(--color-gray-900);
}

.admin-filter-input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.admin-filter-actions {
    display: flex;
    gap: var(--spacing-2);
}

.admin-table-container {
    overflow-x: auto;
}

.admin-table {
    width: 100%;
}

.table-cell-primary {
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
}

.table-cell-secondary {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin-top: 2px;
}

.code-inline {
    background-color: var(--color-gray-100);
    padding: 2px 6px;
    border-radius: var(--radius-sm);
    font-family: 'Courier New', monospace;
    font-size: 11px;
    color: var(--color-gray-800);
}

.code-link {
    text-decoration: none;
}

.code-link:hover .code-inline {
    background-color: var(--color-gray-200);
}

.text-muted {
    color: var(--color-gray-500);
    font-style: italic;
}

.amount-display {
    display: flex;
    align-items: baseline;
    gap: 2px;
}

.currency {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    font-weight: var(--font-weight-semibold);
}

.amount {
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
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
}
</style>

<?php include_admin_footer(); ?>
