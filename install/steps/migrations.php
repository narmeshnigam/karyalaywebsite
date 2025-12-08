<?php
/**
 * Installation Wizard - Database Migration Step
 * 
 * This step executes all database migrations to set up the schema.
 * 
 * Requirements: 2.5, 8.1, 8.3
 */

// This file is included by install/index.php
// Available variables: $installationService, $csrfService, $progress, $currentStep

$errors = [];
$migrationResults = null;
$showResults = false;

// Check if database is configured
if (!$progress['database_configured']) {
    // Redirect back to database step
    header('Location: ?action=jump&step=1');
    exit;
}

// Handle form submission (run migrations)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] === 'migrations') {
    $action = $_POST['migration_action'] ?? '';
    
    if ($action === 'run') {
        // Run migrations
        $result = $installationService->runMigrations();
        $migrationResults = $result['results'];
        $showResults = true;
        
        if ($result['success']) {
            // Mark step as completed
            $progress['migrations_run'] = true;
            if (!in_array(2, $progress['completed_steps'])) {
                $progress['completed_steps'][] = 2;
            }
            $progress['current_step'] = 3; // Move to admin step
            $installationService->saveProgress($progress);
            
            // Show success message but don't auto-redirect
            // Let user see the results and click Next
        } else {
            $errors['general'] = $result['error'] ?? 'Migration execution failed.';
        }
    }
}

// Get list of migration files to display
$migrationsPath = __DIR__ . '/../../database/migrations';
$migrationFiles = [];
if (is_dir($migrationsPath)) {
    $files = glob($migrationsPath . '/*.sql');
    sort($files);
    foreach ($files as $file) {
        $migrationFiles[] = basename($file);
    }
}

?>

<div class="wizard-step">
    <h2>Step 2: Database Migrations</h2>
    <p class="step-description">
        The system will now execute database migrations to create all necessary tables and schema.
    </p>
    
    <?php if (!empty($errors['general'])): ?>
        <?php echo displayError(
            $errors['general'],
            'Migration Failed',
            'Please check the error message above and ensure your database user has sufficient privileges to create tables and modify the database schema.'
        ); ?>
    <?php endif; ?>
    
    <?php if ($showResults && !empty($migrationResults)): ?>
        <?php 
        if (empty($errors['general'])) {
            echo displaySuccess('All migrations executed successfully!', 'Migrations Complete');
        } else {
            echo displayError('Migration execution encountered errors.', 'Migration Failed');
        }
        ?>
    <?php endif; ?>
    
    <form method="post" action="" class="wizard-form" id="migrations-form">
        <?php echo $csrfService->getTokenField(); ?>
        <input type="hidden" name="step" value="migrations">
        
        <div class="form-section">
            <h3>Migrations to Execute</h3>
            <p class="form-help">
                The following database migrations will be executed to set up your database schema:
            </p>
            
            <?php if ($showResults && !empty($migrationResults)): ?>
                <?php
                $totalMigrations = count($migrationFiles);
                $completedMigrations = 0;
                foreach ($migrationResults as $result) {
                    if ($result === 'success' || $result === 'skipped') {
                        $completedMigrations++;
                    }
                }
                echo displayProgressBar($completedMigrations, $totalMigrations, 'Migration Progress');
                ?>
            <?php endif; ?>
            
            <div class="migrations-list">
                <?php if (empty($migrationFiles)): ?>
                    <div class="alert alert-warning">
                        <div class="alert-icon">⚠</div>
                        <div class="alert-content">
                            <div class="alert-message">No migration files found.</div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($migrationFiles as $index => $filename): ?>
                        <div class="migration-item <?php 
                            if ($showResults && isset($migrationResults[$filename])) {
                                if ($migrationResults[$filename] === 'success') {
                                    echo 'migration-success';
                                } elseif ($migrationResults[$filename] === 'skipped') {
                                    echo 'migration-skipped';
                                } elseif (strpos($migrationResults[$filename], 'failed') === 0) {
                                    echo 'migration-failed';
                                }
                            }
                        ?>">
                            <div class="migration-number"><?php echo $index + 1; ?></div>
                            <div class="migration-info">
                                <div class="migration-name"><?php echo htmlspecialchars($filename); ?></div>
                                <?php if ($showResults && isset($migrationResults[$filename])): ?>
                                    <div class="migration-status">
                                        <?php if ($migrationResults[$filename] === 'success'): ?>
                                            <span class="status-badge status-success">✓ Executed</span>
                                        <?php elseif ($migrationResults[$filename] === 'skipped'): ?>
                                            <span class="status-badge status-skipped">↷ Already executed</span>
                                        <?php elseif (strpos($migrationResults[$filename], 'failed') === 0): ?>
                                            <span class="status-badge status-failed">✕ Failed</span>
                                            <div class="migration-error">
                                                <?php echo htmlspecialchars(substr($migrationResults[$filename], 8)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="migration-status">
                                        <span class="status-badge status-pending">Pending</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!$progress['migrations_run']): ?>
            <div class="form-actions">
                <button 
                    type="submit" 
                    name="migration_action" 
                    value="run" 
                    class="btn btn-primary"
                    id="run-migrations-btn"
                    <?php echo empty($migrationFiles) ? 'disabled' : ''; ?>
                >
                    <span class="btn-text">Run Migrations</span>
                    <span class="btn-loading" style="display: none;">
                        <span class="spinner"></span> Running...
                    </span>
                </button>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <div class="alert-icon">ℹ</div>
                <div class="alert-content">
                    <div class="alert-message">
                        Migrations have been completed. Click "Next" to continue to the next step.
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('migrations-form');
    const runBtn = document.getElementById('run-migrations-btn');
    
    if (form && runBtn) {
        form.addEventListener('submit', function(e) {
            // Show loading state
            const btnText = runBtn.querySelector('.btn-text');
            const btnLoading = runBtn.querySelector('.btn-loading');
            
            if (btnText && btnLoading) {
                btnText.style.display = 'none';
                btnLoading.style.display = 'inline-flex';
            }
            
            runBtn.disabled = true;
        });
    }
});
</script>
