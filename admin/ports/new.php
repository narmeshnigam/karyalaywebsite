<?php
/**
 * Admin Create New Port Page
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Services\PortService;
use Karyalay\Services\CsrfService;

// Start secure session
startSecureSession();

// Require admin authentication and ports.create permission
require_admin();
require_permission('ports.create');

// Initialize services
$portService = new PortService();
$csrfService = new CsrfService();

// Initialize variables
$errors = [];
$success = false;
$formData = [
    'instance_url' => '',
    'db_host' => '',
    'db_name' => '',
    'db_username' => '',
    'db_password' => '',
    'status' => 'AVAILABLE',
    'server_region' => '',
    'notes' => '',
    'setup_instructions' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$csrfService->validateToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Get form data
        $formData = [
            'instance_url' => trim($_POST['instance_url'] ?? ''),
            'db_host' => trim($_POST['db_host'] ?? ''),
            'db_name' => trim($_POST['db_name'] ?? ''),
            'db_username' => trim($_POST['db_username'] ?? ''),
            'db_password' => trim($_POST['db_password'] ?? ''),
            'status' => trim($_POST['status'] ?? 'AVAILABLE'),
            'server_region' => trim($_POST['server_region'] ?? ''),
            'notes' => trim($_POST['notes'] ?? ''),
            'setup_instructions' => $_POST['setup_instructions'] ?? ''
        ];

        // Validate required fields
        if (empty($formData['instance_url'])) {
            $errors[] = 'Instance URL is required.';
        }

        if (empty($errors)) {
            // Prepare data for creation
            $portData = [
                'instance_url' => $formData['instance_url'],
                'status' => $formData['status']
            ];

            // Add database connection fields
            if (!empty($formData['db_host'])) {
                $portData['db_host'] = $formData['db_host'];
            }
            if (!empty($formData['db_name'])) {
                $portData['db_name'] = $formData['db_name'];
            }
            if (!empty($formData['db_username'])) {
                $portData['db_username'] = $formData['db_username'];
            }
            if (!empty($formData['db_password'])) {
                $portData['db_password'] = $formData['db_password'];
            }
            // Add other optional fields
            if (!empty($formData['server_region'])) {
                $portData['server_region'] = $formData['server_region'];
            }
            if (!empty($formData['notes'])) {
                $portData['notes'] = $formData['notes'];
            }
            if (!empty($formData['setup_instructions'])) {
                $portData['setup_instructions'] = $formData['setup_instructions'];
            }

            // Add creator info for logging
            $portData['created_by'] = $_SESSION['user_id'] ?? null;
            
            // Create port
            $result = $portService->createPort($portData);

            // Debug: Log the result for troubleshooting
            error_log('Port creation attempt - Data: ' . json_encode($portData));
            error_log('Port creation result: ' . json_encode($result));

            if ($result['success']) {
                $success = true;
                $_SESSION['admin_success'] = 'Port created successfully!';
                header('Location: ' . get_app_base_url() . '/admin/ports.php');
                exit;
            } else {
                // Add detailed error information
                $errorMsg = $result['error'] ?? 'Failed to create port';
                if (isset($result['error_code'])) {
                    $errorMsg .= ' (Code: ' . $result['error_code'] . ')';
                }
                $errors[] = $errorMsg;
                
                // Debug: Store debug info for display
                $debugInfo = [
                    'portData' => $portData,
                    'result' => $result
                ];
            }
        }
    }
}

// Debug variable for template
$showDebug = true; // Set to false in production

// Generate CSRF token
$csrfToken = $csrfService->generateToken();

// Include admin header
include_admin_header('Add Port');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Add New Port</h1>
        <p class="admin-page-description">Add a single port to the pool</p>
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
    
    <?php if (isset($showDebug) && $showDebug && isset($debugInfo)): ?>
    <div class="alert" style="background-color: #fff3cd; border: 1px solid #ffc107; color: #856404; margin-bottom: 1rem;">
        <strong>Debug Information:</strong>
        <pre style="margin-top: 0.5rem; font-size: 12px; overflow-x: auto;"><?php 
            echo "Port Data Sent:\n";
            echo htmlspecialchars(json_encode($debugInfo['portData'] ?? [], JSON_PRETTY_PRINT));
            echo "\n\nService Response:\n";
            echo htmlspecialchars(json_encode($debugInfo['result'] ?? [], JSON_PRETTY_PRINT));
        ?></pre>
    </div>
    <?php endif; ?>
<?php endif; ?>

<div class="admin-card">
    <form method="POST" action="<?php echo get_app_base_url(); ?>/admin/ports/new.php" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        
        <div class="form-section">
            <h2 class="form-section-title">Port Information</h2>
            
            <div class="form-group">
                <label for="instance_url" class="form-label required">Instance URL</label>
                <input 
                    type="text" 
                    id="instance_url" 
                    name="instance_url" 
                    class="form-input" 
                    value="<?php echo htmlspecialchars($formData['instance_url']); ?>"
                    required
                    placeholder="e.g., https://instance1.karyalay.com or http://192.168.1.100"
                >
                <p class="form-help">The full URL or IP address where the Karyalay instance is hosted</p>
            </div>

            <div class="form-group">
                <label for="server_region" class="form-label">Server Region</label>
                <input 
                    type="text" 
                    id="server_region" 
                    name="server_region" 
                    class="form-input" 
                    value="<?php echo htmlspecialchars($formData['server_region']); ?>"
                    placeholder="e.g., US-East, EU-West, Asia-Pacific"
                >
                <p class="form-help">Optional geographic region of the server</p>
            </div>
        </div>

        <div class="form-section">
            <h2 class="form-section-title">Database Connection</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="db_host" class="form-label">Database Host</label>
                    <input 
                        type="text" 
                        id="db_host" 
                        name="db_host" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($formData['db_host']); ?>"
                        placeholder="e.g., localhost or db.example.com"
                    >
                    <p class="form-help">The database server hostname or IP address</p>
                </div>

                <div class="form-group">
                    <label for="db_name" class="form-label">Database Name</label>
                    <input 
                        type="text" 
                        id="db_name" 
                        name="db_name" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($formData['db_name']); ?>"
                        placeholder="e.g., karyalay_instance1"
                    >
                    <p class="form-help">The name of the database for this instance</p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="db_username" class="form-label">Database Username</label>
                    <input 
                        type="text" 
                        id="db_username" 
                        name="db_username" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($formData['db_username']); ?>"
                        placeholder="e.g., db_user"
                    >
                    <p class="form-help">The username for database authentication</p>
                </div>

                <div class="form-group">
                    <label for="db_password" class="form-label">Database Password</label>
                    <input 
                        type="password" 
                        id="db_password" 
                        name="db_password" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($formData['db_password']); ?>"
                        placeholder="Enter database password"
                    >
                    <p class="form-help">The password for database authentication</p>
                </div>
            </div>

        </div>

        <div class="form-section">
            <h2 class="form-section-title">Port Status</h2>
            
            <div class="form-group">
                <label for="status" class="form-label required">Status</label>
                <select id="status" name="status" class="form-select" required>
                    <option value="AVAILABLE" <?php echo $formData['status'] === 'AVAILABLE' ? 'selected' : ''; ?>>Available</option>
                    <option value="RESERVED" <?php echo $formData['status'] === 'RESERVED' ? 'selected' : ''; ?>>Reserved</option>
                    <option value="DISABLED" <?php echo $formData['status'] === 'DISABLED' ? 'selected' : ''; ?>>Disabled</option>
                </select>
                <p class="form-help">Initial status of the port (typically Available)</p>
            </div>

            <div class="form-group">
                <label for="notes" class="form-label">Notes</label>
                <textarea 
                    id="notes" 
                    name="notes" 
                    class="form-textarea" 
                    rows="4"
                    placeholder="Optional notes about this port (e.g., hardware specs, maintenance schedule)"
                ><?php echo htmlspecialchars($formData['notes']); ?></textarea>
                <p class="form-help">Optional internal notes about this port</p>
            </div>
        </div>

        <div class="form-section">
            <h2 class="form-section-title">Setup Instructions</h2>
            <p class="form-section-description">Provide setup instructions that will be shown to the customer on their "My Port" page.</p>
            
            <div class="form-group">
                <label for="setup_instructions" class="form-label">Setup Instructions (Rich Text)</label>
                <div id="setup_instructions_editor" class="rich-text-editor"><?php echo $formData['setup_instructions']; ?></div>
                <input type="hidden" name="setup_instructions" id="setup_instructions_input" value="<?php echo htmlspecialchars($formData['setup_instructions']); ?>">
                <p class="form-help">These instructions will be displayed to the customer. You can use formatting, lists, and links.</p>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Add Port</button>
            <a href="<?php echo get_app_base_url(); ?>/admin/ports.php" class="btn btn-secondary">Cancel</a>
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
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

/* Rich Text Editor */
.rich-text-editor {
    min-height: 250px;
    background: white;
    border: 1px solid var(--color-gray-300);
    border-radius: var(--radius-md);
}

