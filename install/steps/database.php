<?php
/**
 * Installation Wizard - Database Configuration Step
 * 
 * This step allows users to configure database credentials for both localhost
 * and live environments, with intelligent environment detection and automatic
 * credential switching.
 * 
 * Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 2.1, 3.1, 3.2, 3.3, 3.4, 6.1
 */

// This file is included by install/index.php
// Available variables: $installationService, $csrfService, $progress, $currentStep

use Karyalay\Services\EnvironmentConfigManager;

$errors = [];
$formData = [];
$testResult = null;

// Initialize session data structure for dual-environment
if (!isset($_SESSION['wizard_data'][1])) {
    $_SESSION['wizard_data'][1] = [
        'selected_environment' => 'local',
        'configure_both' => false,
        'local' => [
            'host' => 'localhost',
            'port' => '3306',
            'database' => '',
            'username' => '',
            'password' => '',
            'unix_socket' => '',
            'connection_type' => 'tcp',
            'tested' => false,
            'test_success' => false
        ],
        'live' => [
            'host' => '',
            'port' => '3306',
            'database' => '',
            'username' => '',
            'password' => '',
            'unix_socket' => '',
            'connection_type' => 'tcp',
            'tested' => false,
            'test_success' => false
        ]
    ];
}

$wizardData = &$_SESSION['wizard_data'][1];

