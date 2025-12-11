<?php
/**
 * Forgot Password Page
 * Multi-step password reset flow with email OTP verification
 */

require_once __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/app.php';

if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

require_once __DIR__ . '/../includes/auth_helpers.php';
startSecureSession();
require_once __DIR__ . '/../includes/template_helpers.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: ' . get_app_base_url() . '/app/dashboard.php');
    exit;
}

use Karyalay\Services\OtpService;
use Karyalay\Services\EmailService;
use Karyalay\Models\User;

$errors = [];
$success = false;
$email = '';

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // Validate
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['general'] = 'Invalid email address';
    } elseif (empty($password) || strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    } elseif ($password !== $password_confirm) {
        $errors['password_confirm'] = 'Passwords do not match';
    } else {
        // Verify OTP was completed
        $otpService = new OtpService();
        if (!$otpService->isEmailVerified($email)) {
            $errors['general'] = 'Please verify your email first';
        } else {
            // Update password
            $userModel = new User();
            $user = $userModel->findByEmail($email);
            
            if ($user) {
                $updated = $userModel->update($user['id'], ['password' => $password]);
                
                if ($updated) {
                    // Clean up OTP records
                    $otpService->cleanupVerifiedOtp($email);
                    
                    // Send confirmation email
                    $emailService = new EmailService();
                    $emailService->sendPasswordResetConfirmation($email, $user['name']);
                    
                    $success = true;
                } else {
                    $errors['general'] = 'Failed to update password. Please try again.';
                }
            } else {
                $errors['general'] = 'User not found';
            }
        }
    }
}

$page_title = 'Forgot Password';
$page_description = 'Reset your ' . get_brand_name() . ' account password';

include_header($page_title, $page_description);
?>

