<?php
/**
 * Admin Edit Port Page
 * Organized sections for port details, assignment, and allocation history
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Services\PortService;
use Karyalay\Services\CsrfService;
use Karyalay\Models\User;
use Karyalay\Models\Subscription;
use Karyalay\Models\PortAllocationLog;

startSecureSession();
require_admin();
require_permission('ports.edit');

$portService = new PortService();
$csrfService = new CsrfService();
$userModel = new User();
$subscriptionModel = new Subscription();
$logModel = new PortAllocationLog();

$portId = $_GET['id'] ?? '';

if (empty($portId)) {
    $_SESSION['admin_error'] = 'Port ID is required.';
    header('Location: ' . get_app_base_url() . '/admin/ports.php');
    exit;
}

$portResult = $portService->getPort($portId);

if (!$portResult['success']) {
    $_SESSION['admin_error'] = 'Port not found.';
    header('Location: ' . get_app_base_url() . '/admin/ports.php');
    exit;
}

$port = $portResult['port'];
$db = \Karyalay\Database\Connection::getInstance();

// Fetch all customers for reassignment dropdown
$allCustomers = $userModel->findAll(['role' => 'CUSTOMER']);

// Fetch subscription linked to this port (via assigned_subscription_id on port)
$assignedSubscription = null;
$linkedSubscription = null;

if ($port['assigned_subscription_id']) {
    $assignedSubscription = $subscriptionModel->findById($port['assigned_subscription_id']);
}

// Also check if any subscription still has this port linked (even if port is unassigned)
try {
    $linked_sql = "SELECT s.*, p.name as plan_name, u.name as customer_name, u.email as customer_email 
                   FROM subscriptions s 
                   LEFT JOIN plans p ON s.plan_id = p.id 
                   LEFT JOIN users u ON s.customer_id = u.id 
                   WHERE s.assigned_port_id = :port_id";
    $linked_stmt = $db->prepare($linked_sql);
    $linked_stmt->execute([':port_id' => $portId]);
    $linkedSubscription = $linked_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Failed to fetch linked subscription: " . $e->getMessage());
}

// Fetch allocation logs for this port
$allocationLogs = $logModel->findByPortId($portId, 50);

// Enrich logs with user names
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

$errors = [];
$success = false;
$formData = [
    'instance_url' => $port['instance_url'],
    'db_host' => $port['db_host'] ?? '',
    'db_name' => $port['db_name'] ?? '',
    'db_username' => $port['db_username'] ?? '',
    'db_password' => $port['db_password'] ?? '',
    'status' => $port['status'],
    'server_region' => $port['server_region'] ?? '',
    'notes' => $port['notes'] ?? '',
    'setup_instructions' => $port['setup_instructions'] ?? '',
    'assign_subscription_id' => $port['assigned_subscription_id'] ?? ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCsrfToken()) {
        $_SESSION['admin_error'] = 'Invalid security token.';
        header('Location: ' . get_app_base_url() . '/admin/ports/edit.php?id=' . urlencode($portId));
        exit;
    }
    
    if ($_POST['action'] === 'update_port') {
        $formData = [
            'instance_url' => trim($_POST['instance_url'] ?? ''),
            'db_host' => trim($_POST['db_host'] ?? ''),
            'db_name' => trim($_POST['db_name'] ?? ''),
            'db_username' => trim($_POST['db_username'] ?? ''),
            'db_password' => trim($_POST['db_password'] ?? ''),
            'status' => trim($_POST['status'] ?? 'AVAILABLE'),
            'server_region' => trim($_POST['server_region'] ?? ''),
            'notes' => trim($_POST['notes'] ?? ''),
            'setup_instructions' => $_POST['setup_instructions'] ?? '',
            'assign_subscription_id' => trim($_POST['assign_subscription_id'] ?? '')
        ];

        if (empty($formData['instance_url'])) {
            $errors[] = 'Instance URL is required.';
        }

        $oldStatus = $port['status'];
        $newStatus = $formData['status'];
        $newSubscriptionId = $formData['assign_subscription_id'];
        $oldSubscriptionId = $port['assigned_subscription_id'];

        // Check if assignment is requested when status is ASSIGNED
        $isNewAssignment = ($newStatus === 'ASSIGNED' && !empty($newSubscriptionId) && $newSubscriptionId !== $oldSubscriptionId);
        $isReassignment = ($oldStatus === 'ASSIGNED' && $newStatus === 'ASSIGNED' && !empty($newSubscriptionId) && $newSubscriptionId !== $oldSubscriptionId);

        if (empty($errors)) {
            $updateData = [
                'instance_url' => $formData['instance_url'],
                'status' => $formData['status']
            ];

            $updateData['db_host'] = !empty($formData['db_host']) ? $formData['db_host'] : null;
            $updateData['db_name'] = !empty($formData['db_name']) ? $formData['db_name'] : null;
            $updateData['db_username'] = !empty($formData['db_username']) ? $formData['db_username'] : null;
            if (!empty($formData['db_password'])) {
                $updateData['db_password'] = $formData['db_password'];
            }
            $updateData['server_region'] = !empty($formData['server_region']) ? $formData['server_region'] : null;
            $updateData['notes'] = !empty($formData['notes']) ? $formData['notes'] : null;
            $updateData['setup_instructions'] = !empty($formData['setup_instructions']) ? $formData['setup_instructions'] : null;

            // Handle new assignment or reassignment when status is ASSIGNED
            if ($newStatus === 'ASSIGNED' && !empty($newSubscriptionId)) {
                // Validate the subscription exists and doesn't already have a port
                $newSubscription = $subscriptionModel->findById($newSubscriptionId);
                
                if (!$newSubscription) {
                    $errors[] = 'Selected subscription not found.';
                } elseif ($newSubscription['assigned_port_id'] && $newSubscription['assigned_port_id'] !== $portId) {
                    $errors[] = 'Selected subscription already has a port assigned.';
                } else {
                    // Unassign from old subscription if reassigning
                    if (!empty($oldSubscriptionId) && $oldSubscriptionId !== $newSubscriptionId) {
                        try {
                            $update_old_sub_sql = "UPDATE subscriptions SET assigned_port_id = NULL WHERE id = :subscription_id";
                            $update_old_sub_stmt = $db->prepare($update_old_sub_sql);
                            $update_old_sub_stmt->execute([':subscription_id' => $oldSubscriptionId]);
                        } catch (PDOException $e) {
                            error_log("Failed to unassign port from old subscription: " . $e->getMessage());
                            $errors[] = 'Failed to unassign port from previous subscription.';
                        }
                    }
                    
                    // Assign to new subscription
                    if (empty($errors)) {
                        try {
                            $update_new_sub_sql = "UPDATE subscriptions SET assigned_port_id = :port_id WHERE id = :subscription_id";
                            $update_new_sub_stmt = $db->prepare($update_new_sub_sql);
                            $update_new_sub_stmt->execute([
                                ':port_id' => $portId,
                                ':subscription_id' => $newSubscriptionId
                            ]);
                        } catch (PDOException $e) {
                            error_log("Failed to assign port to new subscription: " . $e->getMessage());
                            $errors[] = 'Failed to assign port to new subscription.';
                        }
                    }
                    
                    if (empty($errors)) {
                        $updateData['assigned_subscription_id'] = $newSubscriptionId;
                        $updateData['assigned_at'] = date('Y-m-d H:i:s');
                        
                        // Log the assignment/reassignment
                        if ($isReassignment) {
                            $logModel->logReassignment($portId, $newSubscriptionId, $newSubscription['customer_id'], $_SESSION['user_id'] ?? null);
                        } else {
                            $logModel->logAssignment($portId, $newSubscriptionId, $newSubscription['customer_id'], $_SESSION['user_id'] ?? null);
                        }
                    }
                }
            } elseif ($newStatus === 'ASSIGNED' && empty($newSubscriptionId)) {
                // Status is ASSIGNED but no subscription selected
                $errors[] = 'Please select a subscription to assign this port to.';
            }

            // Handle status change from ASSIGNED to something else
            if ($oldStatus === 'ASSIGNED' && $newStatus !== 'ASSIGNED') {
                // Update the subscription to mark port as unassigned
                if (!empty($oldSubscriptionId)) {
                    try {
                        $update_sub_sql = "UPDATE subscriptions SET assigned_port_id = NULL WHERE id = :subscription_id";
                        $update_sub_stmt = $db->prepare($update_sub_sql);
                        $update_sub_stmt->execute([':subscription_id' => $oldSubscriptionId]);
                        
                        // Get customer ID from subscription for logging
                        $oldSubscription = $subscriptionModel->findById($oldSubscriptionId);
                        $oldCustomerId = $oldSubscription['customer_id'] ?? null;
                        
                        // Log the unassignment
                        $logModel->logUnassignment(
                            $portId,
                            $oldSubscriptionId,
                            $oldCustomerId,
                            $_SESSION['user_id'] ?? null
                        );
                    } catch (PDOException $e) {
                        error_log("Failed to update subscription on port status change: " . $e->getMessage());
                        $errors[] = 'Failed to update related subscription. Please try again.';
                    }
                }
                
                $updateData['assigned_subscription_id'] = null;
                $updateData['assigned_at'] = null;
            }
            
            // Log status change if status changed (and not already logged as assignment/reassignment)
            if ($oldStatus !== $newStatus && empty($errors) && !$isNewAssignment && !$isReassignment) {
                $statusAction = match($newStatus) {
                    'DISABLED' => 'DISABLED',
                    'AVAILABLE' => 'MADE_AVAILABLE',
                    'RESERVED' => 'RESERVED',
                    'ASSIGNED' => 'ASSIGNED',
                    default => 'STATUS_CHANGED'
                };
                
                // Get customer ID from subscription for logging
                $logCustomerId = null;
                if (!empty($oldSubscriptionId)) {
                    $oldSubscription = $subscriptionModel->findById($oldSubscriptionId);
                    $logCustomerId = $oldSubscription['customer_id'] ?? null;
                }
                
                $logModel->logStatusChange(
                    $portId,
                    $statusAction,
                    $oldSubscriptionId,
                    $logCustomerId,
                    $_SESSION['user_id'] ?? null,
                    "Status changed from {$oldStatus} to {$newStatus}"
                );
            }

            if (empty($errors)) {
                $result = $portService->updatePort($portId, $updateData);

                if ($result['success']) {
                    $_SESSION['admin_success'] = 'Port updated successfully!';
                    header('Location: ' . get_app_base_url() . '/admin/ports/edit.php?id=' . urlencode($portId));
                    exit;
                } else {
                    $_SESSION['admin_error'] = $result['error'] ?? 'Failed to update port.';
                }
            }
        }
    }
    
    // Handle unlink subscription action
    if ($_POST['action'] === 'unlink_subscription') {
        $subscriptionId = $_POST['subscription_id'] ?? '';
        if (!empty($subscriptionId)) {
            try {
                $update_sub_sql = "UPDATE subscriptions SET assigned_port_id = NULL WHERE id = :subscription_id";
                $update_sub_stmt = $db->prepare($update_sub_sql);
                $update_sub_stmt->execute([':subscription_id' => $subscriptionId]);
                
                // Log the unlink
                $logModel->create([
                    'port_id' => $portId,
                    'subscription_id' => $subscriptionId,
                    'customer_id' => $linkedSubscription['customer_id'] ?? null,
                    'action' => 'RELEASED',
                    'performed_by' => $_SESSION['user_id'] ?? null,
                    'notes' => 'Subscription link removed via admin'
                ]);
                
                $_SESSION['admin_success'] = 'Subscription link removed successfully.';
            } catch (PDOException $e) {
                error_log("Failed to unlink subscription: " . $e->getMessage());
                $_SESSION['admin_error'] = 'Failed to unlink subscription.';
            }
            header('Location: ' . get_app_base_url() . '/admin/ports/edit.php?id=' . urlencode($portId));
            exit;
        }
    }
}

$csrfToken = getCsrfToken();
include_admin_header('Port Details');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <div class="breadcrumb">
            <a href="<?php echo get_app_base_url(); ?>/admin/ports.php" class="breadcrumb-link">Ports</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?php echo htmlspecialchars(substr($port['id'], 0, 8)); ?></span>
        </div>
        <h1 class="admin-page-title">Port Details</h1>
        <p class="admin-page-description">
            <?php echo get_status_badge($port['status']); ?>
            &nbsp;•&nbsp; Created <?php echo date('M j, Y', strtotime($port['created_at'])); ?>
        </p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/ports/view.php?id=<?php echo urlencode($portId); ?>" class="btn btn-secondary">View Details</a>
        <a href="<?php echo get_app_base_url(); ?>/admin/ports.php" class="btn btn-secondary">← Back to Ports</a>
    </div>
</div>

<?php if (isset($_SESSION['admin_success'])): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['admin_success']); unset($_SESSION['admin_success']); ?></div>
<?php endif; ?>

<?php if (isset($_SESSION['admin_error'])): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['admin_error']); unset($_SESSION['admin_error']); ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul style="margin: 0; padding-left: 1.5rem;">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Assignment Status Alert -->
<?php 
$hasInconsistency = false;
if ($linkedSubscription && $port['status'] !== 'ASSIGNED') {
    $hasInconsistency = true;
}
if ($port['status'] === 'ASSIGNED' && !$linkedSubscription) {
    $hasInconsistency = true;
}
?>

<?php if ($hasInconsistency): ?>
<div class="alert alert-warning">
    <strong>⚠️ Assignment Inconsistency Detected</strong><br>
    <?php if ($linkedSubscription && $port['status'] !== 'ASSIGNED'): ?>
        This port has status "<?php echo $port['status']; ?>" but is still linked to subscription 
        <a href="<?php echo get_app_base_url(); ?>/admin/subscriptions.php?id=<?php echo urlencode($linkedSubscription['id']); ?>">
            <?php echo htmlspecialchars(substr($linkedSubscription['id'], 0, 8)); ?>
        </a>.
        Consider unlinking the subscription or changing status to ASSIGNED.
    <?php elseif ($port['status'] === 'ASSIGNED' && !$linkedSubscription): ?>
        This port has status "ASSIGNED" but no subscription is linked to it.
        Consider changing status to AVAILABLE or assigning to a subscription.
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="port-edit-grid">
    <!-- Left Column: Port Information -->
    <div class="port-edit-main">
        <div class="admin-card">
            <div class="card-header">
                <h2 class="card-title">Port Information</h2>
                <button type="button" id="unlockBtn" class="btn btn-sm btn-warning" onclick="toggleEditMode()">Unlock to Edit</button>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo get_app_base_url(); ?>/admin/ports/edit.php?id=<?php echo urlencode($portId); ?>" id="portForm" class="admin-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="update_port">
            
                    <div class="form-section">
                        <h3 class="form-section-title">Basic Information</h3>
                        
                        <div class="form-group">
                            <label for="port_id" class="form-label">Port ID</label>
                            <input type="text" id="port_id" class="form-input" value="<?php echo htmlspecialchars($port['id']); ?>" disabled>
                        </div>

                        <div class="form-group">
                            <label for="instance_url" class="form-label required">Instance URL</label>
                            <input type="text" id="instance_url" name="instance_url" class="form-input" 
                                   value="<?php echo htmlspecialchars($formData['instance_url']); ?>" required disabled
                                   placeholder="e.g., https://instance1.example.com">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="status" class="form-label required">Status</label>
                                <select id="status" name="status" class="form-select" required disabled>
                                    <option value="AVAILABLE" <?php echo $formData['status'] === 'AVAILABLE' ? 'selected' : ''; ?>>Available</option>
                                    <option value="RESERVED" <?php echo $formData['status'] === 'RESERVED' ? 'selected' : ''; ?>>Reserved</option>
                                    <option value="ASSIGNED" <?php echo $formData['status'] === 'ASSIGNED' ? 'selected' : ''; ?>>Assigned</option>
                                    <option value="DISABLED" <?php echo $formData['status'] === 'DISABLED' ? 'selected' : ''; ?>>Disabled</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="server_region" class="form-label">Server Region</label>
                                <input type="text" id="server_region" name="server_region" class="form-input" 
                                       value="<?php echo htmlspecialchars($formData['server_region']); ?>" disabled
                                       placeholder="e.g., US-East">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="notes" class="form-label">Internal Notes</label>
                            <textarea id="notes" name="notes" class="form-textarea" rows="3" disabled
                                      placeholder="Internal notes about this port"><?php echo htmlspecialchars($formData['notes']); ?></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">Database Configuration</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="db_host" class="form-label">Database Host</label>
                                <input type="text" id="db_host" name="db_host" class="form-input" 
                                       value="<?php echo htmlspecialchars($formData['db_host']); ?>" disabled
                                       placeholder="e.g., localhost">
                            </div>
                            <div class="form-group">
                                <label for="db_name" class="form-label">Database Name</label>
                                <input type="text" id="db_name" name="db_name" class="form-input" 
                                       value="<?php echo htmlspecialchars($formData['db_name']); ?>" disabled
                                       placeholder="e.g., app_db">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="db_username" class="form-label">Database Username</label>
                                <input type="text" id="db_username" name="db_username" class="form-input" 
                                       value="<?php echo htmlspecialchars($formData['db_username']); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label for="db_password" class="form-label">Database Password</label>
                                <input type="password" id="db_password" name="db_password" class="form-input" disabled
                                       placeholder="<?php echo !empty($formData['db_password']) ? '••••••••' : 'Enter password'; ?>">
                                <p class="form-help">Leave blank to keep existing password</p>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">Setup Instructions</h3>
                        <p class="form-section-description">Instructions shown to customers on their "My Port" page.</p>
                        
                        <div class="form-group">
                            <div id="setup_instructions_editor" class="rich-text-editor"><?php echo $formData['setup_instructions']; ?></div>
                            <input type="hidden" name="setup_instructions" id="setup_instructions_input" value="<?php echo htmlspecialchars($formData['setup_instructions']); ?>">
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

    <!-- Right Column: Assignment & Links -->
    <div class="port-edit-sidebar">
        <!-- Port Assignment Section -->
        <div class="admin-card" id="assignmentCard">
            <div class="card-header">
                <h2 class="card-title">Port Assignment</h2>
            </div>
            <div class="card-body">
                <?php if ($port['status'] === 'ASSIGNED' && $assignedSubscription): ?>
                    <div class="assignment-info">
                        <div class="assignment-status assigned">
                            <span class="status-icon">✓</span>
                            <span class="status-text">Currently Assigned</span>
                        </div>
                        
                        <div class="assignment-details">
                            <div class="detail-item">
                                <span class="detail-label">Customer</span>
                                <span class="detail-value">
                                    <?php 
                                    $customer = $userModel->findById($assignedSubscription['customer_id']);
                                    echo htmlspecialchars($customer['name'] ?? 'Unknown');
                                    ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Subscription</span>
                                <span class="detail-value">
                                    <a href="<?php echo get_app_base_url(); ?>/admin/subscriptions.php?id=<?php echo urlencode($port['assigned_subscription_id']); ?>">
                                        <?php echo htmlspecialchars(substr($port['assigned_subscription_id'], 0, 8)); ?>...
                                    </a>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Assigned At</span>
                                <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($port['assigned_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="assignment-actions">
                            <p class="form-help">To reassign, unlock the form and select a different subscription below.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="assignment-info">
                        <div class="assignment-status unassigned">
                            <span class="status-icon">○</span>
                            <span class="status-text">Not Assigned</span>
                        </div>
                        <p class="form-help">This port is not currently assigned to any subscription. Ports are automatically assigned during checkout when a customer purchases a plan.</p>
                    </div>
                <?php endif; ?>
                
                <!-- Subscription Search (shown when status is ASSIGNED) -->
                <div id="subscriptionSearchSection" class="subscription-search-section" style="display: none; margin-top: 1rem;">
                    <div class="form-group">
                        <label for="subscription_search" class="form-label">Assign to Subscription</label>
                        <div class="searchable-dropdown">
                            <input type="text" id="subscription_search" class="form-input" 
                                   placeholder="Search by customer name or email..." autocomplete="off" disabled>
                            <input type="hidden" name="assign_subscription_id" id="assign_subscription_id" form="portForm" 
                                   value="<?php echo htmlspecialchars($port['assigned_subscription_id'] ?? ''); ?>">
                            <div class="dropdown-results" id="subscription_results"></div>
                        </div>
                        <div id="selected_subscription" class="selected-item" style="display: <?php echo $assignedSubscription ? 'flex' : 'none'; ?>;">
                            <?php if ($assignedSubscription): 
                                $subCustomer = $userModel->findById($assignedSubscription['customer_id']);
                            ?>
                            <div class="selected-item-info">
                                <div class="selected-item-name"><?php echo htmlspecialchars($subCustomer['name'] ?? 'Unknown'); ?></div>
                                <div class="selected-item-detail"><?php echo htmlspecialchars($subCustomer['email'] ?? ''); ?> - <?php echo htmlspecialchars(substr($assignedSubscription['id'], 0, 8)); ?>...</div>
                            </div>
                            <button type="button" class="selected-item-remove" onclick="clearSubscription()">×</button>
                            <?php endif; ?>
                        </div>
                        <p class="form-help">Search for active subscriptions without a port assigned.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subscription Link Section -->
        <div class="admin-card">
            <div class="card-header">
                <h2 class="card-title">Subscription Link</h2>
            </div>
            <div class="card-body">
                <?php if ($linkedSubscription): ?>
                    <div class="link-info">
                        <div class="link-status linked">
                            <span class="status-icon">🔗</span>
                            <span class="status-text">Linked to Subscription</span>
                        </div>
                        
                        <div class="link-details">
                            <div class="detail-item">
                                <span class="detail-label">Subscription</span>
                                <span class="detail-value">
                                    <a href="<?php echo get_app_base_url(); ?>/admin/subscriptions.php?id=<?php echo urlencode($linkedSubscription['id']); ?>">
                                        <?php echo htmlspecialchars(substr($linkedSubscription['id'], 0, 8)); ?>...
                                    </a>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Customer</span>
                                <span class="detail-value"><?php echo htmlspecialchars($linkedSubscription['customer_name'] ?? 'Unknown'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Plan</span>
                                <span class="detail-value"><?php echo htmlspecialchars($linkedSubscription['plan_name'] ?? 'Unknown'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Status</span>
                                <span class="detail-value"><?php echo get_status_badge($linkedSubscription['status']); ?></span>
                            </div>
                        </div>
                        
                        <?php if ($port['status'] !== 'ASSIGNED'): ?>
                        <div class="link-warning">
                            <p><strong>Note:</strong> This subscription still references this port even though the port status is "<?php echo $port['status']; ?>". 
                            The customer may still see this port in their dashboard.</p>
                            <form method="POST" action="<?php echo get_app_base_url(); ?>/admin/ports/edit.php?id=<?php echo urlencode($portId); ?>" style="margin-top: 0.5rem;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="action" value="unlink_subscription">
                                <input type="hidden" name="subscription_id" value="<?php echo htmlspecialchars($linkedSubscription['id']); ?>">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to unlink this subscription? The customer will no longer see this port.');">
                                    Unlink Subscription
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="link-info">
                        <div class="link-status unlinked">
                            <span class="status-icon">○</span>
                            <span class="status-text">No Subscription Linked</span>
                        </div>
                        <p class="form-help">No subscription currently references this port.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Allocation History Section -->
<div class="admin-card">
    <div class="card-header">
        <h2 class="card-title">Allocation History</h2>
    </div>
    <?php if (empty($allocationLogs)): ?>
        <div class="card-body">
            <div class="empty-state-small">
                <p class="empty-state-text">No allocation history recorded for this port.</p>
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
                        <th>Subscription</th>
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
                                    'ASSIGNED' => 'success',
                                    'REASSIGNED' => 'info',
                                    'RELEASED' => 'warning',
                                    'UNASSIGNED' => 'warning',
                                    'CREATED' => 'secondary',
                                    'DISABLED' => 'danger',
                                    'ENABLED' => 'success',
                                    'RESERVED' => 'info',
                                    'MADE_AVAILABLE' => 'success',
                                    'STATUS_CHANGED' => 'secondary'
                                ];
                                $badgeClass = $actionBadges[$log['action']] ?? 'secondary';
                                ?>
                                <span class="badge badge-<?php echo $badgeClass; ?>"><?php echo htmlspecialchars($log['action']); ?></span>
                            </td>
                            <td>
                                <?php if (!empty($log['customer_name'])): ?>
                                    <?php echo htmlspecialchars($log['customer_name']); ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($log['subscription_id'])): ?>
                                    <a href="<?php echo get_app_base_url(); ?>/admin/subscriptions.php?id=<?php echo urlencode($log['subscription_id']); ?>">
                                        <?php echo htmlspecialchars(substr($log['subscription_id'], 0, 8)); ?>...
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($log['performer_name'])): ?>
                                    <?php echo htmlspecialchars($log['performer_name']); ?>
                                <?php else: ?>
                                    <span class="text-muted">System</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($log['notes'])): ?>
                                    <span class="log-notes" title="<?php echo htmlspecialchars($log['notes']); ?>">
                                        <?php echo htmlspecialchars(substr($log['notes'], 0, 30)); ?><?php echo strlen($log['notes']) > 30 ? '...' : ''; ?>
                                    </span>
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
.admin-page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:var(--spacing-6);gap:var(--spacing-4)}
.admin-page-header-content{flex:1}
.admin-page-header-actions{display:flex;gap:var(--spacing-3)}
.breadcrumb{display:flex;align-items:center;gap:var(--spacing-2);margin-bottom:var(--spacing-3);font-size:var(--font-size-sm)}
.breadcrumb-link{color:var(--color-primary);text-decoration:none}
.breadcrumb-link:hover{text-decoration:underline}
.breadcrumb-separator{color:var(--color-gray-400)}
.breadcrumb-current{color:var(--color-gray-600)}
.admin-page-title{font-size:var(--font-size-2xl);font-weight:var(--font-weight-bold);color:var(--color-gray-900);margin:0 0 var(--spacing-2) 0}
.admin-page-description{font-size:var(--font-size-base);color:var(--color-gray-600);margin:0;display:flex;align-items:center;gap:var(--spacing-2)}

.alert{padding:var(--spacing-4);border-radius:var(--radius-md);margin-bottom:var(--spacing-4)}
.alert-error{background-color:#fee2e2;border:1px solid #ef4444;color:#991b1b}
.alert-success{background-color:#d1fae5;border:1px solid #10b981;color:#065f46}
.alert-warning{background-color:#fef3c7;border:1px solid #f59e0b;color:#92400e}

.port-edit-grid{display:grid;grid-template-columns:2fr 1fr;gap:var(--spacing-6);margin-bottom:var(--spacing-6)}
.port-edit-main{display:flex;flex-direction:column;gap:var(--spacing-6)}
.port-edit-sidebar{display:flex;flex-direction:column;gap:var(--spacing-6)}

.admin-card{background:white;border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);border:1px solid var(--color-gray-200)}
.card-header{display:flex;justify-content:space-between;align-items:center;padding:var(--spacing-4) var(--spacing-5);border-bottom:1px solid var(--color-gray-200)}
.card-title{font-size:var(--font-size-lg);font-weight:var(--font-weight-semibold);color:var(--color-gray-900);margin:0}
.card-body{padding:var(--spacing-5)}

.form-section{margin-bottom:var(--spacing-6)}
.form-section:last-child{margin-bottom:0}
.form-section-title{font-size:var(--font-size-base);font-weight:var(--font-weight-semibold);color:var(--color-gray-900);margin:0 0 var(--spacing-4) 0;padding-bottom:var(--spacing-3);border-bottom:1px solid var(--color-gray-200)}
.form-section-description{font-size:var(--font-size-sm);color:var(--color-gray-600);margin:-0.5rem 0 1rem 0}
.form-row{display:grid;grid-template-columns:repeat(2,1fr);gap:var(--spacing-4)}
.form-group{margin-bottom:var(--spacing-4)}
.form-label{display:block;font-size:var(--font-size-sm);font-weight:var(--font-weight-semibold);color:var(--color-gray-700);margin-bottom:var(--spacing-2)}
.form-label.required::after{content:' *';color:var(--color-danger)}
.form-input,.form-select,.form-textarea{width:100%;padding:var(--spacing-2) var(--spacing-3);border:1px solid var(--color-gray-300);border-radius:var(--radius-md);font-size:var(--font-size-base);color:var(--color-gray-900);font-family:inherit;background:white}
.form-input:disabled,.form-select:disabled,.form-textarea:disabled{background:var(--color-gray-50);color:var(--color-gray-500);cursor:not-allowed}
.form-input:focus:not(:disabled),.form-select:focus:not(:disabled),.form-textarea:focus:not(:disabled){outline:none;border-color:var(--color-primary);box-shadow:0 0 0 3px rgba(59,130,246,0.1)}
.form-help{font-size:var(--font-size-sm);color:var(--color-gray-600);margin:var(--spacing-1) 0 0 0}
.form-actions{display:flex;gap:var(--spacing-3);margin-top:var(--spacing-6);padding-top:var(--spacing-4);border-top:1px solid var(--color-gray-200)}
</style>

<style>
.assignment-info,.link-info{padding:0}
.assignment-status,.link-status{display:flex;align-items:center;gap:var(--spacing-2);padding:var(--spacing-3);border-radius:var(--radius-md);margin-bottom:var(--spacing-4)}
.assignment-status.assigned,.link-status.linked{background:#d1fae5;color:#065f46}
.assignment-status.unassigned,.link-status.unlinked{background:var(--color-gray-100);color:var(--color-gray-600)}
.status-icon{font-size:1.25rem}
.status-text{font-weight:var(--font-weight-semibold)}

.assignment-details,.link-details{display:flex;flex-direction:column;gap:var(--spacing-2);margin-bottom:var(--spacing-4)}
.detail-item{display:flex;justify-content:space-between;align-items:center;padding:var(--spacing-2) 0;border-bottom:1px solid var(--color-gray-100)}
.detail-item:last-child{border-bottom:none}
.detail-label{font-size:var(--font-size-sm);color:var(--color-gray-600)}
.detail-value{font-size:var(--font-size-sm);color:var(--color-gray-900);font-weight:var(--font-weight-medium)}
.detail-value a{color:var(--color-primary);text-decoration:none}
.detail-value a:hover{text-decoration:underline}

.link-warning{background:#fef3c7;border:1px solid #f59e0b;border-radius:var(--radius-md);padding:var(--spacing-3);margin-top:var(--spacing-4)}
.link-warning p{margin:0;font-size:var(--font-size-sm);color:#92400e}

.empty-state-small{padding:var(--spacing-8) var(--spacing-4);text-align:center}
.empty-state-text{color:var(--color-gray-500);font-style:italic;margin:0}
.text-muted{color:var(--color-gray-500);font-style:italic}
.log-notes{cursor:help}

.rich-text-editor{min-height:200px;background:white;border:1px solid var(--color-gray-300);border-radius:var(--radius-md)}
.ql-toolbar{border-top-left-radius:var(--radius-md);border-top-right-radius:var(--radius-md);border-color:var(--color-gray-300)!important}
.ql-container{border-bottom-left-radius:var(--radius-md);border-bottom-right-radius:var(--radius-md);border-color:var(--color-gray-300)!important;font-size:var(--font-size-base)}
.ql-editor{min-height:150px}

/* Disabled section styles */
.section-disabled{opacity:0.6;pointer-events:none;position:relative}
.section-disabled::after{content:'';position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,0.3);border-radius:var(--radius-lg)}
.section-disabled .card-header{pointer-events:auto}
.details-disabled{opacity:0.5}
.details-disabled *{color:var(--color-gray-400)!important}
.details-disabled a{pointer-events:none;text-decoration:none!important}
.status-change-warning{background:#fef3c7;border:1px solid #f59e0b;border-radius:var(--radius-md);padding:var(--spacing-3);margin-bottom:var(--spacing-4);font-size:var(--font-size-sm);color:#92400e}

/* Searchable dropdown styles */
.searchable-dropdown{position:relative}
.dropdown-results{position:absolute;top:100%;left:0;right:0;background:white;border:1px solid var(--color-gray-300);border-top:none;border-radius:0 0 var(--radius-md) var(--radius-md);max-height:200px;overflow-y:auto;z-index:100;display:none;box-shadow:var(--shadow-md)}
.dropdown-results.show{display:block}
.dropdown-item{padding:var(--spacing-3);cursor:pointer;border-bottom:1px solid var(--color-gray-100)}
.dropdown-item:last-child{border-bottom:none}
.dropdown-item:hover{background:var(--color-gray-50)}
.dropdown-item-primary{font-weight:var(--font-weight-semibold);color:var(--color-gray-900)}
.dropdown-item-secondary{font-size:var(--font-size-sm);color:var(--color-gray-600)}
.dropdown-empty{padding:var(--spacing-3);color:var(--color-gray-500);text-align:center;font-style:italic}
.selected-item{margin-top:var(--spacing-2);padding:var(--spacing-3);background:var(--color-blue-50);border:1px solid var(--color-primary);border-radius:var(--radius-md);display:flex;justify-content:space-between;align-items:center}
.selected-item-info{flex:1}
.selected-item-name{font-weight:var(--font-weight-semibold);color:var(--color-gray-900)}
.selected-item-detail{font-size:var(--font-size-sm);color:var(--color-gray-600)}
.selected-item-remove{background:none;border:none;color:var(--color-gray-500);cursor:pointer;font-size:var(--font-size-lg);padding:0 var(--spacing-2)}
.selected-item-remove:hover{color:var(--color-red-600)}
.subscription-search-section{border-top:1px solid var(--color-gray-200);padding-top:var(--spacing-4)}

@media(max-width:1024px){.port-edit-grid{grid-template-columns:1fr}}
@media(max-width:768px){.admin-page-header{flex-direction:column}.form-row{grid-template-columns:1fr}}
</style>

<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

<script>
let isEditMode = false;
let quill = null;
let subscriptionSearchTimeout = null;
const baseUrl = '<?php echo get_app_base_url(); ?>';
const currentPortId = '<?php echo htmlspecialchars($portId); ?>';

document.addEventListener('DOMContentLoaded', function() {
    var editorElement = document.getElementById('setup_instructions_editor');
    if (!editorElement) return;

    quill = new Quill('#setup_instructions_editor', {
        theme: 'snow',
        placeholder: 'Enter setup instructions for the customer...',
        readOnly: true,
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link', 'code-block'],
                ['clean']
            ]
        }
    });

    var toolbar = document.querySelector('.ql-toolbar');
    if (toolbar) {
        toolbar.style.pointerEvents = 'none';
        toolbar.style.opacity = '0.5';
    }

    var form = document.getElementById('portForm');
    if (form) {
        form.addEventListener('submit', function() {
            var hiddenInput = document.getElementById('setup_instructions_input');
            if (hiddenInput && quill) {
                hiddenInput.value = quill.root.innerHTML;
            }
        });
    }
    
    // Listen for status changes to update assignment section
    var statusSelect = document.getElementById('status');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            updateAssignmentSectionState(this.value);
        });
        // Initialize state based on current status
        updateAssignmentSectionState(statusSelect.value);
    }
    
    // Setup subscription search
    setupSubscriptionSearch();
});

