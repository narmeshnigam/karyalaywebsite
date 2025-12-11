<?php

/**
 * SellerPortal System
 * About Page
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

use Karyalay\Models\Setting;

// Load about page content from settings
$settingModel = new Setting();
$aboutKeys = [
    'about_our_story',
    'about_our_vision',
    'about_our_mission',
    'about_value_1_title',
    'about_value_1_desc',
    'about_value_1_image',
    'about_value_2_title',
    'about_value_2_desc',
    'about_value_2_image',
    'about_value_3_title',
    'about_value_3_desc',
    'about_value_3_image'
];

$settings = $settingModel->getMultiple($aboutKeys);

// Set defaults
$ourStory = $settings['about_our_story'] ?? 'Karyalay was founded with a simple yet powerful vision: to make business management accessible, efficient, and effective for organizations of all sizes. We understand the challenges that businesses face in managing their operations, from customer relationships to subscriptions and support.

Our journey began when we recognized that many businesses were struggling with fragmented systems and complex workflows. We set out to create a comprehensive platform that brings everything together in one place, making it easier for businesses to focus on what they do best.

Over the years, we have grown from a small startup to a trusted partner for businesses across various industries. Our team of dedicated professionals works tirelessly to develop innovative solutions that address real-world challenges. We believe in continuous improvement and are committed to staying ahead of industry trends.

Today, Karyalay serves businesses across various industries, helping them streamline their operations, improve customer satisfaction, and drive sustainable growth. We are proud to be a trusted partner in their success and look forward to continuing this journey together.';

$ourVision = $settings['about_our_vision'] ?? 'To be the leading business management platform that empowers organizations worldwide to achieve operational excellence and sustainable growth through innovative technology solutions.';

$ourMission = $settings['about_our_mission'] ?? 'To provide businesses with intuitive, powerful, and reliable tools that simplify complex operations, enhance productivity, and enable them to deliver exceptional value to their customers.';

$coreValues = [
    [
        'title' => $settings['about_value_1_title'] ?? 'Excellence',
        'desc' => $settings['about_value_1_desc'] ?? 'We strive for excellence in everything we do, from product development to customer support.',
        'image' => $settings['about_value_1_image'] ?? ''
    ],
    [
        'title' => $settings['about_value_2_title'] ?? 'Customer Focus',
        'desc' => $settings['about_value_2_desc'] ?? 'Our customers are at the heart of everything we do. Their success is our success.',
        'image' => $settings['about_value_2_image'] ?? ''
    ],
    [
        'title' => $settings['about_value_3_title'] ?? 'Innovation',
        'desc' => $settings['about_value_3_desc'] ?? 'We continuously innovate to stay ahead and provide cutting-edge solutions to our customers.',
        'image' => $settings['about_value_3_image'] ?? ''
    ]
];

$page_title = 'About Us';
$page_description = 'Learn about Karyalay and our mission to transform business operations';

include_header($page_title, $page_description);
?>

<!-- Hero Section -->
<section class="about-hero">
    <div class="container">
        <div class="about-hero-content">
            <h1 class="about-hero-title">About Karyalay</h1>
            <p class="about-hero-subtitle">
                Empowering businesses with innovative management solutions since day one
            </p>
        </div>
    </div>
</section>

<!-- Our Story Section -->
<section class="about-story-section">
    <div class="container">
        <div class="about-story-wrapper">
            <div class="about-story-header">
                <span class="about-section-label">Our Journey</span>
                <h2 class="about-section-title">Our Story</h2>
            </div>
            <div class="about-story-content">
                <?php 
                $paragraphs = explode("\n\n", $ourStory);
                foreach ($paragraphs as $paragraph): 
                    $paragraph = trim($paragraph);
                    if (!empty($paragraph)):
                ?>
                    <p><?php echo htmlspecialchars($paragraph); ?></p>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        </div>
    </div>
</section>

<!-- Vision & Mission Section -->
<section class="about-vm-section">
    <div class="container">
        <div class="about-vm-grid">
            <div class="about-vm-card about-vision-card">
                <div class="about-vm-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </div>
                <h3 class="about-vm-title">Our Vision</h3>
                <p class="about-vm-text"><?php echo htmlspecialchars($ourVision); ?></p>
            </div>
            
            <div class="about-vm-card about-mission-card">
                <div class="about-vm-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <h3 class="about-vm-title">Our Mission</h3>
                <p class="about-vm-text"><?php echo htmlspecialchars($ourMission); ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Core Values Section -->
<section class="about-values-section">
    <div class="container">
        <div class="about-values-header">
            <span class="about-section-label">What We Stand For</span>
            <h2 class="about-section-title">Our Core Values</h2>
        </div>
        
        <div class="about-values-grid">
            <?php foreach ($coreValues as $index => $value): ?>
                <div class="about-value-card">
                    <?php if (!empty($value['image'])): ?>
                        <div class="about-value-image">
                            <img src="<?php echo htmlspecialchars($value['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($value['title']); ?>"
                                 loading="lazy">
                        </div>
                    <?php else: ?>
                        <div class="about-value-icon">
                            <?php if ($index === 0): ?>
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            <?php elseif ($index === 1): ?>
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            <?php else: ?>
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                </svg>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <h3 class="about-value-title"><?php echo htmlspecialchars($value['title']); ?></h3>
                    <p class="about-value-desc"><?php echo htmlspecialchars($value['desc']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA Section -->
<?php
$cta_title = "Join Us on Our Journey";
$cta_subtitle = "Discover how Karyalay can transform your business operations and help you achieve your goals";
$cta_source = "about-page";
include __DIR__ . '/../templates/cta-form.php';
?>

<style>
/* About Hero */
.about-hero {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 50%, #a7f3d0 100%);
    padding: var(--spacing-16) 0;
    position: relative;
    overflow: hidden;
}

