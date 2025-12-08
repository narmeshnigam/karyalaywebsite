/**
 * Installation Wizard JavaScript
 * Handles client-side validation, AJAX calls, and UI interactions
 */

const WizardApp = {
    currentStep: 1,
    totalSteps: 5,
    formData: {},

    /**
     * Initialize the wizard
     */
    init() {
        this.setupEventListeners();
        this.loadProgress();
        this.updateUI();
    },

    /**
     * Setup event listeners for form interactions
     */
    setupEventListeners() {
        // Form submission prevention
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleFormSubmit(form);
            });
        });

        // Real-time validation
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => {
                if (input.classList.contains('error')) {
                    this.validateField(input);
                }
            });
        });

        // Password strength indicator
        const passwordInput = document.querySelector('input[name="password"]');
        if (passwordInput) {
            passwordInput.addEventListener('input', () => this.updatePasswordStrength(passwordInput));
        }

        // Test connection buttons
        document.querySelectorAll('[data-test-action]').forEach(btn => {
            btn.addEventListener('click', () => this.handleTestAction(btn));
        });
    },

    /**
     * Handle form submission
     */
    handleFormSubmit(form) {
        const isValid = this.validateForm(form);
        
        if (!isValid) {
            this.showAlert('error', 'Please fix the errors before continuing.');
            return;
        }

        // Collect form data
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        // Store in wizard state
        Object.assign(this.formData, data);
        
        // Save progress
        this.saveProgress();
        
        // Submit form
        form.submit();
    },

    /**
     * Validate entire form
     */
    validateForm(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('.form-input, .form-select');
        
        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });
        
        return isValid;
    },

    /**
     * Validate individual field
     */
    validateField(field) {
        const value = field.value.trim();
        const name = field.name;
        const errorElement = field.parentElement.querySelector('.form-error');
        
        let error = null;

        // Required field validation
        if (field.hasAttribute('required') && !value) {
            error = 'This field is required.';
        }
        
        // Email validation
        else if (field.type === 'email' && value) {
            if (!this.isValidEmail(value)) {
                error = 'Please enter a valid email address.';
            }
        }
        
        // Password validation
        else if (name === 'password' && value) {
            if (value.length < 8) {
                error = 'Password must be at least 8 characters long.';
            }
        }
        
        // Password confirmation
        else if (name === 'password_confirm' && value) {
            const password = document.querySelector('input[name="password"]');
            if (password && value !== password.value) {
                error = 'Passwords do not match.';
            }
        }
        
        // Port number validation
        else if (name === 'port' || name === 'smtp_port') {
            const port = parseInt(value);
            if (value && (isNaN(port) || port < 1 || port > 65535)) {
                error = 'Please enter a valid port number (1-65535).';
            }
        }

        // Display error or clear it
        if (error) {
            field.classList.add('error');
            if (errorElement) {
                errorElement.textContent = error;
                errorElement.classList.add('visible');
            }
            return false;
        } else {
            field.classList.remove('error');
            if (errorElement) {
                errorElement.classList.remove('visible');
            }
            return true;
        }
    },

    /**
     * Validate email format
     */
    isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },

    /**
     * Update password strength indicator
     */
    updatePasswordStrength(input) {
        const password = input.value;
        const strengthBar = document.querySelector('.password-strength-bar');
        const strengthText = document.querySelector('.password-strength-text');
        
        if (!strengthBar || !strengthText) return;

        let strength = 0;
        let strengthLabel = '';

        if (password.length === 0) {
            strengthBar.className = 'password-strength-bar';
            strengthText.textContent = '';
            return;
        }

        // Calculate strength
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
        if (/\d/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;

        // Set strength class and label
        if (strength <= 2) {
            strengthBar.className = 'password-strength-bar weak';
            strengthLabel = 'Weak password';
        } else if (strength <= 3) {
            strengthBar.className = 'password-strength-bar medium';
            strengthLabel = 'Medium password';
        } else {
            strengthBar.className = 'password-strength-bar strong';
            strengthLabel = 'Strong password';
        }

        strengthText.textContent = strengthLabel;
    },

    /**
     * Handle test action buttons (database, SMTP)
     */
    async handleTestAction(button) {
        const action = button.dataset.testAction;
        const form = button.closest('form');
        
        if (!form) return;

        // Validate form first
        if (!this.validateForm(form)) {
            this.showAlert('error', 'Please fix the errors before testing.');
            return;
        }

        // Collect form data
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Show loading state
        const originalText = button.textContent;
        button.disabled = true;
        button.innerHTML = '<span class="spinner"></span> Testing...';

        try {
            let endpoint = '';
            if (action === 'database') {
                endpoint = '/install/api/test-database.php';
            } else if (action === 'smtp') {
                endpoint = '/install/api/test-smtp.php';
            }

            const result = await this.ajaxPost(endpoint, data);

            if (result.success) {
                this.showAlert('success', result.message || 'Test successful!');
            } else {
                this.showAlert('error', result.message || 'Test failed. Please check your settings.');
            }
        } catch (error) {
            this.showAlert('error', 'An error occurred during testing: ' + error.message);
        } finally {
            button.disabled = false;
            button.textContent = originalText;
        }
    },

    /**
     * Make AJAX POST request
     */
    async ajaxPost(url, data) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    },

    /**
     * Make AJAX GET request
     */
    async ajaxGet(url) {
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    },

    /**
     * Show alert message
     */
    showAlert(type, message, title = null) {
        // Remove existing alerts
        document.querySelectorAll('.alert').forEach(alert => alert.remove());

        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        
        let icon = '';
        if (type === 'success') {
            icon = '✓';
            title = title || 'Success';
        } else if (type === 'error') {
            icon = '✕';
            title = title || 'Error';
        } else if (type === 'warning') {
            icon = '⚠';
            title = title || 'Warning';
        } else if (type === 'info') {
            icon = 'ℹ';
            title = title || 'Info';
        }

        alertDiv.innerHTML = `
            <div class="alert-icon">${icon}</div>
            <div class="alert-content">
                ${title ? `<div class="alert-title">${title}</div>` : ''}
                <div class="alert-message">${message}</div>
            </div>
        `;

        // Insert at the top of wizard content
        const content = document.querySelector('.wizard-content');
        if (content) {
            content.insertBefore(alertDiv, content.firstChild);
            
            // Scroll to alert
            alertDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    },

    /**
     * Show loading overlay
     */
    showLoading(message = 'Processing...') {
        let overlay = document.querySelector('.loading-overlay');
        
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'loading-overlay';
            overlay.innerHTML = `
                <div class="loading-content">
                    <div class="loading-spinner"></div>
                    <div class="loading-text">${message}</div>
                </div>
            `;
            document.body.appendChild(overlay);
        }

        overlay.querySelector('.loading-text').textContent = message;
        overlay.classList.add('visible');
    },

    /**
     * Hide loading overlay
     */
    hideLoading() {
        const overlay = document.querySelector('.loading-overlay');
        if (overlay) {
            overlay.classList.remove('visible');
        }
    },

    /**
     * Update UI based on current step
     */
    updateUI() {
        // Update progress indicator
        document.querySelectorAll('.progress-step').forEach((step, index) => {
            const stepNumber = index + 1;
            step.classList.remove('active', 'completed');
            
            if (stepNumber < this.currentStep) {
                step.classList.add('completed');
            } else if (stepNumber === this.currentStep) {
                step.classList.add('active');
            }
        });

        // Update step visibility
        document.querySelectorAll('.wizard-step').forEach((step, index) => {
            const stepNumber = index + 1;
            step.classList.toggle('active', stepNumber === this.currentStep);
        });

        // Update navigation buttons
        const prevBtn = document.querySelector('[data-action="prev"]');
        const nextBtn = document.querySelector('[data-action="next"]');
        
        if (prevBtn) {
            prevBtn.disabled = this.currentStep === 1;
        }
        
        if (nextBtn) {
            nextBtn.textContent = this.currentStep === this.totalSteps ? 'Complete' : 'Next';
        }
    },

    /**
     * Save wizard progress to session storage
     */
    saveProgress() {
        const progress = {
            currentStep: this.currentStep,
            formData: this.formData,
            timestamp: Date.now()
        };
        
        try {
            sessionStorage.setItem('wizard_progress', JSON.stringify(progress));
        } catch (e) {
            console.error('Failed to save progress:', e);
        }
    },

    /**
     * Load wizard progress from session storage
     */
    loadProgress() {
        try {
            const saved = sessionStorage.getItem('wizard_progress');
            if (saved) {
                const progress = JSON.parse(saved);
                this.currentStep = progress.currentStep || 1;
                this.formData = progress.formData || {};
                
                // Restore form values
                this.restoreFormValues();
            }
        } catch (e) {
            console.error('Failed to load progress:', e);
        }
    },

    /**
     * Restore form values from saved data
     */
    restoreFormValues() {
        Object.keys(this.formData).forEach(name => {
            const input = document.querySelector(`[name="${name}"]`);
            if (input && !input.value) {
                input.value = this.formData[name];
            }
        });
    },

    /**
     * Clear wizard progress
     */
    clearProgress() {
        try {
            sessionStorage.removeItem('wizard_progress');
            this.formData = {};
        } catch (e) {
            console.error('Failed to clear progress:', e);
        }
    },

    /**
     * Navigate to specific step
     */
    goToStep(stepNumber) {
        if (stepNumber < 1 || stepNumber > this.totalSteps) {
            return;
        }
        
        this.currentStep = stepNumber;
        this.saveProgress();
        this.updateUI();
    },

    /**
     * Navigate to next step
     */
    nextStep() {
        if (this.currentStep < this.totalSteps) {
            this.goToStep(this.currentStep + 1);
        }
    },

    /**
     * Navigate to previous step
     */
    prevStep() {
        if (this.currentStep > 1) {
            this.goToStep(this.currentStep - 1);
        }
    },

    /**
     * Update progress bar
     */
    updateProgressBar(current, total, containerId = 'progress-bar') {
        const container = document.getElementById(containerId);
        if (!container) return;

        const percentage = total > 0 ? Math.round((current / total) * 100) : 0;
        
        const fill = container.querySelector('.progress-bar-fill');
        const text = container.querySelector('.progress-bar-text');
        const status = container.querySelector('.progress-bar-status');

        if (fill) {
            fill.style.width = percentage + '%';
        }

        if (text) {
            text.textContent = percentage + '%';
        }

        if (status) {
            status.textContent = `${current} of ${total} completed`;
        }
    },

    /**
     * Show inline field error
     */
    showFieldError(fieldName, message) {
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (!field) return;

        field.classList.add('error');
        
        const errorElement = field.parentElement.querySelector('.form-error');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.add('visible');
        }
    },

    /**
     * Clear inline field error
     */
    clearFieldError(fieldName) {
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (!field) return;

        field.classList.remove('error');
        
        const errorElement = field.parentElement.querySelector('.form-error');
        if (errorElement) {
            errorElement.classList.remove('visible');
            errorElement.textContent = '';
        }
    },

    /**
     * Clear all field errors
     */
    clearAllFieldErrors() {
        document.querySelectorAll('.form-input.error, .form-select.error').forEach(field => {
            field.classList.remove('error');
        });
        
        document.querySelectorAll('.form-error.visible').forEach(error => {
            error.classList.remove('visible');
            error.textContent = '';
        });
    },

    /**
     * Dismiss alert
     */
    dismissAlert(alertElement) {
        if (alertElement && alertElement.classList.contains('alert')) {
            alertElement.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => {
                alertElement.remove();
            }, 300);
        }
    }
};

// Initialize wizard when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => WizardApp.init());
} else {
    WizardApp.init();
}

// Export for use in other scripts
window.WizardApp = WizardApp;