// Handle AJAX environment switch (save current form data to session)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'switch_environment') {
    // Save current environment's data before switching
    $currentEnv = $_POST['current_environment'] ?? 'local';
    $newEnv = $_POST['new_environment'] ?? 'local';
    
    // Save current form data to session
    if (isset($_POST['credentials'])) {
        $wizardData[$currentEnv] = array_merge($wizardData[$currentEnv], $_POST['credentials']);
    }
    
    // Update selected environment
    $wizardData['selected_environment'] = $newEnv;
    
    // Return JSON response for AJAX
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'environment' => $newEnv]);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] === 'database') {
    $selectedEnv = $_POST['selected_environment'] ?? 'local';
    $configureBoth = isset($_POST['configure_both']) && $_POST['configure_both'] === '1';
    
    // Update wizard data
    $wizardData['selected_environment'] = $selectedEnv;
    $wizardData['configure_both'] = $configureBoth;
    
    // Collect form data for selected environment
    $envPrefix = $selectedEnv . '_';
    $formData = [
        'host' => trim($_POST[$envPrefix . 'host'] ?? ''),
        'port' => trim($_POST[$envPrefix . 'port'] ?? '3306'),
        'database' => trim($_POST[$envPrefix . 'database'] ?? ''),
        'username' => trim($_POST[$envPrefix . 'username'] ?? ''),
        'password' => $_POST[$envPrefix . 'password'] ?? '',
        'unix_socket' => trim($_POST[$envPrefix . 'unix_socket'] ?? ''),
        'connection_type' => $_POST[$envPrefix . 'connection_type'] ?? 'tcp'
    ];
    
    // Save to session
    $wizardData[$selectedEnv] = array_merge($wizardData[$selectedEnv], $formData);
    
    // Also collect secondary environment data if configuring both
    $secondaryFormData = null;
    if ($configureBoth) {
        $secondaryEnv = $selectedEnv === 'local' ? 'live' : 'local';
        $secondaryPrefix = $secondaryEnv . '_';
        $secondaryFormData = [
            'host' => trim($_POST[$secondaryPrefix . 'host'] ?? ''),
            'port' => trim($_POST[$secondaryPrefix . 'port'] ?? '3306'),
            'database' => trim($_POST[$secondaryPrefix . 'database'] ?? ''),
            'username' => trim($_POST[$secondaryPrefix . 'username'] ?? ''),
            'password' => $_POST[$secondaryPrefix . 'password'] ?? '',
            'unix_socket' => trim($_POST[$secondaryPrefix . 'unix_socket'] ?? ''),
            'connection_type' => $_POST[$secondaryPrefix . 'connection_type'] ?? 'tcp'
        ];
        $wizardData[$secondaryEnv] = array_merge($wizardData[$secondaryEnv], $secondaryFormData);
    }
    
    // Server-side validation for primary environment
    if (empty($formData['database'])) {
        $errors[$envPrefix . 'database'] = 'Database name is required.';
    }
    
    if (empty($formData['username'])) {
        $errors[$envPrefix . 'username'] = 'Database username is required.';
    }
    
    // Validate host or unix_socket
    if ($formData['connection_type'] === 'tcp') {
        if (empty($formData['host'])) {
            $errors[$envPrefix . 'host'] = 'Database host is required when using TCP/IP connection.';
        }
    } else {
        if (empty($formData['unix_socket'])) {
            $errors[$envPrefix . 'unix_socket'] = 'Unix socket path is required when using socket connection.';
        }
    }
    
    // Validate port if provided
    if (!empty($formData['port'])) {
        $port = filter_var($formData['port'], FILTER_VALIDATE_INT);
        if ($port === false || $port < 1 || $port > 65535) {
            $errors[$envPrefix . 'port'] = 'Port must be a number between 1 and 65535.';
        }
    }

    // Validate secondary environment if configuring both
    if ($configureBoth && $secondaryFormData !== null) {
        $secondaryEnv = $selectedEnv === 'local' ? 'live' : 'local';
        $secondaryPrefix = $secondaryEnv . '_';
        
        // Only validate if any field is filled
        $hasSecondaryData = !empty($secondaryFormData['host']) || !empty($secondaryFormData['database']) || 
                           !empty($secondaryFormData['username']) || !empty($secondaryFormData['unix_socket']);
        
        if ($hasSecondaryData) {
            if (empty($secondaryFormData['database'])) {
                $errors[$secondaryPrefix . 'database'] = 'Database name is required.';
            }
            if (empty($secondaryFormData['username'])) {
                $errors[$secondaryPrefix . 'username'] = 'Database username is required.';
            }
            if ($secondaryFormData['connection_type'] === 'tcp' && empty($secondaryFormData['host'])) {
                $errors[$secondaryPrefix . 'host'] = 'Database host is required.';
            }
        }
    }
    
    // If no validation errors, test connection and save
    if (empty($errors)) {
        // Test primary environment connection
        $testResult = $installationService->testDatabaseConnection($formData);
        
        if ($testResult['success']) {
            $wizardData[$selectedEnv]['tested'] = true;
            $wizardData[$selectedEnv]['test_success'] = true;
            
            // Test secondary environment if configuring both and has data
            $secondaryTestResult = ['success' => true];
            if ($configureBoth && $secondaryFormData !== null) {
                $hasSecondaryData = !empty($secondaryFormData['host']) || !empty($secondaryFormData['database']) || 
                                   !empty($secondaryFormData['username']) || !empty($secondaryFormData['unix_socket']);
                
                if ($hasSecondaryData) {
                    $secondaryTestResult = $installationService->testDatabaseConnection($secondaryFormData);
                    $secondaryEnv = $selectedEnv === 'local' ? 'live' : 'local';
                    $wizardData[$secondaryEnv]['tested'] = true;
                    $wizardData[$secondaryEnv]['test_success'] = $secondaryTestResult['success'];
                    
                    if (!$secondaryTestResult['success']) {
                        $errors['secondary'] = 'Secondary environment connection failed: ' . htmlspecialchars($secondaryTestResult['error']);
                    }
                }
            }
            
            if ($secondaryTestResult['success']) {
                // Write dual-environment configuration using EnvironmentConfigManager
                $configManager = new EnvironmentConfigManager();
                
                $localCredentials = $selectedEnv === 'local' ? $formData : ($configureBoth ? $secondaryFormData : null);
                $liveCredentials = $selectedEnv === 'live' ? $formData : ($configureBoth ? $secondaryFormData : null);
                
                // If only configuring one environment, preserve existing credentials for the other
                if (!$configureBoth) {
                    if ($selectedEnv === 'local') {
                        $liveCredentials = $configManager->readEnvironmentCredentials('live');
                    } else {
                        $localCredentials = $configManager->readEnvironmentCredentials('local');
                    }
                }
                
                $writeSuccess = $configManager->writeDualConfig($localCredentials, $liveCredentials);
                
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
            }
        } else {
            $wizardData[$selectedEnv]['tested'] = true;
            $wizardData[$selectedEnv]['test_success'] = false;
            $errors['general'] = 'Database connection failed: ' . htmlspecialchars($testResult['error']);
        }
    }
}

// Load saved data from session
$localData = $wizardData['local'];
$liveData = $wizardData['live'];
$selectedEnvironment = $wizardData['selected_environment'];
$configureBoth = $wizardData['configure_both'];

