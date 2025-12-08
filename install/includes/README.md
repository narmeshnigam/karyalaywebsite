# Installation Wizard - Message Helpers

This directory contains helper functions for displaying consistent error messages, success notifications, warnings, and user feedback throughout the installation wizard.

## Overview

The message helpers provide a standardized way to display user feedback that:
- Maintains consistent styling across all wizard steps
- Follows accessibility best practices (ARIA labels, semantic HTML)
- Provides clear, actionable error messages
- Enhances user experience with visual feedback

## Requirements Addressed

- **Requirement 8.1**: Display clear error messages explaining issues
- **Requirement 8.2**: Allow users to correct input and retry after errors
- **Requirement 8.3**: Provide guidance on manual resolution for critical errors

## Available Helper Functions

### 1. `displayError($message, $title = null, $helpText = null)`

Displays an error message with optional title and help text.

**Parameters:**
- `$message` (string): The error message to display
- `$title` (string|null): Optional title (default: "Error")
- `$helpText` (string|null): Optional help text with guidance

**Example:**
```php
echo displayError(
    'Database connection failed.',
    'Connection Error',
    'Please verify your database credentials and ensure the server is running.'
);
```

### 2. `displaySuccess($message, $title = null)`

Displays a success message.

**Parameters:**
- `$message` (string): The success message
- `$title` (string|null): Optional title (default: "Success")

**Example:**
```php
echo displaySuccess('Settings saved successfully!', 'Configuration Complete');
```

### 3. `displayWarning($message, $title = null)`

Displays a warning message.

**Parameters:**
- `$message` (string): The warning message
- `$title` (string|null): Optional title (default: "Warning")

**Example:**
```php
echo displayWarning('SMTP is not configured. Email features will not work.');
```

### 4. `displayInfo($message, $title = null)`

Displays an informational message.

**Parameters:**
- `$message` (string): The info message
- `$title` (string|null): Optional title (default: "Information")

**Example:**
```php
echo displayInfo('This step is optional and can be skipped.');
```

### 5. `displayFieldError($fieldName, $errorMessage = '', $visible = false)`

Displays an inline field error message.

**Parameters:**
- `$fieldName` (string): The name of the field
- `$errorMessage` (string): The error message
- `$visible` (bool): Whether the error should be visible initially

**Example:**
```php
<div class="form-group">
    <input type="email" name="email" class="form-input">
    <?php echo displayFieldError('email', 'Please enter a valid email address.', true); ?>
</div>
```

### 6. `displaySpinner($message = '', $dark = false)`

Displays a loading spinner with optional message.

**Parameters:**
- `$message` (string): Optional message to display
- `$dark` (bool): Whether to use dark spinner for light backgrounds

**Example:**
```php
echo displaySpinner('Processing your request...', true);
```

### 7. `displayProgressBar($current, $total, $label = null)`

Displays a progress bar showing completion status.

**Parameters:**
- `$current` (int): Current progress value
- `$total` (int): Total progress value
- `$label` (string|null): Optional label

**Example:**
```php
echo displayProgressBar(7, 10, 'Migration Progress');
```

### 8. `displayValidationErrors($errors)`

Displays a summary of validation errors.

**Parameters:**
- `$errors` (array): Associative array of field names to error messages

**Example:**
```php
$errors = [
    'email' => 'Email is required.',
    'password' => 'Password must be at least 8 characters.'
];
echo displayValidationErrors($errors);
```

### 9. `displayDismissibleMessage($type, $message, $title = null)`

Displays a dismissible message with a close button.

**Parameters:**
- `$type` (string): Message type ('success', 'error', 'warning', 'info')
- `$message` (string): The message to display
- `$title` (string|null): Optional title

**Example:**
```php
echo displayDismissibleMessage('success', 'Your changes have been saved.');
```

## JavaScript Functions

The wizard JavaScript (`wizard.js`) provides complementary client-side functions:

### `WizardApp.showAlert(type, message, title = null)`

Shows an alert message dynamically.

**Example:**
```javascript
WizardApp.showAlert('error', 'Please fix the errors before continuing.');
```

### `WizardApp.showLoading(message = 'Processing...')`

Shows a loading overlay.

**Example:**
```javascript
WizardApp.showLoading('Testing connection...');
```

### `WizardApp.hideLoading()`

Hides the loading overlay.

### `WizardApp.updateProgressBar(current, total, containerId = 'progress-bar')`

Updates a progress bar dynamically.

**Example:**
```javascript
WizardApp.updateProgressBar(5, 10, 'migration-progress');
```

### `WizardApp.showFieldError(fieldName, message)`

Shows an inline field error.

**Example:**
```javascript
WizardApp.showFieldError('email', 'Please enter a valid email address.');
```

### `WizardApp.clearFieldError(fieldName)`

Clears an inline field error.

### `WizardApp.clearAllFieldErrors()`

Clears all field errors on the page.

## CSS Classes

The following CSS classes are available for styling:

### Alert Types
- `.alert-success` - Green success message
- `.alert-error` - Red error message
- `.alert-warning` - Yellow warning message
- `.alert-info` - Blue info message

### Form States
- `.form-input.error` - Error state for input fields
- `.form-error.visible` - Visible error message
- `.form-error` - Hidden error message

### Loading States
- `.loading-overlay` - Full-screen loading overlay
- `.loading-indicator` - Inline loading indicator
- `.spinner` - Loading spinner animation
- `.spinner-dark` - Dark spinner for light backgrounds

### Progress Bar
- `.progress-bar-container` - Progress bar wrapper
- `.progress-bar-track` - Progress bar background
- `.progress-bar-fill` - Progress bar fill
- `.progress-bar-text` - Percentage text
- `.progress-bar-status` - Status text

## Usage in Step Files

Each step file should include the message helpers at the top:

```php
<?php
// Message helpers are automatically included via install/index.php
// No need to require them again in step files

// Display errors
if (!empty($errors['general'])) {
    echo displayError($errors['general'], 'Configuration Failed');
}

// Display success
if ($success) {
    echo displaySuccess('Configuration saved successfully!');
}
?>
```

## Accessibility Features

All message helpers include:
- Proper ARIA roles (`role="alert"`, `role="progressbar"`)
- ARIA labels for screen readers
- Semantic HTML structure
- Keyboard-accessible dismiss buttons
- High contrast colors for visibility

## Best Practices

1. **Always provide context**: Include helpful error messages that explain what went wrong and how to fix it
2. **Use appropriate message types**: Error for failures, warning for cautions, info for guidance
3. **Include help text**: For errors, provide actionable guidance on resolution
4. **Show progress**: Use progress bars for long-running operations
5. **Clear previous errors**: Clear field errors when user corrects input
6. **Animate transitions**: Messages fade in/out for better UX

## Testing

To test the message helpers, see `message_helpers_examples.php` for comprehensive examples of all functions.

