<?php
/**
 * Admin Edit Port Page
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Services\PortService;
use Karyalay\Services\CsrfService;
use Karyalay\Models\Plan;
use Karyalay\Models\User;
use Karyalay\Models\Subscription;

// Start secure session
startSecureSession();

// Require admin authentication
require_admin();

// Initialize services
$portService = new PortService();
$csrfService = new CsrfService();
$planModel = new Plan();
$userModel = new User();
$subscriptionModel = new Subscription();

// Get port ID from query parameter
$portId = $_GET['id'] ?? '';

if (empty($portId)) {
    $_SESSION['admin_error'] = 'Port ID is required.';
    header('Location: <?php echo get_base_url(); ?>/admin/ports.php');
    exit;
}

// Fetch port data
$portResult = $portService->getPort($portId);

if (!$portResult['success']) {
    $_SESSION['admin_error'] = 'Port not found.';
    header('Location: <?php echo get_base_url(); ?>/admin/ports.php');
    exit;
}

$port = $portResult['port'];

// Fetch all plans for dropdown
$allPlans = $planModel->findAll();

// Fetch all customers for reassignment dropdown
$allCustomers = $userModel->findAll(['role' => 'CUSTOMER']);

// Fetch subscription if port is assigned
$assignedSubscription = null;
if ($port['assigned_subscription_id']) {
    $assignedSubscription = $subscriptionModel->findById($port['assigned_subscription_id']);
}

// Initialize variables
$errors = [];
$success = false;
$formData = [
    'instance_url' => $port['instance_url'],
    'db_host' => $port['db_host'] ?? '',
    'db_name' => $port['db_name'] ?? '',
    'db_username' => $port['db_username'] ?? '',
    'db_password' => $port['db_password'] ?? '',
    'plan_id' => $port['plan_id'],
    'status' => $port['status'],
    'server_region' => $port['server_region'] ?? '',
    'notes' => $port['notes'] ?? '',
    'reassign_customer_id' => $port['assigned_customer_id'] ?? ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$csrfService->validateToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Get form data
        $formData = [
            'instance_url' => trim($_POST['instance_url'] ?? ''),
            'db_host' => trim($_POST['db_host'] ?? ''),
            'db_name' => trim($_POST['db_name'] ?? ''),
            'db_username' => trim($_POST['db_username'] ?? ''),
            'db_password' => trim($_POST['db_password'] ?? ''),
            'plan_id' => trim($_POST['plan_id'] ?? ''),
            'status' => trim($_POST['status'] ?? 'AVAILABLE'),
            'server_region' => trim($_POST['server_region'] ?? ''),
            'notes' => trim($_POST['notes'] ?? ''),
            'reassign_customer_id' => trim($_POST['reassign_customer_id'] ?? '')
        ];

        // Validate required fields
        if (empty($formData['instance_url'])) {
            $errors[] = 'Instance URL is required.';
        }
        if (empty($formData['plan_id'])) {
            $errors[] = 'Plan is required.';
        }

        // Check if reassignment is requested
        $isReassignment = false;
        if ($port['status'] === 'ASSIGNED' && 
            !empty($formData['reassign_customer_id']) && 
            $formData['reassign_customer_id'] !== $port['assigned_customer_id']) {
            $isReassignment = true;
        }

        if (empty($errors)) {
            // Prepare data for update
            $updateData = [
                'instance_url' => $formData['instance_url'],
                'plan_id' => $formData['plan_id'],
                'status' => $formData['status']
            ];

            // Add database connection fields
            $updateData['db_host'] = !empty($formData['db_host']) ? $formData['db_host'] : null;
            $updateData['db_name'] = !empty($formData['db_name']) ? $formData['db_name'] : null;
            $updateData['db_username'] = !empty($formData['db_username']) ? $formData['db_username'] : null;
            // Only update password if provided (don't clear existing password)
            if (!empty($formData['db_password'])) {
                $updateData['db_password'] = $formData['db_password'];
            }
            
            // Add other optional fields
            if (!empty($formData['server_region'])) {
                $updateData['server_region'] = $formData['server_region'];
            } else {
                $updateData['server_region'] = null;
            }
            
            if (!empty($formData['notes'])) {
                $updateData['notes'] = $formData['notes'];
            } else {
                $updateData['notes'] = null;
            }

            // Handle reassignment
            if ($isReassignment) {
                // Find active subscription for new customer
                $newCustomerId = $formData['reassign_customer_id'];
                $newSubscriptions = $subscriptionModel->findAll([
                    'customer_id' => $newCustomerId,
                    'status' => 'ACTIVE'
                ]);

                if (empty($newSubscriptions)) {
                    $errors[] = 'Selected customer does not have an active subscription.';
                } else {
                    // Use the first active subscription
                    $newSubscription = $newSubscriptions[0];
                    
                    $updateData['assigned_customer_id'] = $newCustomerId;
                    $updateData['assigned_subscription_id'] = $newSubscription['id'];
                    $updateData['assigned_at'] = date('Y-m-d H:i:s');
                    
                    // Log the reassignment
                    require_once __DIR__ . '/../../classes/Models/PortAllocationLog.php';
                    $logModel = new \Karyalay\Models\PortAllocationLog();
                    $logModel->create([
                        'port_id' => $portId,
                        'subscription_id' => $newSubscription['id'],
                        'customer_id' => $newCustomerId,
                        'action' => 'REASSIGNED',
                        'performed_by' => $_SESSION['user_id'] ?? null,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                }
            }

            // If status changed from ASSIGNED to something else, clear assignment
            if ($port['status'] === 'ASSIGNED' && $formData['status'] !== 'ASSIGNED') {
                $updateData['assigned_customer_id'] = null;
                $updateData['assigned_subscription_id'] = null;
                $updateData['assigned_at'] = null;
            }

            if (empty($errors)) {
                // Update port
                $result = $portService->updatePort($portId, $updateData);

                if ($result['success']) {
                    $success = true;
                    $_SESSION['admin_success'] = 'Port updated successfully!';
                    header('Location: <?php echo get_base_url(); ?>/admin/ports.php');
                    exit;
                } else {
                    $errors[] = $result['error'] ?? 'Failed to update port. Please check the form and try again.';
                }
            }
        }
    }
}

// Generate CSRF token
$csrfToken = $csrfService->generateToken();

// Include admin header
include_admin_header('Edit Port');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Edit Port</h1>
        <p class="admin-page-description">Update port details and manage assignments</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_base_url(); ?>/admin/ports.php" class="btn btn-secondary">
            ← Back to Ports
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <strong>Error:</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <strong>Success!</strong> Port updated successfully.
    </div>
<?php endif; ?>

<div class="admin-card">
    <form method="POST" action="/admin/ports/edit.php?id=<?php echo urlencode($portId); ?>" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        
        <div class="form-section">
            <h2 class="form-section-title">Port Information</h2>
            
            <div class="form-group">
                <label for="instance_url" class="form-label required">Instance URL</label>
                <input 
                    type="text" 
                    id="instance_url" 
                    name="instance_url" 
                    class="form-input" 
                    value="<?php echo htmlspecialchars($formData['instance_url']); ?>"
                    required
                    placeholder="e.g., https://instance1.karyalay.com or http://192.168.1.100"
                >
                <p class="form-help">The full URL or IP address where the Karyalay instance is hosted</p>
            </div>

            <div class="form-group">
                <label for="server_region" class="form-label">Server Region</label>
                <input 
                    type="text" 
                    id="server_region" 
                    name="server_region" 
                    class="form-input" 
                    value="<?php echo htmlspecialchars($formData['server_region']); ?>"
                    placeholder="e.g., US-East, EU-West, Asia-Pacific"
                >
                <p class="form-help">Optional geographic region of the server</p>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="plan_id" class="form-label required">Associated Plan</label>
                    <select id="plan_id" name="plan_id" class="form-select" required>
                        <option value="">Select a plan...</option>
                        <?php foreach ($allPlans as $plan): ?>
                            <option value="<?php echo htmlspecialchars($plan['id']); ?>"
                                    <?php echo $formData['plan_id'] === $plan['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($plan['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-help">The subscription plan this port is designated for</p>
                </div>

                <div class="form-group">
                    <label for="status" class="form-label required">Status</label>
                    <select id="status" name="status" class="form-select" required>
                        <option value="AVAILABLE" <?php echo $formData['status'] === 'AVAILABLE' ? 'selected' : ''; ?>>Available</option>
                        <option value="RESERVED" <?php echo $formData['status'] === 'RESERVED' ? 'selected' : ''; ?>>Reserved</option>
                        <option value="ASSIGNED" <?php echo $formData['status'] === 'ASSIGNED' ? 'selected' : ''; ?>>Assigned</option>
                        <option value="DISABLED" <?php echo $formData['status'] === 'DISABLED' ? 'selected' : ''; ?>>Disabled</option>
                    </select>
                    <p class="form-help">Current status of the port</p>
                </div>
            </div>

            <div class="form-group">
                <label for="notes" class="form-label">Notes</label>
                <textarea 
                    id="notes" 
                    name="notes" 
                    class="form-textarea" 
                    rows="4"
                    placeholder="Optional notes about this port (e.g., hardware specs, maintenance schedule)"
                ><?php echo htmlspecialchars($formData['notes']); ?></textarea>
                <p class="form-help">Optional internal notes about this port</p>
            </div>
        </div>

        <div class="form-section">
            <h2 class="form-section-title">Database Connection</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="db_host" class="form-label">Database Host</label>
                    <input 
                        type="text" 
                        id="db_host" 
                        name="db_host" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($formData['db_host']); ?>"
                        placeholder="e.g., localhost or db.example.com"
                    >
                    <p class="form-help">The database server hostname or IP address</p>
                </div>

                <div class="form-group">
                    <label for="db_name" class="form-label">Database Name</label>
                    <input 
                        type="text" 
                        id="db_name" 
                        name="db_name" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($formData['db_name']); ?>"
                        placeholder="e.g., karyalay_instance1"
                    >
                    <p class="form-help">The name of the database for this instance</p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="db_username" class="form-label">Database Username</label>
                    <input 
                        type="text" 
                        id="db_username" 
                        name="db_username" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($formData['db_username']); ?>"
                        placeholder="e.g., db_user"
                    >
                    <p class="form-help">The username for database authentication</p>
                </div>

                <div class="form-group">
                    <label for="db_password" class="form-label">Database Password</label>
                    <input 
                        type="password" 
                        id="db_password" 
                        name="db_password" 
                        class="form-input" 
                        placeholder="<?php echo !empty($formData['db_password']) ? '••••••••' : 'Enter database password'; ?>"
                    >
                    <p class="form-help">Leave blank to keep existing password</p>
                </div>
            </div>
        </div>

        <?php if ($port['status'] === 'ASSIGNED'): ?>
        <div class="form-section">
            <h2 class="form-section-title">Assignment Information</h2>
            
            <?php if ($assignedSubscription): ?>
                <div class="info-box">
                    <h3 class="info-box-title">Current Assignment</h3>
                    <div class="info-box-content">
                        <div class="info-row">
                            <span class="info-label">Customer:</span>
                            <span class="info-value">
                                <?php 
                                $customer = $userModel->findById($port['assigned_customer_id']);
                                echo htmlspecialchars($customer['name'] ?? 'Unknown');
                                ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Subscription ID:</span>
                            <span class="info-value">
                                <code><?php echo htmlspecialchars($port['assigned_subscription_id']); ?></code>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Assigned At:</span>
                            <span class="info-value">
                                <?php echo htmlspecialchars($port['assigned_at']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="reassign_customer_id" class="form-label">Reassign to Customer</label>
                <select id="reassign_customer_id" name="reassign_customer_id" class="form-select">
                    <option value="">Keep current assignment</option>
                    <?php foreach ($allCustomers as $customer): ?>
                        <option value="<?php echo htmlspecialchars($customer['id']); ?>"
                                <?php echo $formData['reassign_customer_id'] === $customer['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($customer['name']); ?> 
                            (<?php echo htmlspecialchars($customer['email']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="form-help">Select a different customer to reassign this port (requires active subscription)</p>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Port</button>
            <a href="<?php echo get_base_url(); ?>/admin/ports.php" class="btn btn-secondary">Cancel</a>
            <?php if ($port['status'] !== 'ASSIGNED'): ?>
                <a href="<?php echo get_base_url(); ?>/admin/ports/delete.php?id=<?php echo urlencode($portId); ?>" 
                   class="btn btn-danger"
                   onclick="return confirm('Are you sure you want to delete this port?');">
                    Delete Port
                </a>
            <?php endif; ?>
        </div>
    </form>
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

.admin-page-header-actions {
    display: flex;
    gap: var(--spacing-3);
}

.alert {
    padding: var(--spacing-4);
    border-radius: var(--radius-md);
    margin-bottom: var(--spacing-6);
}

.alert-error {
    background-color: #fee;
    border: 1px solid #fcc;
    color: #c33;
}

.alert-success {
    background-color: #efe;
    border: 1px solid #cfc;
    color: #3c3;
}

.alert ul {
    margin: var(--spacing-2) 0 0 var(--spacing-4);
}

.admin-form {
    padding: var(--spacing-6);
}

.form-section {
    margin-bottom: var(--spacing-8);
}

.form-section:last-child {
    margin-bottom: 0;
}

.form-section-title {
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-4) 0;
    padding-bottom: var(--spacing-3);
    border-bottom: 1px solid var(--color-gray-200);
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-4);
}

.form-group {
    margin-bottom: var(--spacing-4);
}

.form-label {
    display: block;
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
    margin-bottom: var(--spacing-2);
}

.form-label.required::after {
    content: ' *';
    color: var(--color-danger);
}

.form-input,
.form-select,
.form-textarea {
    width: 100%;
    padding: var(--spacing-2) var(--spacing-3);
    border: 1px solid var(--color-gray-300);
    border-radius: var(--radius-md);
    font-size: var(--font-size-base);
    color: var(--color-gray-900);
    font-family: inherit;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-textarea {
    resize: vertical;
}

.form-help {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin: var(--spacing-1) 0 0 0;
}

.info-box {
    background-color: var(--color-gray-50);
    border: 1px solid var(--color-gray-200);
    border-radius: var(--radius-md);
    padding: var(--spacing-4);
    margin-bottom: var(--spacing-4);
}

.info-box-title {
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-3) 0;
}

.info-box-content {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.info-label {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    font-weight: var(--font-weight-medium);
}

.info-value {
    font-size: var(--font-size-sm);
    color: var(--color-gray-900);
}

.info-value code {
    background-color: var(--color-gray-100);
    padding: 2px 6px;
    border-radius: var(--radius-sm);
    font-family: 'Courier New', monospace;
    font-size: var(--font-size-sm);
}

.form-actions {
    display: flex;
    gap: var(--spacing-3);
    padding-top: var(--spacing-6);
    border-top: 1px solid var(--color-gray-200);
}

@media (max-width: 768px) {
    .admin-page-header {
        flex-direction: column;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .info-row {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--spacing-1);
    }
}
</style>

<?php include_admin_footer(); ?>
