<?php
/**
 * Database Settings Management
 * Admin page to view and configure database credentials for dual-environment setup
 * 
 * Requirements: 4.2 - Administrator can configure live database credentials
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/template_helpers.php';

use Karyalay\Services\EnvironmentConfigManager;
use Karyalay\Services\InstallationService;
use Karyalay\Services\DatabaseValidationService;

// Start session and check admin authentication
startSecureSession();
require_admin();
require_permission('settings.general');

$configManager = new EnvironmentConfigManager();
$installationService = new InstallationService();
$dbValidator = new DatabaseValidationService();

$success = null;
$error = null;
$testResult = null;

// Get current environment info
$resolvedCredentials = $configManager->resolveCredentials();
$detectedEnvironment = $resolvedCredentials['detected_environment'];
$activeEnvironment = $resolvedCredentials['environment'];
$localCredentials = $configManager->readEnvironmentCredentials('local');
$liveCredentials = $configManager->readEnvironmentCredentials('live');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid security token. Please try again.');
        }
        
        $action = $_POST['action'] ?? '';

        if ($action === 'save_live_credentials') {
            // Get form data for live credentials
            $newLiveCredentials = [
                'host' => trim($_POST['live_host'] ?? ''),
                'port' => trim($_POST['live_port'] ?? '3306'),
                'database' => trim($_POST['live_database'] ?? ''),
                'username' => trim($_POST['live_username'] ?? ''),
                'password' => $_POST['live_password'] ?? '',
                'unix_socket' => trim($_POST['live_unix_socket'] ?? '')
            ];
            
            // Validate required fields
            $errors = [];
            if (empty($newLiveCredentials['host'])) {
                $errors[] = 'Host is required.';
            }
            if (empty($newLiveCredentials['database'])) {
                $errors[] = 'Database name is required.';
            }
            if (empty($newLiveCredentials['username'])) {
                $errors[] = 'Username is required.';
            }
            
            if (!empty($errors)) {
                throw new Exception(implode(' ', $errors));
            }
            
            // Test connection before saving (Requirements 4.3)
            $testResult = $dbValidator->testConnection($newLiveCredentials);
            
            if (!$testResult['success']) {
                throw new Exception($testResult['error_message']);
            }
            
            // Save credentials without disrupting current connection (Requirements 4.4)
            // Credentials take effect on next request
            $result = $configManager->writeDualConfig($localCredentials, $newLiveCredentials);
            
            if (!$result) {
                throw new Exception('Failed to save credentials. Please check file permissions.');
            }
            
            $success = 'Live database credentials saved successfully! Changes will take effect on the next request.';
            
            // Refresh credentials display
            $liveCredentials = $configManager->readEnvironmentCredentials('live');
            
            // Log the action
            $currentUser = getCurrentUser();
            if ($currentUser) {
                error_log('Live database credentials updated by admin: ' . $currentUser['email']);
            }
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log('Database settings error: ' . $e->getMessage());
    }
}

// Generate new CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Page title
$page_title = 'Database Settings';

// Include admin header
include __DIR__ . '/../templates/admin-header.php';
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Database Settings</h1>
        <p class="admin-page-description">View current environment and configure database credentials</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        <svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span><?php echo htmlspecialchars($success); ?></span>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span><?php echo htmlspecialchars($error); ?></span>
    </div>
<?php endif; ?>

<!-- Current Environment Status -->
<div class="admin-card">
    <div class="card-header">
        <h2 class="card-title">Current Environment Status</h2>
    </div>
    <div class="card-body">
        <div class="environment-status-grid">
            <div class="status-item">
                <span class="status-label">Detected Environment</span>
                <span class="status-value badge <?php echo $detectedEnvironment === 'localhost' ? 'badge-info' : 'badge-success'; ?>">
                    <?php echo ucfirst($detectedEnvironment); ?>
                </span>
            </div>
            <div class="status-item">
                <span class="status-label">Active Credentials</span>
                <span class="status-value badge <?php echo $activeEnvironment === 'local' ? 'badge-info' : 'badge-success'; ?>">
                    <?php echo $activeEnvironment ? ucfirst($activeEnvironment) : 'None'; ?>
                </span>
            </div>
            <div class="status-item">
                <span class="status-label">Local Credentials</span>
                <span class="status-value badge <?php echo $resolvedCredentials['local_available'] ? 'badge-success' : 'badge-secondary'; ?>">
                    <?php echo $resolvedCredentials['local_available'] ? 'Configured' : 'Not Configured'; ?>
                </span>
            </div>
            <div class="status-item">
                <span class="status-label">Live Credentials</span>
                <span class="status-value badge <?php echo $resolvedCredentials['live_available'] ? 'badge-success' : 'badge-secondary'; ?>">
                    <?php echo $resolvedCredentials['live_available'] ? 'Configured' : 'Not Configured'; ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Active Database Credentials (Masked) -->
<div class="admin-card">
    <div class="card-header">
        <h2 class="card-title">Active Database Credentials</h2>
    </div>
    <div class="card-body">
        <p class="card-description">Currently active database connection details (sensitive values masked)</p>
        
        <?php if ($resolvedCredentials['credentials']): ?>
            <div class="credentials-display">
                <div class="credential-row">
                    <span class="credential-label">Host:</span>
                    <span class="credential-value"><?php echo htmlspecialchars($resolvedCredentials['credentials']['host']); ?></span>
                </div>
                <div class="credential-row">
                    <span class="credential-label">Port:</span>
                    <span class="credential-value"><?php echo htmlspecialchars($resolvedCredentials['credentials']['port'] ?? '3306'); ?></span>
                </div>
                <div class="credential-row">
                    <span class="credential-label">Database:</span>
                    <span class="credential-value"><?php echo htmlspecialchars($resolvedCredentials['credentials']['database']); ?></span>
                </div>
                <div class="credential-row">
                    <span class="credential-label">Username:</span>
                    <span class="credential-value"><?php echo htmlspecialchars($resolvedCredentials['credentials']['username']); ?></span>
                </div>
                <div class="credential-row">
                    <span class="credential-label">Password:</span>
                    <span class="credential-value credential-masked">••••••••</span>
                </div>
                <?php if (!empty($resolvedCredentials['credentials']['unix_socket'])): ?>
                <div class="credential-row">
                    <span class="credential-label">Unix Socket:</span>
                    <span class="credential-value"><?php echo htmlspecialchars($resolvedCredentials['credentials']['unix_socket']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>No active credentials configured.</span>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Live Credentials Form -->
<div class="admin-card">
    <div class="card-header">
        <h2 class="card-title">Live Database Credentials</h2>
    </div>
    <div class="card-body">
        <p class="card-description">Configure database credentials for production/live environment (e.g., Hostinger)</p>

        <form method="POST" action="" class="admin-form" id="live-credentials-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="save_live_credentials">

            <div class="form-section">
                <h3 class="form-section-title">Server Settings</h3>
                
                <div class="form-group">
                    <label for="live_host" class="form-label">Host *</label>
                    <input 
                        type="text" 
                        id="live_host" 
                        name="live_host" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($liveCredentials['host'] ?? ''); ?>"
                        placeholder="e.g., localhost or mysql.hostinger.com"
                        required
                    >
                    <span class="form-help">The hostname of your production database server</span>
                </div>

                <div class="form-row">
                    <div class="form-group form-group-half">
                        <label for="live_port" class="form-label">Port</label>
                        <input 
                            type="number" 
                            id="live_port" 
                            name="live_port" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($liveCredentials['port'] ?? '3306'); ?>"
                            placeholder="3306"
                            min="1"
                            max="65535"
                        >
                        <span class="form-help">Default MySQL port is 3306</span>
                    </div>

                    <div class="form-group form-group-half">
                        <label for="live_unix_socket" class="form-label">Unix Socket (optional)</label>
                        <input 
                            type="text" 
                            id="live_unix_socket" 
                            name="live_unix_socket" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($liveCredentials['unix_socket'] ?? ''); ?>"
                            placeholder="/var/run/mysqld/mysqld.sock"
                        >
                        <span class="form-help">Leave empty to use TCP/IP connection</span>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="form-section-title">Database & Authentication</h3>
                
                <div class="form-group">
                    <label for="live_database" class="form-label">Database Name *</label>
                    <input 
                        type="text" 
                        id="live_database" 
                        name="live_database" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($liveCredentials['database'] ?? ''); ?>"
                        placeholder="e.g., u123456789_mydb"
                        required
                    >
                    <span class="form-help">The name of your production database</span>
                </div>

                <div class="form-group">
                    <label for="live_username" class="form-label">Username *</label>
                    <input 
                        type="text" 
                        id="live_username" 
                        name="live_username" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($liveCredentials['username'] ?? ''); ?>"
                        placeholder="e.g., u123456789_user"
                        required
                    >
                    <span class="form-help">Database username for production</span>
                </div>

                <div class="form-group">
                    <label for="live_password" class="form-label">Password *</label>
                    <div class="input-group">
                        <input 
                            type="password" 
                            id="live_password" 
                            name="live_password" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($liveCredentials['password'] ?? ''); ?>"
                            placeholder="Enter database password"
                            required
                        >
                        <button type="button" class="btn btn-secondary" onclick="togglePasswordVisibility('live_password')" aria-label="Toggle password visibility">
                            <svg class="btn-icon eye-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <span class="form-help">Database password for production</span>
                </div>
            </div>

            <!-- Info Section -->
            <div class="alert alert-info">
                <svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <strong>How it works:</strong>
                    <ul style="margin: 0.5rem 0 0 1.5rem; padding: 0;">
                        <li>The system automatically detects whether it's running on localhost or production</li>
                        <li>On localhost: Uses local credentials (for development)</li>
                        <li>On production: Uses live credentials (for your hosting server)</li>
                        <li>Connection will be tested before saving to ensure credentials are valid</li>
                    </ul>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="button" id="test-connection-btn" class="btn btn-secondary">
                    <svg class="btn-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    Test Connection
                </button>
                <button type="submit" class="btn btn-primary">
                    <svg class="btn-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Save Live Credentials
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Test Connection Result -->
<div id="test-result" class="alert" style="margin-top: 1.5rem; display: none;"></div>

<!-- Security Notice -->
<div class="alert alert-warning" style="margin-top: 1.5rem;">
    <svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
    </svg>
    <div>
        <strong>Security Notice:</strong>
        <p style="margin: 0.25rem 0 0 0;">Database credentials are stored in the .env file. Ensure this file is not accessible via web browser and is excluded from version control. Never share your database password with anyone.</p>
    </div>
</div>

<style>
.environment-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.status-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.status-label {
    font-size: 0.875rem;
    color: var(--text-muted);
    font-weight: 500;
}

.status-value {
    font-size: 0.875rem;
}

.credentials-display {
    background: var(--bg-secondary);
    border-radius: 8px;
    padding: 1rem;
}

.credential-row {
    display: flex;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border-color);
}

.credential-row:last-child {
    border-bottom: none;
}

.credential-label {
    font-weight: 500;
    width: 120px;
    color: var(--text-muted);
}

.credential-value {
    flex: 1;
    font-family: monospace;
}

.credential-masked {
    color: var(--text-muted);
}

.badge-info {
    background-color: #3b82f6;
    color: white;
}

.badge-success {
    background-color: #10b981;
    color: white;
}

.badge-secondary {
    background-color: #6b7280;
    color: white;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group-half {
    flex: 1;
}

.input-group {
    display: flex;
    gap: 0.5rem;
}

.input-group .form-input {
    flex: 1;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function togglePasswordVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    if (field.type === 'password') {
        field.type = 'text';
    } else {
        field.type = 'password';
    }
}

document.getElementById('test-connection-btn').addEventListener('click', function() {
    const btn = this;
    const resultDiv = document.getElementById('test-result');
    
    // Get form values
    const formData = new FormData();
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    formData.append('host', document.getElementById('live_host').value);
    formData.append('port', document.getElementById('live_port').value || '3306');
    formData.append('database', document.getElementById('live_database').value);
    formData.append('username', document.getElementById('live_username').value);
    formData.append('password', document.getElementById('live_password').value);
    formData.append('unix_socket', document.getElementById('live_unix_socket').value);
    
    // Disable button and show loading state
    btn.disabled = true;
    btn.innerHTML = '<svg class="btn-icon spin" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg> Testing...';
    
    resultDiv.style.display = 'none';
    
    fetch('./api/test-database-connection.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        resultDiv.style.display = 'flex';
        if (data.success) {
            resultDiv.className = 'alert alert-success';
            resultDiv.innerHTML = '<svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span>' + data.message + '</span>';
        } else {
            resultDiv.className = 'alert alert-danger';
            resultDiv.innerHTML = '<svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span>' + data.message + '</span>';
        }
    })
    .catch(error => {
        resultDiv.style.display = 'flex';
        resultDiv.className = 'alert alert-danger';
        resultDiv.innerHTML = '<svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span>Failed to test connection: ' + error.message + '</span>';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<svg class="btn-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg> Test Connection';
    });
});
</script>

<?php include __DIR__ . '/../templates/admin-footer.php'; ?>
