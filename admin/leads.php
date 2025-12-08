<?php
/**
 * Leads Management Page
 * View and manage leads captured from CTA forms
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration
$config = require __DIR__ . '/../config/app.php';

// Set error reporting based on environment
if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Load authentication helpers
require_once __DIR__ . '/../includes/auth_helpers.php';

// Start secure session
startSecureSession();

// Check if user is authenticated and is admin
if (!isAuthenticated() || !isAdmin()) {
    header('Location: ' . get_base_url() . '/login.php');
    exit;
}

// Include template helpers
require_once __DIR__ . '/../includes/template_helpers.php';

use Karyalay\Models\Lead;

$leadModel = new Lead();

// Handle status filter
$statusFilter = $_GET['status'] ?? null;
$filters = [];
if ($statusFilter && in_array($statusFilter, ['NEW', 'CONTACTED', 'QUALIFIED', 'CONVERTED', 'LOST'])) {
    $filters['status'] = $statusFilter;
}

// Get all leads
$leads = $leadModel->getAll($filters);

// Set page variables
$page_title = 'Leads Management';

// Include admin header
include __DIR__ . '/../templates/admin-header.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <h1>Leads Management</h1>
        <p class="text-gray-600">View and manage leads captured from your website</p>
    </div>

    <!-- Status Filter -->
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex gap-2 flex-wrap">
                <a href="<?php echo get_base_url(); ?>/admin/leads.php" 
                   class="btn <?php echo !$statusFilter ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
                    All Leads (<?php echo count($leadModel->getAll()); ?>)
                </a>
                <a href="<?php echo get_base_url(); ?>/admin/leads.php?status=NEW" 
                   class="btn <?php echo $statusFilter === 'NEW' ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
                    New (<?php echo count($leadModel->getAll(['status' => 'NEW'])); ?>)
                </a>
                <a href="<?php echo get_base_url(); ?>/admin/leads.php?status=CONTACTED" 
                   class="btn <?php echo $statusFilter === 'CONTACTED' ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
                    Contacted
                </a>
                <a href="<?php echo get_base_url(); ?>/admin/leads.php?status=QUALIFIED" 
                   class="btn <?php echo $statusFilter === 'QUALIFIED' ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
                    Qualified
                </a>
                <a href="<?php echo get_base_url(); ?>/admin/leads.php?status=CONVERTED" 
                   class="btn <?php echo $statusFilter === 'CONVERTED' ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
                    Converted
                </a>
                <a href="<?php echo get_base_url(); ?>/admin/leads.php?status=LOST" 
                   class="btn <?php echo $statusFilter === 'LOST' ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
                    Lost
                </a>
            </div>
        </div>
    </div>

    <?php if (empty($leads)): ?>
        <div class="card">
            <div class="card-body text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900">No leads found</h3>
                <p class="mt-1 text-sm text-gray-500">
                    <?php echo $statusFilter ? 'No leads with this status.' : 'Leads will appear here when visitors submit the contact form.'; ?>
                </p>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Company</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leads as $lead): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($lead['name']); ?></strong>
                                </td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($lead['email']); ?>" class="text-primary">
                                        <?php echo htmlspecialchars($lead['email']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if (!empty($lead['phone'])): ?>
                                        <a href="tel:<?php echo htmlspecialchars($lead['phone']); ?>">
                                            <?php echo htmlspecialchars($lead['phone']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $company = $lead['company'] ?? $lead['company_name'] ?? '';
                                    echo !empty($company) ? htmlspecialchars($company) : '<span class="text-gray-400">—</span>'; 
                                    ?>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo htmlspecialchars($lead['source'] ?? 'website'); ?>
                                    </span>
                                </td>
                                <td>
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
                                </td>
                                <td>
                                    <small class="text-gray-600">
                                        <?php echo date('M j, Y g:i A', strtotime($lead['created_at'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <a href="<?php echo get_base_url(); ?>/admin/leads/view.php?id=<?php echo urlencode($lead['id']); ?>" 
                                       class="btn btn-sm btn-outline">
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
</style>

<?php
// Include admin footer
include __DIR__ . '/../templates/admin-footer.php';
?>