// Get environment info
$envInfo = $installationService->getEnvironmentInfo();
?>

<div class="wizard-step">
    <h2>Step 1: Database Configuration</h2>
    <p class="step-description">
        Configure your database credentials. You can set up credentials for both localhost (development) 
        and live (production) environments to enable seamless deployment.
    </p>
    
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
    
    <?php if (!empty($errors['secondary'])): ?>
        <?php echo displayWarning(
            $errors['secondary'], 
            'Secondary Environment Connection Failed',
            'The primary environment was configured successfully, but the secondary environment failed.'
        ); ?>
    <?php endif; ?>
    
    <form method="post" action="" class="wizard-form" id="database-form">
        <?php echo $csrfService->getTokenField(); ?>
        <input type="hidden" name="step" value="database">
        <input type="hidden" name="selected_environment" id="selected_environment" value="<?php echo htmlspecialchars($selectedEnvironment); ?>">
        
        <!-- Environment Selector -->
        <div class="form-section">
            <h3>Environment Selection</h3>
            <p class="form-help">Choose which environment you want to configure. You can configure both environments for seamless deployment.</p>
            
            <div class="environment-selector" role="radiogroup" aria-label="Select environment to configure">
                <label class="environment-option <?php echo $selectedEnvironment === 'local' ? 'selected' : ''; ?>" 
                       for="env_local"
                       title="Configure database credentials for local development (XAMPP, MAMP, WAMP, etc.)">
                    <input type="radio" 
                           name="environment_choice" 
                           id="env_local" 
                           value="local"
                           <?php echo $selectedEnvironment === 'local' ? 'checked' : ''; ?>
                           onchange="switchEnvironment('local')">
                    <div class="option-content">
                        <div class="option-icon">🖥️</div>
                        <div class="option-text">
                            <strong>Localhost (Development)</strong>
                            <span>Configure for local development environment</span>
                        </div>
                    </div>
                </label>
                
                <label class="environment-option <?php echo $selectedEnvironment === 'live' ? 'selected' : ''; ?>" 
                       for="env_live"
                       title="Configure database credentials for production hosting (Hostinger, cPanel, etc.)">
                    <input type="radio" 
                           name="environment_choice" 
                           id="env_live" 
                           value="live"
                           <?php echo $selectedEnvironment === 'live' ? 'checked' : ''; ?>
                           onchange="switchEnvironment('live')">
                    <div class="option-content">
                        <div class="option-icon">🌐</div>
                        <div class="option-text">
                            <strong>Live Server (Production)</strong>
                            <span>Configure for production hosting environment</span>
                        </div>
                    </div>
                </label>
            </div>
        </div>

        <!-- Localhost Credentials Form -->
        <div class="form-section credentials-section" id="local-credentials" style="<?php echo $selectedEnvironment === 'local' ? '' : 'display: none;'; ?>">
            <h3>Localhost Database Credentials</h3>
            <p class="form-help">Enter your local development database credentials (e.g., XAMPP, MAMP, WAMP).</p>
            
            <div class="form-group">
                <label class="form-label" for="local_connection_type">Connection Type</label>
                <select name="local_connection_type" id="local_connection_type" class="form-select" onchange="toggleConnectionFields('local')">
                    <option value="tcp" <?php echo ($localData['connection_type'] ?? 'tcp') === 'tcp' ? 'selected' : ''; ?>>
                        TCP/IP (Host + Port)
                    </option>
                    <option value="socket" <?php echo ($localData['connection_type'] ?? '') === 'socket' ? 'selected' : ''; ?>>
                        Unix Socket
                    </option>
                </select>
            </div>
            
            <div id="local-tcp-fields" style="<?php echo ($localData['connection_type'] ?? 'tcp') === 'socket' ? 'display: none;' : ''; ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="local_host">
                            Database Host <span class="required">*</span>
                        </label>
                        <input type="text" name="local_host" id="local_host" class="form-input"
                               value="<?php echo htmlspecialchars($localData['host']); ?>"
                               placeholder="localhost">
                        <?php if (isset($errors['local_host'])): ?>
                            <span class="form-error visible"><?php echo $errors['local_host']; ?></span>
                        <?php else: ?>
                            <span class="form-error"></span>
                        <?php endif; ?>
                        <span class="form-help">Usually 'localhost' or '127.0.0.1'</span>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="local_port">Port</label>
                        <input type="number" name="local_port" id="local_port" class="form-input"
                               value="<?php echo htmlspecialchars($localData['port']); ?>"
                               placeholder="3306" min="1" max="65535">
                        <?php if (isset($errors['local_port'])): ?>
                            <span class="form-error visible"><?php echo $errors['local_port']; ?></span>
                        <?php else: ?>
                            <span class="form-error"></span>
                        <?php endif; ?>
                        <span class="form-help">Default: 3306</span>
                    </div>
                </div>
            </div>
            
            <div id="local-socket-fields" style="<?php echo ($localData['connection_type'] ?? 'tcp') === 'socket' ? '' : 'display: none;'; ?>">
                <div class="form-group">
                    <label class="form-label" for="local_unix_socket">Unix Socket Path</label>
                    <input type="text" name="local_unix_socket" id="local_unix_socket" class="form-input"
                           value="<?php echo htmlspecialchars($localData['unix_socket']); ?>"
                           placeholder="/var/run/mysqld/mysqld.sock">
                    <?php if (isset($errors['local_unix_socket'])): ?>
                        <span class="form-error visible"><?php echo $errors['local_unix_socket']; ?></span>
                    <?php else: ?>
                        <span class="form-error"></span>
                    <?php endif; ?>
                    <span class="form-help">Common: /var/run/mysqld/mysqld.sock, /tmp/mysql.sock, /Applications/XAMPP/xamppfiles/var/mysql/mysql.sock</span>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="local_database">
                    Database Name <span class="required">*</span>
                </label>
                <input type="text" name="local_database" id="local_database" class="form-input"
                       value="<?php echo htmlspecialchars($localData['database']); ?>"
                       placeholder="karyalay_portal">
                <?php if (isset($errors['local_database'])): ?>
                    <span class="form-error visible"><?php echo $errors['local_database']; ?></span>
                <?php else: ?>
                    <span class="form-error"></span>
                <?php endif; ?>
                <span class="form-help">The database must already exist</span>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="local_username">
                        Database Username <span class="required">*</span>
                    </label>
                    <input type="text" name="local_username" id="local_username" class="form-input"
                           value="<?php echo htmlspecialchars($localData['username']); ?>"
                           placeholder="root" autocomplete="off">
                    <?php if (isset($errors['local_username'])): ?>
                        <span class="form-error visible"><?php echo $errors['local_username']; ?></span>
                    <?php else: ?>
                        <span class="form-error"></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="local_password">Database Password</label>
                    <input type="password" name="local_password" id="local_password" class="form-input"
                           value="<?php echo htmlspecialchars($localData['password']); ?>"
                           placeholder="Enter password" autocomplete="off">
                    <span class="form-error"></span>
                    <span class="form-help">Leave blank if no password is set</span>
                </div>
            </div>
            
            <div class="test-connection-row">
                <button type="button" class="btn btn-secondary" onclick="testConnection('local')" id="test-local-btn">
                    Test Connection
                </button>
                <span class="test-status" id="local-test-status">
                    <?php if ($localData['tested']): ?>
                        <?php if ($localData['test_success']): ?>
                            <span class="status-success">✓ Connected</span>
                        <?php else: ?>
                            <span class="status-error">✕ Failed</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        
        <!-- Live Credentials Form -->
        <div class="form-section credentials-section" id="live-credentials" style="<?php echo $selectedEnvironment === 'live' ? '' : 'display: none;'; ?>">
            <h3>Live Server Database Credentials</h3>
            <p class="form-help">Enter your production database credentials (e.g., Hostinger, cPanel hosting).</p>
            
            <div class="form-group">
                <label class="form-label" for="live_connection_type">Connection Type</label>
                <select name="live_connection_type" id="live_connection_type" class="form-select" onchange="toggleConnectionFields('live')">
                    <option value="tcp" <?php echo ($liveData['connection_type'] ?? 'tcp') === 'tcp' ? 'selected' : ''; ?>>
                        TCP/IP (Host + Port)
                    </option>
                    <option value="socket" <?php echo ($liveData['connection_type'] ?? '') === 'socket' ? 'selected' : ''; ?>>
                        Unix Socket
                    </option>
                </select>
            </div>
            
            <div id="live-tcp-fields" style="<?php echo ($liveData['connection_type'] ?? 'tcp') === 'socket' ? 'display: none;' : ''; ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="live_host">
                            Database Host <span class="required">*</span>
                        </label>
                        <input type="text" name="live_host" id="live_host" class="form-input"
                               value="<?php echo htmlspecialchars($liveData['host']); ?>"
                               placeholder="mysql.hostinger.com">
                        <?php if (isset($errors['live_host'])): ?>
                            <span class="form-error visible"><?php echo $errors['live_host']; ?></span>
                        <?php else: ?>
                            <span class="form-error"></span>
                        <?php endif; ?>
                        <span class="form-help">Your hosting provider's MySQL host</span>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="live_port">Port</label>
                        <input type="number" name="live_port" id="live_port" class="form-input"
                               value="<?php echo htmlspecialchars($liveData['port']); ?>"
                               placeholder="3306" min="1" max="65535">
                        <?php if (isset($errors['live_port'])): ?>
                            <span class="form-error visible"><?php echo $errors['live_port']; ?></span>
                        <?php else: ?>
                            <span class="form-error"></span>
                        <?php endif; ?>
                        <span class="form-help">Default: 3306</span>
                    </div>
                </div>
            </div>
            
            <div id="live-socket-fields" style="<?php echo ($liveData['connection_type'] ?? 'tcp') === 'socket' ? '' : 'display: none;'; ?>">
                <div class="form-group">
                    <label class="form-label" for="live_unix_socket">Unix Socket Path</label>
                    <input type="text" name="live_unix_socket" id="live_unix_socket" class="form-input"
                           value="<?php echo htmlspecialchars($liveData['unix_socket']); ?>"
                           placeholder="/var/run/mysqld/mysqld.sock">
                    <?php if (isset($errors['live_unix_socket'])): ?>
                        <span class="form-error visible"><?php echo $errors['live_unix_socket']; ?></span>
                    <?php else: ?>
                        <span class="form-error"></span>
                    <?php endif; ?>
                    <span class="form-help">Usually not needed for shared hosting</span>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="live_database">
                    Database Name <span class="required">*</span>
                </label>
                <input type="text" name="live_database" id="live_database" class="form-input"
                       value="<?php echo htmlspecialchars($liveData['database']); ?>"
                       placeholder="u123456789_portal">
                <?php if (isset($errors['live_database'])): ?>
                    <span class="form-error visible"><?php echo $errors['live_database']; ?></span>
                <?php else: ?>
                    <span class="form-error"></span>
                <?php endif; ?>
                <span class="form-help">The database must already exist on your hosting</span>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="live_username">
                        Database Username <span class="required">*</span>
                    </label>
                    <input type="text" name="live_username" id="live_username" class="form-input"
                           value="<?php echo htmlspecialchars($liveData['username']); ?>"
                           placeholder="u123456789_user" autocomplete="off">
                    <?php if (isset($errors['live_username'])): ?>
                        <span class="form-error visible"><?php echo $errors['live_username']; ?></span>
                    <?php else: ?>
                        <span class="form-error"></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="live_password">
                        Database Password <span class="required">*</span>
                    </label>
                    <input type="password" name="live_password" id="live_password" class="form-input"
                           value="<?php echo htmlspecialchars($liveData['password']); ?>"
                           placeholder="Enter password" autocomplete="off">
                    <?php if (isset($errors['live_password'])): ?>
                        <span class="form-error visible"><?php echo $errors['live_password']; ?></span>
                    <?php else: ?>
                        <span class="form-error"></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="test-connection-row">
                <button type="button" class="btn btn-secondary" onclick="testConnection('live')" id="test-live-btn">
                    Test Connection
                </button>
                <span class="test-status" id="live-test-status">
                    <?php if ($liveData['tested']): ?>
                        <?php if ($liveData['test_success']): ?>
                            <span class="status-success">✓ Connected</span>
                        <?php else: ?>
                            <span class="status-error">✕ Failed</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </span>
            </div>
        </div>

        <!-- Configure Both Checkbox -->
        <div class="form-section">
            <div class="configure-both-option">
                <label class="checkbox-label" for="configure_both">
                    <input type="checkbox" name="configure_both" id="configure_both" value="1"
                           <?php echo $configureBoth ? 'checked' : ''; ?>
                           onchange="toggleSecondaryForm()">
                    <span class="checkbox-text">
                        <strong>Also configure <?php echo $selectedEnvironment === 'local' ? 'Live' : 'Localhost'; ?> credentials</strong>
                        <small>Enable dual-environment setup for seamless deployment</small>
                    </span>
                </label>
            </div>
            
            <!-- Secondary Environment Form (shown when checkbox is checked) -->
            <div id="secondary-credentials" style="<?php echo $configureBoth ? '' : 'display: none;'; ?>" class="secondary-form">
                <div class="secondary-form-header">
                    <h4 id="secondary-title"><?php echo $selectedEnvironment === 'local' ? 'Live Server' : 'Localhost'; ?> Credentials (Optional)</h4>
                    <p class="form-help">Configure credentials for the other environment to enable automatic switching.</p>
                </div>
                
                <!-- This will be populated dynamically based on selected environment -->
                <div id="secondary-local-form" style="<?php echo $selectedEnvironment === 'live' ? '' : 'display: none;'; ?>">
                    <!-- Localhost form fields for when Live is primary -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="secondary_local_host">Database Host</label>
                            <input type="text" name="local_host" id="secondary_local_host" class="form-input"
                                   value="<?php echo htmlspecialchars($localData['host']); ?>"
                                   placeholder="localhost">
                            <span class="form-help">Usually 'localhost'</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="secondary_local_port">Port</label>
                            <input type="number" name="local_port" id="secondary_local_port" class="form-input"
                                   value="<?php echo htmlspecialchars($localData['port']); ?>"
                                   placeholder="3306" min="1" max="65535">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="secondary_local_database">Database Name</label>
                        <input type="text" name="local_database" id="secondary_local_database" class="form-input"
                               value="<?php echo htmlspecialchars($localData['database']); ?>"
                               placeholder="karyalay_portal">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="secondary_local_username">Username</label>
                            <input type="text" name="local_username" id="secondary_local_username" class="form-input"
                                   value="<?php echo htmlspecialchars($localData['username']); ?>"
                                   placeholder="root" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="secondary_local_password">Password</label>
                            <input type="password" name="local_password" id="secondary_local_password" class="form-input"
                                   value="<?php echo htmlspecialchars($localData['password']); ?>"
                                   placeholder="Enter password" autocomplete="off">
                        </div>
                    </div>
                    <input type="hidden" name="local_connection_type" value="tcp">
                    <input type="hidden" name="local_unix_socket" value="">
                </div>
                
                <div id="secondary-live-form" style="<?php echo $selectedEnvironment === 'local' ? '' : 'display: none;'; ?>">
                    <!-- Live form fields for when Localhost is primary -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="secondary_live_host">Database Host</label>
                            <input type="text" name="live_host" id="secondary_live_host" class="form-input"
                                   value="<?php echo htmlspecialchars($liveData['host']); ?>"
                                   placeholder="mysql.hostinger.com">
                            <span class="form-help">Your hosting provider's MySQL host</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="secondary_live_port">Port</label>
                            <input type="number" name="live_port" id="secondary_live_port" class="form-input"
                                   value="<?php echo htmlspecialchars($liveData['port']); ?>"
                                   placeholder="3306" min="1" max="65535">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="secondary_live_database">Database Name</label>
                        <input type="text" name="live_database" id="secondary_live_database" class="form-input"
                               value="<?php echo htmlspecialchars($liveData['database']); ?>"
                               placeholder="u123456789_portal">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="secondary_live_username">Username</label>
                            <input type="text" name="live_username" id="secondary_live_username" class="form-input"
                                   value="<?php echo htmlspecialchars($liveData['username']); ?>"
                                   placeholder="u123456789_user" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="secondary_live_password">Password</label>
                            <input type="password" name="live_password" id="secondary_live_password" class="form-input"
                                   value="<?php echo htmlspecialchars($liveData['password']); ?>"
                                   placeholder="Enter password" autocomplete="off">
                        </div>
                    </div>
                    <input type="hidden" name="live_connection_type" value="tcp">
                    <input type="hidden" name="live_unix_socket" value="">
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                Save & Continue →
            </button>
        </div>
    </form>
