<?php

/**
 * SellerPortal System
 * Registration Page with Email OTP Verification
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
    header('Location: ' . get_app_base_url() . '/app/dashboard.php');
    exit;
}

use Karyalay\Services\OtpService;

// Handle form submission (final registration after OTP verification)
$errors = [];
$name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // Validate input
    if (empty($name)) {
        $errors['name'] = 'Name is required';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    
    if (empty($phone)) {
        $errors['phone'] = 'Phone number is required';
    } elseif (!preg_match('/^\+[0-9]{1,4}[0-9]{6,15}$/', $phone)) {
        $errors['phone'] = 'Please enter a valid phone number';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }
    
    if ($password !== $password_confirm) {
        $errors['password_confirm'] = 'Passwords do not match';
    }
    
    // Verify that email OTP was verified
    if (empty($errors)) {
        $otpService = new OtpService();
        if (!$otpService->isEmailVerified($email)) {
            $errors['otp'] = 'Please verify your email first';
        }
    }
    
    // Attempt registration if no validation errors
    if (empty($errors)) {
        try {
            $userModel = new \Karyalay\Models\User();
            
            // Check if email already exists
            if ($userModel->findByEmail($email)) {
                $errors['email'] = 'Email already registered';
            } else {
                // Create user with verified email
                $userData = [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'password' => $password,
                    'role' => 'CUSTOMER',
                    'email_verified' => 1
                ];
                
                $user = $userModel->create($userData);
                
                if ($user) {
                    // Clean up OTP records
                    $otpService->cleanupVerifiedOtp($email);
                    
                    // Send welcome email to user
                    try {
                        $emailService = new \Karyalay\Services\EmailService();
                        $emailService->sendWelcomeEmail($email, $name);
                    } catch (Exception $e) {
                        error_log('Failed to send welcome email: ' . $e->getMessage());
                        // Don't fail registration if email fails
                    }
                    
                    // Send notification email to admin
                    try {
                        $emailService = new \Karyalay\Services\EmailService();
                        $emailService->sendNewUserNotification([
                            'name' => $name,
                            'email' => $email,
                            'phone' => $phone,
                            'role' => $user['role'],
                            'business_name' => null,
                            'email_verified' => $user['email_verified']
                        ]);
                    } catch (Exception $e) {
                        error_log('Failed to send admin notification email: ' . $e->getMessage());
                        // Don't fail registration if email fails
                    }
                    
                    // Auto-login after registration
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['session_token'] = bin2hex(random_bytes(32));
                    $_SESSION['user'] = $user;
                    
                    // Redirect to dashboard
                    header('Location: ' . get_app_base_url() . '/app/dashboard.php');
                    exit;
                } else {
                    $errors['general'] = 'Registration failed. Please try again.';
                }
            }
        } catch (Exception $e) {
            error_log('Registration error: ' . $e->getMessage());
            $errors['general'] = 'An error occurred. Please try again.';
        }
    }
}

// Set page variables
$page_title = 'Register';
$page_description = 'Create your ' . get_brand_name() . ' account';

// Include header
include_header($page_title, $page_description);
?>

<!-- Registration Section -->
<section class="auth-section">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <?php echo render_brand_logo('light_bg', 'auth-logo-img', 50); ?>
                </div>
                <h2 class="auth-title">Create Account</h2>
                <p class="auth-subtitle">Start your journey with us today</p>
            </div>
            
            <?php if (isset($errors['general'])): ?>
                <div class="auth-alert auth-alert-error">
                    <svg class="auth-alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span><?php echo esc_html($errors['general']); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (isset($errors['otp'])): ?>
                <div class="auth-alert auth-alert-error">
                    <svg class="auth-alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span><?php echo esc_html($errors['otp']); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo get_base_url(); ?>/register.php" class="auth-form" id="register-form" novalidate>
                <!-- Step 1: Registration Fields -->
                <div id="step-registration">
                    <!-- Name Field -->
                    <div class="auth-form-group">
                        <label for="name" class="auth-label">Full Name</label>
                        <div class="auth-input-wrapper">
                            <svg class="auth-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <input type="text" id="name" name="name" class="auth-input <?php echo isset($errors['name']) ? 'auth-input-error' : ''; ?>"
                                value="<?php echo esc_attr($name); ?>" placeholder="John Doe" required aria-required="true"
                                <?php if (isset($errors['name'])): ?>aria-invalid="true" aria-describedby="name-error"<?php endif; ?>>
                        </div>
                        <?php if (isset($errors['name'])): ?>
                            <div id="name-error" class="auth-error" role="alert"><?php echo esc_html($errors['name']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Email Field -->
                    <div class="auth-form-group">
                        <label for="email" class="auth-label">Email Address</label>
                        <div class="auth-input-wrapper">
                            <svg class="auth-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                            </svg>
                            <input type="email" id="email" name="email" class="auth-input <?php echo isset($errors['email']) ? 'auth-input-error' : ''; ?>"
                                value="<?php echo esc_attr($email); ?>" placeholder="you@example.com" required aria-required="true"
                                <?php if (isset($errors['email'])): ?>aria-invalid="true" aria-describedby="email-error"<?php endif; ?>>
                        </div>
                        <?php if (isset($errors['email'])): ?>
                            <div id="email-error" class="auth-error" role="alert"><?php echo esc_html($errors['email']); ?></div>
                        <?php endif; ?>
                        <div id="email-verified-badge" class="email-verified-badge" style="display: none;">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Email verified</span>
                        </div>
                    </div>
                    
                    <!-- Phone Field -->
                    <div class="auth-form-group">
                        <label for="phone-input" class="auth-label">Phone Number</label>
                        <?php echo render_phone_input([
                            'id' => 'phone',
                            'name' => 'phone',
                            'value' => $phone,
                            'required' => true,
                            'error' => $errors['phone'] ?? '',
                            'class' => isset($errors['phone']) ? 'has-error' : '',
                        ]); ?>
                        <?php if (isset($errors['phone'])): ?>
                            <div id="phone-error" class="auth-error" role="alert"><?php echo esc_html($errors['phone']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Password Field -->
                    <div class="auth-form-group">
                        <label for="password" class="auth-label">Password</label>
                        <div class="auth-input-wrapper">
                            <svg class="auth-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <input type="password" id="password" name="password" class="auth-input <?php echo isset($errors['password']) ? 'auth-input-error' : ''; ?>"
                                placeholder="Minimum 8 characters" required aria-required="true"
                                <?php if (isset($errors['password'])): ?>aria-invalid="true" aria-describedby="password-error"<?php endif; ?>>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <div id="password-error" class="auth-error" role="alert"><?php echo esc_html($errors['password']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Confirm Password Field -->
                    <div class="auth-form-group">
                        <label for="password_confirm" class="auth-label">Confirm Password</label>
                        <div class="auth-input-wrapper">
                            <svg class="auth-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <input type="password" id="password_confirm" name="password_confirm" class="auth-input <?php echo isset($errors['password_confirm']) ? 'auth-input-error' : ''; ?>"
                                placeholder="Re-enter your password" required aria-required="true"
                                <?php if (isset($errors['password_confirm'])): ?>aria-invalid="true" aria-describedby="password_confirm-error"<?php endif; ?>>
                        </div>
                        <?php if (isset($errors['password_confirm'])): ?>
                            <div id="password_confirm-error" class="auth-error" role="alert"><?php echo esc_html($errors['password_confirm']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Create Account Button (triggers OTP) -->
                    <button type="button" class="auth-button" id="btn-send-otp">
                        <span>Create Account</span>
                        <svg class="auth-button-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </div>

                <!-- Step 2: OTP Verification (shown after clicking Create Account) -->
                <div id="step-otp" style="display: none;">
                    <div class="otp-section">
                        <div class="otp-header">
                            <svg class="otp-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="48" height="48">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <h3 class="otp-title">Verify Your Email</h3>
                            <p class="otp-subtitle">We've sent a 6-digit code to <span id="otp-email-display"></span></p>
                        </div>
                        
                        <div class="otp-input-group">
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="one-time-code" data-index="0">
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="1">
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="2">
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="3">
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="4">
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="5">
                        </div>
                        <input type="hidden" id="otp-combined" name="otp_verified" value="">
                        
                        <div id="otp-error" class="auth-error otp-error" style="display: none;" role="alert"></div>
                        <div id="otp-success" class="auth-success otp-success" style="display: none;" role="status"></div>
                        
                        <div class="otp-timer">
                            <span id="otp-timer-text">Code expires in <span id="otp-countdown">10:00</span></span>
                        </div>
                        
                        <div class="otp-actions">
                            <button type="button" class="auth-button" id="btn-verify-otp" disabled>
                                <span>Verify & Complete Registration</span>
                            </button>
                            <button type="button" class="auth-link-btn" id="btn-resend-otp" disabled>
                                Resend Code <span id="resend-countdown"></span>
                            </button>
                            <button type="button" class="auth-link-btn" id="btn-change-email">
                                Change Email
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Login Link -->
                <div class="auth-footer">
                    <p>Already have an account? <a href="<?php echo get_base_url(); ?>/login.php" class="auth-link-primary">Sign in</a></p>
                </div>
            </form>
        </div>
        
        <!-- Side Panel -->
        <div class="auth-side-panel">
            <div class="auth-side-content">
                <h3>Join Thousands of Users</h3>
                <p>Create your account and get instant access to powerful business management tools that will transform the way you work.</p>
                <ul class="auth-features">
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>All-in-One Solution</span>
                    </li>
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>No Setup Fee</span>
                    </li>
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Upgrage as You Grow</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>


<style>
.auth-section { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: var(--color-gray-50); padding: var(--spacing-6); }
.auth-container { display: grid; grid-template-columns: 1fr 1fr; max-width: 1000px; width: 100%; background: var(--color-white); border-radius: var(--radius-2xl); overflow: hidden; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); }
.auth-card { padding: var(--spacing-10); }
.auth-header { text-align: center; margin-bottom: var(--spacing-8); }
.auth-logo { margin-bottom: var(--spacing-4); display: flex; justify-content: center; align-items: center; }
.auth-logo .brand-logo-img { max-width: 200px; max-height: 60px; width: auto; height: auto; object-fit: contain; }
.auth-logo .brand-logo-text { font-size: var(--font-size-3xl); font-weight: var(--font-weight-bold); background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.auth-title { font-size: var(--font-size-2xl); font-weight: var(--font-weight-bold); color: var(--color-gray-900); margin-bottom: var(--spacing-2); }
.auth-subtitle { color: var(--color-gray-600); font-size: var(--font-size-sm); }
.auth-alert { display: flex; align-items: center; gap: var(--spacing-3); padding: var(--spacing-4); border-radius: var(--radius-lg); margin-bottom: var(--spacing-6); }
.auth-alert-error { background-color: #fee2e2; color: #991b1b; }
.auth-alert-icon { width: 20px; height: 20px; flex-shrink: 0; }
.auth-form-group { margin-bottom: var(--spacing-5); }
.auth-label { display: block; font-size: var(--font-size-sm); font-weight: var(--font-weight-semibold); color: var(--color-gray-700); margin-bottom: var(--spacing-2); }
.auth-input-wrapper { position: relative; }
.auth-input-icon { position: absolute; left: var(--spacing-3); top: 50%; transform: translateY(-50%); width: 20px; height: 20px; color: var(--color-gray-400); }
.auth-input { width: 100%; padding: var(--spacing-3) var(--spacing-3) var(--spacing-3) var(--spacing-10); border: 2px solid var(--color-gray-200); border-radius: var(--radius-lg); font-size: var(--font-size-base); transition: all 0.2s; }
.auth-input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
.auth-input-error { border-color: #dc2626; }
.auth-error { color: #dc2626; font-size: var(--font-size-sm); margin-top: var(--spacing-2); }
.auth-success { color: #059669; font-size: var(--font-size-sm); margin-top: var(--spacing-2); }
</style>

<style>
.auth-button { width: 100%; padding: var(--spacing-4); background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: var(--color-white); border: none; border-radius: var(--radius-lg); font-size: var(--font-size-base); font-weight: var(--font-weight-semibold); cursor: pointer; display: flex; align-items: center; justify-content: center; gap: var(--spacing-2); transition: transform 0.2s, box-shadow 0.2s; margin-top: var(--spacing-6); }
.auth-button:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3); }
.auth-button:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
.auth-button-icon { width: 20px; height: 20px; }
.auth-footer { text-align: center; margin-top: var(--spacing-6); font-size: var(--font-size-sm); color: var(--color-gray-600); }
.auth-link-primary { color: #667eea; font-weight: var(--font-weight-semibold); text-decoration: none; }
.auth-link-primary:hover { text-decoration: underline; }
.auth-link-btn { background: none; border: none; color: #667eea; font-size: var(--font-size-sm); cursor: pointer; padding: var(--spacing-2); margin-top: var(--spacing-3); }
.auth-link-btn:hover:not(:disabled) { text-decoration: underline; }
.auth-link-btn:disabled { color: var(--color-gray-400); cursor: not-allowed; }
.auth-side-panel { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: var(--spacing-10); display: flex; align-items: center; justify-content: center; color: var(--color-white); }
.auth-side-content h3 { font-size: var(--font-size-3xl); font-weight: var(--font-weight-bold); margin-bottom: var(--spacing-4); }
.auth-side-content p { font-size: var(--font-size-base); line-height: 1.6; margin-bottom: var(--spacing-8); opacity: 0.95; }
.auth-features { list-style: none; padding: 0; margin: 0; }
.auth-features li { display: flex; align-items: center; gap: var(--spacing-3); margin-bottom: var(--spacing-4); font-size: var(--font-size-base); }
.auth-features svg { width: 24px; height: 24px; flex-shrink: 0; }
</style>

<style>
/* OTP Section Styles */
.otp-section { text-align: center; }
.otp-header { margin-bottom: var(--spacing-6); }
.otp-icon { color: #667eea; margin-bottom: var(--spacing-4); }
.otp-title { font-size: var(--font-size-xl); font-weight: var(--font-weight-bold); color: var(--color-gray-900); margin-bottom: var(--spacing-2); }
.otp-subtitle { color: var(--color-gray-600); font-size: var(--font-size-sm); }
.otp-subtitle span { font-weight: var(--font-weight-semibold); color: #667eea; }
.otp-input-group { display: flex; gap: var(--spacing-2); justify-content: center; margin-bottom: var(--spacing-4); }
.otp-input { width: 48px; height: 56px; text-align: center; font-size: var(--font-size-2xl); font-weight: var(--font-weight-bold); border: 2px solid var(--color-gray-200); border-radius: var(--radius-lg); transition: all 0.2s; }
.otp-input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
.otp-input.filled { border-color: #667eea; background-color: rgba(102, 126, 234, 0.05); }
.otp-input.error { border-color: #dc2626; }
.otp-input.success { border-color: #059669; background-color: rgba(5, 150, 105, 0.05); }
.otp-error { text-align: center; margin-bottom: var(--spacing-4); }
.otp-success { text-align: center; margin-bottom: var(--spacing-4); }
.otp-timer { color: var(--color-gray-500); font-size: var(--font-size-sm); margin-bottom: var(--spacing-4); }
.otp-timer span { font-weight: var(--font-weight-semibold); }
.otp-actions { display: flex; flex-direction: column; align-items: center; }
.email-verified-badge { display: flex; align-items: center; gap: var(--spacing-1); color: #059669; font-size: var(--font-size-sm); margin-top: var(--spacing-2); }
.email-verified-badge svg { color: #059669; }
@media (max-width: 768px) {
    .auth-container { grid-template-columns: 1fr; }
    .auth-side-panel { display: none; }
    .auth-card { padding: var(--spacing-6); }
    .otp-input { width: 40px; height: 48px; font-size: var(--font-size-xl); }
}
</style>


<script>
(function() {
    'use strict';
    
    const baseUrl = '<?php echo get_base_url(); ?>';
    let otpExpiryTime = null;
    let countdownInterval = null;
    let resendCooldown = 60;
    let resendInterval = null;
    let emailVerified = false;
    
    // DOM Elements
    const form = document.getElementById('register-form');
    const stepRegistration = document.getElementById('step-registration');
    const stepOtp = document.getElementById('step-otp');
    const btnSendOtp = document.getElementById('btn-send-otp');
    const btnVerifyOtp = document.getElementById('btn-verify-otp');
    const btnResendOtp = document.getElementById('btn-resend-otp');
    const btnChangeEmail = document.getElementById('btn-change-email');
    const otpInputs = document.querySelectorAll('.otp-input');
    const otpEmailDisplay = document.getElementById('otp-email-display');
    const otpError = document.getElementById('otp-error');
    const otpSuccess = document.getElementById('otp-success');
    const otpCountdown = document.getElementById('otp-countdown');
    let resendCountdownEl = document.getElementById('resend-countdown');
    const emailVerifiedBadge = document.getElementById('email-verified-badge');
    
    // Form fields
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');
    const passwordInput = document.getElementById('password');
    const passwordConfirmInput = document.getElementById('password_confirm');
    
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
    
    function showFieldError(input, message) {
        input.classList.add('auth-input-error');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'auth-error field-error';
        errorDiv.textContent = message;
        input.closest('.auth-form-group').appendChild(errorDiv);
    }
    
    function clearErrors() {
        document.querySelectorAll('.field-error').forEach(function(el) { el.remove(); });
        document.querySelectorAll('.auth-input-error').forEach(function(el) { el.classList.remove('auth-input-error'); });
    }
    
    function validateForm() {
        let isValid = true;
        clearErrors();
        
        if (!nameInput.value.trim()) {
            showFieldError(nameInput, 'Name is required');
            isValid = false;
        }
        
        if (!emailInput.value.trim()) {
            showFieldError(emailInput, 'Email is required');
            isValid = false;
        } else if (!isValidEmail(emailInput.value)) {
            showFieldError(emailInput, 'Invalid email format');
            isValid = false;
        }
        
        if (!phoneInput.value.trim()) {
            showFieldError(phoneInput, 'Phone number is required');
            isValid = false;
        }
        
        if (!passwordInput.value) {
            showFieldError(passwordInput, 'Password is required');
            isValid = false;
        } else if (passwordInput.value.length < 8) {
            showFieldError(passwordInput, 'Password must be at least 8 characters');
            isValid = false;
        }
        
        if (passwordInput.value !== passwordConfirmInput.value) {
            showFieldError(passwordConfirmInput, 'Passwords do not match');
            isValid = false;
        }
        
        return isValid;
    }
    
    function setLoading(button, loading, text) {
        if (loading) {
            button.disabled = true;
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = '<svg class="spinner" viewBox="0 0 24 24" width="20" height="20"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31.4" stroke-linecap="round"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/></circle></svg><span>' + (text || 'Processing...') + '</span>';
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText || text;
        }
    }
    
    function showOtpStep(email, expiresIn) {
        stepRegistration.style.display = 'none';
        stepOtp.style.display = 'block';
        otpEmailDisplay.textContent = email;
        
        nameInput.readOnly = true;
        emailInput.readOnly = true;
        phoneInput.readOnly = true;
        passwordInput.readOnly = true;
        passwordConfirmInput.readOnly = true;
        
        startOtpCountdown(expiresIn || 600);
        startResendCooldown();
        otpInputs[0].focus();
    }
    
    function showRegistrationStep() {
        stepOtp.style.display = 'none';
        stepRegistration.style.display = 'block';
        
        nameInput.readOnly = false;
        emailInput.readOnly = false;
        phoneInput.readOnly = false;
        passwordInput.readOnly = false;
        passwordConfirmInput.readOnly = false;
        
        otpInputs.forEach(function(input) {
            input.value = '';
            input.classList.remove('filled', 'error', 'success');
        });
        
        if (countdownInterval) clearInterval(countdownInterval);
        if (resendInterval) clearInterval(resendInterval);
        
        otpError.style.display = 'none';
        otpSuccess.style.display = 'none';
    }
    
    function startOtpCountdown(seconds) {
        if (countdownInterval) clearInterval(countdownInterval);
        otpExpiryTime = Date.now() + (seconds * 1000);
        
        countdownInterval = setInterval(function() {
            const remaining = Math.max(0, Math.floor((otpExpiryTime - Date.now()) / 1000));
            const mins = Math.floor(remaining / 60);
            const secs = remaining % 60;
            otpCountdown.textContent = mins + ':' + secs.toString().padStart(2, '0');
            
            if (remaining <= 0) {
                clearInterval(countdownInterval);
                otpError.textContent = 'Code expired. Please request a new one.';
                otpError.style.display = 'block';
                btnVerifyOtp.disabled = true;
            }
        }, 1000);
    }
    
    function startResendCooldown() {
        if (resendInterval) clearInterval(resendInterval);
        let remaining = resendCooldown;
        btnResendOtp.disabled = true;
        resendCountdownEl = document.getElementById('resend-countdown');
        if (resendCountdownEl) resendCountdownEl.textContent = '(' + remaining + 's)';
        
        resendInterval = setInterval(function() {
            remaining--;
            resendCountdownEl = document.getElementById('resend-countdown');
            if (remaining > 0) {
                if (resendCountdownEl) resendCountdownEl.textContent = '(' + remaining + 's)';
            } else {
                clearInterval(resendInterval);
                if (resendCountdownEl) resendCountdownEl.textContent = '';
                btnResendOtp.disabled = false;
            }
        }, 1000);
    }
    
    function sendOtp() {
        if (!validateForm()) return;
        
        const email = emailInput.value.trim();
        setLoading(btnSendOtp, true, 'Sending code...');
        
        fetch(baseUrl + '/api/send-otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                showOtpStep(email, data.expires_in);
            } else {
                if (data.cooldown) {
                    alert(data.error + ' (' + data.cooldown + ' seconds)');
                } else {
                    alert(data.error || 'Failed to send verification code');
                }
            }
        })
        .catch(function(error) {
            console.error('Send OTP error:', error);
            alert('An error occurred. Please try again.');
        })
        .finally(function() {
            setLoading(btnSendOtp, false, '<span>Create Account</span><svg class="auth-button-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>');
        });
    }
    
    function verifyOtp() {
        const otp = Array.from(otpInputs).map(function(input) { return input.value; }).join('');
        
        if (otp.length !== 6) {
            otpError.textContent = 'Please enter the complete 6-digit code';
            otpError.style.display = 'block';
            return;
        }
        
        setLoading(btnVerifyOtp, true, 'Verifying...');
        otpError.style.display = 'none';
        
        fetch(baseUrl + '/api/verify-otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: emailInput.value.trim(), otp: otp })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                emailVerified = true;
                otpInputs.forEach(function(input) { input.classList.add('success'); });
                otpSuccess.textContent = 'Email verified! Completing registration...';
                otpSuccess.style.display = 'block';
                setTimeout(function() { form.submit(); }, 1000);
            } else {
                otpInputs.forEach(function(input) { input.classList.add('error'); });
                otpError.textContent = data.error || 'Invalid code';
                otpError.style.display = 'block';
                setLoading(btnVerifyOtp, false, '<span>Verify & Complete Registration</span>');
                setTimeout(function() {
                    otpInputs.forEach(function(input) { input.classList.remove('error'); });
                }, 2000);
            }
        })
        .catch(function(error) {
            console.error('Verify OTP error:', error);
            otpError.textContent = 'An error occurred. Please try again.';
            otpError.style.display = 'block';
            setLoading(btnVerifyOtp, false, '<span>Verify & Complete Registration</span>');
        });
    }
    
    function resendOtp() {
        setLoading(btnResendOtp, true, 'Sending...');
        
        fetch(baseUrl + '/api/send-otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: emailInput.value.trim() })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                otpInputs.forEach(function(input) { input.value = ''; input.classList.remove('filled', 'error'); });
                otpInputs[0].focus();
                otpError.style.display = 'none';
                startOtpCountdown(data.expires_in || 600);
                startResendCooldown();
            } else {
                otpError.textContent = data.error || 'Failed to resend code';
                otpError.style.display = 'block';
            }
        })
        .catch(function(error) {
            console.error('Resend OTP error:', error);
            otpError.textContent = 'An error occurred. Please try again.';
            otpError.style.display = 'block';
        })
        .finally(function() {
            btnResendOtp.disabled = false;
            btnResendOtp.innerHTML = 'Resend Code <span id="resend-countdown"></span>';
        });
    }
    
    // OTP Input handling
    otpInputs.forEach(function(input, index) {
        input.addEventListener('input', function(e) {
            const value = e.target.value.replace(/[^0-9]/g, '');
            e.target.value = value;
            
            if (value) {
                e.target.classList.add('filled');
                if (index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
            } else {
                e.target.classList.remove('filled');
            }
            
            const allFilled = Array.from(otpInputs).every(function(inp) { return inp.value.length === 1; });
            btnVerifyOtp.disabled = !allFilled;
        });
        
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                otpInputs[index - 1].focus();
            }
        });
        
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedData = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '').slice(0, 6);
            
            pastedData.split('').forEach(function(char, i) {
                if (otpInputs[i]) {
                    otpInputs[i].value = char;
                    otpInputs[i].classList.add('filled');
                }
            });
            
            const lastIndex = Math.min(pastedData.length, otpInputs.length) - 1;
            if (lastIndex >= 0) otpInputs[lastIndex].focus();
            
            btnVerifyOtp.disabled = pastedData.length !== 6;
        });
    });
    
    // Event listeners
    btnSendOtp.addEventListener('click', sendOtp);
    btnVerifyOtp.addEventListener('click', verifyOtp);
    btnResendOtp.addEventListener('click', resendOtp);
    btnChangeEmail.addEventListener('click', showRegistrationStep);
    
    form.addEventListener('submit', function(e) {
        if (!emailVerified) {
            e.preventDefault();
            if (stepOtp.style.display === 'none') {
                sendOtp();
            }
        }
    });
})();
</script>

<?php
// Include footer
include_footer();
?>
