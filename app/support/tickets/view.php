<?php
/**
 * Ticket Detail Page
 * View ticket details and message thread, allow customer to add replies
 * 
 * Requirements: 7.3, 7.4, 7.5
 */

// Load Composer autoloader
require_once __DIR__ . '/../../../vendor/autoload.php';

// Include required files
require_once __DIR__ . '/../../../includes/auth_helpers.php';

use Karyalay\Services\TicketService;
use Karyalay\Services\CsrfService;
use Karyalay\Models\TicketMessage;
use Karyalay\Models\User;

// Guard customer portal - requires authentication
$user = guardCustomerPortal();
$customerId = $user['id'];

// Set page variables
$page_title = 'Ticket Details';

// Include customer portal header
require_once __DIR__ . '/../../../templates/customer-header.php';

// Get ticket ID from query string
$ticketId = $_GET['id'] ?? '';

if (empty($ticketId)) {
    $_SESSION['flash_message'] = 'Invalid ticket ID.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /karyalayportal/app/support/tickets.php');
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
    header('Location: /karyalayportal/app/support/tickets.php');
    exit;
}

$ticket = $result['ticket'];

// Verify ticket belongs to customer
if ($ticket['customer_id'] !== $customerId) {
    $_SESSION['flash_message'] = 'You do not have permission to view this ticket.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /karyalayportal/app/support/tickets.php');
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
        // Check if ticket is closed (Requirement 7.5)
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
                    // Set success message
                    $_SESSION['flash_message'] = 'Reply added successfully.';
                    $_SESSION['flash_type'] = 'success';
                    
                    // Redirect to refresh page
                    header('Location: /karyalayportal/app/support/tickets/view.php?id=' . urlencode($ticketId));
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

function getStatusBadgeClass($status) {
    $classes = [
        'OPEN' => 'badge-info',
        'IN_PROGRESS' => 'badge-warning',
        'WAITING_ON_CUSTOMER' => 'badge-secondary',
        'RESOLVED' => 'badge-success',
        'CLOSED' => 'badge-dark'
    ];
    return $classes[$status] ?? 'badge-secondary';
}

function getPriorityBadgeClass($priority) {
    $classes = [
        'LOW' => 'badge-secondary',
        'MEDIUM' => 'badge-info',
        'HIGH' => 'badge-warning',
        'URGENT' => 'badge-danger'
    ];
    return $classes[$priority] ?? 'badge-secondary';
}

function formatStatus($status) {
    return ucwords(str_replace('_', ' ', strtolower($status)));
}

// Get author name for messages
function getAuthorName($authorId, $authorType, $userModel) {
    if ($authorType === 'CUSTOMER') {
        $user = $userModel->findById($authorId);
        return $user ? $user['name'] : 'Customer';
    } else {
        $user = $userModel->findById($authorId);
        return $user ? $user['name'] . ' (Support)' : 'Support Team';
    }
}
?>

<div class="section-header">
    <div>
        <a href="/app/support/tickets.php" class="btn btn-sm btn-outline">← Back to Tickets</a>
        <h2 class="section-title" style="margin-top: 1rem;">Ticket #<?php echo htmlspecialchars(substr($ticket['id'], 0, 8)); ?></h2>
    </div>
</div>

<!-- Ticket Metadata -->
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-body">
        <div class="ticket-header">
            <h3 class="ticket-subject"><?php echo htmlspecialchars($ticket['subject']); ?></h3>
            <div class="ticket-badges">
                <span class="badge <?php echo getStatusBadgeClass($ticket['status']); ?>">
                    <?php echo formatStatus($ticket['status']); ?>
                </span>
                <span class="badge <?php echo getPriorityBadgeClass($ticket['priority']); ?>">
                    <?php echo htmlspecialchars($ticket['priority']); ?> Priority
                </span>
            </div>
        </div>
        
        <div class="ticket-meta" style="margin-top: 1rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div>
                <strong>Category:</strong> <?php echo htmlspecialchars($ticket['category'] ?? 'General'); ?>
            </div>
            <div>
                <strong>Created:</strong> <?php echo formatDateTime($ticket['created_at']); ?>
            </div>
            <div>
                <strong>Last Updated:</strong> <?php echo formatDateTime($ticket['updated_at']); ?>
            </div>
            <?php if ($ticket['subscription_id']): ?>
                <div>
                    <strong>Subscription:</strong> #<?php echo htmlspecialchars(substr($ticket['subscription_id'], 0, 8)); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Message Thread -->
<div class="card">
    <div class="card-header">
        <h4>Conversation</h4>
    </div>
    <div class="card-body">
        <?php if (empty($messages)): ?>
            <p class="text-muted">No messages yet.</p>
        <?php else: ?>
            <div class="message-thread">
                <?php foreach ($messages as $message): ?>
                    <div class="message <?php echo $message['author_type'] === 'CUSTOMER' ? 'message-customer' : 'message-admin'; ?>">
                        <div class="message-header">
                            <div class="message-author">
                                <strong><?php echo htmlspecialchars(getAuthorName($message['author_id'], $message['author_type'], $userModel)); ?></strong>
                                <?php if ($message['author_type'] === 'ADMIN'): ?>
                                    <span class="badge badge-sm badge-primary">Support Team</span>
                                <?php endif; ?>
                            </div>
                            <div class="message-time">
                                <?php echo formatDateTime($message['created_at']); ?>
                            </div>
                        </div>
                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                        </div>
                        <?php if (!empty($message['attachments'])): ?>
                            <div class="message-attachments">
                                <strong>Attachments:</strong>
                                <ul>
                                    <?php foreach ($message['attachments'] as $attachment): ?>
                                        <li><?php echo htmlspecialchars($attachment); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Reply Form -->
<?php if ($ticket['status'] !== 'CLOSED'): ?>
    <div class="card" style="margin-top: 2rem;">
        <div class="card-header">
            <h4>Add Reply</h4>
        </div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <strong>Error:</strong>
                    <ul class="error-list">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="reply">
                
                <div class="form-group">
                    <label for="content" class="form-label">Your Reply</label>
                    <textarea 
                        id="content" 
                        name="content" 
                        class="form-control" 
                        rows="6"
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
    <div class="alert alert-info" style="margin-top: 2rem;">
        <strong>This ticket is closed.</strong> You cannot add replies to a closed ticket. If you need further assistance, please create a new ticket.
    </div>
<?php endif; ?>

<style>
.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
}

.ticket-subject {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.ticket-badges {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.message-thread {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.message {
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
}

.message-customer {
    background-color: #f8f9fa;
    margin-left: 2rem;
}

.message-admin {
    background-color: #e3f2fd;
    margin-right: 2rem;
}

.message-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.message-author {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.message-time {
    font-size: 0.875rem;
    color: #666;
}

.message-content {
    line-height: 1.6;
    color: #333;
}

.message-attachments {
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.message-attachments ul {
    margin: 0.5rem 0 0 0;
    padding-left: 1.5rem;
}

.badge-sm {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.error-list {
    margin: 0.5rem 0 0 0;
    padding-left: 1.5rem;
}

@media (max-width: 768px) {
    .ticket-header {
        flex-direction: column;
    }
    
    .message-customer,
    .message-admin {
        margin-left: 0;
        margin-right: 0;
    }
}
</style>

<?php
// Include customer portal footer
require_once __DIR__ . '/../../../templates/customer-footer.php';
?>
