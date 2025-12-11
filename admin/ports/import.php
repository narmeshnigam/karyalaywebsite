<?php
/**
 * Admin Port Bulk Import Page
 * Allows CSV upload for bulk port creation
 * 
 * Updated for port restructuring: ports are now plan-agnostic with resource limits
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Services\PortService;
use Karyalay\Services\CsrfService;

// Start secure session
startSecureSession();

// Require admin authentication and ports.import permission
require_admin();
require_permission('ports.import');

// Initialize services
$portService = new PortService();
$csrfService = new CsrfService();

// Initialize variables
$errors = [];
$importResults = null;
$uploadedFile = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$csrfService->validateToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Check if file was uploaded
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Please select a CSV file to upload.';
        } elseif ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed. Please try again.';
        } else {
            $file = $_FILES['csv_file'];
            
            // Validate file type
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExtension, ['csv', 'txt'])) {
                $errors[] = 'Invalid file type. Please upload a CSV file.';
            }
            
            // Validate file size (max 5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                $errors[] = 'File is too large. Maximum size is 5MB.';
            }

            if (empty($errors)) {
                // Parse CSV file
                $handle = fopen($file['tmp_name'], 'r');
                if ($handle === false) {
                    $errors[] = 'Failed to read CSV file.';
                } else {
                    $portsData = [];
                    $lineNumber = 0;
                    $header = null;
                    $parseErrors = [];

                    while (($row = fgetcsv($handle)) !== false) {
                        $lineNumber++;
                        
                        // First row is header
                        if ($lineNumber === 1) {
                            $header = array_map('trim', $row);
                            // Validate required columns
                            $requiredColumns = ['instance_url'];
                            $missingColumns = array_diff($requiredColumns, array_map('strtolower', $header));
                            if (!empty($missingColumns)) {
                                $parseErrors[] = "Missing required columns: " . implode(', ', $missingColumns);
                                break;
                            }
                            continue;
                        }

                        // Skip empty rows
                        if (empty(array_filter($row))) {
                            continue;
                        }

                        // Map row to associative array
                        $rowData = [];
                        foreach ($header as $index => $columnName) {
                            $rowData[strtolower(trim($columnName))] = isset($row[$index]) ? trim($row[$index]) : '';
                        }

                        // Validate and prepare port data
                        $portData = [];
                        
                        // Instance URL (required)
                        if (empty($rowData['instance_url'])) {
                            $parseErrors[$lineNumber] = "Line $lineNumber: instance_url is required";
                            continue;
                        }
                        $portData['instance_url'] = $rowData['instance_url'];

                        // Database connection fields (optional)
                        if (!empty($rowData['db_host'])) {
                            $portData['db_host'] = $rowData['db_host'];
                        }
                        if (!empty($rowData['db_name'])) {
                            $portData['db_name'] = $rowData['db_name'];
                        }
                        if (!empty($rowData['db_username'])) {
                            $portData['db_username'] = $rowData['db_username'];
                        }
                        if (!empty($rowData['db_password'])) {
                            $portData['db_password'] = $rowData['db_password'];
                        }

                        // Status (optional, default to AVAILABLE)
                        $status = !empty($rowData['status']) ? strtoupper(trim($rowData['status'])) : 'AVAILABLE';
                        $validStatuses = ['AVAILABLE', 'RESERVED', 'DISABLED'];
                        if (!in_array($status, $validStatuses)) {
                            $parseErrors[$lineNumber] = "Line $lineNumber: invalid status '$status'";
                            continue;
                        }
                        $portData['status'] = $status;

                        // Server region (optional)
                        if (!empty($rowData['server_region'])) {
                            $portData['server_region'] = $rowData['server_region'];
                        }

                        // Notes (optional)
                        if (!empty($rowData['notes'])) {
                            $portData['notes'] = $rowData['notes'];
                        }

                        $portsData[] = $portData;
                    }

                    fclose($handle);

                    // If there were parse errors, show them
                    if (!empty($parseErrors)) {
                        $errors = array_merge($errors, array_values($parseErrors));
                    }

                    // Import ports if we have valid data
                    if (!empty($portsData) && empty($errors)) {
                        $importResults = $portService->bulkImportPorts($portsData);
                    } elseif (empty($portsData) && empty($errors)) {
                        $errors[] = 'No valid port data found in CSV file.';
                    }
                }
            }
        }
    }
}

// Generate CSRF token
$csrfToken = $csrfService->generateToken();

// Include admin header
include_admin_header('Import Ports');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Bulk Import Ports</h1>
        <p class="admin-page-description">Upload a CSV file to import multiple ports at once</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/ports.php" class="btn btn-secondary">
            ← Back to Ports
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

<?php if ($importResults): ?>
    <div class="alert <?php echo $importResults['failed'] > 0 ? 'alert-warning' : 'alert-success'; ?>">
        <strong>Import Complete!</strong>
        <ul>
            <li>Successfully imported: <?php echo $importResults['imported']; ?> port<?php echo $importResults['imported'] !== 1 ? 's' : ''; ?></li>
            <?php if ($importResults['failed'] > 0): ?>
                <li>Failed: <?php echo $importResults['failed']; ?> port<?php echo $importResults['failed'] !== 1 ? 's' : ''; ?></li>
            <?php endif; ?>
        </ul>
        
        <?php if (!empty($importResults['errors'])): ?>
            <details class="error-details">
                <summary>View Error Details</summary>
                <ul class="error-list">
                    <?php foreach ($importResults['errors'] as $index => $error): ?>
                        <li>Row <?php echo $index + 2; ?>: <?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </details>
        <?php endif; ?>
        
        <div style="margin-top: var(--spacing-3);">
            <a href="<?php echo get_app_base_url(); ?>/admin/ports.php" class="btn btn-primary">View All Ports</a>
        </div>
    </div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-form" style="padding: var(--spacing-6);">
        <div class="form-section">
            <h2 class="form-section-title">CSV File Format</h2>
            <p class="form-help" style="margin-bottom: var(--spacing-4);">
                Your CSV file should have the following columns. The first row should contain column headers.
            </p>
            
            <div class="csv-format-table">
                <table class="format-table">
                    <thead>
                        <tr>
                            <th>Column Name</th>
                            <th>Required</th>
                            <th>Description</th>
                            <th>Example</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>instance_url</code></td>
                            <td><span class="badge badge-required">Required</span></td>
                            <td>Full URL or IP address of the instance</td>
                            <td>https://instance1.example.com</td>
                        </tr>
                        <tr>
                            <td><code>db_host</code></td>
                            <td><span class="badge badge-optional">Optional</span></td>
                            <td>Database server hostname</td>
                            <td>localhost</td>
                        </tr>
                        <tr>
                            <td><code>db_name</code></td>
                            <td><span class="badge badge-optional">Optional</span></td>
                            <td>Database name</td>
                            <td>app_db</td>
                        </tr>
                        <tr>
                            <td><code>db_username</code></td>
                            <td><span class="badge badge-optional">Optional</span></td>
                            <td>Database username</td>
                            <td>db_user</td>
                        </tr>
                        <tr>
                            <td><code>db_password</code></td>
                            <td><span class="badge badge-optional">Optional</span></td>
                            <td>Database password</td>
                            <td>secret123</td>
                        </tr>
                        <tr>
                            <td><code>status</code></td>
                            <td><span class="badge badge-optional">Optional</span></td>
                            <td>AVAILABLE, RESERVED, or DISABLED</td>
                            <td>AVAILABLE</td>
                        </tr>
                        <tr>
                            <td><code>server_region</code></td>
                            <td><span class="badge badge-optional">Optional</span></td>
                            <td>Geographic region</td>
                            <td>US-East</td>
                        </tr>
                        <tr>
                            <td><code>notes</code></td>
                            <td><span class="badge badge-optional">Optional</span></td>
                            <td>Internal notes</td>
                            <td>High-performance server</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="example-section">
                <h3 class="example-title">Example CSV Content:</h3>
                <pre class="example-code">instance_url,db_host,db_name,db_username,db_password,status,server_region,notes
https://instance1.example.com,localhost,app_db1,db_user1,pass123,AVAILABLE,US-East,Primary server
https://instance2.example.com,db.example.com,app_db2,db_user2,pass456,AVAILABLE,EU-West,
http://192.168.1.100,192.168.1.50,app_db3,db_user3,pass789,RESERVED,Asia-Pacific,Testing server</pre>
            </div>
        </div>

        <div class="form-section">
            <h2 class="form-section-title">Upload CSV File</h2>
            
            <form method="POST" action="/admin/ports/import.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                
                <div class="form-group">
                    <label for="csv_file" class="form-label required">Select CSV File</label>
                    <input 
                        type="file" 
                        id="csv_file" 
                        name="csv_file" 
                        class="form-input" 
                        accept=".csv,.txt"
                        required
                    >
                    <p class="form-help">Maximum file size: 5MB. Accepted formats: .csv, .txt</p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Upload and Import</button>
                    <a href="<?php echo get_app_base_url(); ?>/admin/ports.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
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

.alert-warning {
    background-color: #fffbeb;
    border: 1px solid #fde68a;
    color: #92400e;
}

.alert ul {
    margin: var(--spacing-2) 0 0 var(--spacing-4);
}

.error-details {
    margin-top: var(--spacing-3);
    padding: var(--spacing-3);
    background-color: rgba(255, 255, 255, 0.5);
    border-radius: var(--radius-sm);
}

.error-details summary {
    cursor: pointer;
    font-weight: var(--font-weight-semibold);
    margin-bottom: var(--spacing-2);
}

.error-list {
    margin: var(--spacing-2) 0 0 var(--spacing-4);
    font-size: var(--font-size-sm);
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

.form-input {
    width: 100%;
    padding: var(--spacing-2) var(--spacing-3);
    border: 1px solid var(--color-gray-300);
    border-radius: var(--radius-md);
    font-size: var(--font-size-base);
    color: var(--color-gray-900);
    font-family: inherit;
}

.form-input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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

.csv-format-table {
    overflow-x: auto;
    margin-bottom: var(--spacing-4);
}

.format-table {
    width: 100%;
    border-collapse: collapse;
    font-size: var(--font-size-sm);
}

.format-table th,
.format-table td {
    padding: var(--spacing-2) var(--spacing-3);
    text-align: left;
    border-bottom: 1px solid var(--color-gray-200);
}

.format-table th {
    background-color: var(--color-gray-50);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
}

.format-table code {
    background-color: var(--color-gray-100);
    padding: 2px 6px;
    border-radius: var(--radius-sm);
    font-family: 'Courier New', monospace;
    font-size: var(--font-size-sm);
}

.badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: var(--radius-sm);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-semibold);
}

.badge-required {
    background-color: #fee;
    color: #c33;
}

.badge-optional {
    background-color: var(--color-gray-100);
    color: var(--color-gray-700);
}

.example-section {
    margin-top: var(--spacing-4);
    margin-bottom: var(--spacing-4);
}

.example-title {
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-2) 0;
}

.example-code {
    background-color: var(--color-gray-50);
    border: 1px solid var(--color-gray-200);
    border-radius: var(--radius-md);
    padding: var(--spacing-3);
    font-family: 'Courier New', monospace;
    font-size: var(--font-size-sm);
    overflow-x: auto;
    white-space: pre;
}

.resource-limits-info {
    margin-top: var(--spacing-4);
}

.limits-list {
    list-style: none;
    padding: 0;
    margin: var(--spacing-2) 0 0 0;
}

.limits-list li {
    padding: var(--spacing-2);
    border-bottom: 1px solid var(--color-gray-200);
}

.limits-list li:last-child {
    border-bottom: none;
}

.text-muted {
    color: var(--color-gray-500);
    font-size: var(--font-size-sm);
}

@media (max-width: 768px) {
    .admin-page-header {
        flex-direction: column;
    }
    
    .csv-format-table {
        font-size: var(--font-size-xs);
    }
}
</style>

<?php include_admin_footer(); ?>
