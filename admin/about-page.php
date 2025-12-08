<?php
/**
 * Admin About Page Content Management
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

if (!isAuthenticated() || !isAdmin()) {
    header('Location: ' . get_base_url() . '/login.php');
    exit;
}

require_once __DIR__ . '/../includes/template_helpers.php';

use Karyalay\Models\Setting;

$settingModel = new Setting();
$errors = [];
$success = false;

// Define setting keys for about page
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $settings = [
            'about_our_story' => trim($_POST['our_story'] ?? ''),
            'about_our_vision' => trim($_POST['our_vision'] ?? ''),
            'about_our_mission' => trim($_POST['our_mission'] ?? ''),
            'about_value_1_title' => trim($_POST['value_1_title'] ?? ''),
            'about_value_1_desc' => trim($_POST['value_1_desc'] ?? ''),
            'about_value_1_image' => trim($_POST['value_1_image'] ?? ''),
            'about_value_2_title' => trim($_POST['value_2_title'] ?? ''),
            'about_value_2_desc' => trim($_POST['value_2_desc'] ?? ''),
            'about_value_2_image' => trim($_POST['value_2_image'] ?? ''),
            'about_value_3_title' => trim($_POST['value_3_title'] ?? ''),
            'about_value_3_desc' => trim($_POST['value_3_desc'] ?? ''),
            'about_value_3_image' => trim($_POST['value_3_image'] ?? '')
        ];

        if ($settingModel->setMultiple($settings)) {
            $success = true;
            $_SESSION['admin_success'] = 'About page content updated successfully!';
        } else {
            $errors[] = 'Failed to save settings. Please try again.';
        }
    }
}

// Get current values
$currentSettings = $settingModel->getMultiple($aboutKeys);

// Set defaults if not exists
$formData = [
    'our_story' => $currentSettings['about_our_story'] ?? 'Karyalay was founded with a simple yet powerful vision: to make business management accessible, efficient, and effective for organizations of all sizes. We understand the challenges that businesses face in managing their operations, from customer relationships to subscriptions and support.

Our journey began when we recognized that many businesses were struggling with fragmented systems and complex workflows. We set out to create a comprehensive platform that brings everything together in one place, making it easier for businesses to focus on what they do best.

Over the years, we have grown from a small startup to a trusted partner for businesses across various industries. Our team of dedicated professionals works tirelessly to develop innovative solutions that address real-world challenges. We believe in continuous improvement and are committed to staying ahead of industry trends.

Today, Karyalay serves businesses across various industries, helping them streamline their operations, improve customer satisfaction, and drive sustainable growth. We are proud to be a trusted partner in their success and look forward to continuing this journey together.',
    'our_vision' => $currentSettings['about_our_vision'] ?? 'To be the leading business management platform that empowers organizations worldwide to achieve operational excellence and sustainable growth through innovative technology solutions.',
    'our_mission' => $currentSettings['about_our_mission'] ?? 'To provide businesses with intuitive, powerful, and reliable tools that simplify complex operations, enhance productivity, and enable them to deliver exceptional value to their customers.',
    'value_1_title' => $currentSettings['about_value_1_title'] ?? 'Excellence',
    'value_1_desc' => $currentSettings['about_value_1_desc'] ?? 'We strive for excellence in everything we do, from product development to customer support.',
    'value_1_image' => $currentSettings['about_value_1_image'] ?? '',
    'value_2_title' => $currentSettings['about_value_2_title'] ?? 'Customer Focus',
    'value_2_desc' => $currentSettings['about_value_2_desc'] ?? 'Our customers are at the heart of everything we do. Their success is our success.',
    'value_2_image' => $currentSettings['about_value_2_image'] ?? '',
    'value_3_title' => $currentSettings['about_value_3_title'] ?? 'Innovation',
    'value_3_desc' => $currentSettings['about_value_3_desc'] ?? 'We continuously innovate to stay ahead and provide cutting-edge solutions to our customers.',
    'value_3_image' => $currentSettings['about_value_3_image'] ?? ''
];

$page_title = 'About Page Content';
$csrf_token = getCsrfToken();

include __DIR__ . '/../templates/admin-header.php';
?>

<div class="admin-content">
    <div class="admin-page-header">
        <div class="admin-page-header-content">
            <h1 class="admin-page-title">About Page Content</h1>
            <p class="admin-page-description">Manage the content displayed on the About Us page</p>
        </div>
    </div>

    <?php if ($success || isset($_SESSION['admin_success'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['admin_success'] ?? 'Settings saved successfully!'); ?>
        </div>
        <?php unset($_SESSION['admin_success']); ?>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo get_base_url(); ?>/admin/about-page.php" class="admin-form-container">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

        <!-- Our Story Section -->
        <div class="admin-card mb-6">
            <div class="admin-card-header">
                <h2>Our Story</h2>
            </div>
            <div class="admin-card-body">
                <div class="form-group">
                    <label for="our_story" class="form-label">Story Content</label>
                    <textarea id="our_story" name="our_story" class="form-textarea" rows="10"
                              placeholder="Tell your company's story..."><?php echo htmlspecialchars($formData['our_story']); ?></textarea>
                    <p class="form-help">Use multiple paragraphs to tell your company's story. Each paragraph will be displayed separately.</p>
                </div>
            </div>
        </div>

        <!-- Vision & Mission Section -->
        <div class="admin-card mb-6">
            <div class="admin-card-header">
                <h2>Vision & Mission</h2>
            </div>
            <div class="admin-card-body">
                <div class="form-group">
                    <label for="our_vision" class="form-label">Our Vision</label>
                    <textarea id="our_vision" name="our_vision" class="form-textarea" rows="4"
                              placeholder="Describe your company's vision..."><?php echo htmlspecialchars($formData['our_vision']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="our_mission" class="form-label">Our Mission</label>
                    <textarea id="our_mission" name="our_mission" class="form-textarea" rows="4"
                              placeholder="Describe your company's mission..."><?php echo htmlspecialchars($formData['our_mission']); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Core Values Section -->
        <div class="admin-card mb-6">
            <div class="admin-card-header">
                <h2>Core Values</h2>
            </div>
            <div class="admin-card-body">
                <!-- Value 1 -->
                <div class="value-group">
                    <h3 class="value-group-title">Core Value 1</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="value_1_title" class="form-label">Title</label>
                            <input type="text" id="value_1_title" name="value_1_title" class="form-input"
                                   value="<?php echo htmlspecialchars($formData['value_1_title']); ?>"
                                   placeholder="e.g., Excellence">
                        </div>
                        <div class="form-group">
                            <label for="value_1_image" class="form-label">Image URL</label>
                            <input type="url" id="value_1_image" name="value_1_image" class="form-input"
                                   value="<?php echo htmlspecialchars($formData['value_1_image']); ?>"
                                   placeholder="https://example.com/image.jpg">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="value_1_desc" class="form-label">Description</label>
                        <textarea id="value_1_desc" name="value_1_desc" class="form-textarea" rows="3"
                                  placeholder="Describe this core value..."><?php echo htmlspecialchars($formData['value_1_desc']); ?></textarea>
                    </div>
                </div>

                <!-- Value 2 -->
                <div class="value-group">
                    <h3 class="value-group-title">Core Value 2</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="value_2_title" class="form-label">Title</label>
                            <input type="text" id="value_2_title" name="value_2_title" class="form-input"
                                   value="<?php echo htmlspecialchars($formData['value_2_title']); ?>"
                                   placeholder="e.g., Customer Focus">
                        </div>
                        <div class="form-group">
                            <label for="value_2_image" class="form-label">Image URL</label>
                            <input type="url" id="value_2_image" name="value_2_image" class="form-input"
                                   value="<?php echo htmlspecialchars($formData['value_2_image']); ?>"
                                   placeholder="https://example.com/image.jpg">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="value_2_desc" class="form-label">Description</label>
                        <textarea id="value_2_desc" name="value_2_desc" class="form-textarea" rows="3"
                                  placeholder="Describe this core value..."><?php echo htmlspecialchars($formData['value_2_desc']); ?></textarea>
                    </div>
                </div>

                <!-- Value 3 -->
                <div class="value-group">
                    <h3 class="value-group-title">Core Value 3</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="value_3_title" class="form-label">Title</label>
                            <input type="text" id="value_3_title" name="value_3_title" class="form-input"
                                   value="<?php echo htmlspecialchars($formData['value_3_title']); ?>"
                                   placeholder="e.g., Innovation">
                        </div>
                        <div class="form-group">
                            <label for="value_3_image" class="form-label">Image URL</label>
                            <input type="url" id="value_3_image" name="value_3_image" class="form-input"
                                   value="<?php echo htmlspecialchars($formData['value_3_image']); ?>"
                                   placeholder="https://example.com/image.jpg">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="value_3_desc" class="form-label">Description</label>
                        <textarea id="value_3_desc" name="value_3_desc" class="form-textarea" rows="3"
                                  placeholder="Describe this core value..."><?php echo htmlspecialchars($formData['value_3_desc']); ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="<?php echo get_base_url(); ?>/about.php" class="btn btn-outline" target="_blank">Preview Page</a>
        </div>
    </form>
</div>

<style>
.admin-form-container {
    max-width: 900px;
}

.admin-card {
    background: var(--color-white);
    border-radius: var(--radius-lg);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.admin-card-header {
    padding: var(--spacing-4) var(--spacing-6);
    background: var(--color-gray-50);
    border-bottom: 1px solid var(--color-gray-200);
}

.admin-card-header h2 {
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin: 0;
}

.admin-card-body {
    padding: var(--spacing-6);
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--spacing-4);
}

.form-group {
    margin-bottom: var(--spacing-4);
}

.form-label {
    display: block;
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-medium);
    color: var(--color-gray-700);
    margin-bottom: var(--spacing-2);
}

.form-input,
.form-textarea {
    width: 100%;
    padding: var(--spacing-3);
    border: 1px solid var(--color-gray-300);
    border-radius: var(--radius-md);
    font-size: var(--font-size-base);
    font-family: inherit;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-input:focus,
.form-textarea:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-textarea {
    resize: vertical;
}

.form-help {
    font-size: var(--font-size-xs);
    color: var(--color-gray-500);
    margin-top: var(--spacing-1);
}

.value-group {
    padding: var(--spacing-5);
    background: var(--color-gray-50);
    border-radius: var(--radius-lg);
    margin-bottom: var(--spacing-5);
}

.value-group:last-child {
    margin-bottom: 0;
}

.value-group-title {
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-800);
    margin: 0 0 var(--spacing-4) 0;
}

.form-actions {
    display: flex;
    gap: var(--spacing-3);
    padding-top: var(--spacing-4);
}

.alert {
    padding: var(--spacing-4);
    border-radius: var(--radius-lg);
    margin-bottom: var(--spacing-6);
}

.alert-success {
    background-color: #d1fae5;
    border: 1px solid #6ee7b7;
    color: #065f46;
}

.alert-error {
    background-color: #fee2e2;
    border: 1px solid #fca5a5;
    color: #991b1b;
}

.mb-6 {
    margin-bottom: var(--spacing-6);
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include __DIR__ . '/../templates/admin-footer.php'; ?>