.about-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 50%, rgba(16, 185, 129, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(5, 150, 105, 0.08) 0%, transparent 50%);
    pointer-events: none;
}

.about-hero-content {
    position: relative;
    z-index: 1;
    text-align: center;
    max-width: 700px;
    margin: 0 auto;
}

.about-hero-title {
    font-size: var(--font-size-4xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-4) 0;
    line-height: 1.2;
}

.about-hero-subtitle {
    font-size: var(--font-size-lg);
    color: var(--color-gray-600);
    margin: 0;
    line-height: 1.6;
}

/* Section Labels & Titles */
.about-section-label {
    display: inline-block;
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-primary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: var(--spacing-2);
}

.about-section-title {
    font-size: var(--font-size-3xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0;
}

/* Our Story Section */
.about-story-section {
    padding: var(--spacing-16) 0;
    background: var(--color-white);
}

.about-story-wrapper {
    max-width: 800px;
    margin: 0 auto;
}

.about-story-header {
    text-align: center;
    margin-bottom: var(--spacing-10);
}

.about-story-content {
    font-size: var(--font-size-lg);
    line-height: 1.8;
    color: var(--color-gray-700);
}

.about-story-content p {
    margin-bottom: var(--spacing-6);
}

.about-story-content p:last-child {
    margin-bottom: 0;
}

/* Vision & Mission Section */
.about-vm-section {
    padding: var(--spacing-16) 0;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
}

.about-vm-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--spacing-8);
    max-width: 1000px;
    margin: 0 auto;
}

.about-vm-card {
    background: var(--color-white);
    border-radius: var(--radius-xl);
    padding: var(--spacing-8);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    text-align: center;
    transition: transform 0.3s, box-shadow 0.3s;
}

.about-vm-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
}

.about-vm-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto var(--spacing-5);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--radius-lg);
}

.about-vision-card .about-vm-icon {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #2563eb;
}

.about-mission-card .about-vm-icon {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #d97706;
}

.about-vm-icon svg {
    width: 32px;
    height: 32px;
}

.about-vm-title {
    font-size: var(--font-size-xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-4) 0;
}

.about-vm-text {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
    line-height: 1.7;
    margin: 0;
}

/* Core Values Section */
.about-values-section {
    padding: var(--spacing-16) 0;
    background: var(--color-white);
}

.about-values-header {
    text-align: center;
    margin-bottom: var(--spacing-12);
}

.about-values-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--spacing-6);
    max-width: 1100px;
    margin: 0 auto;
}

.about-value-card {
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: var(--radius-xl);
    padding: var(--spacing-8);
    text-align: center;
    transition: transform 0.3s, box-shadow 0.3s;
    border: 1px solid var(--color-gray-100);
}

.about-value-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
    border-color: var(--color-primary);
}

.about-value-image {
    width: 80px;
    height: 80px;
    margin: 0 auto var(--spacing-5);
    border-radius: var(--radius-lg);
    overflow: hidden;
}

.about-value-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.about-value-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto var(--spacing-5);
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--color-primary) 0%, #7c3aed 100%);
    border-radius: var(--radius-lg);
    color: var(--color-white);
}

.about-value-icon svg {
    width: 32px;
    height: 32px;
}

.about-value-title {
    font-size: var(--font-size-xl);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-3) 0;
}

.about-value-desc {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
    line-height: 1.6;
    margin: 0;
}

/* Responsive */
@media (max-width: 1024px) {
    .about-values-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .about-hero {
        padding: var(--spacing-12) 0;
    }
    
    .about-hero-title {
        font-size: var(--font-size-3xl);
    }
    
    .about-story-section,
    .about-vm-section,
    .about-values-section {
        padding: var(--spacing-12) 0;
    }
    
    .about-vm-grid {
        grid-template-columns: 1fr;
        gap: var(--spacing-6);
    }
    
    .about-values-grid {
        grid-template-columns: 1fr;
    }
    
    .about-story-content {
        font-size: var(--font-size-base);
    }
}
</style>

<?php include_footer(); ?>