.form-section-description {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin: -0.5rem 0 1rem 0;
}

.ql-toolbar {
    border-top-left-radius: var(--radius-md);
    border-top-right-radius: var(--radius-md);
    border-color: var(--color-gray-300) !important;
}

.ql-container {
    border-bottom-left-radius: var(--radius-md);
    border-bottom-right-radius: var(--radius-md);
    border-color: var(--color-gray-300) !important;
    font-size: var(--font-size-base);
}

.ql-editor {
    min-height: 200px;
}
</style>

<!-- Quill Editor CSS -->
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">

<!-- Quill Editor JS -->
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if editor element exists
    var editorElement = document.getElementById('setup_instructions_editor');
    if (!editorElement) {
        console.warn('Setup instructions editor element not found');
        return;
    }

    // Initialize Quill editor
    var quill = new Quill('#setup_instructions_editor', {
        theme: 'snow',
        placeholder: 'Enter setup instructions for the customer...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'indent': '-1'}, { 'indent': '+1' }],
                ['link', 'code-block'],
                ['clean']
            ]
        }
    });

    // Update hidden input on form submit
    var form = document.querySelector('.admin-form');
    if (form) {
        form.addEventListener('submit', function() {
            var hiddenInput = document.getElementById('setup_instructions_input');
            if (hiddenInput && quill) {
                hiddenInput.value = quill.root.innerHTML;
            }
        });
    }
});
</script>

<?php include_admin_footer(); ?>
