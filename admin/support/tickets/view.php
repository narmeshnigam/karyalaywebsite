<?php
/**
 * Admin Ticket Detail Page
 * Display full thread, customer details, linked subscription
 * Allow admin to add replies and internal notes
 * Allow admin to change status and assign to team members
 */

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../includes/auth_helpers.php';
require_once __DIR__ . '/../../../includes/admin_helpers.php';

use Karyalay\Services\TicketService;
use Karyalay\Models\Ticket;
use Karyalay\Models\TicketMessage;
use Karyalay\Models\User;
use Karyalay\Models\Subscription;
use Karyalay\Services\CsrfService;

// Start secure session
startSecureSession();

// Require admin authentication and tickets.view_details permission
require_admin();
require_permission('tickets.view_details');

// Get database connection
$db = \Karyalay\Database\Connection::getInstance();

// Initialize services and models
$ticketService = new TicketService();
$ticketModel = new Ticket();
$messageModel = new TicketMessage();
$userModel = new User();
$subscriptionModel = new Subscription();
$csrfService = new CsrfService();

// Get ticket ID from query parameter
$ticket_id = $_GET['id'] ?? '';

if (empty($ticket_id)) {
    header('Location: ' . get_app_base_url() . '/admin/support/tickets.php');
    exit;
}

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!$csrfService->validateToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add_reply') {
            // Add admin reply (visible to customer)
            $content = trim($_POST['content'] ?? '');
            
            if (empty($content)) {
                $error_message = 'Reply content cannot be empty.';
            } else {
                // Use addAdminReply which sends notification email
                $result = $ticketService->addAdminReply($ticket_id, $_SESSION['user_id'], $content, false);
                
                if ($result['success']) {
                    $success_message = 'Reply added successfully. Customer has been notified via email.';
                } else {
                    $error_message = $result['error'] ?? 'Failed to add reply. Please try again.';
                }
            }
        } elseif ($action === 'add_internal_note') {
            // Add internal note (not visible to customer)
            $content = trim($_POST['content'] ?? '');
            
            if (empty($content)) {
                $error_message = 'Note content cannot be empty.';
            } else {
                // Use addAdminReply with isInternal=true (no email notification)
                $result = $ticketService->addAdminReply($ticket_id, $_SESSION['user_id'], $content, true);
                
                if ($result['success']) {
                    $success_message = 'Internal note added successfully.';
                } else {
                    $error_message = $result['error'] ?? 'Failed to add internal note. Please try again.';
                }
            }
        } elseif ($action === 'update_status') {
            // Update ticket status
            $new_status = $_POST['status'] ?? '';
            
            $result = $ticketService->updateTicketStatus($ticket_id, $new_status);
            
            if ($result['success']) {
                $success_message = 'Ticket status updated successfully.';
            } else {
                $error_message = $result['error'] ?? 'Failed to update ticket status.';
            }
        } elseif ($action === 'assign_ticket') {
            // Assign ticket to admin
            $assignee_id = $_POST['assignee_id'] ?? '';
            
            if (empty($assignee_id)) {
                // Unassign ticket
                $ticketModel->update($ticket_id, ['assigned_to' => null]);
                $success_message = 'Ticket unassigned successfully.';
            } else {
                $result = $ticketService->assignTicket($ticket_id, $assignee_id);
                
                if ($result['success']) {
                    $success_message = 'Ticket assigned successfully.';
                } else {
                    $error_message = $result['error'] ?? 'Failed to assign ticket.';
                }
            }
        }
    }
}

// Fetch ticket details
$ticket = $ticketModel->findById($ticket_id);

if (!$ticket) {
    header('Location: /admin/support/tickets.php');
    exit;
}

// Fetch customer details
$customer = $userModel->findById($ticket['customer_id']);