</div>

<script>
/**
 * Switch between localhost and live environment forms
 */
function switchEnvironment(env) {
    const localCredentials = document.getElementById('local-credentials');
    const liveCredentials = document.getElementById('live-credentials');
    const selectedEnvInput = document.getElementById('selected_environment');
    const configureBothCheckbox = document.getElementById('configure_both');
    const secondaryTitle = document.getElementById('secondary-title');
    const secondaryLocalForm = document.getElementById('secondary-local-form');
    const secondaryLiveForm = document.getElementById('secondary-live-form');
    
    // Update hidden input
    selectedEnvInput.value = env;
    
    // Update radio button visual state
    document.querySelectorAll('.environment-option').forEach(opt => {
        opt.classList.remove('selected');
    });
    document.querySelector(`label[for="env_${env}"]`).classList.add('selected');
    
    // Show/hide credential forms
    if (env === 'local') {
        localCredentials.style.display = '';
        liveCredentials.style.display = 'none';
        secondaryTitle.textContent = 'Live Server Credentials (Optional)';
        secondaryLocalForm.style.display = 'none';
        secondaryLiveForm.style.display = '';
    } else {
        localCredentials.style.display = 'none';
        liveCredentials.style.display = '';
        secondaryTitle.textContent = 'Localhost Credentials (Optional)';
        secondaryLocalForm.style.display = '';
        secondaryLiveForm.style.display = 'none';
    }
    
    // Update checkbox label
    const checkboxText = configureBothCheckbox.parentElement.querySelector('.checkbox-text strong');
    checkboxText.textContent = `Also configure ${env === 'local' ? 'Live' : 'Localhost'} credentials`;
}

