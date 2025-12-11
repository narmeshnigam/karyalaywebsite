<?php
/**
 * Admin Leads List Page
 * Displays all leads captured from CTA forms
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

use Karyalay\Models\Lead;

// Start secure session
startSecureSession();

// Require admin authentication and leads.view permission
require_admin();
require_permission('leads.view');

// Get database connection
$db = \Karyalay\Database\Connection::getInstance();

// Initialize models
$leadModel = new Lead();

// Get filters from query parameters
$status_filter = $_GET['status'] ?? '';
$search_query = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query for counting total leads
$count_sql = "SELECT COUNT(*) FROM leads WHERE 1=1";
$count_params = [];

if (!empty($status_filter)) {
    $count_sql .= " AND status = :status";
    $count_params[':status'] = $status_filter;
}

if (!empty($search_query)) {
    $count_sql .= " AND (name LIKE :search OR email LIKE :search OR company LIKE :search OR phone LIKE :search)";
    $count_params[':search'] = '%' . $search_query . '%';
}

try {
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($count_params);
    $total_leads = $count_stmt->fetchColumn();
    $total_pages = ceil($total_leads / $per_page);
} catch (PDOException $e) {
    error_log("Leads count error: " . $e->getMessage());
    $total_leads = 0;
    $total_pages = 0;
}

// Build query for fetching leads
$sql = "SELECT * FROM leads WHERE 1=1";
$params = [];

if (!empty($status_filter)) {
    $sql .= " AND status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search_query)) {
    $sql .= " AND (name LIKE :search OR email LIKE :search OR company LIKE :search OR phone LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

$sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

try {
    $stmt = $db->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Leads list error: " . $e->getMessage());
    $leads = [];
}

// Include admin header
include_admin_header('Leads');

// Include export button helper
require_once __DIR__ . '/../includes/export_button_helper.php';
?>

<?php render_export_button_styles(); ?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Lead Management</h1>
        <p class="admin-page-description">View and manage leads captured from your website</p>
    </div>
    <div class="admin-page-header-actions">
        <?php render_export_button(get_app_base_url() . '/admin/api/export-leads.php'); ?>
    </div>
</div>

<!-- Filters and Search -->
<div class="admin-filters-section">
    <form method="GET" action="<?php echo get_app_base_url(); ?>/admin/leads.php" class="admin-filters-form">
        <div class="admin-filter-group">
            <label for="search" class="admin-filter-label">Search</label>
            <input 
                type="text" 
                id="search" 
                name="search" 
                class="admin-filter-input" 
                placeholder="Search by name, email, company, or phone..."
                value="<?php echo htmlspecialchars($search_query); ?>"
            >
        </div>
        
        <div class="admin-filter-group">
            <label for="status" class="admin-filter-label">Lead Status</label>
            <select id="status" name="status" class="admin-filter-select">
                <option value="">All Leads</option>
                <option value="NEW" <?php echo $status_filter === 'NEW' ? 'selected' : ''; ?>>New</option>
                <option value="CONTACTED" <?php echo $status_filter === 'CONTACTED' ? 'selected' : ''; ?>>Contacted</option>
                <option value="QUALIFIED" <?php echo $status_filter === 'QUALIFIED' ? 'selected' : ''; ?>>Qualified</option>
                <option value="CONVERTED" <?php echo $status_filter === 'CONVERTED' ? 'selected' : ''; ?>>Converted</option>
                <option value="LOST" <?php echo $status_filter === 'LOST' ? 'selected' : ''; ?>>Lost</option>
            </select>
        </div>
        
        <div class="admin-filter-actions">
            <button type="submit" class="btn btn-secondary">Apply Filters</button>
            <a href="<?php echo get_app_base_url(); ?>/admin/leads.php" class="btn btn-text">Clear</a>
        </div>
    </form>
</div>

<!-- Leads Table -->
<div class="admin-card">
    <?php if (empty($leads)): ?>
        <?php 
        render_empty_state(
            'No leads found',
            'No leads match your current filters',
            '',
            ''
        );
        ?>
    <?php else: ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Lead</th>
                        <th>Company</th>
                        <th>Contact Info</th>
                        <th>Date Received</th>
                        <th>Lead Status</th>
                        <th>Source</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $lead): ?>
                        <tr>
                            <td>
                                <div class="table-cell-primary">
                                    <?php echo htmlspecialchars($lead['name']); ?>
                                </div>
                                <div class="table-cell-secondary">
                                    <?php echo htmlspecialchars($lead['email']); ?>
                                </div>
                            </td>
                            <td>
                                <?php 
                                $company = $lead['company'] ?? $lead['company_name'] ?? '';
                                if (!empty($company)): 
                                ?>
                                    <?php echo htmlspecialchars($company); ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($lead['phone'])): ?>
                                    <?php echo htmlspecialchars($lead['phone']); ?>
                                <?php else: ?>
                                    <span class="text-muted">No phone</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo get_relative_time($lead['created_at']); ?></td>
                            <td>
                                <?php 
                                $status_config = [
                                    'NEW' => 'info',
                                    'CONTACTED' => 'warning',
                                    'QUALIFIED' => 'warning',
                                    'CONVERTED' => 'success',
                                    'LOST' => 'danger'
                                ];
                                echo get_status_badge($lead['status'], $status_config);
                                ?>
                            </td>
                            <td>
                                <span class="badge badge-secondary">
                                    <?php echo htmlspecialchars($lead['source'] ?? 'website'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?php echo get_app_base_url(); ?>/admin/leads/view.php?id=<?php echo urlencode($lead['id']); ?>" 
                                       class="btn btn-sm btn-primary"
                                       title="View lead details">
                                        View
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="admin-card-footer">
                <?php 
                $base_url = get_app_base_url() . '/admin/leads.php';
                $query_params = [];
                if (!empty($status_filter)) {
                    $query_params[] = 'status=' . urlencode($status_filter);
                }
                if (!empty($search_query)) {
                    $query_params[] = 'search=' . urlencode($search_query);
                }
                if (!empty($query_params)) {
                    $base_url .= '?' . implode('&', $query_params);
                }
                render_admin_pagination($page, $total_pages, $base_url);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-card-footer">
            <p class="admin-card-footer-text">
                Showing <?php echo count($leads); ?> of <?php echo $total_leads; ?> lead<?php echo $total_leads !== 1 ? 's' : ''; ?>
            </p>
        </div>
    <?php endif; ?>
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

.admin-filters-section {
    background: white;
    border: 1px solid var(--color-gray-200);
    border-radius: var(--radius-lg);
    padding: var(--spacing-4);
    margin-bottom: var(--spacing-6);
}

.admin-filters-form {
    display: flex;
    gap: var(--spacing-4);
    align-items: flex-end;
    flex-wrap: wrap;
}

.admin-filter-group {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
    flex: 1;
    min-width: 200px;
}

.admin-filter-label {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
}

.admin-filter-input,
.admin-filter-select {
    padding: var(--spacing-2) var(--spacing-3);
    border: 1px solid var(--color-gray-300);
    border-radius: var(--radius-md);
    font-size: var(--font-size-base);
    color: var(--color-gray-900);
}

.admin-filter-input:focus,
.admin-filter-select:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.admin-filter-actions {
    display: flex;
    gap: var(--spacing-2);
}

/* Table Layout */
.admin-table-container {
    overflow-x: auto;
}

