<?php
/**
 * View Lead Details
 */

// Load Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Load configuration
$config = require __DIR__ . '/../../config/app.php';

// Set error reporting based on environment
if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Load authentication helpers
require_once __DIR__ . '/../../includes/auth_helpers.php';

// Start secure session
startSecureSession();

// Check if user is authenticated and is admin
if (!isAuthenticated() || !isAdmin()) {
    header('Location: ' . get_base_url() . '/login.php');
    exit;
}

// Include template helpers
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Models\Lead;

$leadModel = new Lead();

// Get lead ID from query string
$leadId = $_GET['id'] ?? null;

if (!$leadId) {
    header('Location: ' . get_base_url() . '/admin/leads.php');
    exit;
}

// Get lead details
$lead = $leadModel->findById($leadId);

if (!$lead) {
    $_SESSION['flash_message'] = 'Lead not found.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . get_base_url() . '/admin/leads.php');
    exit;
}

// Set page variables
$page_title = 'View Lead - ' . $lead['name'];

// Include admin header
include __DIR__ . '/../../templates/admin-header.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <div>
            <a href="<?php echo get_base_url(); ?>/admin/leads.php" class="text-primary hover:underline mb-2 inline-block">
                ← Back to Leads
            </a>
            <h1>Lead Details</h1>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Details -->
        <div class="lg:col-span-2">
            <div class="card">
                <div class="card-header">
                    <h2 class="text-xl font-semibold">Contact Information</h2>
                </div>
                <div class="card-body">
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Name</label>
                            <p class="text-lg font-semibold"><?php echo htmlspecialchars($lead['name']); ?></p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-500">Email</label>
                            <p>
                                <a href="mailto:<?php echo htmlspecialchars($lead['email']); ?>" class="text-primary hover:underline">
                                    <?php echo htmlspecialchars($lead['email']); ?>
                                </a>
                            </p>
                        </div>

                        <?php if (!empty($lead['phone'])): ?>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Phone</label>
                                <p>
                                    <a href="tel:<?php echo htmlspecialchars($lead['phone']); ?>" class="text-primary hover:underline">
                                        <?php echo htmlspecialchars($lead['phone']); ?>
                                    </a>
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php 
                        $company = $lead['company'] ?? $lead['company_name'] ?? '';
                        if (!empty($company)): 
                        ?>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Company</label>
                                <p class="text-lg"><?php echo htmlspecialchars($company); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($lead['message'])): ?>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Message</label>
                                <div class="mt-1 p-4 bg-gray-50 rounded-lg">
                                    <p class="whitespace-pre-wrap"><?php echo htmlspecialchars($lead['message']); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <!-- Status Card -->
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="text-lg font-semibold">Lead Status</h3>
                </div>
                <div class="card-body">
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Current Status</label>
                            <p class="mt-1">
                                <span class="badge badge-<?php 
                                    echo match($lead['status']) {
                                        'NEW' => 'primary',
                                        'CONTACTED' => 'info',
                                        'QUALIFIED' => 'warning',
                                        'CONVERTED' => 'success',
                                        'LOST' => 'danger',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?php echo htmlspecialchars($lead['status']); ?>
                                </span>
                            </p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-500">Source</label>
                            <p class="mt-1">
                                <span class="badge badge-info">
                                    <?php echo htmlspecialchars($lead['source'] ?? 'website'); ?>
                                </span>
                            </p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-500">Received</label>
                            <p class="text-sm">
                                <?php echo date('F j, Y', strtotime($lead['created_at'])); ?><br>
                                <span class="text-gray-500"><?php echo date('g:i A', strtotime($lead['created_at'])); ?></span>
                            </p>
                        </div>

                        <?php if ($lead['updated_at'] !== $lead['created_at']): ?>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Last Updated</label>
                                <p class="text-sm">
                                    <?php echo date('F j, Y', strtotime($lead['updated_at'])); ?><br>
                                    <span class="text-gray-500"><?php echo date('g:i A', strtotime($lead['updated_at'])); ?></span>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h3 class="text-lg font-semibold">Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="space-y-2">
                        <a href="mailto:<?php echo htmlspecialchars($lead['email']); ?>" 
                           class="btn btn-primary btn-block">
                            Send Email
                        </a>
                        <?php if (!empty($lead['phone'])): ?>
                            <a href="tel:<?php echo htmlspecialchars($lead['phone']); ?>" 
                               class="btn btn-outline btn-block">
                                Call Phone
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.375rem;
}

.badge-primary {
    background-color: #3b82f6;
    color: white;
}

.badge-info {
    background-color: #06b6d4;
    color: white;
}

.badge-warning {
    background-color: #f59e0b;
    color: white;
}

.badge-success {
    background-color: #10b981;
    color: white;
}

.badge-danger {
    background-color: #ef4444;
    color: white;
}

.badge-secondary {
    background-color: #6b7280;
    color: white;
}

.btn-block {
    display: block;
    width: 100%;
}

.space-y-2 > * + * {
    margin-top: 0.5rem;
}

.space-y-3 > * + * {
    margin-top: 0.75rem;
}

.space-y-4 > * + * {
    margin-top: 1rem;
}

.grid {
    display: grid;
}

.grid-cols-1 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
}

.gap-6 {
    gap: 1.5rem;
}

@media (min-width: 1024px) {
    .lg\:col-span-1 {
        grid-column: span 1 / span 1;
    }
    
    .lg\:col-span-2 {
        grid-column: span 2 / span 2;
    }
    
    .lg\:grid-cols-3 {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}
</style>

<?php
// Include admin footer
include __DIR__ . '/../../templates/admin-footer.php';
?>
