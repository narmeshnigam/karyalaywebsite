<?php
/**
 * Admin Order Details Page
 * View and edit order details with unlock mechanism
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Services\InvoiceService;

startSecureSession();
require_admin();
require_permission('orders.view_details');

$db = \Karyalay\Database\Connection::getInstance();
$order_id = $_GET['id'] ?? '';

if (empty($order_id)) {
    $_SESSION['admin_error'] = 'Order ID is required.';
    header('Location: ' . get_app_base_url() . '/admin/orders.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrf_token)) {
        $_SESSION['admin_error'] = 'Invalid security token.';
        header('Location: ' . get_app_base_url() . '/admin/orders/view.php?id=' . urlencode($order_id));
        exit;
    }
    
    if ($_POST['action'] === 'update_order') {
        try {
            $update_sql = "UPDATE orders SET 
                amount = :amount,
                currency = :currency,
                payment_method = :payment_method,
                pg_order_id = :pg_order_id,
                pg_payment_id = :pg_payment_id,
                status = :status,
                updated_at = NOW()
                WHERE id = :order_id";
            
            $update_stmt = $db->prepare($update_sql);
            $update_stmt->execute([
                ':amount' => $_POST['amount'],
                ':currency' => $_POST['currency'],
                ':payment_method' => $_POST['payment_method'],
                ':pg_order_id' => $_POST['pg_order_id'] ?: null,
                ':pg_payment_id' => $_POST['pg_payment_id'] ?: null,
                ':status' => $_POST['status'],
                ':order_id' => $order_id
            ]);
            
            $_SESSION['admin_success'] = 'Order updated successfully.';
            header('Location: ' . get_app_base_url() . '/admin/orders/view.php?id=' . urlencode($order_id));
            exit;
        } catch (PDOException $e) {
            error_log("Order update error: " . $e->getMessage());
            $_SESSION['admin_error'] = 'Failed to update order.';
        }
    }
}

// Fetch order details
try {
    $sql = "SELECT o.*, 
            p.name as plan_name,
            p.mrp as plan_mrp,
            p.discounted_price as plan_discounted_price,
            p.currency as plan_currency,
            p.billing_period_months,
            u.name as customer_name,
            u.email as customer_email,
            u.phone as customer_phone
            FROM orders o
            LEFT JOIN plans p ON o.plan_id = p.id
            LEFT JOIN users u ON o.customer_id = u.id
            WHERE o.id = :order_id";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':order_id' => $order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        $_SESSION['admin_error'] = 'Order not found.';
        header('Location: ' . get_app_base_url() . '/admin/orders.php');
        exit;
    }
    
    // Try to find associated subscription
    $sub_sql = "SELECT id, status FROM subscriptions WHERE customer_id = :customer_id AND plan_id = :plan_id ORDER BY created_at DESC LIMIT 1";
    $sub_stmt = $db->prepare($sub_sql);
    $sub_stmt->execute([
        ':customer_id' => $order['customer_id'],
        ':plan_id' => $order['plan_id']
    ]);
    $subscription = $sub_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($subscription) {
        $order['subscription_id'] = $subscription['id'];
        $order['subscription_status'] = $subscription['status'];
    }
} catch (PDOException $e) {
    error_log("Order fetch error: " . $e->getMessage());
    $_SESSION['admin_error'] = 'Failed to load order.';
    header('Location: ' . get_app_base_url() . '/admin/orders.php');
    exit;
}

$csrf_token = getCsrfToken();

include_admin_header('Order Details');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <div class="breadcrumb">
            <a href="<?php echo get_app_base_url(); ?>/admin/orders.php" class="breadcrumb-link">Orders</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">Order #<?php echo htmlspecialchars(substr($order['id'], 0, 8)); ?></span>
        </div>
        <h1 class="admin-page-title">Order Details</h1>
        <p class="admin-page-description">Created on <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
    </div>
    <div class="admin-page-header-actions">
        <?php if ($order['status'] === 'SUCCESS'): ?>
        <a href="<?php echo get_app_base_url(); ?>/admin/invoices/view.php?order_id=<?php echo urlencode($order['id']); ?>" 
           class="btn btn-primary">
            View Invoice
        </a>
        <?php endif; ?>
        <a href="<?php echo get_app_base_url(); ?>/admin/customers/view.php?id=<?php echo urlencode($order['customer_id']); ?>" 
           class="btn btn-secondary">
            View Customer
        </a>
    </div>
</div>

<!-- Flash Messages -->
<?php if (isset($_SESSION['admin_success'])): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($_SESSION['admin_success']); ?>
        <?php unset($_SESSION['admin_success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['admin_error'])): ?>
    <div class="alert alert-error">
        <?php echo htmlspecialchars($_SESSION['admin_error']); ?>
        <?php unset($_SESSION['admin_error']); ?>
    </div>
<?php endif; ?>

<div class="order-details-grid">
    <!-- Customer Information -->
    <div class="admin-card">
        <div class="card-header">
            <h2 class="card-title">Customer Information</h2>
        </div>
        <div class="card-body">
            <div class="detail-row">
                <span class="detail-label">Name</span>
                <span class="detail-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Email</span>
                <span class="detail-value">
                    <a href="mailto:<?php echo htmlspecialchars($order['customer_email']); ?>" class="detail-link">
                        <?php echo htmlspecialchars($order['customer_email']); ?>
                    </a>
                </span>
            </div>
            <?php if ($order['customer_phone']): ?>
            <div class="detail-row">
                <span class="detail-label">Phone</span>
                <span class="detail-value">
                    <a href="tel:<?php echo htmlspecialchars($order['customer_phone']); ?>" class="detail-link">
                        <?php echo htmlspecialchars($order['customer_phone']); ?>
                    </a>
                </span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Plan Information -->
    <div class="admin-card">
        <div class="card-header">
            <h2 class="card-title">Plan Information</h2>
        </div>
        <div class="card-body">
            <div class="detail-row">
                <span class="detail-label">Plan Name</span>
                <span class="detail-value"><?php echo htmlspecialchars($order['plan_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Plan Price</span>
                <span class="detail-value">
                    <?php 
                    $planPrice = !empty($order['plan_discounted_price']) ? $order['plan_discounted_price'] : $order['plan_mrp'];
                    echo format_price($planPrice); 
                    ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Billing Period</span>
                <span class="detail-value">
                    <?php echo $order['billing_period_months']; ?> month<?php echo $order['billing_period_months'] > 1 ? 's' : ''; ?>
                </span>
            </div>
            <?php if ($order['subscription_id']): ?>
            <div class="detail-row">
                <span class="detail-label">Subscription</span>
                <span class="detail-value">
                    <code class="code-inline"><?php echo htmlspecialchars(substr($order['subscription_id'], 0, 8)); ?></code>
                    <?php echo get_status_badge($order['subscription_status']); ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Editable Order Details -->
<div class="admin-card">
    <div class="card-header">
        <h2 class="card-title">Order Details</h2>
        <button type="button" id="unlockBtn" class="btn btn-sm btn-warning" onclick="toggleEditMode()">
            Unlock to Edit
        </button>
    </div>
    <div class="card-body">
        <div class="restricted-zone" id="restrictedZone">
            <form method="POST" id="orderForm" class="order-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="update_order">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="order_id" class="form-label">Order ID</label>
                        <input type="text" id="order_id" class="form-input" 
                               value="<?php echo htmlspecialchars($order['id']); ?>" 
                               disabled>
                    </div>
                    
                    <div class="form-group">
                        <label for="status" class="form-label">Payment Status *</label>
                        <select id="status" name="status" class="form-input" required disabled>
                            <option value="PENDING" <?php echo $order['status'] === 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                            <option value="SUCCESS" <?php echo $order['status'] === 'SUCCESS' ? 'selected' : ''; ?>>Success</option>
                            <option value="FAILED" <?php echo $order['status'] === 'FAILED' ? 'selected' : ''; ?>>Failed</option>
                            <option value="CANCELLED" <?php echo $order['status'] === 'CANCELLED' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="amount" class="form-label">Amount *</label>
                        <input type="number" id="amount" name="amount" class="form-input" 
                               value="<?php echo htmlspecialchars($order['amount']); ?>" 
                               step="0.01" min="0" required disabled>
                    </div>
                    
                    <div class="form-group">
                        <label for="currency" class="form-label">Currency *</label>
                        <select id="currency" name="currency" class="form-input" required disabled>
                            <option value="INR" <?php echo $order['currency'] === 'INR' ? 'selected' : ''; ?>>INR</option>
                            <option value="USD" <?php echo $order['currency'] === 'USD' ? 'selected' : ''; ?>>USD</option>
                            <option value="EUR" <?php echo $order['currency'] === 'EUR' ? 'selected' : ''; ?>>EUR</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <input type="text" id="payment_method" name="payment_method" class="form-input" 
                               value="<?php echo htmlspecialchars($order['payment_method'] ?? ''); ?>" 
                               placeholder="e.g., card, upi, netbanking" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label for="pg_order_id" class="form-label">Payment Gateway Order ID</label>
                        <input type="text" id="pg_order_id" name="pg_order_id" class="form-input" 
                               value="<?php echo htmlspecialchars($order['pg_order_id'] ?? ''); ?>" 
                               placeholder="Order ID from payment gateway" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label for="pg_payment_id" class="form-label">Payment Gateway Payment ID</label>
                        <input type="text" id="pg_payment_id" name="pg_payment_id" class="form-input" 
                               value="<?php echo htmlspecialchars($order['pg_payment_id'] ?? ''); ?>" 
                               placeholder="Payment ID from payment gateway" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label for="created_at" class="form-label">Created At</label>
                        <input type="text" id="created_at" class="form-input" 
                               value="<?php echo date('Y-m-d H:i:s', strtotime($order['created_at'])); ?>" 
                               disabled>
                    </div>
                    
                    <div class="form-group">
                        <label for="updated_at" class="form-label">Last Updated</label>
                        <input type="text" id="updated_at" class="form-input" 
                               value="<?php echo $order['updated_at'] ? date('Y-m-d H:i:s', strtotime($order['updated_at'])) : 'Never'; ?>" 
                               disabled>
                    </div>
                </div>
                
                <div class="form-actions" id="formActions" style="display: none;">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <button type="button" class="btn btn-secondary" onclick="cancelEdit()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Invoice Information (only for successful orders) -->
<?php if ($order['status'] === 'SUCCESS'): ?>
<?php
$invoiceService = new InvoiceService();
$invoiceData = $invoiceService->getInvoiceData($order['id']);
?>
<?php if ($invoiceData): ?>
<div class="admin-card">
    <div class="card-header">
        <h2 class="card-title">Invoice Details</h2>
        <a href="<?php echo get_app_base_url(); ?>/admin/invoices/view.php?order_id=<?php echo urlencode($order['id']); ?>" 
           class="btn btn-sm btn-primary">
            View Full Invoice
        </a>
    </div>
    <div class="card-body">
        <div class="invoice-summary-grid">
            <div class="detail-row">
                <span class="detail-label">Invoice Number</span>
                <span class="detail-value">
                    <code class="code-inline"><?php echo htmlspecialchars($invoiceData['invoice_number']); ?></code>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Invoice Date</span>
                <span class="detail-value"><?php echo htmlspecialchars($invoiceData['invoice_date']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Subtotal (Net)</span>
                <span class="detail-value"><?php echo htmlspecialchars($invoiceData['currency_symbol']); ?><?php echo number_format($invoiceData['subtotal'], 2); ?></span>
            </div>
            <?php if ($invoiceData['tax'] > 0): ?>
            <div class="detail-row">
                <span class="detail-label">
                    <?php echo !empty($invoiceData['tax_name']) ? htmlspecialchars($invoiceData['tax_name']) : 'Tax'; ?>
                    <?php if ($invoiceData['tax_percent'] > 0): ?>(<?php echo number_format($invoiceData['tax_percent'], 2); ?>%)<?php endif; ?>
                </span>
                <span class="detail-value"><?php echo htmlspecialchars($invoiceData['currency_symbol']); ?><?php echo number_format($invoiceData['tax'], 2); ?></span>
            </div>
            <?php endif; ?>
            <div class="detail-row detail-row-total">
                <span class="detail-label">Total Paid</span>
                <span class="detail-value detail-value-total"><?php echo htmlspecialchars($invoiceData['currency_symbol']); ?><?php echo number_format($invoiceData['total'], 2); ?></span>
            </div>
        </div>
        
        <div class="invoice-billing-info">
            <h4>Billing Address</h4>
            <div class="billing-address-content">
                <strong><?php echo htmlspecialchars($invoiceData['customer']['name']); ?></strong>
                <?php if ($invoiceData['customer']['business_name']): ?>
                    <br><?php echo htmlspecialchars($invoiceData['customer']['business_name']); ?>
                <?php endif; ?>
                <?php if ($invoiceData['customer']['address_line1']): ?>
                    <br><?php echo htmlspecialchars($invoiceData['customer']['address_line1']); ?>
                    <?php if ($invoiceData['customer']['address_line2']): ?>
                        <br><?php echo htmlspecialchars($invoiceData['customer']['address_line2']); ?>
                    <?php endif; ?>
                    <?php if ($invoiceData['customer']['city'] || $invoiceData['customer']['state'] || $invoiceData['customer']['postal_code']): ?>
                        <br><?php echo htmlspecialchars(trim($invoiceData['customer']['city'] . ', ' . $invoiceData['customer']['state'] . ' ' . $invoiceData['customer']['postal_code'], ', ')); ?>
                    <?php endif; ?>
                    <?php if ($invoiceData['customer']['country']): ?>
                        <br><?php echo htmlspecialchars($invoiceData['customer']['country']); ?>
                    <?php endif; ?>
                <?php endif; ?>
                <br><?php echo htmlspecialchars($invoiceData['customer']['email']); ?>
                <?php if ($invoiceData['customer']['phone']): ?>
                    <br>Tel: <?php echo htmlspecialchars($invoiceData['customer']['phone']); ?>
                <?php endif; ?>
                <?php if ($invoiceData['customer']['tax_id']): ?>
                    <br>Tax ID: <?php echo htmlspecialchars($invoiceData['customer']['tax_id']); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Additional Information -->
<div class="admin-card">
    <div class="card-header">
        <h2 class="card-title">Additional Information</h2>
    </div>
    <div class="card-body">
        <div class="info-notice">
            <strong>Note:</strong> Order details can be edited manually in case of payment gateway failures or manual payments received through other methods. 
            Always verify payment details before making changes.
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

.admin-page-header-actions {
    display: flex;
    gap: var(--spacing-3);
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

.admin-card {
    margin-bottom: var(--spacing-6);
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

.order-details-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--spacing-6);
    margin-bottom: var(--spacing-6);
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
}

.detail-value {
    font-size: var(--font-size-base);
    color: var(--color-gray-900);
    text-align: right;
}

.detail-link {
    color: var(--color-primary);
    text-decoration: none;
}

.detail-link:hover {
    text-decoration: underline;
}

.code-inline {
    background-color: var(--color-gray-100);
    padding: 2px 6px;
    border-radius: var(--radius-sm);
    font-family: 'Courier New', monospace;
    font-size: var(--font-size-sm);
    color: var(--color-gray-800);
    margin-right: var(--spacing-2);
}

.restricted-zone {
    position: relative;
}

.order-form {
    position: relative;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--spacing-4);
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
}

.form-label {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
}

.form-input {
    padding: var(--spacing-2) var(--spacing-3);
    border: 1px solid var(--color-gray-300);
    border-radius: var(--radius-md);
    font-size: var(--font-size-base);
    color: var(--color-gray-900);
    background: white;
}

.form-input:disabled {
    background: var(--color-gray-50);
    color: var(--color-gray-500);
    cursor: not-allowed;
}

.form-input:focus:not(:disabled) {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-actions {
    display: flex;
    gap: var(--spacing-3);
    margin-top: var(--spacing-6);
    padding-top: var(--spacing-4);
    border-top: 1px solid var(--color-gray-200);
}

.info-notice {
    padding: var(--spacing-4);
    background: var(--color-blue-50);
    border-left: 4px solid var(--color-primary);
    border-radius: var(--radius-md);
    color: var(--color-gray-700);
    font-size: var(--font-size-sm);
    line-height: 1.6;
}

.invoice-summary-grid {
    max-width: 400px;
}

.detail-row-total {
    border-top: 2px solid var(--color-gray-300);
    margin-top: var(--spacing-2);
    padding-top: var(--spacing-3);
}

.detail-value-total {
    font-weight: var(--font-weight-bold);
    font-size: var(--font-size-lg);
    color: var(--color-gray-900);
}

.invoice-billing-info {
    margin-top: var(--spacing-6);
    padding-top: var(--spacing-4);
    border-top: 1px solid var(--color-gray-200);
}

.invoice-billing-info h4 {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin: 0 0 var(--spacing-3) 0;
}

.billing-address-content {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    line-height: 1.6;
}

.alert {
    padding: var(--spacing-4);
    border-radius: var(--radius-md);
    margin-bottom: var(--spacing-4);
    font-size: var(--font-size-base);
}

.alert-success {
    background-color: #d1fae5;
    border: 1px solid #10b981;
    color: #065f46;
}

.alert-error {
    background-color: #fee2e2;
    border: 1px solid #ef4444;
    color: #991b1b;
}

@media (max-width: 768px) {
    .admin-page-header {
        flex-direction: column;
    }
    
    .order-details-grid {
        grid-template-columns: 1fr;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
let isEditMode = false;

function toggleEditMode() {
    isEditMode = !isEditMode;
    const unlockBtn = document.getElementById('unlockBtn');
    const formActions = document.getElementById('formActions');
    const formInputs = document.querySelectorAll('#orderForm input:not([type="hidden"]), #orderForm select');
    
    if (isEditMode) {
        unlockBtn.innerHTML = 'Lock';
        unlockBtn.classList.remove('btn-warning');
        unlockBtn.classList.add('btn-secondary');
        formActions.style.display = 'flex';
        
        // Enable editable fields (not order_id, created_at, updated_at)
        formInputs.forEach(input => {
            if (input.id !== 'order_id' && input.id !== 'created_at' && input.id !== 'updated_at') {
                input.disabled = false;
            }
        });
    } else {
        unlockBtn.innerHTML = 'Unlock to Edit';
        unlockBtn.classList.remove('btn-secondary');
        unlockBtn.classList.add('btn-warning');
        formActions.style.display = 'none';
        
        // Disable editable fields (order_id, created_at, updated_at stay disabled)
        formInputs.forEach(input => {
            if (input.id !== 'order_id' && input.id !== 'created_at' && input.id !== 'updated_at') {
                input.disabled = true;
            }
        });
    }
}

function cancelEdit() {
    // Reload the page to reset form
    window.location.reload();
}

// Confirm before leaving if in edit mode
window.addEventListener('beforeunload', function(e) {
    if (isEditMode) {
        e.preventDefault();
        e.returnValue = '';
    }
});
</script>

<?php include_admin_footer(); ?>
