<?php
/**
 * Installation Wizard Progress Indicator
 * 
 * Displays a visual progress indicator showing all 5 installation steps
 * with states: completed (✓), current (●), and pending ( )
 * 
 * Expected variables:
 * - $currentStep: int (1-5) - The current step number
 * - $completedSteps: array - Array of completed step numbers
 */

$currentStep = $currentStep ?? 1;
$completedSteps = $completedSteps ?? [];

$steps = [
    1 => 'Database',
    2 => 'Migrations',
    3 => 'Admin Account',
    4 => 'SMTP Config',
    5 => 'Brand Settings'
];

/**
 * Determine the state of a step
 * 
 * @param int $stepNumber The step number to check
 * @param int $currentStep The current active step
 * @param array $completedSteps Array of completed step numbers
 * @return string The state: 'completed', 'current', or 'pending'
 */
function getStepState($stepNumber, $currentStep, $completedSteps) {
    if (in_array($stepNumber, $completedSteps)) {
        return 'completed';
    } elseif ($stepNumber === $currentStep) {
        return 'current';
    } else {
        return 'pending';
    }
}
?>

<div class="progress-indicator">
    <div class="progress-steps">
        <?php foreach ($steps as $stepNumber => $stepName): ?>
            <?php 
                $state = getStepState($stepNumber, $currentStep, $completedSteps);
                $isLast = $stepNumber === count($steps);
            ?>
            
            <div class="progress-step <?php echo $state; ?>">
                <div class="step-marker">
                    <?php if ($state === 'completed'): ?>
                        <span class="step-icon">✓</span>
                    <?php elseif ($state === 'current'): ?>
                        <span class="step-icon">●</span>
                    <?php else: ?>
                        <span class="step-icon"><?php echo $stepNumber; ?></span>
                    <?php endif; ?>
                </div>
                <div class="step-label"><?php echo htmlspecialchars($stepName); ?></div>
            </div>
            
            <?php if (!$isLast): ?>
                <div class="step-connector <?php echo in_array($stepNumber, $completedSteps) ? 'completed' : ''; ?>"></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
