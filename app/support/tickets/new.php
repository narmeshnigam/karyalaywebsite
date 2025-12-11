<?php
/**
 * Create New Support Ticket Page
 * Allows customers to create new support tickets
 */

// Load Composer autoloader
require_once __DIR__ . '/../../../vendor/autoload.php';

// Include required files
require_once __DIR__ . '/../../../includes/auth_helpers.php';
require_once __DIR__ . '/../../../includes/template_helpers.php';

use Karyalay\Services\TicketService;
use Karyalay\Services\CsrfService;
use Karyalay\Models\Subscription;
use Karyalay\Models\TicketMessage;

// Guard customer portal - requires authentication
$user = guardCustomerPortal();
$customerId = $user['id'];

// Initialize services
$ticketService = new TicketService();
$csrfService = new CsrfService();
$subscriptionModel = new Subscription();
$messageModel = new TicketMessage();

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
        } elseif (strlen($subject) > 255) {
            $errors[] = 'Subject must be less than 255 characters.';
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
                'status' => 'OPEN',
                'description' => $description // Pass description for email notification
            ];
            
            $result = $ticketService->createTicket($ticketData);
            
            if ($result['success']) {
                // Create initial message with the description
                $messageData = [
                    'ticket_id' => $result['ticket']['id'],
                    'author_id' => $customerId,
                    'author_type' => 'CUSTOMER',
                    'content' => $description,
                    'is_internal' => false
                ];
                $messageModel->create($messageData);
                
                // Set success message
                $_SESSION['flash_message'] = 'Ticket created successfully! You will receive a confirmation email shortly. Our support team will contact you via email or phone.';
                $_SESSION['flash_type'] = 'success';
                
                // Redirect to ticket detail page
                header('Location: ' . get_app_base_url() . '/app/support/tickets/view.php?id=' . urlencode($result['ticket']['id']));
                exit;
            } else {
                $errors[] = $result['error'] ?? 'Failed to create ticket. Please try again.';
            }
        }
    }
}

// Generate CSRF token
$csrfToken = $csrfService->generateToken();

// Set page variables
$page_title = 'Create New Ticket';

// Include customer portal header
require_once __DIR__ . '/../../../templates/customer-header.php';
?>

<div class="section-header">
    <h2 class="section-title">Create New Ticket</h2>
</div>

<!-- Back Link -->
<div class="quick-actions" style="margin-bottom: 1.5rem;">
    <a href="<?php echo get_app_base_url(); ?>/app/support/tickets.php" class="btn btn-outline">← Back to Tickets</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="info-box" style="border-left: 4px solid #ef4444; margin-bottom: 1.5rem;">
        <div class="info-box-content">
            <p style="color: #ef4444; font-weight: 600; margin-bottom: 0.5rem;">Please fix the following errors:</p>
            <ul style="margin: 0; padding-left: 1.25rem; color: #dc2626;">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<!-- Ticket Form -->