// Fetch subscription details if linked
$subscription = null;
$plan = null;
if (!empty($ticket['subscription_id'])) {
    $subscription = $subscriptionModel->findById($ticket['subscription_id']);
    if ($subscription) {
        $plan_sql = "SELECT * FROM plans WHERE id = :plan_id";
        $plan_stmt = $db->prepare($plan_sql);
        $plan_stmt->execute([':plan_id' => $subscription['plan_id']]);
        $plan = $plan_stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Fetch all messages (including internal notes for admin)
$messages = $messageModel->findByTicketId($ticket_id, true);

// Fetch all admin users for assignment dropdown
$admin_users_sql = "SELECT id, name, email FROM users WHERE role = 'ADMIN' ORDER BY name ASC";
$admin_users_stmt = $db->query($admin_users_sql);
$admin_users = $admin_users_stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate CSRF token
$csrf_token = $csrfService->generateToken();

// Include admin header
include_admin_header('Ticket #' . substr($ticket_id, 0, 8));
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <div class="breadcrumb">
            <a href="<?php echo get_app_base_url(); ?>/admin/support/tickets.php" class="breadcrumb-link">Support Tickets</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">Ticket #<?php echo htmlspecialchars(substr($ticket_id, 0, 8)); ?></span>
        </div>
        <h1 class="admin-page-title"><?php echo htmlspecialchars($ticket['subject']); ?></h1>
        <div class="ticket-meta">
            <?php echo get_status_badge($ticket['status']); ?>
            <?php 
            $priority_config = [
                'LOW' => 'secondary',
                'MEDIUM' => 'info',
                'HIGH' => 'warning',
                'URGENT' => 'danger'
            ];
            echo get_status_badge($ticket['priority'], $priority_config); 
            ?>
            <?php if ($ticket['category']): ?>
                <span class="badge badge-secondary"><?php echo htmlspecialchars($ticket['category']); ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($success_message): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($success_message); ?>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<div class="ticket-layout">
    <!-- Main Content -->
    <div class="ticket-main">
        <!-- Messages Thread -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title">Conversation</h2>
            </div>
            
            <div class="ticket-messages">
                <?php if (empty($messages)): ?>
                    <div class="empty-state-small">
                        <p>No messages yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="ticket-message <?php echo $message['is_internal'] ? 'ticket-message-internal' : ''; ?>">
                            <div class="ticket-message-header">
                                <div class="ticket-message-author">
                                    <?php 
                                    $author = $userModel->findById($message['author_id']);
                                    $author_name = $author ? $author['name'] : 'Unknown';
                                    ?>
                                    <strong><?php echo htmlspecialchars($author_name); ?></strong>
                                    <span class="badge badge-sm badge-<?php echo $message['author_type'] === 'ADMIN' ? 'primary' : 'secondary'; ?>">
                                        <?php echo htmlspecialchars($message['author_type']); ?>
                                    </span>
                                    <?php if ($message['is_internal']): ?>
                                        <span class="badge badge-sm badge-warning">Internal Note</span>
                                    <?php endif; ?>
                                </div>
                                <div class="ticket-message-time">
                                    <?php echo get_relative_time($message['created_at']); ?>
                                </div>
                            </div>
                            <div class="ticket-message-content">
                                <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Add Reply Form -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title">Add Reply</h2>
            </div>
            
            <form method="POST" class="ticket-reply-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="add_reply">
                
                <div class="form-group">
                    <label for="content" class="form-label">Reply to Customer</label>
                    <textarea 
                        id="content" 
                        name="content" 
                        class="form-textarea" 
                        rows="5" 
                        placeholder="Type your reply here..."
                        required
                    ></textarea>
                    <p class="form-help">This reply will be visible to the customer.</p>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Send Reply</button>
                </div>
            </form>
        </div>
        
        <!-- Add Internal Note Form -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title">Add Internal Note</h2>
            </div>
            
            <form method="POST" class="ticket-reply-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="add_internal_note">
                
                <div class="form-group">
                    <label for="internal_content" class="form-label">Internal Note</label>
                    <textarea 
                        id="internal_content" 
                        name="content" 
                        class="form-textarea" 
                        rows="4" 
                        placeholder="Type your internal note here..."
                        required
                    ></textarea>
                    <p class="form-help">This note will NOT be visible to the customer.</p>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-secondary">Add Internal Note</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="ticket-sidebar">
        <!-- Customer Details -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">Customer Details</h3>
            </div>
            
            <?php if ($customer): ?>
                <div class="ticket-info-group">
                    <div class="ticket-info-item">
                        <span class="ticket-info-label">Name</span>
                        <span class="ticket-info-value"><?php echo htmlspecialchars($customer['name']); ?></span>
                    </div>
                    <div class="ticket-info-item">
                        <span class="ticket-info-label">Email</span>
                        <span class="ticket-info-value">
                            <a href="mailto:<?php echo htmlspecialchars($customer['email']); ?>">
                                <?php echo htmlspecialchars($customer['email']); ?>
                            </a>
                        </span>
                    </div>
                    <?php if ($customer['phone']): ?>
                        <div class="ticket-info-item">
                            <span class="ticket-info-label">Phone</span>
                            <span class="ticket-info-value"><?php echo htmlspecialchars($customer['phone']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($customer['business_name']): ?>
                        <div class="ticket-info-item">
                            <span class="ticket-info-label">Business</span>
                            <span class="ticket-info-value"><?php echo htmlspecialchars($customer['business_name']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p class="text-muted">Customer information not available</p>
            <?php endif; ?>
        </div>
        
        <!-- Subscription Details -->
        <?php if ($subscription && $plan): ?>
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">Linked Subscription</h3>
                </div>
                
                <div class="ticket-info-group">
                    <div class="ticket-info-item">
                        <span class="ticket-info-label">Plan</span>
                        <span class="ticket-info-value"><?php echo htmlspecialchars($plan['name']); ?></span>
                    </div>
                    <div class="ticket-info-item">
                        <span class="ticket-info-label">Status</span>
                        <span class="ticket-info-value"><?php echo get_status_badge($subscription['status']); ?></span>
                    </div>
                    <div class="ticket-info-item">
                        <span class="ticket-info-label">End Date</span>
                        <span class="ticket-info-value"><?php echo date('M j, Y', strtotime($subscription['end_date'])); ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Ticket Management -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">Ticket Management</h3>
            </div>
            
            <!-- Update Status -->
            <form method="POST" class="ticket-management-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="update_status">
                
                <div class="form-group">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select" onchange="this.form.submit()">
                        <option value="OPEN" <?php echo $ticket['status'] === 'OPEN' ? 'selected' : ''; ?>>Open</option>
                        <option value="IN_PROGRESS" <?php echo $ticket['status'] === 'IN_PROGRESS' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="WAITING_ON_CUSTOMER" <?php echo $ticket['status'] === 'WAITING_ON_CUSTOMER' ? 'selected' : ''; ?>>Waiting on Customer</option>
                        <option value="RESOLVED" <?php echo $ticket['status'] === 'RESOLVED' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="CLOSED" <?php echo $ticket['status'] === 'CLOSED' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>
            </form>
            
            <!-- Assign Ticket -->
            <form method="POST" class="ticket-management-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="assign_ticket">
                
                <div class="form-group">
                    <label for="assignee_id" class="form-label">Assignee</label>
                    <select id="assignee_id" name="assignee_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Unassigned</option>
                        <?php foreach ($admin_users as $admin): ?>
                            <option value="<?php echo htmlspecialchars($admin['id']); ?>" 
                                    <?php echo $ticket['assigned_to'] === $admin['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($admin['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
            
            <!-- Ticket Info -->
            <div class="ticket-info-group">
                <div class="ticket-info-item">
                    <span class="ticket-info-label">Created</span>
                    <span class="ticket-info-value"><?php echo date('M j, Y g:i A', strtotime($ticket['created_at'])); ?></span>
                </div>
                <div class="ticket-info-item">
                    <span class="ticket-info-label">Last Updated</span>
                    <span class="ticket-info-value"><?php echo get_relative_time($ticket['updated_at']); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.admin-page-header {
    margin-bottom: var(--spacing-6);
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
    margin: 0 0 var(--spacing-3) 0;
}

.ticket-meta {
    display: flex;
    gap: var(--spacing-2);
    flex-wrap: wrap;
}

.alert {
    padding: var(--spacing-4);
    border-radius: var(--radius-md);
    margin-bottom: var(--spacing-6);
}

.alert-success {
    background-color: #d1fae5;
    border: 1px solid #6ee7b7;
    color: #065f46;
}

.alert-danger {
    background-color: #fee2e2;
    border: 1px solid #fca5a5;
    color: #991b1b;
}

.ticket-layout {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: var(--spacing-6);
}

.ticket-main {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-6);
}

.ticket-sidebar {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-4);
}

.ticket-messages {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-4);
    padding: var(--spacing-4);
}

.ticket-message {
    padding: var(--spacing-4);
    background-color: var(--color-gray-50);
    border-radius: var(--radius-md);
    border-left: 3px solid var(--color-gray-300);
}

.ticket-message-internal {
    background-color: #fef3c7;
    border-left-color: #f59e0b;
}

.ticket-message-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-3);
}

