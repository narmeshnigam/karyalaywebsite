<?php
/**
 * Message Display Helpers for Installation Wizard
 * 
 * Provides consistent error, success, warning, and info message display
 * throughout the installation wizard.
 * 
 * Requirements: 8.1, 8.2, 8.3
 */

/**
 * Display an error message
 * 
 * @param string $message The error message to display
 * @param string|null $title Optional title for the error
 * @param string|null $helpText Optional help text with guidance
 * @return string HTML for the error message
 */
function displayError($message, $title = null, $helpText = null) {
    $title = $title ?? 'Error';
    
    $html = '<div class="alert alert-error" role="alert">';
    $html .= '<div class="alert-icon" aria-hidden="true">✕</div>';
    $html .= '<div class="alert-content">';
    $html .= '<div class="alert-title">' . htmlspecialchars($title) . '</div>';
    $html .= '<div class="alert-message">' . htmlspecialchars($message) . '</div>';
    
    if ($helpText) {
        $html .= '<div class="alert-help">' . htmlspecialchars($helpText) . '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Display a success message
 * 
 * @param string $message The success message to display
 * @param string|null $title Optional title for the success message
 * @return string HTML for the success message
 */
function displaySuccess($message, $title = null) {
    $title = $title ?? 'Success';
    
    $html = '<div class="alert alert-success" role="alert">';
    $html .= '<div class="alert-icon" aria-hidden="true">✓</div>';
    $html .= '<div class="alert-content">';
    $html .= '<div class="alert-title">' . htmlspecialchars($title) . '</div>';
    $html .= '<div class="alert-message">' . htmlspecialchars($message) . '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Display a warning message
 * 
 * @param string $message The warning message to display
 * @param string|null $title Optional title for the warning
 * @return string HTML for the warning message
 */
function displayWarning($message, $title = null) {
    $title = $title ?? 'Warning';
    
    $html = '<div class="alert alert-warning" role="alert">';
    $html .= '<div class="alert-icon" aria-hidden="true">⚠</div>';
    $html .= '<div class="alert-content">';
    $html .= '<div class="alert-title">' . htmlspecialchars($title) . '</div>';
    $html .= '<div class="alert-message">' . htmlspecialchars($message) . '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Display an info message
 * 
 * @param string $message The info message to display
 * @param string|null $title Optional title for the info message
 * @return string HTML for the info message
 */
function displayInfo($message, $title = null) {
    $title = $title ?? 'Information';
    
    $html = '<div class="alert alert-info" role="alert">';
    $html .= '<div class="alert-icon" aria-hidden="true">ℹ</div>';
    $html .= '<div class="alert-content">';
    $html .= '<div class="alert-title">' . htmlspecialchars($title) . '</div>';
    $html .= '<div class="alert-message">' . htmlspecialchars($message) . '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Display an inline field error
 * 
 * @param string $fieldName The name of the field
 * @param string $errorMessage The error message
 * @param bool $visible Whether the error should be visible initially
 * @return string HTML for the inline field error
 */
function displayFieldError($fieldName, $errorMessage = '', $visible = false) {
    $visibleClass = $visible ? ' visible' : '';
    return '<span class="form-error' . $visibleClass . '" id="' . htmlspecialchars($fieldName) . '-error">' 
           . htmlspecialchars($errorMessage) . '</span>';
}

/**
 * Display a loading spinner
 * 
 * @param string $message Optional message to display with the spinner
 * @param bool $dark Whether to use dark spinner (for light backgrounds)
 * @return string HTML for the loading spinner
 */
function displaySpinner($message = '', $dark = false) {
    $spinnerClass = $dark ? 'spinner-dark' : 'spinner';
    
    $html = '<div class="loading-indicator">';
    $html .= '<span class="' . $spinnerClass . '" role="status" aria-label="Loading"></span>';
    
    if ($message) {
        $html .= '<span class="loading-text">' . htmlspecialchars($message) . '</span>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Display a progress bar
 * 
 * @param int $current Current progress value
 * @param int $total Total progress value
 * @param string|null $label Optional label to display
 * @return string HTML for the progress bar
 */
function displayProgressBar($current, $total, $label = null) {
    $percentage = $total > 0 ? round(($current / $total) * 100) : 0;
    
    $html = '<div class="progress-bar-container">';
    
    if ($label) {
        $html .= '<div class="progress-bar-label">' . htmlspecialchars($label) . '</div>';
    }
    
    $html .= '<div class="progress-bar-track" role="progressbar" aria-valuenow="' . $percentage . '" aria-valuemin="0" aria-valuemax="100">';
    $html .= '<div class="progress-bar-fill" style="width: ' . $percentage . '%">';
    $html .= '<span class="progress-bar-text">' . $percentage . '%</span>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="progress-bar-status">' . $current . ' of ' . $total . ' completed</div>';
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Display validation errors for a form
 * 
 * @param array $errors Associative array of field names to error messages
 * @return string HTML for the validation errors summary
 */
function displayValidationErrors($errors) {
    if (empty($errors)) {
        return '';
    }
    
    $html = '<div class="alert alert-error" role="alert">';
    $html .= '<div class="alert-icon" aria-hidden="true">✕</div>';
    $html .= '<div class="alert-content">';
    $html .= '<div class="alert-title">Please fix the following errors:</div>';
    $html .= '<ul class="alert-list">';
    
    foreach ($errors as $field => $message) {
        $html .= '<li>' . htmlspecialchars($message) . '</li>';
    }
    
    $html .= '</ul>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Display a dismissible message
 * 
 * @param string $type Message type: 'success', 'error', 'warning', 'info'
 * @param string $message The message to display
 * @param string|null $title Optional title
 * @return string HTML for the dismissible message
 */
function displayDismissibleMessage($type, $message, $title = null) {
    $icons = [
        'success' => '✓',
        'error' => '✕',
        'warning' => '⚠',
        'info' => 'ℹ'
    ];
    
    $titles = [
        'success' => 'Success',
        'error' => 'Error',
        'warning' => 'Warning',
        'info' => 'Information'
    ];
    
    $icon = $icons[$type] ?? 'ℹ';
    $title = $title ?? $titles[$type] ?? 'Message';
    
    $html = '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible" role="alert">';
    $html .= '<div class="alert-icon" aria-hidden="true">' . $icon . '</div>';
    $html .= '<div class="alert-content">';
    $html .= '<div class="alert-title">' . htmlspecialchars($title) . '</div>';
    $html .= '<div class="alert-message">' . htmlspecialchars($message) . '</div>';
    $html .= '</div>';
    $html .= '<button type="button" class="alert-dismiss" aria-label="Dismiss message" onclick="this.parentElement.remove()">&times;</button>';
    $html .= '</div>';
    
    return $html;
}