<div class="info-box">
    <h3 class="info-box-title">Ticket Details</h3>
    <div class="info-box-content">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            
            <!-- Subject -->
            <div class="form-group">
                <label for="subject" class="form-label">Subject <span style="color: #ef4444;">*</span></label>
                <input 
                    type="text" 
                    id="subject" 
                    name="subject" 
                    class="form-input" 
                    value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>"
                    required
                    maxlength="255"
                    placeholder="Brief description of your issue"
                >
                <p class="form-hint">Provide a clear, concise subject for your ticket</p>
            </div>
            
            <!-- Category & Priority Row -->
            <div class="form-row">
                <div class="form-group form-col">
                    <label for="category" class="form-label">Category <span style="color: #ef4444;">*</span></label>
                    <select id="category" name="category" class="form-input" required>
                        <option value="">Select a category</option>
                        <option value="Technical" <?php echo (($_POST['category'] ?? '') === 'Technical') ? 'selected' : ''; ?>>Technical Issue</option>
                        <option value="Billing" <?php echo (($_POST['category'] ?? '') === 'Billing') ? 'selected' : ''; ?>>Billing Question</option>
                        <option value="Account" <?php echo (($_POST['category'] ?? '') === 'Account') ? 'selected' : ''; ?>>Account Management</option>
                        <option value="Feature Request" <?php echo (($_POST['category'] ?? '') === 'Feature Request') ? 'selected' : ''; ?>>Feature Request</option>
                        <option value="General" <?php echo (($_POST['category'] ?? '') === 'General') ? 'selected' : ''; ?>>General Inquiry</option>
                    </select>
                </div>
                
                <div class="form-group form-col">
                    <label for="priority" class="form-label">Priority <span style="color: #ef4444;">*</span></label>
                    <select id="priority" name="priority" class="form-input" required>
                        <option value="LOW" <?php echo (($_POST['priority'] ?? 'MEDIUM') === 'LOW') ? 'selected' : ''; ?>>Low - General question</option>
                        <option value="MEDIUM" <?php echo (($_POST['priority'] ?? 'MEDIUM') === 'MEDIUM') ? 'selected' : ''; ?>>Medium - Need help soon</option>
                        <option value="HIGH" <?php echo (($_POST['priority'] ?? 'MEDIUM') === 'HIGH') ? 'selected' : ''; ?>>High - Affecting my work</option>
                        <option value="URGENT" <?php echo (($_POST['priority'] ?? 'MEDIUM') === 'URGENT') ? 'selected' : ''; ?>>Urgent - Critical issue</option>
                    </select>
                </div>
            </div>
            
            <?php if (!empty($subscriptions)): ?>
                <!-- Related Subscription -->
                <div class="form-group">
                    <label for="subscription_id" class="form-label">Related Subscription</label>
                    <select id="subscription_id" name="subscription_id" class="form-input">
                        <option value="">None - General inquiry</option>
                        <?php foreach ($subscriptions as $subscription): ?>
                            <option 
                                value="<?php echo htmlspecialchars($subscription['id']); ?>"
                                <?php echo (($_POST['subscription_id'] ?? '') === $subscription['id']) ? 'selected' : ''; ?>
                            >
                                Subscription #<?php echo strtoupper(substr($subscription['id'], 0, 8)); ?> - 
                                <?php echo htmlspecialchars(ucfirst(strtolower($subscription['status']))); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-hint">Link this ticket to a specific subscription if applicable</p>
                </div>
            <?php endif; ?>
            
            <!-- Description -->
            <div class="form-group">
                <label for="description" class="form-label">Description <span style="color: #ef4444;">*</span></label>
                <textarea 
                    id="description" 
                    name="description" 
                    class="form-input form-textarea" 
                    rows="8"
                    required
                    placeholder="Please provide detailed information about your issue or question...

Include:
• What you were trying to do
• What happened instead
• Any error messages you saw
• Steps to reproduce the issue"
                ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                <p class="form-hint">Include as much detail as possible to help us assist you quickly</p>
            </div>
            
            <!-- Submit Buttons -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create Ticket</button>
                <a href="<?php echo get_app_base_url(); ?>/app/support/tickets.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<!-- Help Tips -->
<div class="info-box" style="margin-top: 1.5rem;">
    <h3 class="info-box-title">Tips for Faster Resolution</h3>
    <div class="info-box-content">
        <ul style="margin: 0; padding-left: 1.25rem; color: #6b7280; line-height: 1.8;">
            <li>Be specific about the issue you're experiencing</li>
            <li>Include any error messages or codes you've seen</li>
            <li>Mention what steps you've already tried</li>
            <li>For technical issues, include your browser and device information</li>
            <li>Check our <a href="<?php echo get_base_url(); ?>/faqs.php" style="color: #2563eb;">FAQs</a> for common solutions</li>
        </ul>
    </div>
</div>

<style>
.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
    font-size: 0.9375rem;
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

.form-input::placeholder {
    color: #9ca3af;
}

.form-textarea {
    resize: vertical;
    min-height: 160px;
    font-family: inherit;
    line-height: 1.6;
}

.form-hint {
    margin-top: 0.5rem;
    font-size: 0.8125rem;
    color: #6b7280;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

@media (max-width: 640px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
}
</style>

<?php
// Include customer portal footer
require_once __DIR__ . '/../../../templates/customer-footer.php';
?>