/**
 * Toggle connection type fields (TCP/IP vs Unix Socket)
 */
function toggleConnectionFields(env) {
    const connectionType = document.getElementById(`${env}_connection_type`).value;
    const tcpFields = document.getElementById(`${env}-tcp-fields`);
    const socketFields = document.getElementById(`${env}-socket-fields`);
    
    if (connectionType === 'socket') {
        tcpFields.style.display = 'none';
        socketFields.style.display = '';
    } else {
        tcpFields.style.display = '';
        socketFields.style.display = 'none';
    }
}

/**
 * Toggle secondary environment form visibility
 */
function toggleSecondaryForm() {
    const checkbox = document.getElementById('configure_both');
    const secondaryCredentials = document.getElementById('secondary-credentials');
    
    if (checkbox.checked) {
        secondaryCredentials.style.display = '';
    } else {
        secondaryCredentials.style.display = 'none';
    }
}

/**
 * Test database connection for specified environment
 * Displays detailed error messages and troubleshooting suggestions
 * Requirements: 6.3, 6.4, 6.5
 */
async function testConnection(env) {
    const btn = document.getElementById(`test-${env}-btn`);
    const statusSpan = document.getElementById(`${env}-test-status`);
    
    // Remove any existing error details
    const existingDetails = document.querySelector(`#${env}-credentials .connection-error-details`);
    if (existingDetails) {
        existingDetails.remove();
    }
    
    // Collect credentials
    const prefix = env + '_';
    const connectionType = document.getElementById(`${env}_connection_type`)?.value || 'tcp';
    
    const credentials = {
        host: document.getElementById(`${env}_host`)?.value || '',
        port: document.getElementById(`${env}_port`)?.value || '3306',
        database: document.getElementById(`${env}_database`)?.value || '',
        username: document.getElementById(`${env}_username`)?.value || '',
        password: document.getElementById(`${env}_password`)?.value || '',
        unix_socket: connectionType === 'socket' ? (document.getElementById(`${env}_unix_socket`)?.value || '') : ''
    };
    
    // Validate required fields
    if (!credentials.database || !credentials.username) {
        statusSpan.innerHTML = '<span class="status-error">✕ Missing required fields</span>';
        highlightField(env, 'database');
        highlightField(env, 'username');
        return;
    }
    
    if (connectionType === 'tcp' && !credentials.host) {
        statusSpan.innerHTML = '<span class="status-error">✕ Host is required</span>';
        highlightField(env, 'host');
        return;
    }
    
    // Clear any field highlights
    clearFieldHighlights(env);
    
    // Show loading state
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Testing...';
    statusSpan.innerHTML = '';
    
    try {
        // Get CSRF token
        const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
        
        const response = await fetch('/install/api/test-database.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify(credentials)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Show success with server info
            const serverInfo = result.server_info || {};
            statusSpan.innerHTML = '<span class="status-success">✓ Connected successfully</span>';
            
            // Display server info
            displayConnectionSuccess(env, serverInfo);
        } else {
            // Show error with details
            statusSpan.innerHTML = `<span class="status-error">✕ Connection failed</span>`;
            
            // Display detailed error information
            displayConnectionError(env, result);
            
            // Highlight the relevant field
            if (result.field) {
                highlightField(env, result.field);
            }
        }
    } catch (error) {
        statusSpan.innerHTML = `<span class="status-error">✕ Error: ${error.message}</span>`;
    } finally {
        btn.disabled = false;
        btn.textContent = originalText;
    }
}