.ticket-message-author {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
}

.ticket-message-time {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
}

.ticket-message-content {
    color: var(--color-gray-800);
    line-height: 1.6;
}

.badge-sm {
    font-size: var(--font-size-xs);
    padding: 2px 6px;
}

.empty-state-small {
    text-align: center;
    padding: var(--spacing-8);
    color: var(--color-gray-500);
}

.ticket-reply-form {
    padding: var(--spacing-4);
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

.form-textarea,
.form-select {
    width: 100%;
    padding: var(--spacing-3);
    border: 1px solid var(--color-gray-300);
    border-radius: var(--radius-md);
    font-size: var(--font-size-base);
    color: var(--color-gray-900);
    font-family: inherit;
}

.form-textarea:focus,
.form-select:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-help {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin-top: var(--spacing-2);
}

.form-actions {
    display: flex;
    gap: var(--spacing-2);
}

.ticket-management-form {
    padding: 0 var(--spacing-4) var(--spacing-4) var(--spacing-4);
}

.ticket-management-form:last-child {
    padding-bottom: 0;
}

.ticket-info-group {
    padding: var(--spacing-4);
    display: flex;
    flex-direction: column;
    gap: var(--spacing-3);
}

.ticket-info-item {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-1);
}

.ticket-info-label {
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-600);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.ticket-info-value {
    font-size: var(--font-size-sm);
    color: var(--color-gray-900);
}

.ticket-info-value a {
    color: var(--color-primary);
    text-decoration: none;
}

.ticket-info-value a:hover {
    text-decoration: underline;
}

.text-muted {
    color: var(--color-gray-500);
    font-style: italic;
    padding: var(--spacing-4);
}

@media (max-width: 1024px) {
    .ticket-layout {
        grid-template-columns: 1fr;
    }
    
    .ticket-sidebar {
        order: -1;
    }
}
</style>

<?php include_admin_footer(); ?>
