<?php
/**
 * Admin Create Testimonial Page
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Models\Testimonial;

startSecureSession();
require_admin();
require_permission('testimonials.manage');

$testimonialModel = new Testimonial();

$errors = [];
$form_data = [
    'customer_name' => '',
    'customer_title' => '',
    'customer_company' => '',
    'customer_image' => '',
    'testimonial_text' => '',
    'rating' => 5,
    'display_order' => 0,
    'is_featured' => false,
    'status' => 'DRAFT'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $form_data['customer_name'] = sanitizeString($_POST['customer_name'] ?? '');
        $form_data['customer_title'] = sanitizeString($_POST['customer_title'] ?? '');
        $form_data['customer_company'] = sanitizeString($_POST['customer_company'] ?? '');
        $form_data['customer_image'] = sanitizeString($_POST['customer_image'] ?? '');
        $form_data['testimonial_text'] = sanitizeString($_POST['testimonial_text'] ?? '');
        $form_data['rating'] = sanitizeInt($_POST['rating'] ?? 5);
        $form_data['display_order'] = sanitizeInt($_POST['display_order'] ?? 0);
        $form_data['is_featured'] = isset($_POST['is_featured']) ? true : false;
        $form_data['status'] = sanitizeString($_POST['status'] ?? 'DRAFT');
        
        if (empty($form_data['customer_name'])) {
            $errors[] = 'Customer name is required.';
        }
        
        if (empty($form_data['testimonial_text'])) {
            $errors[] = 'Testimonial text is required.';
        }
        
        if ($form_data['rating'] < 1 || $form_data['rating'] > 5) {
            $errors[] = 'Rating must be between 1 and 5.';
        }
        
        if (!in_array($form_data['status'], ['DRAFT', 'PUBLISHED', 'ARCHIVED'])) {
            $errors[] = 'Invalid status value.';
        }
        
        if (empty($errors)) {
            $result = $testimonialModel->create($form_data);
            
            if ($result) {
                $_SESSION['admin_success'] = 'Testimonial created successfully!';
                header('Location: ' . get_app_base_url() . '/admin/testimonials.php');
                exit;
            } else {
                $errors[] = 'Failed to create testimonial.';
            }
        }
    }
}

$csrf_token = getCsrfToken();
include_admin_header('Create Testimonial');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <nav class="admin-breadcrumb">
            <a href="<?php echo get_app_base_url(); ?>/admin/testimonials.php">Testimonials</a>
            <span class="breadcrumb-separator">/</span>
            <span>Create Testimonial</span>
        </nav>
        <h1 class="admin-page-title">Create New Testimonial</h1>
        <p class="admin-page-description">Add a new customer testimonial</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/testimonials.php" class="btn btn-secondary">
            ← Back to Testimonials
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

<div class="admin-card">
    <form method="POST" action="<?php echo get_app_base_url(); ?>/admin/testimonials/new.php" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        
        <div class="form-section">
            <h2 class="form-section-title">Customer Information</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="customer_name" class="form-label required">Customer Name</label>
                    <input type="text" id="customer_name" name="customer_name" class="form-input" 
                        value="<?php echo htmlspecialchars($form_data['customer_name']); ?>"
                        required maxlength="255" placeholder="John Doe">
                    <p class="form-help">Full name of the customer</p>
                </div>
                
                <div class="form-group">
                    <label for="customer_title" class="form-label">Job Title</label>
                    <input type="text" id="customer_title" name="customer_title" class="form-input" 
                        value="<?php echo htmlspecialchars($form_data['customer_title']); ?>"
                        maxlength="255" placeholder="CEO">
                    <p class="form-help">Customer's job title or position</p>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="customer_company" class="form-label">Company</label>
                    <input type="text" id="customer_company" name="customer_company" class="form-input" 
                        value="<?php echo htmlspecialchars($form_data['customer_company']); ?>"
                        maxlength="255" placeholder="Acme Corp">
                    <p class="form-help">Customer's company name</p>
                </div>
                
                <div class="form-group">
                    <label for="customer_image" class="form-label">Customer Image URL</label>
                    <input type="text" id="customer_image" name="customer_image" class="form-input" 
                        value="<?php echo htmlspecialchars($form_data['customer_image']); ?>"
                        placeholder="https://example.com/avatar.jpg">
                    <p class="form-help">URL to customer's photo (optional)</p>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h2 class="form-section-title">Testimonial Content</h2>
            
            <div class="form-group">
                <label for="testimonial_text" class="form-label required">Testimonial</label>
                <textarea id="testimonial_text" name="testimonial_text" class="form-textarea" rows="6"
                    required placeholder="Share your experience with our product..."><?php echo htmlspecialchars($form_data['testimonial_text']); ?></textarea>
                <p class="form-help">The testimonial text</p>
            </div>
            
            <div class="form-group">
                <label for="rating" class="form-label required">Rating</label>
                <div class="rating-input">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <label class="rating-star">
                            <input type="radio" name="rating" value="<?php echo $i; ?>" 
                                   <?php echo $form_data['rating'] == $i ? 'checked' : ''; ?> required>
                            <span class="star">★</span>
                        </label>
                    <?php endfor; ?>
                </div>
                <p class="form-help">Select a rating from 1 to 5 stars</p>
            </div>
        </div>
        
        <div class="form-section">
            <h2 class="form-section-title">Display Settings</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="display_order" class="form-label">Display Order</label>
                    <input type="number" id="display_order" name="display_order" class="form-input" 
                        value="<?php echo htmlspecialchars($form_data['display_order']); ?>" min="0" placeholder="0">
                    <p class="form-help">Lower numbers appear first</p>
                </div>
                
                <div class="form-group">
                    <label for="status" class="form-label required">Status</label>
                    <select id="status" name="status" class="form-select" required>
                        <option value="DRAFT" <?php echo $form_data['status'] === 'DRAFT' ? 'selected' : ''; ?>>Draft</option>
                        <option value="PUBLISHED" <?php echo $form_data['status'] === 'PUBLISHED' ? 'selected' : ''; ?>>Published</option>
                        <option value="ARCHIVED" <?php echo $form_data['status'] === 'ARCHIVED' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                    <p class="form-help">Only published testimonials appear on the website</p>
                </div>
            </div>
            
            <div class="form-group">
                <div class="form-checkbox-group">
                    <label class="form-checkbox-label">
                        <input type="checkbox" id="is_featured" name="is_featured" 
                               class="form-checkbox" value="1"
                               <?php echo $form_data['is_featured'] ? 'checked' : ''; ?>>
                        <span class="form-checkbox-text">
                            <strong>Feature on Homepage</strong>
                            <span class="form-checkbox-help">Display this testimonial in the featured testimonials section</span>
                        </span>
                    </label>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Testimonial</button>
            <a href="<?php echo get_app_base_url(); ?>/admin/testimonials.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<style>
.admin-breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 14px; margin-bottom: 8px; }
.admin-breadcrumb a { color: var(--color-primary); text-decoration: none; }
.admin-breadcrumb a:hover { text-decoration: underline; }
.breadcrumb-separator { color: var(--color-gray-400); }
.admin-page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; gap: 16px; }
.admin-page-header-content { flex: 1; }
.admin-page-title { font-size: 24px; font-weight: 700; color: var(--color-gray-900); margin: 0 0 8px 0; }
.admin-page-description { font-size: 14px; color: var(--color-gray-600); margin: 0; }
.admin-page-header-actions { display: flex; gap: 12px; }
.alert { padding: 16px; border-radius: 8px; margin-bottom: 24px; }
.alert-error { background-color: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }
.alert ul { margin: 8px 0 0 16px; padding: 0; }
.admin-form { padding: 24px; }
.form-section { margin-bottom: 32px; }
.form-section:last-of-type { margin-bottom: 0; }
.form-section-title { font-size: 18px; font-weight: 600; color: var(--color-gray-900); margin: 0 0 16px 0; padding-bottom: 12px; border-bottom: 1px solid var(--color-gray-200); }
.form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
.form-group { margin-bottom: 16px; }
.form-label { display: block; font-size: 14px; font-weight: 600; color: var(--color-gray-700); margin-bottom: 8px; }
.form-label.required::after { content: ' *'; color: #dc2626; }
.form-input, .form-select, .form-textarea { width: 100%; padding: 10px 12px; border: 1px solid var(--color-gray-300); border-radius: 6px; font-size: 14px; color: var(--color-gray-900); font-family: inherit; box-sizing: border-box; }
.form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.form-textarea { resize: vertical; }
.form-help { font-size: 12px; color: var(--color-gray-500); margin: 4px 0 0 0; }
.form-actions { display: flex; gap: 12px; padding-top: 24px; border-top: 1px solid var(--color-gray-200); }
.form-checkbox-group { padding: var(--spacing-4); background-color: var(--color-gray-50); border-radius: var(--radius-md); border: 1px solid var(--color-gray-200); }
.form-checkbox-label { display: flex; align-items: flex-start; gap: var(--spacing-3); cursor: pointer; }
.form-checkbox { width: 20px; height: 20px; margin-top: 2px; cursor: pointer; }
.form-checkbox-text { flex: 1; }
.form-checkbox-text strong { display: block; font-size: var(--font-size-sm); font-weight: var(--font-weight-semibold); color: var(--color-gray-900); margin-bottom: var(--spacing-1); }
.form-checkbox-help { display: block; font-size: var(--font-size-xs); color: var(--color-gray-600); }
.rating-input { display: flex; gap: 8px; }
.rating-star { cursor: pointer; }
.rating-star input { display: none; }
.rating-star .star { font-size: 32px; color: #d1d5db; transition: color 0.2s; }
.rating-star input:checked ~ .star,
.rating-star:hover .star { color: #fbbf24; }
.rating-input:has(input:nth-child(1):checked) .rating-star:nth-child(-n+1) .star,
.rating-input:has(input:nth-child(2):checked) .rating-star:nth-child(-n+2) .star,
.rating-input:has(input:nth-child(3):checked) .rating-star:nth-child(-n+3) .star,
.rating-input:has(input:nth-child(4):checked) .rating-star:nth-child(-n+4) .star,
.rating-input:has(input:nth-child(5):checked) .rating-star:nth-child(-n+5) .star { color: #fbbf24; }
@media (max-width: 768px) { .admin-page-header { flex-direction: column; } .form-row { grid-template-columns: 1fr; } }
</style>

<script>
document.querySelectorAll('.rating-star').forEach((star, index) => {
    star.addEventListener('click', () => {
        document.querySelectorAll('.rating-star input')[index].checked = true;
    });
});
</script>

<?php include_admin_footer(); ?>
