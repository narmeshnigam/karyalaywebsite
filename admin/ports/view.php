<?php
/**
 * Admin Port Details Page
 * View port details, assignment information, and allocation history
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Models\User;
use Karyalay\Models\PortAllocationLog;

startSecureSession();
require_admin();
require_permission('ports.view_details');

$db = \Karyalay\Database\Connection::getInstance();
$userModel = new User();
$logModel = new PortAllocationLog();
$port_id = $_GET['id'] ?? '';

if (empty($port_id)) {
    $_SESSION['admin_error'] = 'Port ID is required.';
    header('Location: ' . get_app_base_url() . '/admin/ports.php');
    exit;
}

// Fetch port details with assignment info - get customer via subscription
try {
    $sql = "SELECT p.*, 
            u.name as customer_name,
            u.email as customer_email,
            u.phone as customer_phone,
            s.id as subscription_id,
            s.customer_id as customer_id,
            s.status as subscription_status,
            s.start_date as subscription_start,
            s.end_date as subscription_end,
            pl.name as plan_name
            FROM ports p
            LEFT JOIN subscriptions s ON p.assigned_subscription_id = s.id
            LEFT JOIN users u ON s.customer_id = u.id
            LEFT JOIN plans pl ON s.plan_id = pl.id
            WHERE p.id = :port_id";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':port_id' => $port_id]);
    $port = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$port) {
        $_SESSION['admin_error'] = 'Port not found.';
        header('Location: ' . get_app_base_url() . '/admin/ports.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Port fetch error: " . $e->getMessage());
    $_SESSION['admin_error'] = 'Failed to load port.';
    header('Location: ' . get_app_base_url() . '/admin/ports.php');
    exit;
}

// Check for subscription that still links to this port
$linkedSubscription = null;
try {
    $linked_sql = "SELECT s.*, p.name as plan_name, u.name as customer_name, u.email as customer_email 
                   FROM subscriptions s 
                   LEFT JOIN plans p ON s.plan_id = p.id 
                   LEFT JOIN users u ON s.customer_id = u.id 
                   WHERE s.assigned_port_id = :port_id";
    $linked_stmt = $db->prepare($linked_sql);
    $linked_stmt->execute([':port_id' => $port_id]);
    $linkedSubscription = $linked_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Failed to fetch linked subscription: " . $e->getMessage());
}

// Fetch allocation logs
$allocationLogs = $logModel->findByPortId($port_id, 20);
foreach ($allocationLogs as &$log) {
    if ($log['performed_by']) {
        $performer = $userModel->findById($log['performed_by']);
        $log['performer_name'] = $performer ? $performer['name'] : 'Unknown';
    }
    if ($log['customer_id']) {
        $customer = $userModel->findById($log['customer_id']);
        $log['customer_name'] = $customer ? $customer['name'] : 'Unknown';
    }
}
unset($log);

// Check for inconsistencies
$hasInconsistency = false;
$inconsistencyMessage = '';
if ($linkedSubscription && $port['status'] !== 'ASSIGNED') {
    $hasInconsistency = true;
    $inconsistencyMessage = 'This port has status "' . $port['status'] . '" but is still linked to a subscription.';
}
if ($port['status'] === 'ASSIGNED' && !$linkedSubscription) {
    $hasInconsistency = true;
    $inconsistencyMessage = 'This port has status "ASSIGNED" but no subscription is linked to it.';
}

include_admin_header('Port Details');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <div class="breadcrumb">
            <a href="<?php echo get_app_base_url(); ?>/admin/ports.php" class="breadcrumb-link">Ports</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?php echo htmlspecialchars($port['instance_url']); ?></span>
        </div>
        <h1 class="admin-page-title">Port Details</h1>
        <p class="admin-page-description"><?php echo get_status_badge($port['status']); ?></p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/ports/edit.php?id=<?php echo urlencode($port_id); ?>" class="btn btn-primary">Edit Port</a>
        <a href="<?php echo get_app_base_url(); ?>/admin/ports.php" class="btn btn-secondary">← Back</a>
    </div>
</div>

<?php if ($hasInconsistency): ?>
<div class="alert alert-warning">
    <strong>⚠️ Assignment Inconsistency:</strong> <?php echo htmlspecialchars($inconsistencyMessage); ?>
    <a href="<?php echo get_app_base_url(); ?>/admin/ports/edit.php?id=<?php echo urlencode($port_id); ?>">Edit port to resolve</a>
</div>
<?php endif; ?>

<div class="port-details-grid">
    <!-- Instance Information -->
    <div class="admin-card">
        <div class="card-header">
            <h2 class="card-title">Instance Information</h2>
        </div>
        <div class="card-body">
            <div class="detail-row">
                <span class="detail-label">Instance URL</span>
                <span class="detail-value">
                    <a href="<?php echo htmlspecialchars($port['instance_url']); ?>" target="_blank" class="detail-link">
                        <?php echo htmlspecialchars($port['instance_url']); ?> ↗
                    </a>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Server Region</span>
                <span class="detail-value"><?php echo $port['server_region'] ? htmlspecialchars($port['server_region']) : '<em class="text-muted">Not specified</em>'; ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value"><?php echo get_status_badge($port['status']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Port ID</span>
                <span class="detail-value"><code class="code-inline"><?php echo htmlspecialchars($port['id']); ?></code></span>
            </div>
        </div>
    </div>

    <!-- Database Configuration -->
    <div class="admin-card">
        <div class="card-header">
            <h2 class="card-title">Database Configuration</h2>
        </div>
        <div class="card-body">
            <div class="detail-row">
                <span class="detail-label">Database Host</span>
                <span class="detail-value"><code class="code-inline"><?php echo $port['db_host'] ? htmlspecialchars($port['db_host']) : '—'; ?></code></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Database Name</span>
                <span class="detail-value"><code class="code-inline"><?php echo $port['db_name'] ? htmlspecialchars($port['db_name']) : '—'; ?></code></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Database Username</span>
                <span class="detail-value"><code class="code-inline"><?php echo $port['db_username'] ? htmlspecialchars($port['db_username']) : '—'; ?></code></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Database Password</span>
                <span class="detail-value">
                    <?php if ($port['db_password']): ?>
                        <code class="code-inline password-hidden">••••••••</code>
                        <button type="button" class="btn btn-sm btn-text" onclick="togglePassword(this, '<?php echo htmlspecialchars($port['db_password']); ?>')">Show</button>
                    <?php else: ?>
                        <em class="text-muted">Not set</em>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Assignment & Subscription Link Section -->
<div class="admin-card">
    <div class="card-header">
        <h2 class="card-title">Assignment & Subscription Link</h2>
    </div>
    <div class="card-body">
        <div class="assignment-grid">
            <!-- Port Assignment (from port record via subscription) -->
            <div class="assignment-section">
                <h3 class="section-subtitle">Port Assignment Status</h3>
                <?php if ($port['assigned_subscription_id'] && $port['customer_id']): ?>
                    <div class="detail-row">
                        <span class="detail-label">Assigned Customer</span>
                        <span class="detail-value">
                            <a href="<?php echo get_app_base_url(); ?>/admin/customers/view.php?id=<?php echo urlencode($port['customer_id']); ?>" class="detail-link">
                                <?php echo htmlspecialchars($port['customer_name']); ?>
                            </a>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Customer Email</span>
                        <span class="detail-value"><?php echo htmlspecialchars($port['customer_email']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Assigned At</span>
                        <span class="detail-value"><?php echo $port['assigned_at'] ? date('M j, Y g:i A', strtotime($port['assigned_at'])) : '—'; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Subscription</span>
                        <span class="detail-value">
                            <a href="<?php echo get_app_base_url(); ?>/admin/subscriptions.php?id=<?php echo urlencode($port['subscription_id']); ?>">
                                <?php echo htmlspecialchars(substr($port['subscription_id'], 0, 8)); ?>...
                            </a>
                            <?php echo get_status_badge($port['subscription_status']); ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Plan</span>
                        <span class="detail-value"><?php echo htmlspecialchars($port['plan_name'] ?? 'Unknown'); ?></span>
                    </div>
                <?php else: ?>
                    <div class="empty-state-small">
                        <p class="empty-state-text">Port is not assigned to any subscription.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Subscription Link (from subscription record) -->
            <div class="assignment-section">
                <h3 class="section-subtitle">Subscription Link Status</h3>
                <?php if ($linkedSubscription): ?>
                    <div class="detail-row">
                        <span class="detail-label">Linked Subscription</span>
                        <span class="detail-value">
                            <a href="<?php echo get_app_base_url(); ?>/admin/subscriptions.php?id=<?php echo urlencode($linkedSubscription['id']); ?>">
                                <?php echo htmlspecialchars(substr($linkedSubscription['id'], 0, 8)); ?>...
                            </a>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Customer</span>
                        <span class="detail-value"><?php echo htmlspecialchars($linkedSubscription['customer_name'] ?? 'Unknown'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Plan</span>
                        <span class="detail-value"><?php echo htmlspecialchars($linkedSubscription['plan_name'] ?? 'Unknown'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Subscription Status</span>
                        <span class="detail-value"><?php echo get_status_badge($linkedSubscription['status']); ?></span>
                    </div>
                    <?php if ($port['status'] !== 'ASSIGNED'): ?>
                    <div class="link-warning">
                        <p><strong>Note:</strong> Customer can still see this port in their dashboard because the subscription references it.</p>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state-small">
                        <p class="empty-state-text">No subscription links to this port.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Setup Instructions & Notes -->
<?php if ($port['setup_instructions'] || $port['notes']): ?>
<div class="admin-card">
    <div class="card-header">
        <h2 class="card-title">Additional Information</h2>
    </div>
    <div class="card-body">
        <?php if ($port['setup_instructions']): ?>
        <div class="info-section">
            <h3 class="section-subtitle">Setup Instructions</h3>
            <div class="rich-content"><?php echo $port['setup_instructions']; ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($port['notes']): ?>
        <div class="info-section">
            <h3 class="section-subtitle">Internal Notes</h3>
            <p class="notes-text"><?php echo nl2br(htmlspecialchars($port['notes'])); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Allocation History -->
<div class="admin-card">
    <div class="card-header">
        <h2 class="card-title">Allocation History</h2>
    </div>
    <?php if (empty($allocationLogs)): ?>
        <div class="card-body">
            <div class="empty-state-small">
                <p class="empty-state-text">No allocation history recorded.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Action</th>
                        <th>Customer</th>
                        <th>Performed By</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allocationLogs as $log): ?>
                        <tr>
                            <td><?php echo date('M j, Y g:i A', strtotime($log['timestamp'])); ?></td>
                            <td>
                                <?php 
                                $actionBadges = [
                                    'ASSIGNED' => 'success', 'REASSIGNED' => 'info', 'RELEASED' => 'warning',
                                    'UNASSIGNED' => 'warning', 'CREATED' => 'secondary', 'DISABLED' => 'danger',
                                    'ENABLED' => 'success', 'RESERVED' => 'info', 'MADE_AVAILABLE' => 'success',
                                    'STATUS_CHANGED' => 'secondary'
                                ];
                                $badgeClass = $actionBadges[$log['action']] ?? 'secondary';
                                ?>
                                <span class="badge badge-<?php echo $badgeClass; ?>"><?php echo htmlspecialchars($log['action']); ?></span>
                            </td>
                            <td><?php echo !empty($log['customer_name']) ? htmlspecialchars($log['customer_name']) : '<span class="text-muted">—</span>'; ?></td>
                            <td><?php echo !empty($log['performer_name']) ? htmlspecialchars($log['performer_name']) : '<span class="text-muted">System</span>'; ?></td>
                            <td><?php echo !empty($log['notes']) ? htmlspecialchars($log['notes']) : '<span class="text-muted">—</span>'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Timestamps -->
<div class="admin-card">
    <div class="card-header">
        <h2 class="card-title">Timestamps</h2>
    </div>
    <div class="card-body">
        <div class="detail-row">
            <span class="detail-label">Created</span>
            <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($port['created_at'])); ?></span>
        </div>
        <?php if ($port['updated_at']): ?>
        <div class="detail-row">
            <span class="detail-label">Last Updated</span>
            <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($port['updated_at'])); ?></span>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.admin-page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:var(--spacing-6);gap:var(--spacing-4)}
.admin-page-header-content{flex:1}
.admin-page-header-actions{display:flex;gap:var(--spacing-3)}
.breadcrumb{display:flex;align-items:center;gap:var(--spacing-2);margin-bottom:var(--spacing-3);font-size:var(--font-size-sm)}
.breadcrumb-link{color:var(--color-primary);text-decoration:none}
.breadcrumb-link:hover{text-decoration:underline}
.breadcrumb-separator{color:var(--color-gray-400)}
.breadcrumb-current{color:var(--color-gray-600)}
.admin-page-title{font-size:var(--font-size-2xl);font-weight:var(--font-weight-bold);color:var(--color-gray-900);margin:0 0 var(--spacing-2) 0}
.admin-page-description{font-size:var(--font-size-base);color:var(--color-gray-600);margin:0}
.admin-card{margin-bottom:var(--spacing-6);background:white;border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);border:1px solid var(--color-gray-200)}
.card-header{display:flex;justify-content:space-between;align-items:center;padding:var(--spacing-4) var(--spacing-5);border-bottom:1px solid var(--color-gray-200)}
.card-title{font-size:var(--font-size-lg);font-weight:var(--font-weight-semibold);color:var(--color-gray-900);margin:0}
.card-body{padding:var(--spacing-5)}
.port-details-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:var(--spacing-6);margin-bottom:var(--spacing-6)}
.detail-row{display:flex;justify-content:space-between;align-items:flex-start;padding:var(--spacing-3) 0;border-bottom:1px solid var(--color-gray-100)}
.detail-row:last-child{border-bottom:none}
.detail-label{font-size:var(--font-size-sm);font-weight:var(--font-weight-medium);color:var(--color-gray-600)}
.detail-value{font-size:var(--font-size-base);color:var(--color-gray-900);text-align:right}
.detail-link{color:var(--color-primary);text-decoration:none}
.detail-link:hover{text-decoration:underline}
.code-inline{background-color:var(--color-gray-100);padding:2px 6px;border-radius:var(--radius-sm);font-family:'Courier New',monospace;font-size:var(--font-size-sm);color:var(--color-gray-800)}
.assignment-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:var(--spacing-6)}
.assignment-section{padding:var(--spacing-4);background:var(--color-gray-50);border-radius:var(--radius-md)}
.section-subtitle{font-size:var(--font-size-base);font-weight:var(--font-weight-semibold);color:var(--color-gray-800);margin:0 0 var(--spacing-4) 0}
.info-section{margin-bottom:var(--spacing-6)}
.info-section:last-child{margin-bottom:0}
.rich-content{line-height:1.6;color:var(--color-gray-700)}
.notes-text{color:var(--color-gray-700);line-height:1.6;margin:0}
.empty-state-small{padding:var(--spacing-4);text-align:center}
.empty-state-text{color:var(--color-gray-500);font-style:italic;margin:0}
.password-hidden{letter-spacing:2px}
.text-muted{color:var(--color-gray-500);font-style:italic}
.alert{padding:var(--spacing-4);border-radius:var(--radius-md);margin-bottom:var(--spacing-4)}
.alert-warning{background-color:#fef3c7;border:1px solid #f59e0b;color:#92400e}
.alert-warning a{color:#92400e;font-weight:var(--font-weight-semibold)}
.link-warning{background:#fef3c7;border:1px solid #f59e0b;border-radius:var(--radius-md);padding:var(--spacing-3);margin-top:var(--spacing-4)}
.link-warning p{margin:0;font-size:var(--font-size-sm);color:#92400e}
@media(max-width:768px){.admin-page-header{flex-direction:column}.port-details-grid,.assignment-grid{grid-template-columns:1fr}}
</style>

<script>
function togglePassword(btn, password) {
    const codeEl = btn.previousElementSibling;
    if (codeEl.textContent === '••••••••') {
        codeEl.textContent = password;
        codeEl.classList.remove('password-hidden');
        btn.textContent = 'Hide';
    } else {
        codeEl.textContent = '••••••••';
        codeEl.classList.add('password-hidden');
        btn.textContent = 'Show';
    }
}
</script>

<?php include_admin_footer(); ?>
