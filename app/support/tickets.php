<?php
/**
 * Support Tickets List Page
 * View all support tickets for the logged-in customer
 */

// Load Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Include required files
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Services\TicketService;

// Guard customer portal - requires authentication
$user = guardCustomerPortal();
$customerId = $user['id'];

// Initialize ticket service
$ticketService = new TicketService();

// Fetch customer's tickets
$result = $ticketService->getCustomerTickets($customerId);
$tickets = $result['success'] ? $result['tickets'] : [];

// Calculate ticket statistics
$openTickets = array_filter($tickets, fn($t) => in_array($t['status'], ['OPEN', 'IN_PROGRESS', 'WAITING_ON_CUSTOMER']));
$resolvedTickets = array_filter($tickets, fn($t) => in_array($t['status'], ['RESOLVED', 'CLOSED']));

// Helper functions
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
    if ($diff < 604800) {
        $d = floor($diff / 86400);
        return $d . ' day' . ($d > 1 ? 's' : '') . ' ago';
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

// Set page variables
$page_title = 'Support Tickets';

// Include customer portal header
require_once __DIR__ . '/../../templates/customer-header.php';
?>

<div class="section-header">
    <h2 class="section-title">Support Tickets</h2>
</div>

<!-- Quick Actions -->
<div class="quick-actions" style="margin-bottom: 1.5rem;">
    <a href="<?php echo get_app_base_url(); ?>/app/support/tickets/new.php" class="btn btn-primary">+ Create New Ticket</a>
    <a href="<?php echo get_base_url(); ?>/faqs.php" class="btn btn-outline">View FAQs</a>
</div>

<?php if (empty($tickets)): ?>
    <div class="info-box">
        <div class="info-box-content">
            <p style="text-align: center; padding: 2rem 0;">

                <strong>No Support Tickets</strong><br>
                <span style="color: #666; font-size: 0.9rem;">You haven't created any support tickets yet.<br>If you need help, create a new ticket and our team will assist you.</span>
            </p>
            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="<?php echo get_app_base_url(); ?>/app/support/tickets/new.php" class="btn btn-primary">Create Your First Ticket</a>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Tickets Table -->
    <div class="info-box">
        <h3 class="info-box-title">Your Tickets</h3>
        <div class="info-box-content" style="padding: 0;">
            <table class="billing-table">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <code class="order-id-code">#<?php echo strtoupper(substr($ticket['id'], 0, 8)); ?></code>
                                    <span style="font-weight: 500; color: #1f2937;"><?php echo htmlspecialchars($ticket['subject']); ?></span>
                                </div>
                            </td>
                            <td>
                                <span style="color: #6b7280; font-size: 0.875rem;">
                                    <?php echo htmlspecialchars($ticket['category'] ?? 'General'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="subscription-status <?php echo getPriorityClass($ticket['priority']); ?>">
                                    <?php echo htmlspecialchars($ticket['priority']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="subscription-status <?php echo getStatusClass($ticket['status']); ?>">
                                    <?php echo formatStatus($ticket['status']); ?>
                                </span>
                            </td>
                            <td>
                                <span style="color: #6b7280; font-size: 0.875rem;">
                                    <?php echo formatRelativeTime($ticket['updated_at']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo get_app_base_url(); ?>/app/support/tickets/view.php?id=<?php echo urlencode($ticket['id']); ?>" class="btn btn-sm btn-outline">
                                    View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<style>
.billing-table {
    width: 100%;
    border-collapse: collapse;
}

.billing-table th {
    background: #f9fafb;
    padding: 0.875rem 1rem;
    text-align: left;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #6b7280;
    border-bottom: 1px solid #e5e7eb;
}

.billing-table td {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
    font-size: 0.9375rem;
    color: #374151;
    vertical-align: middle;
}

.billing-table tbody tr:hover {
    background: #f9fafb;
}

.billing-table tbody tr:last-child td {
    border-bottom: none;
}

.order-id-code {
    font-family: 'SF Mono', 'Monaco', 'Inconsolata', monospace;
    font-size: 0.75rem;
    background: #f3f4f6;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    color: #374151;
}

.btn-sm {
    font-size: 0.8125rem;
    padding: 0.375rem 0.75rem;
}

.btn-outline {
    background: white;
    border: 1px solid #d1d5db;
    color: #374151;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.btn-outline:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

@media (max-width: 768px) {
    .billing-table {
        display: block;
        overflow-x: auto;
    }
    
    .billing-table th,
    .billing-table td {
        white-space: nowrap;
    }
}
</style>

<?php
// Include customer portal footer
require_once __DIR__ . '/../../templates/customer-footer.php';
?>
