<?php
/**
 * Ticket Detail Page
 * View ticket details and message thread, allow customer to add replies
 */

// Load Composer autoloader
require_once __DIR__ . '/../../../vendor/autoload.php';

// Include required files
require_once __DIR__ . '/../../../includes/auth_helpers.php';
require_once __DIR__ . '/../../../includes/template_helpers.php';

use Karyalay\Services\TicketService;
use Karyalay\Services\CsrfService;
use Karyalay\Models\TicketMessage;
use Karyalay\Models\User;

// Guard customer portal - requires authentication
$user = guardCustomerPortal();
$customerId = $user['id'];

// Get ticket ID from query string
$ticketId = $_GET['id'] ?? '';

if (empty($ticketId)) {
    $_SESSION['flash_message'] = 'Invalid ticket ID.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . get_app_base_url() . '/app/support/tickets.php');
    exit;
}

// Initialize services
$ticketService = new TicketService();
$csrfService = new CsrfService();
$messageModel = new TicketMessage();
$userModel = new User();

// Fetch ticket
$result = $ticketService->getTicket($ticketId);

if (!$result['success']) {
    $_SESSION['flash_message'] = 'Ticket not found.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . get_app_base_url() . '/app/support/tickets.php');
    exit;
}

$ticket = $result['ticket'];

// Verify ticket belongs to customer
if ($ticket['customer_id'] !== $customerId) {
    $_SESSION['flash_message'] = 'You do not have permission to view this ticket.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . get_app_base_url() . '/app/support/tickets.php');
    exit;
}

// Fetch messages (customer-visible only)
$messages = $messageModel->findCustomerVisibleByTicketId($ticketId);

// Handle reply submission
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply') {
    // Validate CSRF token
    if (!$csrfService->validateToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Check if ticket is closed
        if ($ticketService->isTicketClosed($ticketId)) {
            $errors[] = 'Cannot reply to a closed ticket.';
        } else {
            // Get reply content
            $content = trim($_POST['content'] ?? '');
            
            // Validate content
            if (empty($content)) {
                $errors[] = 'Reply content is required.';
            }
            
            // If no errors, create message
            if (empty($errors)) {
                $messageData = [
                    'ticket_id' => $ticketId,
                    'author_id' => $customerId,
                    'author_type' => 'CUSTOMER',
                    'content' => $content,
                    'is_internal' => false
                ];
                
                $message = $messageModel->create($messageData);
                
                if ($message) {
                    $_SESSION['flash_message'] = 'Reply added successfully.';
                    $_SESSION['flash_type'] = 'success';
                    
                    header('Location: ' . get_app_base_url() . '/app/support/tickets/view.php?id=' . urlencode($ticketId));
                    exit;
                } else {
                    $errors[] = 'Failed to add reply. Please try again.';
                }
            }
        }
    }
}

// Generate CSRF token
$csrfToken = $csrfService->generateToken();

// Helper functions
function formatDateTime($datetime) {
    return date('M j, Y \a\t g:i A', strtotime($datetime));
}

function formatRelativeTime($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) {
        $m = floor($diff / 60);
        return $m . ' min' . ($m > 1 ? 's' : '') . ' ago';
    }
    if ($diff < 86400) {
        $h = floor($diff / 3600);
        return $h . ' hour' . ($h > 1 ? 's' : '') . ' ago';
    }
    return date('M j, Y', $timestamp);
}

function getStatusClass($status) {
    return match($status) {
        'OPEN' => 'pending',
        'IN_PROGRESS' => 'pending',
        'WAITING_ON_CUSTOMER' => 'inactive',
        'RESOLVED' => 'active',
        'CLOSED' => 'expired',
        default => 'inactive'
    };
}

function getPriorityClass($priority) {
    return match($priority) {
        'LOW' => 'inactive',
        'MEDIUM' => 'pending',
        'HIGH' => 'pending',
        'URGENT' => 'expired',
        default => 'inactive'
    };
}

function formatStatus($status) {
    return ucwords(str_replace('_', ' ', strtolower($status)));
}

function getAuthorName($authorId, $authorType, $userModel) {
    $user = $userModel->findById($authorId);
    if ($authorType === 'CUSTOMER') {
        return $user ? $user['name'] : 'You';
    }
    return $user ? $user['name'] : 'Support Team';
}

// Set page variables
$page_title = 'Ticket #' . strtoupper(substr($ticket['id'], 0, 8));

// Include customer portal header
require_once __DIR__ . '/../../../templates/customer-header.php';
?>

<div class="section-header">
    <h2 class="section-title">Ticket Details</h2>
</div>

<!-- Back Link -->
<div class="quick-actions" style="margin-bottom: 1.5rem;">
    <a href="<?php echo get_app_base_url(); ?>/app/support/tickets.php" class="btn btn-outline">← Back to Tickets</a>
</div>

