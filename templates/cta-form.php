<?php
/**
 * Reusable CTA Form Component
 * 
 * Usage: include this file where you want the CTA to appear
 * Optional variables:
 * - $cta_title: Custom title (default: "Ready to Transform Your Business?")
 * - $cta_subtitle: Custom subtitle
 * - $cta_source: Source identifier for tracking (default: current page)
 */

$cta_title = $cta_title ?? "Ready to Transform Your Business?";
$cta_subtitle = $cta_subtitle ?? "Get in touch with us today and discover how " . get_brand_name() . " can streamline your operations";
$cta_source = $cta_source ?? ($_SERVER['REQUEST_URI'] ?? 'unknown');
?>

<section class="cta-section">
    <div class="cta-container">
        <div class="cta-grid">
            <!-- Left Side: CTA Content -->
            <div class="cta-content-side">
                <div class="cta-content-wrapper">
                    <h2 class="cta-main-title"><?php echo htmlspecialchars($cta_title); ?></h2>
                    <p class="cta-main-subtitle"><?php echo htmlspecialchars($cta_subtitle); ?></p>
                    
                    <ul class="cta-benefits">
                        <li>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Quick Response Time</span>
                        </li>
                        <li>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Expert Consultation</span>
                        </li>
                        <li>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Tailored Solutions</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Right Side: Form -->
            <div class="cta-form-side">
                <form id="ctaForm" class="cta-form" method="POST" action="<?php echo get_base_url(); ?>/submit-lead.php">
                    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
                    <input type="hidden" name="source" value="<?php echo htmlspecialchars($cta_source); ?>">
                    
                    <div class="cta-form-group">
                        <input type="text" name="name" class="cta-form-input" placeholder="Your Name *" required>
                    </div>
                    
                    <div class="cta-form-group">
                        <input type="email" name="email" class="cta-form-input" placeholder="Email Address *" required>
                    </div>
                    
                    <div class="cta-form-group">
                        <?php echo render_phone_input([
                            'id' => 'cta-phone',
                            'name' => 'phone',
                            'value' => '',
                            'required' => false,
                            'class' => 'cta-phone-wrapper',
                        ]); ?>
                    </div>
                    
                    <div class="cta-form-group">
                        <input type="text" name="company" class="cta-form-input" placeholder="Company Name">
                    </div>
                    
                    <div class="cta-form-group">
                        <textarea name="message" class="cta-form-textarea" rows="3" placeholder="Tell us about your needs..."></textarea>
                    </div>
                    
                    <button type="submit" class="cta-form-submit">
                        <span class="cta-form-submit-text">Submit</span>
                        <span class="cta-form-submit-icon">→</span>
                    </button>
                    
                    <div id="ctaFormMessage" class="cta-form-message" style="display: none;"></div>
                </form>
            </div>
        </div>
    </div>
</section>

<style>
.cta-section {
    padding: var(--spacing-16) 0;
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cta-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(139, 92, 246, 0.1) 0%, transparent 50%);
    pointer-events: none;
}

.cta-container {
    max-width: 1100px;
    width: 100%;
    margin: 0 auto;
    padding: 0 var(--spacing-6);
    position: relative;
    z-index: 1;
}

.cta-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    background: var(--color-white);
    border-radius: var(--radius-2xl);
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.cta-content-side {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    padding: var(--spacing-10);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-white);
    position: relative;
}

.cta-content-side::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.15) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(139, 92, 246, 0.15) 0%, transparent 50%);
    pointer-events: none;
}

.cta-content-wrapper {
    position: relative;
    z-index: 1;
}

.cta-main-title {
    font-size: var(--font-size-3xl);
    font-weight: var(--font-weight-bold);
    margin: 0 0 var(--spacing-4) 0;
    line-height: 1.2;
}

.cta-main-subtitle {
    font-size: var(--font-size-base);
    line-height: 1.6;
    margin-bottom: var(--spacing-8);
    opacity: 0.95;
}

.cta-benefits {
    list-style: none;
    padding: 0;
    margin: 0;
}