/**
 * Display connection success message with server details
 * Requirements: 6.4
 */
function displayConnectionSuccess(env, serverInfo) {
    const container = document.getElementById(`${env}-credentials`);
    const testRow = container.querySelector('.test-connection-row');
    
    // Remove any existing details
    const existingDetails = container.querySelector('.connection-error-details');
    if (existingDetails) {
        existingDetails.remove();
    }
    
    // Create success details element
    const successDiv = document.createElement('div');
    successDiv.className = 'connection-success-details';
    successDiv.innerHTML = `
        <div class="success-box">
            <div class="success-header">
                <span class="success-icon">✓</span>
                <strong>Connection Successful</strong>
            </div>
            <div class="success-info">
                <p>${serverInfo.message || 'Successfully connected to the database server.'}</p>
                <ul class="server-details">
                    <li><strong>Server Version:</strong> MySQL ${serverInfo.version || 'Unknown'}</li>
                    <li><strong>Host:</strong> ${serverInfo.host || 'Unknown'}</li>
                    <li><strong>Database:</strong> ${serverInfo.database || 'Unknown'}</li>
                </ul>
            </div>
        </div>
    `;
    
    testRow.insertAdjacentElement('afterend', successDiv);
    
    // Auto-remove after 10 seconds
    setTimeout(() => {
        if (successDiv.parentNode) {
            successDiv.remove();
        }
    }, 10000);
}