function setupSubscriptionSearch() {
    const searchInput = document.getElementById('subscription_search');
    const resultsDiv = document.getElementById('subscription_results');
    
    if (!searchInput || !resultsDiv) return;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(subscriptionSearchTimeout);
        const query = this.value.trim();
        
        subscriptionSearchTimeout = setTimeout(() => {
            const url = `${baseUrl}/admin/api/search-subscriptions-for-port.php?q=${encodeURIComponent(query)}&current_port_id=${encodeURIComponent(currentPortId)}`;
            console.log('Fetching subscriptions from:', url);
            
            fetch(url)
                .then(r => {
                    console.log('Response status:', r.status);
                    if (!r.ok) {
                        throw new Error(`HTTP ${r.status}: ${r.statusText}`);
                    }
                    return r.json();
                })
                .then(data => {
                    console.log('Subscription data:', data);
                    if (data.error) {
                        resultsDiv.innerHTML = `<div class="dropdown-empty">Error: ${escapeHtml(data.error)}</div>`;
                    } else if (data.length === 0) {
                        resultsDiv.innerHTML = '<div class="dropdown-empty">No available subscriptions found</div>';
                    } else {
                        resultsDiv.innerHTML = data.map(s => `
                            <div class="dropdown-item" onclick="selectSubscription('${s.id}', '${escapeHtml(s.customer_name)}', '${escapeHtml(s.customer_email)}', '${escapeHtml(s.plan_name)}')">
                                <div class="dropdown-item-primary">${escapeHtml(s.customer_name)}</div>
                                <div class="dropdown-item-secondary">${escapeHtml(s.customer_email)} - ${escapeHtml(s.plan_name)}</div>
                            </div>
                        `).join('');
                    }
                    resultsDiv.classList.add('show');
                })
                .catch(err => {
                    console.error('Search error:', err);
                    resultsDiv.innerHTML = `<div class="dropdown-empty">Error: ${err.message}</div>`;
                    resultsDiv.classList.add('show');
                });
        }, 300);
    });
    
    searchInput.addEventListener('focus', function() {
        if (this.value.trim() === '' && document.getElementById('assign_subscription_id').value === '') {
            // Show all available subscriptions on focus
            const url = `${baseUrl}/admin/api/search-subscriptions-for-port.php?q=&current_port_id=${encodeURIComponent(currentPortId)}`;
            console.log('Fetching all subscriptions from:', url);
            
            fetch(url)
                .then(r => {
                    console.log('Focus response status:', r.status);
                    if (!r.ok) {
                        throw new Error(`HTTP ${r.status}: ${r.statusText}`);
                    }
                    return r.json();
                })
                .then(data => {
                    console.log('Focus subscription data:', data);
                    if (data.error) {
                        resultsDiv.innerHTML = `<div class="dropdown-empty">Error: ${escapeHtml(data.error)}</div>`;
                    } else if (data.length === 0) {
                        resultsDiv.innerHTML = '<div class="dropdown-empty">No available subscriptions</div>';
                    } else {
                        resultsDiv.innerHTML = data.map(s => `
                            <div class="dropdown-item" onclick="selectSubscription('${s.id}', '${escapeHtml(s.customer_name)}', '${escapeHtml(s.customer_email)}', '${escapeHtml(s.plan_name)}')">
                                <div class="dropdown-item-primary">${escapeHtml(s.customer_name)}</div>
                                <div class="dropdown-item-secondary">${escapeHtml(s.customer_email)} - ${escapeHtml(s.plan_name)}</div>
                            </div>
                        `).join('');
                    }
                    resultsDiv.classList.add('show');
                })
                .catch(err => {
                    console.error('Focus search error:', err);
                    resultsDiv.innerHTML = `<div class="dropdown-empty">Error: ${err.message}</div>`;
                    resultsDiv.classList.add('show');
                });
        }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.searchable-dropdown')) {
            resultsDiv.classList.remove('show');
        }
    });
}