<!-- Forgot Password Section -->
<section class="auth-section">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <?php echo render_brand_logo('light_bg', 'auth-logo-img', 50); ?>
                </div>
                <h2 class="auth-title">Reset Password</h2>
                <p class="auth-subtitle">We'll help you get back into your account</p>
            </div>
            
            <?php if ($success): ?>
            <!-- Success State -->
            <div class="reset-success">
                <div class="reset-success-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="64" height="64">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3>Password Reset Successful!</h3>
                <p>Your password has been updated. A confirmation email has been sent to your address.</p>
                <a href="<?php echo get_base_url(); ?>/login.php" class="auth-button">
                    <span>Sign In</span>
                    <svg class="auth-button-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </a>
            </div>
            <?php else: ?>
            
            <?php if (isset($errors['general'])): ?>
            <div class="auth-alert auth-alert-error">
                <svg class="auth-alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span><?php echo esc_html($errors['general']); ?></span>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo get_base_url(); ?>/forgot-password.php" id="forgot-password-form" class="auth-form" novalidate>
                <input type="hidden" name="action" value="reset_password">
                
                <!-- Step 1: Email Input -->
                <div id="step-email">
                    <div class="step-indicator">
                        <span class="step-badge active">1</span>
                        <span class="step-text">Enter your email address</span>
                    </div>
                    
                    <div class="auth-form-group">
                        <label for="email" class="auth-label">Email Address</label>
                        <div class="auth-input-wrapper">
                            <svg class="auth-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                            </svg>
                            <input type="email" id="email" name="email" class="auth-input" 
                                value="<?php echo esc_attr($email); ?>" placeholder="you@example.com" required>
                        </div>
                        <div id="email-error" class="auth-error" style="display: none;"></div>
                    </div>
                    
                    <button type="button" class="auth-button" id="btn-send-code">
                        <span>Send Verification Code</span>
                        <svg class="auth-button-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </div>

                <!-- Step 2: OTP Verification -->
                <div id="step-otp" style="display: none;">
                    <div class="step-indicator">
                        <span class="step-badge completed">1</span>
                        <span class="step-badge active">2</span>
                        <span class="step-text">Verify your email</span>
                    </div>
                    
                    <div class="otp-section">
                        <div class="otp-header">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="40" height="40">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <p>We've sent a 6-digit code to <strong id="otp-email-display"></strong></p>
                        </div>
                        
                        <div class="otp-input-group">
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="0">
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="1">
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="2">
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="3">
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="4">
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="5">
                        </div>
                        
                        <div id="otp-error" class="auth-error" style="display: none;"></div>
                        
                        <div class="otp-timer">Code expires in <span id="otp-countdown">10:00</span></div>
                        
                        <div class="otp-actions">
                            <button type="button" class="auth-button" id="btn-verify-otp" disabled>Verify Code</button>
                            <button type="button" class="btn-link" id="btn-resend-otp" disabled>Resend Code <span id="resend-countdown"></span></button>
                            <button type="button" class="btn-link" id="btn-change-email">Change Email</button>
                        </div>
                    </div>
                </div>
                
                <!-- Step 3: New Password -->
                <div id="step-password" style="display: none;">
                    <div class="step-indicator">
                        <span class="step-badge completed">1</span>
                        <span class="step-badge completed">2</span>
                        <span class="step-badge active">3</span>
                        <span class="step-text">Set new password</span>
                    </div>
                    
                    <div class="auth-form-group">
                        <label for="password" class="auth-label">New Password</label>
                        <div class="auth-input-wrapper">
                            <svg class="auth-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <input type="password" id="password" name="password" class="auth-input" placeholder="Minimum 8 characters" required>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                        <div class="auth-error"><?php echo esc_html($errors['password']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="auth-form-group">
                        <label for="password_confirm" class="auth-label">Confirm New Password</label>
                        <div class="auth-input-wrapper">
                            <svg class="auth-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <input type="password" id="password_confirm" name="password_confirm" class="auth-input" placeholder="Re-enter your password" required>
                        </div>
                        <?php if (isset($errors['password_confirm'])): ?>
                        <div class="auth-error"><?php echo esc_html($errors['password_confirm']); ?></div>
                        <?php endif; ?>
                        <div id="password-error" class="auth-error" style="display: none;"></div>
                    </div>
                    
                    <button type="submit" class="auth-button" id="btn-reset-password">
                        <span>Reset Password</span>
                        <svg class="auth-button-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </button>
                </div>
                
                <!-- Back to Login -->
                <div class="auth-footer">
                    <p>Remember your password? <a href="<?php echo get_base_url(); ?>/login.php" class="auth-link-primary">Sign in</a></p>
                </div>
            </form>
            <?php endif; ?>
        </div>
        
        <!-- Side Panel -->
        <div class="auth-side-panel">
            <div class="auth-side-content">
                <h3>Secure Password Reset</h3>
                <p>We take your account security seriously. Follow the steps to safely reset your password.</p>
                <ul class="auth-features">
                    <li><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><span>Email verification required</span></li>
                    <li><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><span>Secure OTP authentication</span></li>
                    <li><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><span>Confirmation email sent</span></li>
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
.auth-button { width: 100%; padding: var(--spacing-4); background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: var(--color-white); border: none; border-radius: var(--radius-lg); font-size: var(--font-size-base); font-weight: var(--font-weight-semibold); cursor: pointer; display: flex; align-items: center; justify-content: center; gap: var(--spacing-2); transition: transform 0.2s, box-shadow 0.2s; margin-top: var(--spacing-4); text-decoration: none; }
.auth-button:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3); }
.auth-button:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
.auth-button-icon { width: 20px; height: 20px; }
.auth-footer { text-align: center; margin-top: var(--spacing-6); font-size: var(--font-size-sm); color: var(--color-gray-600); }
.auth-link-primary { color: #667eea; font-weight: var(--font-weight-semibold); text-decoration: none; }
.auth-link-primary:hover { text-decoration: underline; }
.auth-side-panel { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: var(--spacing-10); display: flex; align-items: center; justify-content: center; color: var(--color-white); }
.auth-side-content h3 { font-size: var(--font-size-3xl); font-weight: var(--font-weight-bold); margin-bottom: var(--spacing-4); }
.auth-side-content p { font-size: var(--font-size-base); line-height: 1.6; margin-bottom: var(--spacing-8); opacity: 0.95; }
.auth-features { list-style: none; padding: 0; margin: 0; }
.auth-features li { display: flex; align-items: center; gap: var(--spacing-3); margin-bottom: var(--spacing-4); font-size: var(--font-size-base); }
.auth-features svg { width: 24px; height: 24px; flex-shrink: 0; }

/* Step Indicator */
.step-indicator { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
.step-badge { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; font-weight: 600; background: #e5e7eb; color: #6b7280; }
.step-badge.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.step-badge.completed { background: #059669; color: white; }
.step-text { color: #6b7280; font-size: 0.875rem; margin-left: 0.5rem; }

/* OTP Styles */
.otp-section { text-align: center; }
.otp-header { margin-bottom: 1.5rem; }
.otp-header svg { color: #667eea; margin-bottom: 0.75rem; }
.otp-header p { color: #6b7280; font-size: 0.9375rem; margin: 0; }
.otp-header strong { color: #667eea; }
.otp-input-group { display: flex; gap: 0.5rem; justify-content: center; margin-bottom: 1rem; }
.otp-input { width: 46px; height: 54px; text-align: center; font-size: 1.25rem; font-weight: 600; border: 2px solid #e5e7eb; border-radius: 8px; transition: all 0.2s; }
.otp-input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
.otp-input.filled { border-color: #667eea; background-color: rgba(102, 126, 234, 0.05); }
.otp-input.error { border-color: #dc2626; }
.otp-input.success { border-color: #059669; background-color: rgba(5, 150, 105, 0.05); }
.otp-timer { color: #6b7280; font-size: 0.875rem; margin-bottom: 1rem; }
.otp-timer span { font-weight: 600; }
.otp-actions { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; }
.btn-link { background: none; border: none; color: #667eea; font-size: 0.875rem; cursor: pointer; padding: 0.5rem; }
.btn-link:hover:not(:disabled) { text-decoration: underline; }
.btn-link:disabled { color: #9ca3af; cursor: not-allowed; }

/* Success State */
.reset-success { text-align: center; padding: 2rem 0; }
.reset-success-icon { color: #059669; margin-bottom: 1rem; }
.reset-success h3 { font-size: 1.5rem; font-weight: 700; color: #1f2937; margin: 0 0 0.75rem 0; }
.reset-success p { color: #6b7280; margin: 0 0 1.5rem 0; }

@media (max-width: 768px) {
    .auth-container { grid-template-columns: 1fr; }
    .auth-side-panel { display: none; }
    .auth-card { padding: var(--spacing-6); }
    .otp-input { width: 40px; height: 48px; font-size: 1.125rem; }
}
</style>


<script>
(function() {
    'use strict';
    
    const baseUrl = '<?php echo get_base_url(); ?>';
    let countdownInterval = null;
    let resendInterval = null;
    let otpExpiryTime = null;
    let verifiedEmail = '';
    
    // Elements
    const form = document.getElementById('forgot-password-form');
    const stepEmail = document.getElementById('step-email');
    const stepOtp = document.getElementById('step-otp');
    const stepPassword = document.getElementById('step-password');
    const emailInput = document.getElementById('email');
    const emailError = document.getElementById('email-error');
    const btnSendCode = document.getElementById('btn-send-code');
    const btnVerifyOtp = document.getElementById('btn-verify-otp');
    const btnResendOtp = document.getElementById('btn-resend-otp');
    const btnChangeEmail = document.getElementById('btn-change-email');
    const otpInputs = document.querySelectorAll('.otp-input');
    const otpEmailDisplay = document.getElementById('otp-email-display');
    const otpError = document.getElementById('otp-error');
    const otpCountdown = document.getElementById('otp-countdown');
    const resendCountdown = document.getElementById('resend-countdown');
    const passwordInput = document.getElementById('password');
    const passwordConfirmInput = document.getElementById('password_confirm');
    const passwordError = document.getElementById('password-error');
    
    if (!form) return;
    
    // Send code button
    btnSendCode.addEventListener('click', sendCode);
    
    // Verify OTP button
    btnVerifyOtp.addEventListener('click', verifyOtp);
    
    // Resend button
    btnResendOtp.addEventListener('click', sendCode);
    
    // Change email button
    btnChangeEmail.addEventListener('click', function() {
        showStep('email');
    });
    
    // Form submission validation
    form.addEventListener('submit', function(e) {
        const password = passwordInput.value;
        const confirm = passwordConfirmInput.value;
        
        if (password.length < 8) {
            e.preventDefault();
            passwordError.textContent = 'Password must be at least 8 characters';
            passwordError.style.display = 'block';
            return;
        }
        
        if (password !== confirm) {
            e.preventDefault();
            passwordError.textContent = 'Passwords do not match';
            passwordError.style.display = 'block';
            return;
        }
        
        passwordError.style.display = 'none';
    });
    
    // OTP input handling
    otpInputs.forEach(function(input, index) {
        input.addEventListener('input', function(e) {
            const value = e.target.value.replace(/[^0-9]/g, '');
            e.target.value = value;
            if (value) {
                e.target.classList.add('filled');
                if (index < otpInputs.length - 1) otpInputs[index + 1].focus();
            } else {
                e.target.classList.remove('filled');
            }
            btnVerifyOtp.disabled = !Array.from(otpInputs).every(function(inp) { return inp.value.length === 1; });
        });
        
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !e.target.value && index > 0) otpInputs[index - 1].focus();
        });
        
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedData = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '').slice(0, 6);
            pastedData.split('').forEach(function(char, i) {
                if (otpInputs[i]) { otpInputs[i].value = char; otpInputs[i].classList.add('filled'); }
            });
            btnVerifyOtp.disabled = pastedData.length !== 6;
        });
    });
    
    function sendCode() {
        const email = emailInput.value.trim();
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            emailError.textContent = 'Please enter a valid email address';
            emailError.style.display = 'block';
            return;
        }
        emailError.style.display = 'none';
        
        setLoading(btnSendCode, true, 'Sending...');
        
        fetch(baseUrl + '/api/send-otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email, purpose: 'password_reset' })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                verifiedEmail = email;
                otpEmailDisplay.textContent = email;
                showStep('otp');
                startCountdown(data.expires_in || 600);
                startResendCooldown();
                otpInputs[0].focus();
            } else {
                emailError.textContent = data.error || 'Failed to send code';
                emailError.style.display = 'block';
            }
        })
        .catch(function() {
            emailError.textContent = 'An error occurred. Please try again.';
            emailError.style.display = 'block';
        })
        .finally(function() {
            setLoading(btnSendCode, false, '<span>Send Verification Code</span><svg class="auth-button-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>');
        });
    }
    
    function verifyOtp() {
        const otp = Array.from(otpInputs).map(function(i) { return i.value; }).join('');
        if (otp.length !== 6) return;
        
        setLoading(btnVerifyOtp, true, 'Verifying...');
        otpError.style.display = 'none';
        
        fetch(baseUrl + '/api/verify-otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: verifiedEmail, otp: otp })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                otpInputs.forEach(function(i) { i.classList.add('success'); });
                setTimeout(function() { showStep('password'); passwordInput.focus(); }, 500);
            } else {
                otpInputs.forEach(function(i) { i.classList.add('error'); });
                otpError.textContent = data.error || 'Invalid code';
                otpError.style.display = 'block';
                setLoading(btnVerifyOtp, false, 'Verify Code');
                setTimeout(function() { otpInputs.forEach(function(i) { i.classList.remove('error'); }); }, 2000);
            }
        })
        .catch(function() {
            otpError.textContent = 'An error occurred. Please try again.';
            otpError.style.display = 'block';
            setLoading(btnVerifyOtp, false, 'Verify Code');
        });
    }
    
    function showStep(step) {
        stepEmail.style.display = step === 'email' ? 'block' : 'none';
        stepOtp.style.display = step === 'otp' ? 'block' : 'none';
        stepPassword.style.display = step === 'password' ? 'block' : 'none';
        
        if (step === 'email') {
            if (countdownInterval) clearInterval(countdownInterval);
            if (resendInterval) clearInterval(resendInterval);
            clearOtpInputs();
        }
    }
    
    function clearOtpInputs() {
        otpInputs.forEach(function(i) { i.value = ''; i.classList.remove('filled', 'error', 'success'); });
        btnVerifyOtp.disabled = true;
        btnVerifyOtp.textContent = 'Verify Code';
        otpError.style.display = 'none';
    }
    
    function setLoading(btn, loading, text) {
        if (loading) {
            btn.disabled = true;
            btn.dataset.originalText = btn.innerHTML;
            btn.innerHTML = '<svg class="spinner" viewBox="0 0 24 24" width="20" height="20"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31.4" stroke-linecap="round"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/></circle></svg><span>' + text + '</span>';
        } else {
            btn.disabled = false;
            btn.innerHTML = text;
        }
    }
    
    function startCountdown(seconds) {
        if (countdownInterval) clearInterval(countdownInterval);
        otpExpiryTime = Date.now() + (seconds * 1000);
        countdownInterval = setInterval(function() {
            const remaining = Math.max(0, Math.floor((otpExpiryTime - Date.now()) / 1000));
            otpCountdown.textContent = Math.floor(remaining / 60) + ':' + (remaining % 60).toString().padStart(2, '0');
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
        let remaining = 60;
        btnResendOtp.disabled = true;
        resendCountdown.textContent = '(' + remaining + 's)';
        resendInterval = setInterval(function() {
            remaining--;
            if (remaining > 0) {
                resendCountdown.textContent = '(' + remaining + 's)';
            } else {
                clearInterval(resendInterval);
                resendCountdown.textContent = '';
                btnResendOtp.disabled = false;
            }
        }, 1000);
    }
})();
</script>

<?php include_footer(); ?>
