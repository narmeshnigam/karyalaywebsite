<?php
/**
 * Admin Edit Plan Page
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Services\PlanService;

startSecureSession();
require_admin();
require_permission('plans.edit');

$planService = new PlanService();
$planId = $_GET['id'] ?? '';

if (empty($planId)) {
    $_SESSION['admin_error'] = 'Plan ID is required.';
    header('Location: ' . get_app_base_url() . '/admin/plans.php');
    exit;
}

$plan = $planService->read($planId);

if (!$plan) {
    $_SESSION['admin_error'] = 'Plan not found.';
    header('Location: ' . get_app_base_url() . '/admin/plans.php');
    exit;
}

$errors = [];
$formData = [
    'name' => $plan['name'],
    'slug' => $plan['slug'],
    'description' => $plan['description'] ?? '',
    'currency' => $plan['currency'] ?? 'INR',
    'billing_period_months' => $plan['billing_period_months'],
    'features_html' => $plan['features_html'] ?? '',
    'status' => $plan['status'],
    'number_of_users' => $plan['number_of_users'] ?? '',
    'allowed_storage_gb' => $plan['allowed_storage_gb'] ?? '',
    'mrp' => $plan['mrp'] ?? '',
    'discounted_price' => $plan['discounted_price'] ?? '',
    'discount_amount' => $plan['discount_amount'] ?? '',
    'net_price' => $plan['net_price'] ?? '',
    'tax_percent' => $plan['tax_percent'] ?? '',
    'tax_name' => $plan['tax_name'] ?? '',
    'tax_description' => $plan['tax_description'] ?? '',
    'tax_amount' => $plan['tax_amount'] ?? ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $errors[] = 'Invalid security token.';
    } else {
        $formData = [
            'name' => trim($_POST['name'] ?? ''),
            'slug' => trim($_POST['slug'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'currency' => trim($_POST['currency'] ?? 'INR'),
            'billing_period_months' => trim($_POST['billing_period_months'] ?? '1'),
            'features_html' => $_POST['features_html'] ?? '',
            'status' => trim($_POST['status'] ?? 'ACTIVE'),
            'number_of_users' => trim($_POST['number_of_users'] ?? ''),
            'allowed_storage_gb' => trim($_POST['allowed_storage_gb'] ?? ''),
            'mrp' => trim($_POST['mrp'] ?? ''),
            'discounted_price' => trim($_POST['discounted_price'] ?? ''),
            'discount_amount' => trim($_POST['discount_amount'] ?? ''),
            'net_price' => trim($_POST['net_price'] ?? ''),
            'tax_percent' => trim($_POST['tax_percent'] ?? ''),
            'tax_name' => trim($_POST['tax_name'] ?? ''),
            'tax_description' => trim($_POST['tax_description'] ?? ''),
            'tax_amount' => trim($_POST['tax_amount'] ?? '')
        ];

        if (empty($formData['name'])) $errors[] = 'Plan name is required.';
        if (empty($formData['billing_period_months'])) $errors[] = 'Billing period is required.';
        if (empty($formData['mrp']) || !is_numeric($formData['mrp']) || $formData['mrp'] <= 0) {
            $errors[] = 'MRP (Maximum Retail Price) is required and must be greater than 0.';
        }

        if (empty($errors)) {
            $updateData = [
                'name' => $formData['name'],
                'slug' => $formData['slug'],
                'description' => $formData['description'] ?: null,
                'currency' => $formData['currency'],
                'billing_period_months' => intval($formData['billing_period_months']),
                'features_html' => $formData['features_html'] ?: null,
                'status' => $formData['status'],
                'number_of_users' => $formData['number_of_users'] !== '' ? intval($formData['number_of_users']) : null,
                'allowed_storage_gb' => $formData['allowed_storage_gb'] !== '' ? floatval($formData['allowed_storage_gb']) : null,
                'mrp' => floatval($formData['mrp']),
                'discounted_price' => $formData['discounted_price'] !== '' ? floatval($formData['discounted_price']) : null,
                'discount_amount' => $formData['discount_amount'] !== '' ? floatval($formData['discount_amount']) : null,
                'net_price' => $formData['net_price'] !== '' ? floatval($formData['net_price']) : null,
                'tax_percent' => $formData['tax_percent'] !== '' ? floatval($formData['tax_percent']) : null,
                'tax_name' => $formData['tax_name'] ?: null,
                'tax_description' => $formData['tax_description'] ?: null,
                'tax_amount' => $formData['tax_amount'] !== '' ? floatval($formData['tax_amount']) : null
            ];

            if ($planService->update($planId, $updateData)) {
                $_SESSION['admin_success'] = 'Plan updated successfully!';
                header('Location: ' . get_app_base_url() . '/admin/plans.php');
                exit;
            }
            $errors[] = 'Failed to update plan.';
        }
    }
}

$csrfToken = getCsrfToken();
include_admin_header('Edit Plan');
?>

<!-- Quill CSS -->
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Edit Plan</h1>
        <p class="admin-page-description">Update plan details</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/plans.php" class="btn btn-secondary">← Back to Plans</a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error"><ul><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="admin-card">
<form method="POST" class="admin-form" id="planForm">
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
<input type="hidden" name="features_html" id="featuresInput">

<div class="form-section">
<h2 class="form-section-title">Basic Information</h2>
<div class="form-group">
    <label for="name" class="form-label required">Plan Name</label>
    <input type="text" id="name" name="name" class="form-input" value="<?php echo htmlspecialchars($formData['name']); ?>" required>
</div>
<div class="form-group">
    <label for="slug" class="form-label required">Slug</label>
    <input type="text" id="slug" name="slug" class="form-input" value="<?php echo htmlspecialchars($formData['slug']); ?>" required>
</div>
<div class="form-group">
    <label for="description" class="form-label">Description</label>
    <textarea id="description" name="description" class="form-textarea" rows="3"><?php echo htmlspecialchars($formData['description']); ?></textarea>
</div>
<div class="form-group">
    <label for="status" class="form-label required">Status</label>
    <select id="status" name="status" class="form-select" required>
        <option value="ACTIVE" <?php echo $formData['status'] === 'ACTIVE' ? 'selected' : ''; ?>>Active</option>
        <option value="INACTIVE" <?php echo $formData['status'] === 'INACTIVE' ? 'selected' : ''; ?>>Inactive</option>
    </select>
</div>
</div>

<div class="form-section">
<h2 class="form-section-title">Plan Limits</h2>
<div class="form-row">
    <div class="form-group">
        <label for="number_of_users" class="form-label">Number of Users</label>
        <input type="number" id="number_of_users" name="number_of_users" class="form-input" value="<?php echo htmlspecialchars($formData['number_of_users']); ?>" min="0" placeholder="Unlimited">
    </div>
    <div class="form-group">
        <label for="allowed_storage_gb" class="form-label">Allowed Storage (GB)</label>
        <input type="number" id="allowed_storage_gb" name="allowed_storage_gb" class="form-input" value="<?php echo htmlspecialchars($formData['allowed_storage_gb']); ?>" step="0.01" min="0" placeholder="Unlimited">
    </div>
</div>
</div>

<div class="form-section">
<h2 class="form-section-title">Pricing</h2>
<div class="form-row">
    <div class="form-group">
        <label for="currency" class="form-label required">Currency</label>
        <select id="currency" name="currency" class="form-select" required>
            <option value="INR" <?php echo $formData['currency'] === 'INR' ? 'selected' : ''; ?>>INR</option>
            <option value="USD" <?php echo $formData['currency'] === 'USD' ? 'selected' : ''; ?>>USD</option>
            <option value="EUR" <?php echo $formData['currency'] === 'EUR' ? 'selected' : ''; ?>>EUR</option>
            <option value="GBP" <?php echo $formData['currency'] === 'GBP' ? 'selected' : ''; ?>>GBP</option>
        </select>
    </div>
    <div class="form-group">
        <label for="billing_period_months" class="form-label required">Billing Period</label>
        <select id="billing_period_months" name="billing_period_months" class="form-select" required>
            <option value="1" <?php echo $formData['billing_period_months'] == '1' ? 'selected' : ''; ?>>Monthly</option>
            <option value="3" <?php echo $formData['billing_period_months'] == '3' ? 'selected' : ''; ?>>Quarterly</option>
            <option value="6" <?php echo $formData['billing_period_months'] == '6' ? 'selected' : ''; ?>>Semi-Annual</option>
            <option value="12" <?php echo $formData['billing_period_months'] == '12' ? 'selected' : ''; ?>>Annual</option>
        </select>
    </div>
</div>
<div class="form-row">
    <div class="form-group">
        <label for="mrp" class="form-label required">MRP (Maximum Retail Price)</label>
        <input type="number" id="mrp" name="mrp" class="form-input price-input" value="<?php echo htmlspecialchars($formData['mrp']); ?>" step="0.01" min="0.01" required>
        <p class="form-help">The original/regular price (shown as strikethrough if discounted)</p>
    </div>
    <div class="form-group">
        <label for="discounted_price" class="form-label">Discounted Price (Selling Price)</label>
        <input type="number" id="discounted_price" name="discounted_price" class="form-input price-input" value="<?php echo htmlspecialchars($formData['discounted_price']); ?>" step="0.01" min="0">
        <p class="form-help">Final selling price (tax inclusive). Leave empty to use MRP.</p>
    </div>
</div>
<div class="form-row">
    <div class="form-group">
        <label for="discount_amount" class="form-label">Discount Amount</label>
        <input type="number" id="discount_amount" name="discount_amount" class="form-input price-input" value="<?php echo htmlspecialchars($formData['discount_amount']); ?>" step="0.01" min="0" readonly>
        <p class="form-help">Auto-calculated: MRP - Discounted Price</p>
    </div>
    <div class="form-group">
        <label class="form-label">Effective Selling Price</label>
        <div class="selling-price-display" id="sellingPriceDisplay">
            <span class="currency-symbol">₹</span>
            <span class="price-value" id="effectiveSellingPrice">0.00</span>
        </div>
        <p class="form-help">This is the price charged at checkout (discounted price or MRP)</p>
    </div>
</div>
</div>

<div class="form-section">
<h2 class="form-section-title">Tax Configuration</h2>
<p class="form-section-description">Configure tax details. The selling price is tax-inclusive (net price + tax = selling price).</p>
<div class="form-row">
    <div class="form-group">
        <label for="tax_name" class="form-label">Tax Name</label>
        <input type="text" id="tax_name" name="tax_name" class="form-input" value="<?php echo htmlspecialchars($formData['tax_name']); ?>" placeholder="e.g., GST, VAT, Sales Tax">
        <p class="form-help">Name of the tax (displayed on invoice)</p>
    </div>
    <div class="form-group">
        <label for="tax_percent" class="form-label">Tax Percentage (%)</label>
        <input type="number" id="tax_percent" name="tax_percent" class="form-input price-input" value="<?php echo htmlspecialchars($formData['tax_percent']); ?>" step="0.01" min="0" max="100" placeholder="e.g., 18">
        <p class="form-help">Tax rate percentage (e.g., 18 for 18% GST)</p>
    </div>
</div>
<div class="form-group">
    <label for="tax_description" class="form-label">Tax Description</label>
    <input type="text" id="tax_description" name="tax_description" class="form-input" value="<?php echo htmlspecialchars($formData['tax_description']); ?>" placeholder="e.g., Goods and Services Tax @ 18%">
    <p class="form-help">Detailed tax description for invoice</p>
</div>
<div class="tax-breakdown-card">
    <h3 class="tax-breakdown-title">Price Breakdown (Auto-calculated)</h3>
    <div class="tax-breakdown-grid">
        <div class="breakdown-item">
            <label for="net_price" class="form-label">Net Price (Before Tax)</label>
            <input type="number" id="net_price" name="net_price" class="form-input price-input" value="<?php echo htmlspecialchars($formData['net_price']); ?>" step="0.01" min="0" readonly>
        </div>
        <div class="breakdown-item">
            <label for="tax_amount" class="form-label">Tax Amount</label>
            <input type="number" id="tax_amount" name="tax_amount" class="form-input price-input" value="<?php echo htmlspecialchars($formData['tax_amount']); ?>" step="0.01" min="0" readonly>
        </div>
        <div class="breakdown-item breakdown-total">
            <label class="form-label">Total (Selling Price)</label>
            <div class="breakdown-total-value" id="breakdownTotal">₹0.00</div>
        </div>
    </div>
    <p class="form-help">Net Price + Tax Amount = Selling Price (tax inclusive pricing)</p>
</div>
</div>

<div class="form-section">
<h2 class="form-section-title">Features</h2>
<div class="form-group">
    <label class="form-label">Plan Features</label>
    <div id="editor-container">
        <div id="editor"><?php echo $formData['features_html']; ?></div>
    </div>
    <p class="form-help">Use the toolbar to format features, add lists, links, and more</p>
</div>
</div>

<div class="form-actions">
    <button type="submit" class="btn btn-primary">Update Plan</button>
    <a href="<?php echo get_app_base_url(); ?>/admin/plans.php" class="btn btn-secondary">Cancel</a>
    <a href="<?php echo get_app_base_url(); ?>/admin/plans/delete.php?id=<?php echo urlencode($planId); ?>" class="btn btn-danger" onclick="return confirm('Delete this plan?');">Delete</a>
</div>
</form>
</div>

<style>
.admin-page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:var(--spacing-6);gap:var(--spacing-4)}
.admin-page-header-content{flex:1}
.admin-page-title{font-size:var(--font-size-2xl);font-weight:var(--font-weight-bold);color:var(--color-gray-900);margin:0 0 var(--spacing-2) 0}
.admin-page-description{font-size:var(--font-size-base);color:var(--color-gray-600);margin:0}
.admin-page-header-actions{display:flex;gap:var(--spacing-3)}
.alert{padding:var(--spacing-4);border-radius:var(--radius-md);margin-bottom:var(--spacing-6)}
.alert-error{background-color:#fee;border:1px solid #fcc;color:#c33}
.alert ul{margin:0;padding-left:var(--spacing-4)}
.admin-form{padding:var(--spacing-6)}
.form-section{margin-bottom:var(--spacing-8)}
.form-section-title{font-size:var(--font-size-lg);font-weight:var(--font-weight-semibold);color:var(--color-gray-900);margin:0 0 var(--spacing-4) 0;padding-bottom:var(--spacing-3);border-bottom:1px solid var(--color-gray-200)}
.form-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:var(--spacing-4)}
.form-group{margin-bottom:var(--spacing-4)}
.form-label{display:block;font-size:var(--font-size-sm);font-weight:var(--font-weight-semibold);color:var(--color-gray-700);margin-bottom:var(--spacing-2)}
.form-label.required::after{content:' *';color:var(--color-danger)}
.form-input,.form-select,.form-textarea{width:100%;padding:var(--spacing-2) var(--spacing-3);border:1px solid var(--color-gray-300);border-radius:var(--radius-md);font-size:var(--font-size-base);color:var(--color-gray-900);font-family:inherit}
.form-input:focus,.form-select:focus,.form-textarea:focus{outline:none;border-color:var(--color-primary);box-shadow:0 0 0 3px rgba(59,130,246,0.1)}
.form-textarea{resize:vertical}
.form-help{font-size:var(--font-size-sm);color:var(--color-gray-600);margin:var(--spacing-1) 0 0 0}
.form-actions{display:flex;gap:var(--spacing-3);padding-top:var(--spacing-6);border-top:1px solid var(--color-gray-200)}

#editor-container{border:1px solid var(--color-gray-300);border-radius:var(--radius-md);overflow:hidden}
#editor{min-height:300px;font-size:15px;line-height:1.7}
.ql-toolbar{border:none!important;border-bottom:1px solid var(--color-gray-300)!important;background:var(--color-gray-50)}
.ql-container{border:none!important;font-family:inherit}
.ql-editor{padding:20px}
.ql-editor p,.ql-editor h1,.ql-editor h2,.ql-editor h3,.ql-editor ul,.ql-editor ol{margin-bottom:1em}
.ql-editor ul,.ql-editor ol{padding-left:1.5em}

/* Tax and pricing styles */
.form-section-description{font-size:var(--font-size-sm);color:var(--color-gray-600);margin:-var(--spacing-2) 0 var(--spacing-4) 0}
.selling-price-display{background:var(--color-gray-100);border:2px solid var(--color-primary);border-radius:var(--radius-md);padding:var(--spacing-3);display:flex;align-items:center;gap:var(--spacing-1);font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);color:var(--color-primary)}
.currency-symbol{font-size:var(--font-size-lg)}
.tax-breakdown-card{background:var(--color-gray-50);border:1px solid var(--color-gray-200);border-radius:var(--radius-md);padding:var(--spacing-4);margin-top:var(--spacing-4)}
.tax-breakdown-title{font-size:var(--font-size-base);font-weight:var(--font-weight-semibold);color:var(--color-gray-800);margin:0 0 var(--spacing-3) 0}
.tax-breakdown-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:var(--spacing-4);align-items:end}
.breakdown-item .form-input[readonly]{background:var(--color-gray-100);cursor:not-allowed}
.breakdown-total{text-align:center}
.breakdown-total-value{background:var(--color-success);color:white;padding:var(--spacing-2) var(--spacing-3);border-radius:var(--radius-md);font-size:var(--font-size-lg);font-weight:var(--font-weight-bold)}
.form-input[readonly]{background:var(--color-gray-100);cursor:not-allowed}

