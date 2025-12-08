<?php
/**
 * Support Tickets List Page
 * View all support tickets for the logged-in customer
 * 
 * Requirements: 7.2
 */

// Load Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Include required files
require_once __DIR__ . '/../../includes/auth_helpers.php';

use Karyalay\Services\TicketService;

// Guard customer portal - requires authentication
$user = guardCustomerPortal();
$customerId = $user['id'];

// Set page variables
$page_title = 'Support Tickets';

// Include customer portal header
require_once __DIR__ . '/../../templates/customer-header.php';

// Initialize ticket service
$ticketService = new TicketService();

// Fetch customer's tickets
$result = $ticketService->getCustomerTickets($customerId);
$tickets = $result['success'] ? $result['tickets'] : [];

/**
 * Format date for display
 */
function formatRelativeTime($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}

/**
 * Get badge class for status
 */
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

/**
 * Get badge class for priority
 */
function getPriorityBadgeClass($priority) {
    $classes = [
        'LOW' => 'badge-secondary',
        'MEDIUM' => 'badge-info',
        'HIGH' => 'badge-warning',
        'URGENT' => 'badge-danger'
    ];
    return $classes[$priority] ?? 'badge-secondary';
}

/**
 * Format status for display
 */
function formatStatus($status) {
    return ucwords(str_replace('_', ' ', strtolower($status)));
}
?>

<div class="section-header">
    <h2 class="section-title">Support Tickets</h2>
    <div class="section-actions">
        <a href="<?php echo get_base_url(); ?>/app/support/tickets/new.php" class="btn btn-primary">Create New Ticket</a>
    </div>
</div>

<?php if (empty($tickets)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">🎫</div>
        <h3 class="empty-state-title">No Support Tickets</h3>
        <p class="empty-state-text">You haven't created any support tickets yet. If you need help, create a new ticket and our team will assist you.</p>
        <a href="<?php echo get_base_url(); ?>/app/support/tickets/new.php" class="btn btn-primary">Create Your First Ticket</a>
    </div>
<?php else: ?>
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Ticket ID</th>
                    <th>Subject</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Last Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td><strong>#<?php echo htmlspecialchars(substr($ticket['id'], 0, 8)); ?></strong></td>
                        <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                        <td><?php echo htmlspecialchars($ticket['category'] ?? 'General'); ?></td>
                        <td>
                            <span class="badge <?php echo getPriorityBadgeClass($ticket['priority']); ?>">
                                <?php echo htmlspecialchars($ticket['priority']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo getStatusBadgeClass($ticket['status']); ?>">
                                <?php echo formatStatus($ticket['status']); ?>
                            </span>
                        </td>
                        <td><?php echo formatRelativeTime($ticket['updated_at']); ?></td>
                        <td>
                            <a href="<?php echo get_base_url(); ?>/app/support/tickets/view.php?id=<?php echo urlencode($ticket['id']); ?>" class="btn btn-sm btn-outline">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
// Include customer portal footer
require_once __DIR__ . '/../../templates/customer-footer.php';
?>