function selectSubscription(id, name, email, plan) {
    document.getElementById('assign_subscription_id').value = id;
    document.getElementById('subscription_search').style.display = 'none';
    document.getElementById('subscription_results').classList.remove('show');
    
    const selectedDiv = document.getElementById('selected_subscription');
    selectedDiv.innerHTML = `
        <div class="selected-item-info">
            <div class="selected-item-name">${escapeHtml(name)}</div>
            <div class="selected-item-detail">${escapeHtml(email)} - ${escapeHtml(plan)}</div>
        </div>
        <button type="button" class="selected-item-remove" onclick="clearSubscription()">×</button>
    `;
    selectedDiv.style.display = 'flex';
}

function clearSubscription() {
    document.getElementById('assign_subscription_id').value = '';
    document.getElementById('subscription_search').value = '';
    document.getElementById('subscription_search').style.display = 'block';
    document.getElementById('selected_subscription').style.display = 'none';
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function updateAssignmentSectionState(status) {
    const assignmentCard = document.getElementById('assignmentCard');
    const subscriptionSearchSection = document.getElementById('subscriptionSearchSection');
    const subscriptionSearch = document.getElementById('subscription_search');
    const assignmentDetails = document.querySelector('.assignment-details');
    const assignmentActions = document.querySelector('.assignment-actions');
    
    if (!assignmentCard) return;
    
    if (status !== 'ASSIGNED') {
        // Hide subscription search when status is not ASSIGNED
        if (subscriptionSearchSection) {
            subscriptionSearchSection.style.display = 'none';
        }
        if (assignmentDetails) {
            assignmentDetails.classList.add('details-disabled');
        }
        if (assignmentActions) {
            assignmentActions.classList.add('details-disabled');
        }
        
        // Show warning message if there was an assignment
        let warningEl = assignmentCard.querySelector('.status-change-warning');
        if (!warningEl && document.querySelector('.assignment-status.assigned')) {
            warningEl = document.createElement('div');
            warningEl.className = 'status-change-warning';
            warningEl.innerHTML = '<strong>⚠️ Warning:</strong> Changing status from ASSIGNED will clear the current assignment and unlink the subscription.';
            const cardBody = assignmentCard.querySelector('.card-body');
            if (cardBody) {
                cardBody.insertBefore(warningEl, cardBody.firstChild);
            }
        }
    } else {
        // Show subscription search when status is ASSIGNED
        if (subscriptionSearchSection) {
            subscriptionSearchSection.style.display = 'block';
        }
        if (subscriptionSearch && isEditMode) {
            subscriptionSearch.disabled = false;
        }
        if (assignmentDetails) {
            assignmentDetails.classList.remove('details-disabled');
        }
        if (assignmentActions) {
            assignmentActions.classList.remove('details-disabled');
        }
        
        // Remove warning message
        const warningEl = assignmentCard.querySelector('.status-change-warning');
        if (warningEl) {
            warningEl.remove();
        }
    }
}

function toggleEditMode() {
    isEditMode = !isEditMode;
    const unlockBtn = document.getElementById('unlockBtn');
    const formActions = document.getElementById('formActions');
    const formInputs = document.querySelectorAll('#portForm input:not([type="hidden"]), #portForm select, #portForm textarea');
    const subscriptionSearch = document.getElementById('subscription_search');
    const toolbar = document.querySelector('.ql-toolbar');
    const statusSelect = document.getElementById('status');
    
    if (isEditMode) {
        unlockBtn.innerHTML = 'Lock';
        unlockBtn.classList.remove('btn-warning');
        unlockBtn.classList.add('btn-secondary');
        formActions.style.display = 'flex';
        
        formInputs.forEach(input => {
            if (input.id !== 'port_id' && input.id !== 'created_at' && input.id !== 'updated_at') {
                input.disabled = false;
            }
        });
        
        // Enable subscription search if status is ASSIGNED
        if (subscriptionSearch && statusSelect && statusSelect.value === 'ASSIGNED') {
            subscriptionSearch.disabled = false;
        }

        if (quill) quill.enable();
        if (toolbar) {
            toolbar.style.pointerEvents = 'auto';
            toolbar.style.opacity = '1';
        }
    } else {
        unlockBtn.innerHTML = 'Unlock to Edit';
        unlockBtn.classList.remove('btn-secondary');
        unlockBtn.classList.add('btn-warning');
        formActions.style.display = 'none';
        
        formInputs.forEach(input => { input.disabled = true; });
        if (subscriptionSearch) subscriptionSearch.disabled = true;

        if (quill) quill.disable();
        if (toolbar) {
            toolbar.style.pointerEvents = 'none';
            toolbar.style.opacity = '0.5';
        }
    }
    
    // Update assignment section state based on current status
    if (statusSelect) {
        updateAssignmentSectionState(statusSelect.value);
    }
}

function cancelEdit() {
    window.location.reload();
}

window.addEventListener('beforeunload', function(e) {
    if (isEditMode) {
        e.preventDefault();
        e.returnValue = '';
    }
});
</script>

<?php include_admin_footer(); ?>
