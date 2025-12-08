<?php
/**
 * Admin Edit Plan Page
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Services\PlanService;

// Start secure session
startSecureSession();

// Require admin authentication
require_admin();

// Initialize services
$planService = new PlanService();

// Get plan ID from query parameter
$planId = $_GET['id'] ?? '';

if (empty($planId)) {
    $_SESSION['admin_error'] = 'Plan ID is required.';
    header('Location: ' . get_base_url() . '/admin/plans.php');
    exit;
}

// Fetch plan data
$plan = $planService->read($planId);

if (!$plan) {
    $_SESSION['admin_error'] = 'Plan not found.';
    header('Location: ' . get_base_url() . '/admin/plans.php');
    exit;
}

// Initialize variables
$errors = [];
$success = false;
$formData = [
    'name' => $plan['name'],
    'slug' => $plan['slug'],
    'description' => $plan['description'] ?? '',
    'price' => $plan['price'],
    'currency' => $plan['currency'] ?? 'USD',
    'billing_period_months' => $plan['billing_period_months'],
    'features' => is_array($plan['features']) ? implode("\n", $plan['features']) : '',
    'status' => $plan['status']
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken()) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Get form data
        $formData = [
            'name' => trim($_POST['name'] ?? ''),
            'slug' => trim($_POST['slug'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'price' => trim($_POST['price'] ?? ''),
            'currency' => trim($_POST['currency'] ?? 'USD'),
            'billing_period_months' => trim($_POST['billing_period_months'] ?? '1'),
            'features' => trim($_POST['features'] ?? ''),
            'status' => trim($_POST['status'] ?? 'ACTIVE')
        ];

        // Validate required fields
        if (empty($formData['name'])) {
            $errors[] = 'Plan name is required.';
        }
        if (empty($formData['price']) || !is_numeric($formData['price']) || $formData['price'] < 0) {
            $errors[] = 'Valid price is required.';
        }
        if (empty($formData['billing_period_months']) || !is_numeric($formData['billing_period_months']) || $formData['billing_period_months'] <= 0) {
            $errors[] = 'Valid billing period is required.';
        }

        // Parse features (one per line)
        $featuresArray = [];
        if (!empty($formData['features'])) {
            $featuresArray = array_filter(
                array_map('trim', explode("\n", $formData['features'])),
                function($line) { return !empty($line); }
            );
        }

        if (empty($errors)) {
            // Prepare data for update
            $updateData = [
                'name' => $formData['name'],
                'slug' => $formData['slug'],
                'description' => !empty($formData['description']) ? $formData['description'] : null,
                'price' => floatval($formData['price']),
                'currency' => $formData['currency'],
                'billing_period_months' => intval($formData['billing_period_months']),
                'features' => $featuresArray,
                'status' => $formData['status']
            ];

            // Update plan
            $result = $planService->update($planId, $updateData);

            if ($result) {
                $success = true;
                $_SESSION['admin_success'] = 'Plan updated successfully!';
                header('Location: ' . get_base_url() . '/admin/plans.php');
                exit;
            } else {
                $errors[] = 'Failed to update plan. Please check the form and try again.';
            }
        }
    }
}

// Generate CSRF token
$csrfToken = getCsrfToken();

// Include admin header
include_admin_header('Edit Plan');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Edit Plan</h1>
        <p class="admin-page-description">Update plan details</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_base_url(); ?>/admin/plans.php" class="btn btn-secondary">
            ← Back to Plans
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
        <strong>Success!</strong> Plan updated successfully.
    </div>
<?php endif; ?>

<div class="admin-card">
    <form method="POST" action="<?php echo get_base_url(); ?>/admin/plans/edit.php?id=<?php echo urlencode($planId); ?>" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        
        <div class="form-section">
            <h2 class="form-section-title">Basic Information</h2>
            
            <div class="form-group">
                <label for="name" class="form-label required">Plan Name</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    class="form-input" 
                    value="<?php echo htmlspecialchars($formData['name']); ?>"
                    required
                    placeholder="e.g., Basic Plan, Pro Plan"
                >
                <p class="form-help">The display name for this plan</p>
            </div>

            <div class="form-group">
                <label for="slug" class="form-label required">Slug</label>
                <input 
                    type="text" 
                    id="slug" 
                    name="slug" 
                    class="form-input" 
                    value="<?php echo htmlspecialchars($formData['slug']); ?>"
                    required
                    placeholder="e.g., basic-plan"
                >
                <p class="form-help">URL-friendly identifier</p>
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea 
                    id="description" 
                    name="description" 
                    class="form-textarea" 
                    rows="4"
                    placeholder="Brief description of this plan..."
                ><?php echo htmlspecialchars($formData['description']); ?></textarea>
                <p class="form-help">Optional description of the plan</p>
            </div>

            <div class="form-group">
                <label for="status" class="form-label required">Status</label>
                <select id="status" name="status" class="form-select" required>
                    <option value="ACTIVE" <?php echo $formData['status'] === 'ACTIVE' ? 'selected' : ''; ?>>Active</option>
                    <option value="INACTIVE" <?php echo $formData['status'] === 'INACTIVE' ? 'selected' : ''; ?>>Inactive</option>
                </select>
                <p class="form-help">Only active plans are visible to customers</p>
            </div>
        </div>

        <div class="form-section">
            <h2 class="form-section-title">Pricing</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="price" class="form-label required">Price</label>
                    <input 
                        type="number" 
                        id="price" 
                        name="price" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($formData['price']); ?>"
                        step="0.01"
                        min="0"
                        required
                        placeholder="0.00"
                    >
                    <p class="form-help">Price amount</p>
                </div>

                <div class="form-group">
                    <label for="currency" class="form-label required">Currency</label>
                    <select id="currency" name="currency" class="form-select" required>
                        <option value="USD" <?php echo $formData['currency'] === 'USD' ? 'selected' : ''; ?>>USD</option>
                        <option value="EUR" <?php echo $formData['currency'] === 'EUR' ? 'selected' : ''; ?>>EUR</option>
                        <option value="GBP" <?php echo $formData['currency'] === 'GBP' ? 'selected' : ''; ?>>GBP</option>
                        <option value="INR" <?php echo $formData['currency'] === 'INR' ? 'selected' : ''; ?>>INR</option>
                    </select>
                    <p class="form-help">Currency code</p>
                </div>

                <div class="form-group">
                    <label for="billing_period_months" class="form-label required">Billing Period (Months)</label>
                    <input 
                        type="number" 
                        id="billing_period_months" 
                        name="billing_period_months" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($formData['billing_period_months']); ?>"
                        min="1"
                        required
                        placeholder="1"
                    >
                    <p class="form-help">Number of months per billing cycle</p>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h2 class="form-section-title">Features</h2>
            
            <div class="form-group">
                <label for="features" class="form-label">Plan Features</label>
                <textarea 
                    id="features" 
                    name="features" 
                    class="form-textarea" 
                    rows="8"
                    placeholder="Enter one feature per line&#10;e.g.,&#10;Unlimited users&#10;24/7 support&#10;Advanced analytics"
                ><?php echo htmlspecialchars($formData['features']); ?></textarea>
                <p class="form-help">Enter one feature per line</p>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Plan</button>
            <a href="<?php echo get_base_url(); ?>/admin/plans.php" class="btn btn-secondary">Cancel</a>
            <a href="<?php echo get_base_url(); ?>/admin/plans/delete.php?id=<?php echo urlencode($planId); ?>" 
               class="btn btn-danger"
               onclick="return confirm('Are you sure you want to delete this plan?');">
                Delete Plan
            </a>
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
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
}
</style>

<?php include_admin_footer(); ?>
