<?php
/**
 * Accessible Form Example
 * Demonstrates proper form accessibility with labels and ARIA attributes
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/template_helpers.php';

startSecureSession();

use Karyalay\Services\CsrfService;

$csrfService = new CsrfService();
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$csrfService->validateToken($_POST['csrf_token'] ?? '')) {
        $errors['csrf'] = 'Invalid security token. Please try again.';
    }
    
    // Validate fields
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name)) {
        $errors['name'] = 'Name is required.';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    
    if (empty($message)) {
        $errors['message'] = 'Message is required.';
    } elseif (strlen($message) < 10) {
        $errors['message'] = 'Message must be at least 10 characters long.';
    }
    
    if (empty($errors)) {
        $success = true;
        $_POST = []; // Clear form
    }
}

$csrfToken = $csrfService->generateToken();
$page_title = 'Accessible Form Example';
include_header($page_title);
?>

<section class="section">
    <div class="container">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-3xl font-bold mb-6">Accessible Form Example</h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success" role="alert" aria-live="polite">
                    <strong>Success!</strong> Your form has been submitted successfully.
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <?php echo render_form_errors($errors); ?>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="/accessible-form-example.php" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        
                        <!-- Name Field -->
                        <div class="form-group<?php echo isset($errors['name']) ? ' has-error' : ''; ?>">
                            <label for="name" class="form-label">
                                Name <span class="text-red-500" aria-label="required">*</span>
                            </label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   class="form-control<?php echo isset($errors['name']) ? ' is-invalid' : ''; ?>"
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                   required
                                   aria-required="true"
                                   <?php if (isset($errors['name'])): ?>
                                   aria-invalid="true"
                                   aria-describedby="name-error"
                                   <?php endif; ?>>
                            <?php if (isset($errors['name'])): ?>
                                <div id="name-error" class="form-error" role="alert" aria-live="polite">
                                    <?php echo htmlspecialchars($errors['name']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Email Field -->
                        <div class="form-group<?php echo isset($errors['email']) ? ' has-error' : ''; ?>">
                            <label for="email" class="form-label">
                                Email <span class="text-red-500" aria-label="required">*</span>
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="form-control<?php echo isset($errors['email']) ? ' is-invalid' : ''; ?>"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                   required
                                   aria-required="true"
                                   <?php if (isset($errors['email'])): ?>
                                   aria-invalid="true"
                                   aria-describedby="email-error"
                                   <?php endif; ?>>
                            <?php if (isset($errors['email'])): ?>
                                <div id="email-error" class="form-error" role="alert" aria-live="polite">
                                    <?php echo htmlspecialchars($errors['email']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Phone Field (Optional) -->
                        <div class="form-group">
                            <label for="phone-input" class="form-label">
                                Phone
                            </label>
                            <?php echo render_phone_input([
                                'id' => 'phone',
                                'name' => 'phone',
                                'value' => $_POST['phone'] ?? '',
                                'required' => false,
                            ]); ?>
                            <small id="phone-help" class="form-help">
                                Optional: Include your phone number if you'd like us to call you.
                            </small>
                        </div>
                        
                        <!-- Message Field -->
                        <div class="form-group<?php echo isset($errors['message']) ? ' has-error' : ''; ?>">
                            <label for="message" class="form-label">
                                Message <span class="text-red-500" aria-label="required">*</span>
                            </label>
                            <textarea id="message" 
                                      name="message" 
                                      class="form-control<?php echo isset($errors['message']) ? ' is-invalid' : ''; ?>"
                                      rows="5"
                                      required
                                      aria-required="true"
                                      <?php if (isset($errors['message'])): ?>
                                      aria-invalid="true"
                                      aria-describedby="message-error message-help"
                                      <?php else: ?>
                                      aria-describedby="message-help"
                                      <?php endif; ?>><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                            <?php if (isset($errors['message'])): ?>
                                <div id="message-error" class="form-error" role="alert" aria-live="polite">
                                    <?php echo htmlspecialchars($errors['message']); ?>
                                </div>
                            <?php endif; ?>
                            <small id="message-help" class="form-help">
                                Please provide at least 10 characters.
                            </small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            Submit Form
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card mt-6">
                <div class="card-body">
                    <h2 class="text-xl font-bold mb-4">Accessibility Features Demonstrated</h2>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>All form fields have associated <code>&lt;label&gt;</code> elements with <code>for</code> attributes</li>
                        <li>Required fields are marked with <code>aria-required="true"</code></li>
                        <li>Required field indicators have <code>aria-label="required"</code></li>
                        <li>Invalid fields have <code>aria-invalid="true"</code></li>
                        <li>Error messages are linked via <code>aria-describedby</code></li>
                        <li>Error messages have <code>role="alert"</code> and <code>aria-live="polite"</code></li>
                        <li>Help text is linked via <code>aria-describedby</code></li>
                        <li>Form errors summary at top with links to fields</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include_footer(); ?>
