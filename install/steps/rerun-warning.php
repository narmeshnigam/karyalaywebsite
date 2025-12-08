<?php
/**
 * Installation Wizard - Re-run Warning Step
 * 
 * This step is displayed when the wizard detects existing installation data.
 * It warns the user about overwriting existing configuration and offers options
 * to preserve or reset existing data.
 * 
 * Requirements: 9.1, 9.2, 9.3
 */

// Detect existing data
$existingData = $installationService->detectExistingData();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rerun_action'])) {
    $rerunAction = $_POST['rerun_action'];
    
    if ($rerunAction === 'preserve') {
        // Set preserve mode and continue
        $installationService->setRerunMode('preserve');
        
        // Mark this step as completed
        $progress['completed_steps'][] = 0; // Step 0 for warning
        $progress['completed_steps'] = array_unique($progress['completed_steps']);
        $progress['current_step'] = 1;
        $installationService->saveProgress($progress);
        
        // Redirect to first step
        header('Location: ?action=next');
        exit;
    } elseif ($rerunAction === 'reset') {
        // Reset existing data
        $resetResult = $installationService->resetExistingData();
        
        if ($resetResult['success']) {
            // Set reset mode and continue
            $installationService->setRerunMode('reset');
            
            // Mark this step as completed
            $progress['completed_steps'][] = 0; // Step 0 for warning
            $progress['completed_steps'] = array_unique($progress['completed_steps']);
            $progress['current_step'] = 1;
            $installationService->saveProgress($progress);
            
            // Redirect to first step
            header('Location: ?action=next');
            exit;
        } else {
            $error = 'Failed to reset existing data: ' . $resetResult['error'];
        }
    } elseif ($rerunAction === 'cancel') {
        // Redirect to main application
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        $basePath = str_replace('/install', '', $basePath);
        if ($basePath === '/' || $basePath === '\\') {
            $basePath = '';
        }
        header('Location: ' . $basePath . '/');
        exit;
    }
}

?>

<div class="wizard-step rerun-warning-step">
    <div class="warning-icon">⚠️</div>
    <h2>Re-running Installation Wizard</h2>
    
    <div class="alert alert-warning">
        <div class="alert-icon">⚠</div>
        <div class="alert-content">
            <div class="alert-title">Existing Installation Detected</div>
            <div class="alert-message">
                The system has detected that this installation may have been previously configured.
                Re-running the wizard will affect your existing configuration.
            </div>
        </div>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <div class="alert-icon">✕</div>
            <div class="alert-content">
                <div class="alert-message"><?php echo htmlspecialchars($error); ?></div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="existing-data-info">
        <h3>Detected Configuration:</h3>
        <ul>
            <?php if ($existingData['details']['has_database']): ?>
                <li>✓ Database connection is configured</li>
            <?php endif; ?>
            
            <?php if ($existingData['details']['has_users']): ?>
                <li>✓ <?php echo $existingData['details']['user_count']; ?> user(s) exist in the database</li>
            <?php endif; ?>
            
            <?php if ($existingData['details']['has_settings']): ?>
                <li>✓ <?php echo $existingData['details']['setting_count']; ?> setting(s) exist in the database</li>
            <?php endif; ?>
            
            <?php if (!$existingData['has_data']): ?>
                <li>No existing data detected</li>
            <?php endif; ?>
        </ul>
    </div>
    
    <div class="rerun-options">
        <h3>How would you like to proceed?</h3>
        
        <form method="post" class="rerun-form">
            <?php echo $csrfService->getTokenField(); ?>
            
            <div class="option-card">
                <input type="radio" id="preserve" name="rerun_action" value="preserve" checked>
                <label for="preserve">
                    <div class="option-title">
                        <strong>Preserve Existing Data</strong>
                        <span class="badge badge-recommended">Recommended</span>
                    </div>
                    <div class="option-description">
                        Keep existing database tables and data. The wizard will update configuration
                        settings but will not delete any existing users, settings, or content.
                        This is useful if you only need to reconfigure certain settings.
                    </div>
                    <div class="option-note">
                        <strong>Note:</strong> Some steps may be skipped if data already exists.
                    </div>
                </label>
            </div>
            
            <div class="option-card">
                <input type="radio" id="reset" name="rerun_action" value="reset">
                <label for="reset">
                    <div class="option-title">
                        <strong>Reset and Start Fresh</strong>
                        <span class="badge badge-danger">Destructive</span>
                    </div>
                    <div class="option-description">
                        Delete all existing database tables and data, then run the wizard as if
                        it were a fresh installation. This will permanently remove all users,
                        settings, content, and other data.
                    </div>
                    <div class="option-warning">
                        <strong>⚠️ Warning:</strong> This action cannot be undone. All existing data will be permanently lost.
                    </div>
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="rerun_action" value="cancel" class="btn btn-secondary">
                    Cancel and Exit
                </button>
                <button type="submit" class="btn btn-primary" id="continue-btn">
                    Continue with Selected Option
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.rerun-warning-step {
    max-width: 800px;
    margin: 0 auto;
    text-align: center;
}

.warning-icon {
    font-size: 64px;
    margin-bottom: 20px;
}

.rerun-warning-step h2 {
    margin-bottom: 30px;
}

.existing-data-info {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin: 30px 0;
    text-align: left;
}

.existing-data-info h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 18px;
}

.existing-data-info ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.existing-data-info li {
    padding: 8px 0;
    font-size: 15px;
}

.rerun-options {
    text-align: left;
    margin-top: 30px;
}

.rerun-options h3 {
    margin-bottom: 20px;
    font-size: 20px;
}

.rerun-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.option-card {
    border: 2px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    transition: all 0.2s ease;
    cursor: pointer;
}

.option-card:hover {
    border-color: #007bff;
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.1);
}

.option-card input[type="radio"] {
    display: none;
}

.option-card input[type="radio"]:checked + label {
    color: inherit;
}

.option-card input[type="radio"]:checked ~ label,
.option-card:has(input[type="radio"]:checked) {
    border-color: #007bff;
    background-color: #f0f7ff;
}

.option-card label {
    cursor: pointer;
    display: block;
    margin: 0;
}

.option-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    font-size: 16px;
}

.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-recommended {
    background-color: #28a745;
    color: white;
}

.badge-danger {
    background-color: #dc3545;
    color: white;
}

.option-description {
    color: #6c757d;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 10px;
}

.option-note {
    background-color: #e7f3ff;
    border-left: 3px solid #007bff;
    padding: 10px;
    margin-top: 10px;
    font-size: 13px;
}

.option-warning {
    background-color: #fff3cd;
    border-left: 3px solid #ffc107;
    padding: 10px;
    margin-top: 10px;
    font-size: 13px;
    color: #856404;
}

.form-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
}

@media (max-width: 768px) {
    .form-actions {
        flex-direction: column;
        gap: 10px;
    }
    
    .form-actions button {
        width: 100%;
    }
}
</style>

<script>
// Add confirmation for reset option
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.rerun-form');
    const resetRadio = document.getElementById('reset');
    const continueBtn = document.getElementById('continue-btn');
    
    form.addEventListener('submit', function(e) {
        if (resetRadio.checked && e.submitter === continueBtn) {
            if (!confirm('Are you absolutely sure you want to delete all existing data? This action cannot be undone.')) {
                e.preventDefault();
            }
        }
    });
});
</script>