.cta-benefits li {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    margin-bottom: var(--spacing-4);
    font-size: var(--font-size-base);
}

.cta-benefits svg {
    width: 24px;
    height: 24px;
    flex-shrink: 0;
}

.cta-form-side {
    padding: var(--spacing-10);
    background: var(--color-white);
}

.cta-form-group {
    margin-bottom: var(--spacing-4);
}

/* Phone input wrapper styling for CTA form */
.cta-phone-wrapper {
    border: 2px solid var(--color-gray-200);
    border-radius: var(--radius-lg);
}

.cta-phone-wrapper:focus-within {
    border-color: #1e293b;
    box-shadow: 0 0 0 3px rgba(30, 41, 59, 0.1);
}

.cta-phone-wrapper .phone-isd-prefix {
    background: var(--color-gray-100);
}

.cta-phone-wrapper .phone-input-field {
    padding: var(--spacing-3);
}

.cta-form-input,
.cta-form-textarea {
    width: 100%;
    padding: var(--spacing-3);
    border: 2px solid var(--color-gray-200);
    border-radius: var(--radius-lg);
    font-size: var(--font-size-base);
    font-family: inherit;
    transition: all 0.2s;
    box-sizing: border-box;
}

.cta-form-input:focus,
.cta-form-textarea:focus {
    outline: none;
    border-color: #1e293b;
    box-shadow: 0 0 0 3px rgba(30, 41, 59, 0.1);
}

.cta-form-textarea {
    resize: vertical;
}

.cta-form-submit {
    width: 100%;
    padding: var(--spacing-4);
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    color: var(--color-white);
    border: none;
    border-radius: var(--radius-lg);
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-semibold);
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-2);
}

.cta-form-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(30, 41, 59, 0.3);
}

.cta-form-submit:active {
    transform: translateY(0);
}

.cta-form-submit-icon {
    transition: transform 0.2s;
}

.cta-form-submit:hover .cta-form-submit-icon {
    transform: translateX(4px);
}

.cta-form-message {
    margin-top: var(--spacing-4);
    padding: var(--spacing-3);
    border-radius: var(--radius-md);
    text-align: center;
    font-size: var(--font-size-sm);
}

.cta-form-message.success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #6ee7b7;
}

.cta-form-message.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

@media (max-width: 768px) {
    .cta-section {
        padding: var(--spacing-10) 0;
    }
    
    .cta-grid {
        grid-template-columns: 1fr;
    }
    
    .cta-content-side {
        padding: var(--spacing-8);
    }
    
    .cta-form-side {
        padding: var(--spacing-6);
    }
    
    .cta-main-title {
        font-size: var(--font-size-2xl);
    }
    
    .cta-main-subtitle {
        font-size: var(--font-size-sm);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctaForm = document.getElementById('ctaForm');
    if (!ctaForm) return;
    
    ctaForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const form = this;
        const submitBtn = form.querySelector('.cta-form-submit');
        const messageDiv = document.getElementById('ctaFormMessage');
        const originalText = submitBtn.innerHTML;
        
        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span>Submitting...</span>';
        
        try {
            const formData = new FormData(form);
            
            console.log('Submitting to:', form.action);
            
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });
            
            console.log('Response status:', response.status);
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Server did not return JSON');
            }
            
            const result = await response.json();
            console.log('Result:', result);
            
            if (result.success) {
                messageDiv.className = 'cta-form-message success';
                messageDiv.textContent = result.message || 'Thank you! We\'ll be in touch soon.';
                messageDiv.style.display = 'block';
                form.reset();
            } else {
                messageDiv.className = 'cta-form-message error';
                messageDiv.textContent = result.message || 'Something went wrong. Please try again.';
                messageDiv.style.display = 'block';
            }
        } catch (error) {
            console.error('Form submission error:', error);
            messageDiv.className = 'cta-form-message error';
            messageDiv.textContent = 'Network error. Please try again. Check console for details.';
            messageDiv.style.display = 'block';
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            
            // Hide message after 5 seconds
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        }
    });
});
</script>