.admin-table {
    width: 100%;
    table-layout: fixed;
}

.admin-table th,
.admin-table td {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    vertical-align: middle;
}

/* Column widths */
.admin-table th:nth-child(1),
.admin-table td:nth-child(1) { width: 200px; } /* Lead */

.admin-table th:nth-child(2),
.admin-table td:nth-child(2) { width: 150px; } /* Company */

.admin-table th:nth-child(3),
.admin-table td:nth-child(3) { width: 120px; } /* Contact Info */

.admin-table th:nth-child(4),
.admin-table td:nth-child(4) { width: 120px; } /* Date Received */

.admin-table th:nth-child(5),
.admin-table td:nth-child(5) { width: 110px; } /* Lead Status */

.admin-table th:nth-child(6),
.admin-table td:nth-child(6) { width: 100px; } /* Source */

.admin-table th:nth-child(7),
.admin-table td:nth-child(7) { width: 90px; } /* Actions */

.table-cell-primary {
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.table-cell-secondary {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.text-muted {
    color: var(--color-gray-500);
    font-style: italic;
}

.table-actions {
    display: flex;
    gap: var(--spacing-2);
}

.admin-card-footer {
    padding: var(--spacing-4);
    border-top: 1px solid var(--color-gray-200);
}

.admin-card-footer-text {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin: 0;
}

@media (max-width: 1200px) {
    .admin-table {
        table-layout: auto;
    }
    
    .admin-table th,
    .admin-table td {
        white-space: normal;
    }
}

@media (max-width: 768px) {
    .admin-page-header {
        flex-direction: column;
    }
    
    .admin-filters-form {
        flex-direction: column;
    }
    
    .admin-filter-group {
        width: 100%;
    }
}
</style>

<?php include_admin_footer(); ?>