/**
 * Display detailed connection error with troubleshooting suggestions
 * Requirements: 6.3, 6.5
 */
function displayConnectionError(env, errorResult) {
    const container = document.getElementById(`${env}-credentials`);
    const testRow = container.querySelector('.test-connection-row');
    
    // Remove any existing details
    const existingDetails = container.querySelector('.connection-error-details');
    if (existingDetails) {
        existingDetails.remove();
    }
    const existingSuccess = container.querySelector('.connection-success-details');
    if (existingSuccess) {
        existingSuccess.remove();
    }
    
    // Build troubleshooting list
    let troubleshootingHtml = '';
    if (errorResult.troubleshooting && errorResult.troubleshooting.length > 0) {
        troubleshootingHtml = `
            <div class="troubleshooting-section">
                <strong>Troubleshooting suggestions:</strong>
                <ul>
                    ${errorResult.troubleshooting.map(tip => `<li>${escapeHtml(tip)}</li>`).join('')}
                </ul>
            </div>
        `;
    }
    
    // Create error details element
    const errorDiv = document.createElement('div');
    errorDiv.className = 'connection-error-details';
    errorDiv.innerHTML = `
        <div class="error-box">
            <div class="error-header">
                <span class="error-icon">✕</span>
                <strong>Connection Failed</strong>
            </div>
            <div class="error-message">
                <p>${escapeHtml(errorResult.message || 'Unknown error occurred')}</p>
            </div>
            ${errorResult.error_details ? `
                <div class="error-technical">
                    <details>
                        <summary>Technical Details</summary>
                        <code>${escapeHtml(errorResult.error_details)}</code>
                    </details>
                </div>
            ` : ''}
            ${troubleshootingHtml}
        </div>
    `;
    
    testRow.insertAdjacentElement('afterend', errorDiv);
}

/**
 * Highlight a form field to indicate an error
 */
function highlightField(env, fieldName) {
    const field = document.getElementById(`${env}_${fieldName}`);
    if (field) {
        field.classList.add('field-error');
        field.focus();
    }
}

/**
 * Clear all field highlights for an environment
 */
function clearFieldHighlights(env) {
    const container = document.getElementById(`${env}-credentials`);
    if (container) {
        container.querySelectorAll('.field-error').forEach(field => {
            field.classList.remove('field-error');
        });
    }
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set initial state based on selected environment
    const selectedEnv = document.getElementById('selected_environment').value;
    switchEnvironment(selectedEnv);
});
</script>
