<?php
/**
 * Message Helpers Usage Examples
 * 
 * This file demonstrates how to use the message helper functions
 * in the installation wizard.
 */

require_once __DIR__ . '/message_helpers.php';

// Example 1: Display an error message
echo displayError(
    'Database connection failed. Please check your credentials.',
    'Connection Error',
    'Ensure your database server is running and the credentials are correct.'
);

// Example 2: Display a success message
echo displaySuccess(
    'Your settings have been saved successfully!',
    'Settings Saved'
);

// Example 3: Display a warning message
echo displayWarning(
    'SMTP is not configured. Email features will not work.',
    'Configuration Warning'
);

// Example 4: Display an info message
echo displayInfo(
    'This step is optional and can be skipped.',
    'Optional Step'
);

// Example 5: Display inline field error
echo '<div class="form-group">';
echo '<input type="text" name="email" class="form-input error">';
echo displayFieldError('email', 'Please enter a valid email address.', true);
echo '</div>';

// Example 6: Display a loading spinner
echo displaySpinner('Processing your request...', true);

// Example 7: Display a progress bar
echo displayProgressBar(7, 10, 'Migration Progress');

// Example 8: Display validation errors summary
$errors = [
    'email' => 'Email is required.',
    'password' => 'Password must be at least 8 characters.',
    'name' => 'Name cannot be empty.'
];
echo displayValidationErrors($errors);

// Example 9: Display a dismissible message
echo displayDismissibleMessage(
    'success',
    'Your changes have been saved.',
    'Success'
);

?>