<!-- Ticket Info Card -->
<div class="info-box">
    <div class="info-box-content" style="padding: 0;">
        <!-- Ticket Header -->
        <div class="ticket-header">
            <div class="ticket-header-main">
                <code class="ticket-id">#<?php echo strtoupper(substr($ticket['id'], 0, 8)); ?></code>
                <h3 class="ticket-subject"><?php echo htmlspecialchars($ticket['subject']); ?></h3>
            </div>
            <div class="ticket-badges">
                <span class="subscription-status <?php echo getStatusClass($ticket['status']); ?>">
                    <?php echo formatStatus($ticket['status']); ?>
                </span>
                <span class="subscription-status <?php echo getPriorityClass($ticket['priority']); ?>">
                    <?php echo htmlspecialchars($ticket['priority']); ?>
                </span>
            </div>
        </div>
        
        <!-- Ticket Meta -->
        <div class="ticket-meta">
            <div class="ticket-meta-item">
                <span class="ticket-meta-label">Category</span>
                <span class="ticket-meta-value"><?php echo htmlspecialchars($ticket['category'] ?? 'General'); ?></span>
            </div>
            <div class="ticket-meta-item">
                <span class="ticket-meta-label">Created</span>
                <span class="ticket-meta-value"><?php echo formatDateTime($ticket['created_at']); ?></span>
            </div>
            <div class="ticket-meta-item">
                <span class="ticket-meta-label">Last Updated</span>
                <span class="ticket-meta-value"><?php echo formatRelativeTime($ticket['updated_at']); ?></span>
            </div>
            <?php if ($ticket['subscription_id']): ?>
                <div class="ticket-meta-item">
                    <span class="ticket-meta-label">Subscription</span>
                    <span class="ticket-meta-value">#<?php echo strtoupper(substr($ticket['subscription_id'], 0, 8)); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Conversation Thread -->
<div class="info-box" style="margin-top: 1.5rem;">
    <h3 class="info-box-title">Conversation</h3>
    <div class="info-box-content" style="padding: 0;">
        <?php if (empty($messages)): ?>
            <div style="padding: 2rem; text-align: center; color: #6b7280;">
                <p>No messages yet. Add a reply below to start the conversation.</p>
            </div>
        <?php else: ?>
            <div class="message-thread">
                <?php foreach ($messages as $index => $message): ?>
                    <?php 
                    $isCustomer = $message['author_type'] === 'CUSTOMER';
                    $authorName = getAuthorName($message['author_id'], $message['author_type'], $userModel);
                    ?>
                    <div class="message <?php echo $isCustomer ? 'message-customer' : 'message-support'; ?>">
                        <div class="message-header">
                            <div class="message-author">
                                <span class="message-avatar <?php echo $isCustomer ? 'avatar-customer' : 'avatar-support'; ?>">
                                    <?php echo $isCustomer ? '👤' : '🛟'; ?>
                                </span>
                                <div class="message-author-info">
                                    <span class="message-author-name"><?php echo htmlspecialchars($authorName); ?></span>
                                    <?php if (!$isCustomer): ?>
                                        <span class="message-author-badge">Support Team</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="message-time"><?php echo formatDateTime($message['created_at']); ?></span>
                        </div>
                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Reply Form -->
<?php if ($ticket['status'] !== 'CLOSED'): ?>
    <div class="info-box" style="margin-top: 1.5rem;">
        <h3 class="info-box-title">Add Reply</h3>
        <div class="info-box-content">
            <?php if (!empty($errors)): ?>
                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
                    <p style="color: #dc2626; font-weight: 500; margin: 0;">
                        <?php echo htmlspecialchars($errors[0]); ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="reply">
                
                <div class="form-group">
                    <textarea 
                        id="content" 
                        name="content" 
                        class="form-input form-textarea" 
                        rows="5"
                        required
                        placeholder="Type your reply here..."
                    ><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Send Reply</button>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="info-box" style="margin-top: 1.5rem; border-left: 4px solid #6b7280;">
        <div class="info-box-content">
            <p style="margin: 0; color: #6b7280;">
                <strong>This ticket is closed.</strong> You cannot add replies to a closed ticket. 
                If you need further assistance, please <a href="<?php echo get_app_base_url(); ?>/app/support/tickets/new.php" style="color: #2563eb;">create a new ticket</a>.
            </p>
        </div>
    </div>
<?php endif; ?>

<style>
/* Ticket Header */
.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.ticket-header-main {
    flex: 1;
}

.ticket-id {
    font-family: 'SF Mono', 'Monaco', 'Inconsolata', monospace;
    font-size: 0.75rem;
    background: #f3f4f6;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    color: #6b7280;
}

.ticket-subject {
    margin: 0.75rem 0 0 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    line-height: 1.4;
}

.ticket-badges {
    display: flex;
    gap: 0.5rem;
    flex-shrink: 0;
}

/* Ticket Meta */
.ticket-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    background: #f9fafb;
}

.ticket-meta-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.ticket-meta-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.ticket-meta-value {
    font-size: 0.9375rem;
    color: #1f2937;
}

/* Message Thread */
.message-thread {
    display: flex;
    flex-direction: column;
}

.message {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #f3f4f6;
}

.message:last-child {
    border-bottom: none;
}

.message-customer {
    background: #fff;
}

.message-support {
    background: #f0f9ff;
}

.message-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.message-author {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.message-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.avatar-customer {
    background: #e5e7eb;
}

.avatar-support {
    background: #dbeafe;
}

.message-author-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.message-author-name {
    font-weight: 600;
    color: #1f2937;
    font-size: 0.9375rem;
}

.message-author-badge {
    font-size: 0.6875rem;
    font-weight: 600;
    color: #2563eb;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.message-time {
    font-size: 0.8125rem;
    color: #9ca3af;
}

.message-content {
    color: #374151;
    line-height: 1.7;
    font-size: 0.9375rem;
    padding-left: 2.75rem;
}

/* Form Styles */
.form-group {
    margin-bottom: 1rem;
}

.form-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9375rem;
    color: #1f2937;
    background: #fff;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 120px;
    font-family: inherit;
    line-height: 1.6;
}

.form-actions {
    display: flex;
    gap: 1rem;
}

@media (max-width: 640px) {
    .ticket-header {
        flex-direction: column;
    }
    
    .ticket-badges {
        margin-top: 1rem;
    }
    
    .message-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .message-content {
        padding-left: 0;
        margin-top: 0.75rem;
    }
}
</style>

<?php
// Include customer portal footer
require_once __DIR__ . '/../../../templates/customer-footer.php';
?>
