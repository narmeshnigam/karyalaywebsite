<?php
/**
 * Admin Create Subscription Page
 * Create new subscription with searchable customer and port dropdowns
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

startSecureSession();
require_admin();
require_permission('subscriptions.create');

$db = \Karyalay\Database\Connection::getInstance();

// Fetch all plans
$plans_stmt = $db->query("SELECT id, name, mrp, discounted_price, currency, billing_period_months FROM plans WHERE status = 'ACTIVE' ORDER BY name");
$plans = $plans_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $_SESSION['admin_error'] = 'Invalid security token.';
    } else {
        $customer_id = $_POST['customer_id'] ?? '';
        $plan_id = $_POST['plan_id'] ?? '';
        $port_id = $_POST['port_id'] ?? '';
        $start_date = $_POST['start_date'] ?? date('Y-m-d');
        $status = $_POST['status'] ?? 'ACTIVE';
        
        if (empty($customer_id) || empty($plan_id)) {
            $_SESSION['admin_error'] = 'Customer and Plan are required.';
        } else {
            try {
                // Get plan details for end date calculation and order
                $plan_stmt = $db->prepare("SELECT billing_period_months, mrp, discounted_price, currency FROM plans WHERE id = ?");
                $plan_stmt->execute([$plan_id]);
                $plan = $plan_stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$plan) {
                    throw new Exception('Selected plan not found');
                }
                
                $end_date = date('Y-m-d', strtotime($start_date . ' + ' . $plan['billing_period_months'] . ' months'));
                
                // Generate UUIDs
                $subscription_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                
                $order_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                
                // Create a manual order record first
                $order_sql = "INSERT INTO orders (id, customer_id, plan_id, amount, currency, status, payment_method, created_at) 
                              VALUES (:id, :customer_id, :plan_id, :amount, :currency, 'SUCCESS', 'MANUAL', NOW())";
                $order_stmt = $db->prepare($order_sql);
                // Calculate effective price: discounted_price if available, otherwise mrp
                $effectivePrice = !empty($plan['discounted_price']) ? $plan['discounted_price'] : $plan['mrp'];
                
                $order_stmt->execute([
                    ':id' => $order_id,
                    ':customer_id' => $customer_id,
                    ':plan_id' => $plan_id,
                    ':amount' => $effectivePrice,
                    ':currency' => $plan['currency']
                ]);
                
                // Create subscription
                $insert_sql = "INSERT INTO subscriptions (id, customer_id, plan_id, assigned_port_id, start_date, end_date, status, order_id, created_at) 
                               VALUES (:id, :customer_id, :plan_id, :port_id, :start_date, :end_date, :status, :order_id, NOW())";
                $insert_stmt = $db->prepare($insert_sql);
                $insert_stmt->execute([
                    ':id' => $subscription_id,
                    ':customer_id' => $customer_id,
                    ':plan_id' => $plan_id,
                    ':port_id' => !empty($port_id) ? $port_id : null,
                    ':start_date' => $start_date,
                    ':end_date' => $end_date,
                    ':status' => $status,
                    ':order_id' => $order_id
                ]);
                
                // Update port status if assigned
                if (!empty($port_id)) {
                    // First check if port is still available
                    $check_port_sql = "SELECT status FROM ports WHERE id = :port_id";
                    $check_port_stmt = $db->prepare($check_port_sql);
                    $check_port_stmt->execute([':port_id' => $port_id]);
                    $port_status = $check_port_stmt->fetchColumn();
                    
                    if ($port_status !== 'AVAILABLE') {
                        throw new Exception('Selected port is no longer available');
                    }
                    
                    $port_sql = "UPDATE ports SET status = 'ASSIGNED', 
                                 assigned_subscription_id = :subscription_id, assigned_at = NOW() 
                                 WHERE id = :port_id AND status = 'AVAILABLE'";
                    $port_stmt = $db->prepare($port_sql);
                    $port_stmt->execute([
                        ':subscription_id' => $subscription_id,
                        ':port_id' => $port_id
                    ]);
                    
                    if ($port_stmt->rowCount() === 0) {
                        throw new Exception('Failed to assign port - it may have been assigned to another subscription');
                    }
                    
                    // Log the port allocation
                    $logModel = new \Karyalay\Models\PortAllocationLog();
                    $logModel->logAssignment($port_id, $subscription_id, $customer_id, $_SESSION['user_id'] ?? null);
                }
                
                $_SESSION['admin_success'] = 'Subscription created successfully.';
                header('Location: ' . get_app_base_url() . '/admin/subscriptions.php');
                exit;
            } catch (Exception $e) {
                error_log("Subscription create error: " . $e->getMessage());
                $_SESSION['admin_error'] = $e->getMessage();
            } catch (PDOException $e) {
                error_log("Subscription create error: " . $e->getMessage());
                $_SESSION['admin_error'] = 'Failed to create subscription. Please try again.';
            }
        }
    }
}

$csrf_token = getCsrfToken();

include_admin_header('Create Subscription');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <div class="breadcrumb">
            <a href="<?php echo get_app_base_url(); ?>/admin/subscriptions.php" class="breadcrumb-link">Subscriptions</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">Create New</span>
        </div>
        <h1 class="admin-page-title">Create Subscription</h1>
        <p class="admin-page-description">Manually create a new subscription for a customer</p>
    </div>
</div>

<!-- Flash Messages -->
<?php if (isset($_SESSION['admin_error'])): ?>
    <div class="alert alert-error">
        <?php echo htmlspecialchars($_SESSION['admin_error']); ?>
        <?php unset($_SESSION['admin_error']); ?>
    </div>
<?php endif; ?>

<div class="admin-card">
    <div class="card-header">
        <h2 class="card-title">Subscription Details</h2>
    </div>
    <div class="card-body">
        <form method="POST" class="subscription-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            
            <div class="form-grid">
                <!-- Customer Search -->
                <div class="form-group form-group-full">
                    <label for="customer_search" class="form-label">Customer *</label>
                    <div class="searchable-dropdown">
                        <input type="text" id="customer_search" class="form-input" 
                               placeholder="Search by email or name..." autocomplete="off">
                        <input type="hidden" name="customer_id" id="customer_id">
                        <div class="dropdown-results" id="customer_results"></div>
                    </div>
                    <div id="selected_customer" class="selected-item" style="display: none;"></div>
                    <small class="form-error" id="customer_error" style="display: none; color: var(--color-red-600);">Please select a customer</small>
                </div>
                
                <!-- Plan Search -->
                <div class="form-group">
                    <label for="plan_search" class="form-label">Plan *</label>
                    <div class="searchable-dropdown">
                        <input type="text" id="plan_search" class="form-input" 
                               placeholder="Search plans by name..." autocomplete="off">
                        <input type="hidden" name="plan_id" id="plan_id">
                        <div class="dropdown-results" id="plan_results"></div>
                    </div>
                    <div id="selected_plan" class="selected-item" style="display: none;"></div>
                    <small class="form-error" id="plan_error" style="display: none; color: var(--color-red-600);">Please select a plan</small>
                </div>
                
                <!-- Status -->
                <div class="form-group">
                    <label for="status" class="form-label">Status *</label>
                    <select id="status" name="status" class="form-input" required>
                        <option value="ACTIVE">Active</option>
                        <option value="PENDING_ALLOCATION">Pending Allocation</option>
                    </select>
                </div>
                
                <!-- Start Date -->
                <div class="form-group">
                    <label for="start_date" class="form-label">Start Date *</label>
                    <input type="date" id="start_date" name="start_date" class="form-input" 
                           value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <!-- Port Search -->
                <div class="form-group">
                    <label for="port_search" class="form-label">Assign Port (Optional)</label>
                    <div class="searchable-dropdown">
                        <input type="text" id="port_search" class="form-input" 
                               placeholder="Search available ports..." autocomplete="off">
                        <input type="hidden" name="port_id" id="port_id">
                        <div class="dropdown-results" id="port_results"></div>
                    </div>
                    <div id="selected_port" class="selected-item" style="display: none;"></div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="submit_btn">Create Subscription</button>
                <a href="<?php echo get_app_base_url(); ?>/admin/subscriptions.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
.admin-page-header{margin-bottom:var(--spacing-6)}
.breadcrumb{display:flex;align-items:center;gap:var(--spacing-2);margin-bottom:var(--spacing-3);font-size:var(--font-size-sm)}
.breadcrumb-link{color:var(--color-primary);text-decoration:none}
.breadcrumb-link:hover{text-decoration:underline}
.breadcrumb-separator{color:var(--color-gray-400)}
.breadcrumb-current{color:var(--color-gray-600)}
.admin-page-title{font-size:var(--font-size-2xl);font-weight:var(--font-weight-bold);color:var(--color-gray-900);margin:0 0 var(--spacing-2) 0}
.admin-page-description{font-size:var(--font-size-base);color:var(--color-gray-600);margin:0}
.card-header{display:flex;justify-content:space-between;align-items:center;padding:var(--spacing-4) var(--spacing-5);border-bottom:1px solid var(--color-gray-200)}
.card-title{font-size:var(--font-size-lg);font-weight:var(--font-weight-semibold);color:var(--color-gray-900);margin:0}
.card-body{padding:var(--spacing-5)}
.form-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:var(--spacing-4)}
.form-group{display:flex;flex-direction:column;gap:var(--spacing-2)}
.form-group-full{grid-column:span 2}
.form-label{font-size:var(--font-size-sm);font-weight:var(--font-weight-semibold);color:var(--color-gray-700)}
.form-input{padding:var(--spacing-2) var(--spacing-3);border:1px solid var(--color-gray-300);border-radius:var(--radius-md);font-size:var(--font-size-base);color:var(--color-gray-900)}
.form-input:focus{outline:none;border-color:var(--color-primary);box-shadow:0 0 0 3px rgba(59,130,246,0.1)}
.searchable-dropdown{position:relative}
.dropdown-results{position:absolute;top:100%;left:0;right:0;background:white;border:1px solid var(--color-gray-300);border-top:none;border-radius:0 0 var(--radius-md) var(--radius-md);max-height:200px;overflow-y:auto;z-index:100;display:none}
.dropdown-results.show{display:block}
.dropdown-item{padding:var(--spacing-3);cursor:pointer;border-bottom:1px solid var(--color-gray-100)}
.dropdown-item:last-child{border-bottom:none}
.dropdown-item:hover{background:var(--color-gray-50)}
.dropdown-item-primary{font-weight:var(--font-weight-semibold);color:var(--color-gray-900)}
.dropdown-item-secondary{font-size:var(--font-size-sm);color:var(--color-gray-600)}
.dropdown-empty{padding:var(--spacing-3);color:var(--color-gray-500);text-align:center;font-style:italic}
.selected-item{margin-top:var(--spacing-2);padding:var(--spacing-3);background:var(--color-blue-50);border:1px solid var(--color-primary);border-radius:var(--radius-md);display:flex;justify-content:space-between;align-items:center}
.selected-item-info{flex:1}
.selected-item-name{font-weight:var(--font-weight-semibold);color:var(--color-gray-900)}
.selected-item-detail{font-size:var(--font-size-sm);color:var(--color-gray-600)}
.selected-item-remove{background:none;border:none;color:var(--color-gray-500);cursor:pointer;font-size:var(--font-size-lg);padding:0 var(--spacing-2)}
.selected-item-remove:hover{color:var(--color-red-600)}
.form-actions{display:flex;gap:var(--spacing-3);margin-top:var(--spacing-6);padding-top:var(--spacing-4);border-top:1px solid var(--color-gray-200)}
.alert{padding:var(--spacing-4);border-radius:var(--radius-md);margin-bottom:var(--spacing-4)}
.alert-error{background-color:#fee2e2;border:1px solid #ef4444;color:#991b1b}
.form-error{font-size:var(--font-size-sm);margin-top:var(--spacing-1)}
.btn:disabled{opacity:0.6;cursor:not-allowed}
@media(max-width:768px){.form-grid{grid-template-columns:1fr}.form-group-full{grid-column:span 1}}
</style>

<script>
const baseUrl = '<?php echo get_app_base_url(); ?>';
let customerSearchTimeout, planSearchTimeout, portSearchTimeout;

// Form validation before submit
document.querySelector('.subscription-form').addEventListener('submit', function(e) {
    let isValid = true;
    
    // Validate customer selection
    const customerId = document.getElementById('customer_id').value;
    const customerError = document.getElementById('customer_error');
    if (!customerId) {
        customerError.style.display = 'block';
        isValid = false;
    } else {
        customerError.style.display = 'none';
    }
    
    // Validate plan selection
    const planId = document.getElementById('plan_id').value;
    const planError = document.getElementById('plan_error');
    if (!planId) {
        planError.style.display = 'block';
        isValid = false;
    } else {
        planError.style.display = 'none';
    }
    
    if (!isValid) {
        e.preventDefault();
        return false;
    }
    
    // Disable submit button to prevent double submission
    const submitBtn = document.getElementById('submit_btn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Creating...';
});

// Customer search
document.getElementById('customer_search').addEventListener('input', function() {
    clearTimeout(customerSearchTimeout);
    const query = this.value.trim();
    const resultsDiv = document.getElementById('customer_results');
    
    customerSearchTimeout = setTimeout(() => {
        fetch(`${baseUrl}/admin/api/search-customers.php?q=${encodeURIComponent(query)}`)
            .then(r => r.json())
            .then(data => {
                if (data.length === 0) {
                    resultsDiv.innerHTML = '<div class="dropdown-empty">No customers found</div>';
                } else {
                    resultsDiv.innerHTML = data.map(c => `
                        <div class="dropdown-item" onclick="selectCustomer('${c.id}', '${escapeHtml(c.name)}', '${escapeHtml(c.email)}')">
                            <div class="dropdown-item-primary">${escapeHtml(c.name)}</div>
                            <div class="dropdown-item-secondary">${escapeHtml(c.email)}</div>
                        </div>
                    `).join('');
                }
                resultsDiv.classList.add('show');
            });
    }, 300);
});

// Show all customers on focus
document.getElementById('customer_search').addEventListener('focus', function() {
    if (this.value.trim() === '' && document.getElementById('customer_id').value === '') {
        fetch(`${baseUrl}/admin/api/search-customers.php?q=`)
            .then(r => r.json())
            .then(data => {
                const resultsDiv = document.getElementById('customer_results');
                if (data.length === 0) {
                    resultsDiv.innerHTML = '<div class="dropdown-empty">No customers available</div>';
                } else {
                    resultsDiv.innerHTML = data.map(c => `
                        <div class="dropdown-item" onclick="selectCustomer('${c.id}', '${escapeHtml(c.name)}', '${escapeHtml(c.email)}')">
                            <div class="dropdown-item-primary">${escapeHtml(c.name)}</div>
                            <div class="dropdown-item-secondary">${escapeHtml(c.email)}</div>
                        </div>
                    `).join('');
                }
                resultsDiv.classList.add('show');
            });
    }
});

// Plan search
document.getElementById('plan_search').addEventListener('input', function() {
    clearTimeout(planSearchTimeout);
    const query = this.value.trim();
    const resultsDiv = document.getElementById('plan_results');
    
    if (query.length < 1) {
        resultsDiv.classList.remove('show');
        return;
    }
    
    planSearchTimeout = setTimeout(() => {
        fetch(`${baseUrl}/admin/api/search-plans.php?q=${encodeURIComponent(query)}`)
            .then(r => r.json())
            .then(data => {
                if (data.length === 0) {
                    resultsDiv.innerHTML = '<div class="dropdown-empty">No plans found</div>';
                } else {
                    resultsDiv.innerHTML = data.map(p => {
                        const effectivePrice = p.discounted_price && p.discounted_price > 0 ? p.discounted_price : p.mrp;
                        return `
                        <div class="dropdown-item" onclick="selectPlan('${p.id}', '${escapeHtml(p.name)}', '${escapeHtml(p.currency)}', '${effectivePrice}', '${p.billing_period_months}')">
                            <div class="dropdown-item-primary">${escapeHtml(p.name)}</div>
                            <div class="dropdown-item-secondary">${escapeHtml(p.currency)} ${parseFloat(effectivePrice).toFixed(2)} / ${p.billing_period_months} month${p.billing_period_months > 1 ? 's' : ''}</div>
                        </div>
                        `;
                    }).join('');
                }
                resultsDiv.classList.add('show');
            });
    }, 200);
});

// Show all plans on focus
document.getElementById('plan_search').addEventListener('focus', function() {
    if (this.value.trim() === '' && document.getElementById('plan_id').value === '') {
        fetch(`${baseUrl}/admin/api/search-plans.php?q=`)
            .then(r => r.json())
            .then(data => {
                const resultsDiv = document.getElementById('plan_results');
                if (data.length === 0) {
                    resultsDiv.innerHTML = '<div class="dropdown-empty">No active plans available</div>';
                } else {
                    resultsDiv.innerHTML = data.map(p => {
                        const effectivePrice = p.discounted_price && p.discounted_price > 0 ? p.discounted_price : p.mrp;
                        return `
                        <div class="dropdown-item" onclick="selectPlan('${p.id}', '${escapeHtml(p.name)}', '${escapeHtml(p.currency)}', '${effectivePrice}', '${p.billing_period_months}')">
                            <div class="dropdown-item-primary">${escapeHtml(p.name)}</div>
                            <div class="dropdown-item-secondary">${escapeHtml(p.currency)} ${parseFloat(effectivePrice).toFixed(2)} / ${p.billing_period_months} month${p.billing_period_months > 1 ? 's' : ''}</div>
                        </div>
                        `;
                    }).join('');
                }
                resultsDiv.classList.add('show');
            });
    }
});

// Port search
document.getElementById('port_search').addEventListener('input', function() {
    clearTimeout(portSearchTimeout);
    const query = this.value.trim();
    const resultsDiv = document.getElementById('port_results');
    
    portSearchTimeout = setTimeout(() => {
        fetch(`${baseUrl}/admin/api/search-ports.php?q=${encodeURIComponent(query)}`)
            .then(r => r.json())
            .then(data => {
                if (data.length === 0) {
                    resultsDiv.innerHTML = '<div class="dropdown-empty">No available ports found</div>';
                } else {
                    resultsDiv.innerHTML = data.map(p => `
                        <div class="dropdown-item" onclick="selectPort('${p.id}', '${escapeHtml(p.instance_url)}', '${escapeHtml(p.db_name || '')}')">
                            <div class="dropdown-item-primary">${escapeHtml(p.instance_url)}</div>
                            <div class="dropdown-item-secondary">DB: ${escapeHtml(p.db_name || 'N/A')} | Region: ${escapeHtml(p.server_region || 'N/A')}</div>
                        </div>
                    `).join('');
                }
                resultsDiv.classList.add('show');
            });
    }, 300);
});

// Show all available ports on focus
document.getElementById('port_search').addEventListener('focus', function() {
    if (this.value.trim() === '' && document.getElementById('port_id').value === '') {
        fetch(`${baseUrl}/admin/api/search-ports.php?q=`)
            .then(r => r.json())
            .then(data => {
                const resultsDiv = document.getElementById('port_results');
                if (data.length === 0) {
                    resultsDiv.innerHTML = '<div class="dropdown-empty">No available ports</div>';
                } else {
                    resultsDiv.innerHTML = data.map(p => `
                        <div class="dropdown-item" onclick="selectPort('${p.id}', '${escapeHtml(p.instance_url)}', '${escapeHtml(p.db_name || '')}')">
                            <div class="dropdown-item-primary">${escapeHtml(p.instance_url)}</div>
                            <div class="dropdown-item-secondary">DB: ${escapeHtml(p.db_name || 'N/A')} | Region: ${escapeHtml(p.server_region || 'N/A')}</div>
                        </div>
                    `).join('');
                }
                resultsDiv.classList.add('show');
            });
    }
});

function selectCustomer(id, name, email) {
    document.getElementById('customer_id').value = id;
    document.getElementById('customer_search').style.display = 'none';
    document.getElementById('customer_results').classList.remove('show');
    
    const selectedDiv = document.getElementById('selected_customer');
    selectedDiv.innerHTML = `
        <div class="selected-item-info">
            <div class="selected-item-name">${escapeHtml(name)}</div>
            <div class="selected-item-detail">${escapeHtml(email)}</div>
        </div>
        <button type="button" class="selected-item-remove" onclick="clearCustomer()">×</button>
    `;
    selectedDiv.style.display = 'flex';
}

function clearCustomer() {
    document.getElementById('customer_id').value = '';
    document.getElementById('customer_search').value = '';
    document.getElementById('customer_search').style.display = 'block';
    document.getElementById('selected_customer').style.display = 'none';
}

function selectPlan(id, name, currency, price, months) {
    document.getElementById('plan_id').value = id;
    document.getElementById('plan_search').style.display = 'none';
    document.getElementById('plan_results').classList.remove('show');
    
    const selectedDiv = document.getElementById('selected_plan');
    selectedDiv.innerHTML = `
        <div class="selected-item-info">
            <div class="selected-item-name">${escapeHtml(name)}</div>
            <div class="selected-item-detail">${escapeHtml(currency)} ${parseFloat(price).toFixed(2)} / ${months} month${months > 1 ? 's' : ''}</div>
        </div>
        <button type="button" class="selected-item-remove" onclick="clearPlan()">×</button>
    `;
    selectedDiv.style.display = 'flex';
}

function clearPlan() {
    document.getElementById('plan_id').value = '';
    document.getElementById('plan_search').value = '';
    document.getElementById('plan_search').style.display = 'block';
    document.getElementById('selected_plan').style.display = 'none';
}

function selectPort(id, url, dbName) {
    document.getElementById('port_id').value = id;
    document.getElementById('port_search').style.display = 'none';
    document.getElementById('port_results').classList.remove('show');
    
    const selectedDiv = document.getElementById('selected_port');
    selectedDiv.innerHTML = `
        <div class="selected-item-info">
            <div class="selected-item-name">${escapeHtml(url)}</div>
            <div class="selected-item-detail">DB: ${escapeHtml(dbName || 'N/A')}</div>
        </div>
        <button type="button" class="selected-item-remove" onclick="clearPort()">×</button>
    `;
    selectedDiv.style.display = 'flex';
}

function clearPort() {
    document.getElementById('port_id').value = '';
    document.getElementById('port_search').value = '';
    document.getElementById('port_search').style.display = 'block';
    document.getElementById('selected_port').style.display = 'none';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.searchable-dropdown')) {
        document.querySelectorAll('.dropdown-results').forEach(d => d.classList.remove('show'));
    }
});
</script>

<?php include_admin_footer(); ?>
