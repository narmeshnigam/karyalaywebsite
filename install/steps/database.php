<?php
/**
 * Installation Wizard - Database Configuration Step
 * 
 * This step allows users to configure database credentials and test the connection.
 * 
 * Requirements: 2.1, 2.2, 2.3, 2.4
 */

// This file is included by install/index.php
// Available variables: $installationService, $csrfService, $progress, $currentStep

$errors = [];
$formData = [];
$testResult = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] === 'database') {
    // Collect form data
    $formData = [
        'host' => trim($_POST['host'] ?? ''),
        'port' => trim($_POST['port'] ?? '3306'),
        'database' => trim($_POST['database'] ?? ''),
        'username' => trim($_POST['username'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'unix_socket' => trim($_POST['unix_socket'] ?? '')
    ];
    
    // Save form data to session immediately for recovery
    $installationService->saveStepData(1, $formData);
    
    // Server-side validation
    if (empty($formData['database'])) {
        $errors['database'] = 'Database name is required.';
    }
    
    if (empty($formData['username'])) {
        $errors['username'] = 'Database username is required.';
    }
    
    // Validate host or unix_socket
    if (empty($formData['unix_socket'])) {
        if (empty($formData['host'])) {
            $errors['host'] = 'Database host is required when not using Unix socket.';
        }
    }
    
    // Validate port if provided
    if (!empty($formData['port'])) {
        $port = filter_var($formData['port'], FILTER_VALIDATE_INT);
        if ($port === false || $port < 1 || $port > 65535) {
            $errors['port'] = 'Port must be a number between 1 and 65535.';
        }
    }
    
    // If no validation errors, test connection and save
    if (empty($errors)) {
        // Test database connection
        $testResult = $installationService->testDatabaseConnection($formData);
        
        if ($testResult['success']) {
            // Write database configuration
            $writeSuccess = $installationService->writeDatabaseConfig($formData);
            
            if ($writeSuccess) {
                // Mark step as completed
                $progress['database_configured'] = true;
                if (!in_array(1, $progress['completed_steps'])) {
                    $progress['completed_steps'][] = 1;
                }
                $progress['current_step'] = 2; // Move to migrations step
                $installationService->saveProgress($progress);
                
                // Redirect to next step
                header('Location: ?action=next');
                exit;
            } else {
                $errors['general'] = 'Failed to write database configuration. Please check file permissions.';
            }
        } else {
            $errors['general'] = 'Database connection failed: ' . htmlspecialchars($testResult['error']);
        }
    }
}

// Load saved data if available (for back navigation or page reload)
if (empty($formData)) {
    $savedData = $installationService->getStepData(1);
    if ($savedData !== null) {
        $formData = $savedData;
    }
}

// Set default values
$formData = array_merge([
    'host' => 'localhost',
    'port' => '3306',
    'database' => '',
    'username' => '',
    'password' => '',
    'unix_socket' => ''
], $formData);

?>

<div class="wizard-step">
    <h2>Step 1: Database Configuration</h2>
    <p class="step-description">
        Enter your MySQL/MariaDB database credentials. The system will test the connection before proceeding.
    </p>
    
    <?php 
    // Display environment information
    $envInfo = $installationService->getEnvironmentInfo();
    ?>
    
    <div class="info-box environment-info">
        <div class="info-header">
            <strong>Environment Detected:</strong> 
            <?php echo $envInfo['is_localhost'] ? 'Local Development' : 'Production Server'; ?>
        </div>
        <div class="info-details">
            <small>
                Server: <?php echo htmlspecialchars($envInfo['server_software']); ?> | 
                PHP: <?php echo htmlspecialchars($envInfo['php_version']); ?> | 
                OS: <?php echo htmlspecialchars($envInfo['php_os']); ?>
            </small>
        </div>
    </div>
    
    <?php if (!empty($envInfo['warnings'])): ?>
        <?php foreach ($envInfo['warnings'] as $warning): ?>
            <?php if ($warning['severity'] === 'high'): ?>
                <?php echo displayError($warning['message'], 'Security Warning'); ?>
            <?php elseif ($warning['severity'] === 'medium'): ?>
                <?php echo displayWarning($warning['message'], 'Warning'); ?>
            <?php else: ?>
                <?php echo displayInfo($warning['message']); ?>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (!empty($errors['general'])): ?>
        <?php echo displayError(
            $errors['general'], 
            'Database Connection Failed',
            'Please verify your database credentials and ensure the database server is running.'
        ); ?>
    <?php endif; ?>
    
    <form method="post" action="" class="wizard-form" id="database-form">
        <?php echo $csrfService->getTokenField(); ?>
        <input type="hidden" name="step" value="database">
        
        <div class="form-section">
            <h3>Connection Method</h3>
            <p class="form-help">Choose between standard TCP/IP connection or Unix socket.</p>
            
            <div class="form-group">
                <label class="form-label" for="connection_type">
                    Connection Type
                </label>
                <select name="connection_type" id="connection_type" class="form-select">
                    <option value="tcp" <?php echo empty($formData['unix_socket']) ? 'selected' : ''; ?>>
                        TCP/IP (Host + Port)
                    </option>
                    <option value="socket" <?php echo !empty($formData['unix_socket']) ? 'selected' : ''; ?>>
                        Unix Socket
                    </option>
                </select>
            </div>
        </div>
        
        <div class="form-section" id="tcp-fields">
            <h3>TCP/IP Connection</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="host">
                        Database Host <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="host" 
                        id="host" 
                        class="form-input"
                        value="<?php echo htmlspecialchars($formData['host']); ?>"
                        placeholder="localhost"
                        required
                    >
                    <?php if (isset($errors['host'])): ?>
                        <span class="form-error visible"><?php echo $errors['host']; ?></span>
                    <?php else: ?>
                        <span class="form-error"></span>
                    <?php endif; ?>
                    <span class="form-help">Usually 'localhost' or '127.0.0.1'</span>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="port">
                        Port
                    </label>
                    <input 
                        type="number" 
                        name="port" 
                        id="port" 
                        class="form-input"
                        value="<?php echo htmlspecialchars($formData['port']); ?>"
                        placeholder="3306"
                        min="1"
                        max="65535"
                    >
                    <?php if (isset($errors['port'])): ?>
                        <span class="form-error visible"><?php echo $errors['port']; ?></span>
                    <?php else: ?>
                        <span class="form-error"></span>
                    <?php endif; ?>
                    <span class="form-help">Default: 3306</span>
                </div>
            </div>
        </div>
        
        <div class="form-section" id="socket-fields" style="display: none;">
            <h3>Unix Socket Connection</h3>
            
            <div class="form-group">
                <label class="form-label" for="unix_socket">
                    Unix Socket Path
                </label>
                <input 
                    type="text" 
                    name="unix_socket" 
                    id="unix_socket" 
                    class="form-input"
                    value="<?php echo htmlspecialchars($formData['unix_socket']); ?>"
                    placeholder="/var/run/mysqld/mysqld.sock"
                >
                <span class="form-error"></span>
                <span class="form-help">Common paths: /var/run/mysqld/mysqld.sock, /tmp/mysql.sock</span>
            </div>
        </div>
        
        <div class="form-section">
            <h3>Database Credentials</h3>
            
            <div class="form-group">
                <label class="form-label" for="database">
                    Database Name <span class="required">*</span>
                </label>
                <input 
                    type="text" 
                    name="database" 
                    id="database" 
                    class="form-input"
                    value="<?php echo htmlspecialchars($formData['database']); ?>"
                    placeholder="karyalay_portal"
                    required
                >
                <?php if (isset($errors['database'])): ?>
                    <span class="form-error visible"><?php echo $errors['database']; ?></span>
                <?php else: ?>
                    <span class="form-error"></span>
                <?php endif; ?>
                <span class="form-help">The database must already exist</span>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="username">
                    Database Username <span class="required">*</span>
                </label>
                <input 
                    type="text" 
                    name="username" 
                    id="username" 
                    class="form-input"
                    value="<?php echo htmlspecialchars($formData['username']); ?>"
                    placeholder="root"
                    required
                    autocomplete="off"
                >
                <?php if (isset($errors['username'])): ?>
                    <span class="form-error visible"><?php echo $errors['username']; ?></span>
                <?php else: ?>
                    <span class="form-error"></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">
                    Database Password
                </label>
                <input 
                    type="password" 
                    name="password" 
                    id="password" 
                    class="form-input"
                    value="<?php echo htmlspecialchars($formData['password']); ?>"
                    placeholder="Enter password"
                    autocomplete="off"
                >
                <span class="form-error"></span>
                <span class="form-help">Leave blank if no password is set</span>
            </div>
        </div>
        
        <div class="form-actions">
            <button 
                type="button" 
                class="btn btn-secondary" 
                id="test-connection-btn"
                data-test-action="database"
            >
                Test Connection
            </button>
            
            <button type="submit" class="btn btn-primary">
                Save & Continue →
            </button>
        </div>
    </form>
</div>

<script>
// Toggle between TCP/IP and Unix socket fields
document.addEventListener('DOMContentLoaded', function() {
    const connectionType = document.getElementById('connection_type');
    const tcpFields = document.getElementById('tcp-fields');
    const socketFields = document.getElementById('socket-fields');
    const hostInput = document.getElementById('host');
    const unixSocketInput = document.getElementById('unix_socket');
    
    function toggleConnectionFields() {
        if (connectionType.value === 'socket') {
            tcpFields.style.display = 'none';
            socketFields.style.display = 'block';
            hostInput.removeAttribute('required');
            unixSocketInput.setAttribute('required', 'required');
        } else {
            tcpFields.style.display = 'block';
            socketFields.style.display = 'none';
            hostInput.setAttribute('required', 'required');
            unixSocketInput.removeAttribute('required');
        }
    }
    
    connectionType.addEventListener('change', toggleConnectionFields);
    toggleConnectionFields(); // Initialize on page load
});
</script>
