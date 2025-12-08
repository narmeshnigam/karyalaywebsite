<?php
/**
 * Create New Support Ticket Page
 * Allows customers to create new support tickets
 * 
 * Requirements: 7.1
 */

// Load Composer autoloader
require_once __DIR__ . '/../../../vendor/autoload.php';

// Include required files
require_once __DIR__ . '/../../../includes/auth_helpers.php';

use Karyalay\Services\TicketService;
use Karyalay\Services\CsrfService;
use Karyalay\Models\Subscription;

// Guard customer portal - requires authentication
$user = guardCustomerPortal();
$customerId = $user['id'];

// Set page variables
$page_title = 'Create New Ticket';

// Include customer portal header
require_once __DIR__ . '/../../../templates/customer-header.php';

// Initialize services
$ticketService = new TicketService();
$csrfService = new CsrfService();
$subscriptionModel = new Subscription();

// Get customer ID from session
$customerId = $_SESSION['user_id'];

// Get customer's subscriptions for linking
$subscriptions = $subscriptionModel->findByCustomerId($customerId);

// Handle form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$csrfService->validateToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Get form data
        $subject = trim($_POST['subject'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $priority = trim($_POST['priority'] ?? 'MEDIUM');
        $description = trim($_POST['description'] ?? '');
        $subscriptionId = trim($_POST['subscription_id'] ?? '');
        
        // Validate required fields
        if (empty($subject)) {
            $errors[] = 'Subject is required.';
        }
        
        if (empty($description)) {
            $errors[] = 'Description is required.';
        }
        
        if (empty($category)) {
            $errors[] = 'Category is required.';
        }
        
        // If no errors, create ticket
        if (empty($errors)) {
            $ticketData = [
                'customer_id' => $customerId,
                'subscription_id' => !empty($subscriptionId) ? $subscriptionId : null,
                'subject' => $subject,
                'category' => $category,
                'priority' => $priority,
                'status' => 'OPEN'
            ];
            
            $result = $ticketService->createTicket($ticketData);
            
            if ($result['success']) {
                // Set success message
                $_SESSION['flash_message'] = 'Ticket created successfully! Our support team will respond soon.';
                $_SESSION['flash_type'] = 'success';
                
                // Redirect to ticket detail page
                header('Location: /karyalayportal/app/support/tickets/view.php?id=' . urlencode($result['ticket']['id']));
                exit;
            } else {
                $errors[] = $result['error'] ?? 'Failed to create ticket. Please try again.';
            }
        }
    }
}

// Generate CSRF token
$csrfToken = $csrfService->generateToken();
?>

<div class="section-header">
    <h2 class="section-title">Create New Support Ticket</h2>
</div>

<div class="form-container">
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
        
        <div class="form-group">
            <label for="subject" class="form-label">Subject <span class="required">*</span></label>
            <input 
                type="text" 
                id="subject" 
                name="subject" 
                class="form-control" 
                value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>"
                required
                maxlength="255"
                placeholder="Brief description of your issue"
            >
            <small class="form-text">Provide a clear, concise subject for your ticket</small>
        </div>
        
        <div class="form-row">
            <div class="form-group form-col-6">
                <label for="category" class="form-label">Category <span class="required">*</span></label>
                <select id="category" name="category" class="form-control" required>
                    <option value="">Select a category</option>
                    <option value="Technical" <?php echo (($_POST['category'] ?? '') === 'Technical') ? 'selected' : ''; ?>>Technical Issue</option>
                    <option value="Billing" <?php echo (($_POST['category'] ?? '') === 'Billing') ? 'selected' : ''; ?>>Billing Question</option>
                    <option value="Account" <?php echo (($_POST['category'] ?? '') === 'Account') ? 'selected' : ''; ?>>Account Management</option>
                    <option value="Feature Request" <?php echo (($_POST['category'] ?? '') === 'Feature Request') ? 'selected' : ''; ?>>Feature Request</option>
                    <option value="General" <?php echo (($_POST['category'] ?? '') === 'General') ? 'selected' : ''; ?>>General Inquiry</option>
                </select>
            </div>
            
            <div class="form-group form-col-6">
                <label for="priority" class="form-label">Priority <span class="required">*</span></label>
                <select id="priority" name="priority" class="form-control" required>
                    <option value="LOW" <?php echo (($_POST['priority'] ?? 'MEDIUM') === 'LOW') ? 'selected' : ''; ?>>Low</option>
                    <option value="MEDIUM" <?php echo (($_POST['priority'] ?? 'MEDIUM') === 'MEDIUM') ? 'selected' : ''; ?>>Medium</option>
                    <option value="HIGH" <?php echo (($_POST['priority'] ?? 'MEDIUM') === 'HIGH') ? 'selected' : ''; ?>>High</option>
                    <option value="URGENT" <?php echo (($_POST['priority'] ?? 'MEDIUM') === 'URGENT') ? 'selected' : ''; ?>>Urgent</option>
                </select>
            </div>
        </div>
        
        <?php if (!empty($subscriptions)): ?>
            <div class="form-group">
                <label for="subscription_id" class="form-label">Related Subscription (Optional)</label>
                <select id="subscription_id" name="subscription_id" class="form-control">
                    <option value="">None</option>
                    <?php foreach ($subscriptions as $subscription): ?>
                        <option 
                            value="<?php echo htmlspecialchars($subscription['id']); ?>"
                            <?php echo (($_POST['subscription_id'] ?? '') === $subscription['id']) ? 'selected' : ''; ?>
                        >
                            Subscription #<?php echo htmlspecialchars(substr($subscription['id'], 0, 8)); ?> - 
                            <?php echo htmlspecialchars($subscription['status']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="form-text">Link this ticket to a specific subscription if applicable</small>
            </div>
        <?php endif; ?>
        
        <div class="form-group">
            <label for="description" class="form-label">Description <span class="required">*</span></label>
            <textarea 
                id="description" 
                name="description" 
                class="form-control" 
                rows="8"
                required
                placeholder="Please provide detailed information about your issue or question..."
            ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            <small class="form-text">Include as much detail as possible to help us assist you quickly</small>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Ticket</button>
            <a href="/app/support/tickets.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<?php
// Include customer portal footer
require_once __DIR__ . '/../../../templates/customer-footer.php';
?>
