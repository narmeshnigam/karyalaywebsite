<?php

/**
 * SellerPortal System
 * Login Page
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration
$config = require __DIR__ . '/../config/app.php';

// Set error reporting based on environment
if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Load authentication helpers
require_once __DIR__ . '/../includes/auth_helpers.php';

// Start secure session
startSecureSession();

// Include template helpers
require_once __DIR__ . '/../includes/template_helpers.php';

// Redirect if already logged in
if (is_logged_in()) {
    $baseUrl = get_base_url();
    // Redirect admins to admin dashboard, others to customer dashboard
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'ADMIN') {
        $appBaseUrl = get_app_base_url();
        header('Location: ' . $appBaseUrl . '/admin/dashboard.php');
    } else {
        $appBaseUrl = get_app_base_url();
        header('Location: ' . $appBaseUrl . '/app/dashboard.php');
    }
    exit;
}

// Handle form submission
$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validate input
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    
    // Attempt login if no validation errors
    if (empty($errors)) {
        try {
            $userModel = new \Karyalay\Models\User();
            $user = $userModel->findByEmail($email);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['session_token'] = bin2hex(random_bytes(32));
                $_SESSION['user'] = $user; // Store full user data for getCurrentUser()
                
                // Set remember me cookie if requested
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (86400 * 30), '/'); // 30 days
                    // Store token in database (implement this in User model)
                }
                
                // Redirect to appropriate dashboard based on role
                $baseUrl = get_base_url();
                $appBaseUrl = get_app_base_url();
                if ($user['role'] === 'ADMIN') {
                    header('Location: ' . $appBaseUrl . '/admin/dashboard.php');
                } else {
                    header('Location: ' . $appBaseUrl . '/app/dashboard.php');
                }
                exit;
            } else {
                $errors['general'] = 'Invalid email or password';
            }
        } catch (Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            $errors['general'] = 'An error occurred. Please try again.';
        }
    }
}

// Set page variables
$page_title = 'Login';
$page_description = 'Login to your ' . get_brand_name() . ' account';

// Include header
include_header($page_title, $page_description);
?>

<!-- Login Section -->
<section class="auth-section">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <?php echo render_brand_logo('light_bg', 'auth-logo-img', 50); ?>
                </div>
                <h2 class="auth-title">Welcome Back</h2>
                <p class="auth-subtitle">Login to your account to continue</p>
            </div>
            
            <?php if (isset($errors['general'])): ?>
                <div class="auth-alert auth-alert-error">
                    <svg class="auth-alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span><?php echo esc_html($errors['general']); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo get_base_url(); ?>/login.php" class="auth-form" novalidate>
                <!-- Email Field -->
                <div class="auth-form-group">
                    <label for="email" class="auth-label">Email Address</label>
                    <div class="auth-input-wrapper">
                        <svg class="auth-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                        </svg>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="auth-input <?php echo isset($errors['email']) ? 'auth-input-error' : ''; ?>"
                            value="<?php echo esc_attr($email); ?>"
                            placeholder="you@example.com"
                            required
                            aria-required="true"
                            <?php if (isset($errors['email'])): ?>
                                aria-invalid="true"
                                aria-describedby="email-error"
                            <?php endif; ?>
                        >
                    </div>
                    <?php if (isset($errors['email'])): ?>
                        <div id="email-error" class="auth-error" role="alert">
                            <?php echo esc_html($errors['email']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Password Field -->
                <div class="auth-form-group">
                    <label for="password" class="auth-label">Password</label>
                    <div class="auth-input-wrapper">
                        <svg class="auth-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="auth-input <?php echo isset($errors['password']) ? 'auth-input-error' : ''; ?>"
                            placeholder="Enter your password"
                            required
                            aria-required="true"
                            <?php if (isset($errors['password'])): ?>
                                aria-invalid="true"
                                aria-describedby="password-error"
                            <?php endif; ?>
                        >
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <div id="password-error" class="auth-error" role="alert">
                            <?php echo esc_html($errors['password']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Remember Me & Forgot Password -->
                <div class="auth-options">
                    <label class="auth-checkbox-label">
                        <input type="checkbox" name="remember" id="remember" class="auth-checkbox">
                        <span>Remember me</span>
                    </label>
                    <a href="<?php echo get_base_url(); ?>/forgot-password.php" class="auth-link">Forgot password?</a>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="auth-button">
                    <span>Sign In</span>
                    <svg class="auth-button-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </button>
                
                <!-- Sign Up Link -->
                <div class="auth-footer">
                    <p>Don't have an account? <a href="<?php echo get_base_url(); ?>/register.php" class="auth-link-primary">Sign up</a></p>
                </div>
            </form>
        </div>
        
        <!-- Side Panel -->
        <div class="auth-side-panel">
            <div class="auth-side-content">
                <h3>Start Your Journey</h3>
                <p>Access powerful business management tools designed to streamline your operations and boost productivity.</p>
                <ul class="auth-features">
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Comprehensive Solutions</span>
                    </li>
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Secure & Reliable</span>
                    </li>
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>24/7 Support</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<style>
.auth-section {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--color-gray-50);
    padding: var(--spacing-6);
}

.auth-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    max-width: 1000px;
    width: 100%;
    background: var(--color-white);
    border-radius: var(--radius-2xl);
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.auth-card {
    padding: var(--spacing-10);
}

.auth-header {
    text-align: center;
    margin-bottom: var(--spacing-8);
}

.auth-logo {
    margin-bottom: var(--spacing-4);
    display: flex;
    justify-content: center;
    align-items: center;
}

.auth-logo .brand-logo-img {
    max-width: 200px;
    max-height: 60px;
    width: auto;
    height: auto;
    object-fit: contain;
}

.auth-logo .brand-logo-text {
    font-size: var(--font-size-3xl);
    font-weight: var(--font-weight-bold);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.auth-title {
    font-size: var(--font-size-2xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin-bottom: var(--spacing-2);
}

.auth-subtitle {
    color: var(--color-gray-600);
    font-size: var(--font-size-sm);
}

.auth-alert {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    padding: var(--spacing-4);
    border-radius: var(--radius-lg);
    margin-bottom: var(--spacing-6);
}

.auth-alert-error {
    background-color: #fee2e2;
    color: #991b1b;
}

.auth-alert-icon {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

.auth-form-group {
    margin-bottom: var(--spacing-5);
}

.auth-label {
    display: block;
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
    margin-bottom: var(--spacing-2);
}

.auth-input-wrapper {
    position: relative;
}

.auth-input-icon {
    position: absolute;
    left: var(--spacing-3);
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    color: var(--color-gray-400);
}

.auth-input {
    width: 100%;
    padding: var(--spacing-3) var(--spacing-3) var(--spacing-3) var(--spacing-10);
    border: 2px solid var(--color-gray-200);
    border-radius: var(--radius-lg);
    font-size: var(--font-size-base);
    transition: all 0.2s;
}

.auth-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.auth-input-error {
    border-color: #dc2626;
}

.auth-error {
    color: #dc2626;
    font-size: var(--font-size-sm);
    margin-top: var(--spacing-2);
}

.auth-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-6);
}

.auth-checkbox-label {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    font-size: var(--font-size-sm);
    color: var(--color-gray-700);
    cursor: pointer;
}

.auth-checkbox {
    width: 16px;
    height: 16px;
    cursor: pointer;
}

.auth-link {
    font-size: var(--font-size-sm);
    color: #667eea;
    text-decoration: none;
    font-weight: var(--font-weight-medium);
}

.auth-link:hover {
    text-decoration: underline;
}

.auth-button {
    width: 100%;
    padding: var(--spacing-4);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: var(--color-white);
    border: none;
    border-radius: var(--radius-lg);
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-semibold);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-2);
    transition: transform 0.2s, box-shadow 0.2s;
}

.auth-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

.auth-button-icon {
    width: 20px;
    height: 20px;
}

.auth-footer {
    text-align: center;
    margin-top: var(--spacing-6);
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
}

.auth-link-primary {
    color: #667eea;
    font-weight: var(--font-weight-semibold);
    text-decoration: none;
}

.auth-link-primary:hover {
    text-decoration: underline;
}

.auth-side-panel {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: var(--spacing-10);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-white);
}

.auth-side-content h3 {
    font-size: var(--font-size-3xl);
    font-weight: var(--font-weight-bold);
    margin-bottom: var(--spacing-4);
}

.auth-side-content p {
    font-size: var(--font-size-base);
    line-height: 1.6;
    margin-bottom: var(--spacing-8);
    opacity: 0.95;
}

.auth-features {
    list-style: none;
    padding: 0;
    margin: 0;
}

.auth-features li {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    margin-bottom: var(--spacing-4);
    font-size: var(--font-size-base);
}

.auth-features svg {
    width: 24px;
    height: 24px;
    flex-shrink: 0;
}

@media (max-width: 768px) {
    .auth-container {
        grid-template-columns: 1fr;
    }
    
    .auth-side-panel {
        display: none;
    }
    
    .auth-card {
        padding: var(--spacing-6);
    }
}
</style>

<?php
// Include footer
include_footer();
?>
