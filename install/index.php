<?php
/**
 * Installation Wizard - Main Controller
 * 
 * This is the main entry point for the installation wizard.
 * It manages wizard state, step navigation, and routes to appropriate step files.
 * 
 * State Persistence:
 * - Progress state (current step, completed steps) is stored in session
 * - Form data for each step is stored in session for recovery on back navigation or page reload
 * - All state is cleared upon installation completion
 * 
 * Requirements: 1.2, 7.3, 8.2, 8.4, 10.1, 10.2
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load required classes
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/includes/message_helpers.php';

use Karyalay\Services\InstallationService;
use Karyalay\Services\CsrfService;
use Karyalay\Services\UrlService;

// Initialize services
$installationService = new InstallationService();
$csrfService = new CsrfService();

// Check if already installed - redirect to main app if so
if ($installationService->isInstalled()) {
    // Use UrlService for proper URL resolution
    // Requirements: 7.3 - Use resolved base URL for redirects
    $urlService = new UrlService();
    $homepageUrl = $urlService->getHomepageUrl();
    
    header('Location: ' . $homepageUrl);
    exit;
}

// Get current wizard progress
$progress = $installationService->getProgress();

// Check for existing installation data (re-run scenario)
$existingData = $installationService->detectExistingData();
$isRerun = $existingData['has_data'];
$rerunMode = $installationService->getRerunMode();

// If this is a re-run and user hasn't chosen a mode yet, show warning
$showRerunWarning = $isRerun && $rerunMode === null && !in_array(0, $progress['completed_steps']);

// Define available steps
$availableSteps = [
    1 => 'database',
    2 => 'migrations',
    3 => 'admin',
    4 => 'smtp',
    5 => 'brand'
];

// Handle step navigation
$requestedStep = null;
$action = $_GET['action'] ?? null;

// Handle POST requests (form submissions)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token for all POST requests
    if (!$csrfService->validateRequest()) {
        $error = 'Invalid security token. Please try again.';
        $currentStep = $progress['current_step'];
    } else {
        // Handle step-specific form submissions
        // The actual form processing will be done in the step files
        // Here we just handle navigation actions
        
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
        }
    }
}

// If we need to show re-run warning, override current step
if ($showRerunWarning) {
    $currentStep = 0; // Special step for re-run warning
    $requestedStep = 0;
}

// Handle navigation actions
if ($action === 'complete') {
    // Installation complete - use the completeInstallation method
    $completionResult = $installationService->completeInstallation();
    
    if ($completionResult['success']) {
        // Set flag to show completion screen
        $isComplete = true;
        $currentStep = 6; // Beyond last step
    } else {
        // If completion failed, show error and stay on current step
        $error = 'Failed to complete installation: ' . $completionResult['error'];
        $currentStep = $progress['current_step'];
    }
} elseif ($action === 'next') {
    // Move to next step (validation will be done in step files)
    $requestedStep = $progress['current_step'] + 1;
} elseif ($action === 'previous') {
    // Move to previous step
    $requestedStep = max(1, $progress['current_step'] - 1);
} elseif ($action === 'jump' && isset($_GET['step'])) {
    // Jump to specific step
    $requestedStep = (int) $_GET['step'];
} else {
    // Default to current step from progress
    $requestedStep = $progress['current_step'];
}

// Validate requested step
if (!isset($availableSteps[$requestedStep])) {
    // Invalid step, default to step 1
    $requestedStep = 1;
}

// Step progression validation: can only access a step if previous step is completed
// Exception: can always go back to any completed step or current step
if ($requestedStep > $progress['current_step']) {
    // Trying to skip ahead - check if previous step is completed
    $previousStep = $requestedStep - 1;
    if (!in_array($previousStep, $progress['completed_steps'])) {
        // Previous step not completed, redirect to first incomplete step
        $requestedStep = $progress['current_step'];
        $error = 'Please complete the current step before proceeding.';
    }
}

// Update current step in progress
if ($requestedStep !== $progress['current_step']) {
    $progress['current_step'] = $requestedStep;
    $installationService->saveProgress($progress);
}

if (!isset($currentStep)) {
    $currentStep = $requestedStep;
}
$completedSteps = $progress['completed_steps'];

// Determine which step file to include (only if not complete)
if (!$isComplete) {
    // Check if we need to show re-run warning
    if ($currentStep === 0) {
        $stepFile = __DIR__ . '/steps/rerun-warning.php';
    } else {
        $stepFile = __DIR__ . '/steps/' . $availableSteps[$currentStep] . '.php';
    }

    // Check if step file exists
    if (!file_exists($stepFile)) {
        // Step file doesn't exist yet, show placeholder
        $stepContent = '<div class="wizard-step">
            <h2>Step ' . $currentStep . ': ' . ucfirst($availableSteps[$currentStep]) . '</h2>
            <div class="alert alert-info">
                <p>This step is under construction.</p>
            </div>
        </div>';
    } else {
        // Capture step file output
        ob_start();
        include $stepFile;
        $stepContent = ob_get_clean();
    }
}

// Check if this is the completion step (all steps completed or manually triggered)
if (!isset($isComplete)) {
    $isComplete = (count($completedSteps) === count($availableSteps));
}

?>
<?php include __DIR__ . '/templates/header.php'; ?>

<?php if (!$isComplete && $currentStep > 0): ?>
    <?php include __DIR__ . '/templates/progress.php'; ?>
<?php endif; ?>

<main class="wizard-content">
    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <p><?php echo htmlspecialchars($success); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($isComplete): ?>
        <!-- Completion screen -->
        <div class="wizard-complete">
            <div class="complete-icon">✓</div>
            <h2>Installation Complete!</h2>
            <p class="complete-message">
                Your portal system has been successfully installed and configured.
            </p>
            
            <?php if (isset($_SESSION['admin_email'])): ?>
                <div class="alert alert-info" style="text-align: left; max-width: 600px; margin: 0 auto 30px;">
                    <div class="alert-icon">ℹ</div>
                    <div class="alert-content">
                        <div class="alert-title">Admin Login Credentials</div>
                        <div class="alert-message">
                            <strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['admin_email']); ?><br>
                            <strong>Password:</strong> The password you created during setup
                        </div>
                        <div class="alert-help" style="margin-top: 8px; font-size: 13px;">
                            Please save these credentials in a secure location. You'll need them to access the admin panel.
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="complete-info">
                <h3>What's Next?</h3>
                <ul>
                    <li>Log in to the admin panel with the credentials you created</li>
                    <li>Configure additional settings in the admin dashboard</li>
                    <li>Customize your portal's appearance and branding</li>
                    <li>Add plans, modules, and features for your customers</li>
                    <li>Start managing your portal content and users</li>
                </ul>
            </div>
            
            <div class="complete-actions">
                <?php
                // Use UrlService for proper URL resolution
                // Requirements: 7.3 - Use resolved base URL for admin dashboard redirect
                $urlService = new UrlService();
                $adminUrl = $urlService->getAdminDashboardUrl();
                $homepageUrl = $urlService->getHomepageUrl();
                ?>
                <a href="<?php echo htmlspecialchars($adminUrl); ?>" class="btn btn-primary">Go to Admin Panel</a>
                <a href="<?php echo htmlspecialchars($homepageUrl); ?>" class="btn btn-secondary">Go to Website</a>
            </div>
        </div>
    <?php else: ?>
        <!-- Step content -->
        <?php echo $stepContent; ?>
        
        <!-- Navigation buttons -->
        <div class="wizard-navigation">
            <?php if ($currentStep > 1): ?>
                <form method="post" action="?action=previous" style="display: inline;">
                    <?php echo $csrfService->getTokenField(); ?>
                    <button type="submit" name="action" value="previous" class="btn btn-secondary">
                        ← Previous
                    </button>
                </form>
            <?php endif; ?>
            
            <div class="nav-spacer"></div>
            
            <?php if ($currentStep < count($availableSteps)): ?>
                <?php if (in_array($currentStep, $completedSteps)): ?>
                    <form method="post" action="?action=next" style="display: inline;">
                        <?php echo $csrfService->getTokenField(); ?>
                        <button type="submit" name="action" value="next" class="btn btn-primary">
                            Next →
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/templates/footer.php'; ?>