@media(max-width:768px){.admin-page-header{flex-direction:column}.form-row{grid-template-columns:1fr}.tax-breakdown-grid{grid-template-columns:1fr}}
</style>

<!-- Quill JS -->
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Quill editor
    var quill = new Quill('#editor', {
        theme: 'snow',
        placeholder: 'Describe plan features here...\n\nExample:\n• Unlimited users\n• 24/7 support\n• Advanced analytics',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'indent': '-1'}, { 'indent': '+1' }],
                ['blockquote', 'code-block'],
                ['link'],
                [{ 'align': [] }],
                ['clean']
            ]
        }
    });
    
    // Sync editor content to hidden input on form submit
    document.getElementById('planForm').addEventListener('submit', function() {
        document.getElementById('featuresInput').value = quill.root.innerHTML;
    });

    // Currency symbols mapping
    const currencySymbols = { 'INR': '₹', 'USD': '$', 'EUR': '€', 'GBP': '£' };
    
    // Get form elements
    const mrpInput = document.getElementById('mrp');
    const discountedPriceInput = document.getElementById('discounted_price');
    const discountAmountInput = document.getElementById('discount_amount');
    const taxPercentInput = document.getElementById('tax_percent');
    const netPriceInput = document.getElementById('net_price');
    const taxAmountInput = document.getElementById('tax_amount');
    const currencySelect = document.getElementById('currency');
    const effectiveSellingPrice = document.getElementById('effectiveSellingPrice');
    const breakdownTotal = document.getElementById('breakdownTotal');
    const sellingPriceDisplay = document.querySelector('.selling-price-display .currency-symbol');

    // Calculate all values
    function recalculate() {
        const mrp = parseFloat(mrpInput.value) || 0;
        const discountedPrice = parseFloat(discountedPriceInput.value) || 0;
        const taxPercent = parseFloat(taxPercentInput.value) || 0;
        const currency = currencySelect.value;
        const symbol = currencySymbols[currency] || currency + ' ';

        // Determine selling price (discounted price if set, otherwise MRP)
        const sellingPrice = discountedPrice > 0 ? discountedPrice : mrp;

        // Calculate discount amount
        const discountAmount = discountedPrice > 0 ? Math.max(0, mrp - discountedPrice) : 0;
        discountAmountInput.value = discountAmount > 0 ? discountAmount.toFixed(2) : '';

        // Calculate tax breakdown (selling price is tax inclusive)
        let netPrice = sellingPrice;
        let taxAmount = 0;
        
        if (taxPercent > 0 && sellingPrice > 0) {
            // net_price = selling_price / (1 + tax_percent/100)
            netPrice = sellingPrice / (1 + taxPercent / 100);
            taxAmount = sellingPrice - netPrice;
        }

        netPriceInput.value = netPrice > 0 ? netPrice.toFixed(2) : '';
        taxAmountInput.value = taxAmount > 0 ? taxAmount.toFixed(2) : '';

        // Update display elements
        effectiveSellingPrice.textContent = sellingPrice.toFixed(2);
        sellingPriceDisplay.textContent = symbol;
        breakdownTotal.textContent = symbol + sellingPrice.toFixed(2);
    }

    // Attach event listeners
    mrpInput.addEventListener('input', recalculate);
    discountedPriceInput.addEventListener('input', recalculate);
    taxPercentInput.addEventListener('input', recalculate);
    currencySelect.addEventListener('change', recalculate);

    // Initial calculation
    recalculate();
});
</script>

<?php include_admin_footer(); ?>
