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
require_once __DIR__ . '/../includes/admin_helpers.php';
startSecureSession();

// Require admin authentication and about.manage permission
require_admin();
require_permission('about.manage');

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

    <!-- Quick Preview Link -->
    <div class="legal-preview-links">
        <a href="<?php echo get_base_url(); ?>/about.php" class="legal-preview-link" target="_blank">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
            </svg>
            Preview About Page
        </a>
    </div>

    <div class="admin-card">
        <form method="POST" action="<?php echo get_app_base_url(); ?>/admin/about-page.php" class="admin-form" id="aboutForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" id="storyInput" name="our_story">
            <input type="hidden" id="visionInput" name="our_vision">
            <input type="hidden" id="missionInput" name="our_mission">
            <input type="hidden" id="value1DescInput" name="value_1_desc">
            <input type="hidden" id="value2DescInput" name="value_2_desc">
            <input type="hidden" id="value3DescInput" name="value_3_desc">

            <!-- Our Story Section -->
            <div class="form-section">
                <h3 class="form-section-title">Our Story</h3>
                <p class="form-section-description">Tell your company's story. Use the rich text editor for formatting with headings, lists, and emphasis.</p>
                
                <div class="form-group">
                    <label class="form-label">Story Content</label>
                    <div id="editor-story-container">
                        <div id="editor-story"></div>
                    </div>
                </div>
            </div>

            <!-- Vision & Mission Section -->
            <div class="form-section">
                <h3 class="form-section-title">Vision & Mission</h3>
                <p class="form-section-description">Define your company's vision and mission statements. Use the rich text editor for formatting.</p>
                
                <div class="form-group">
                    <label class="form-label">Our Vision</label>
                    <div id="editor-vision-container">
                        <div id="editor-vision"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Our Mission</label>
                    <div id="editor-mission-container">
                        <div id="editor-mission"></div>
                    </div>
                </div>
            </div>

            <!-- Core Values Section -->
            <div class="form-section">
                <h3 class="form-section-title">Core Values</h3>
                <p class="form-section-description">Define your company's core values with titles, descriptions, and optional images.</p>
                
                <!-- Value 1 -->
                <div class="value-group">
                    <h4 class="value-group-title">Core Value 1</h4>
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
                        <label class="form-label">Description</label>
                        <div id="editor-value1-container">
                            <div id="editor-value1"></div>
                        </div>
                    </div>
                </div>

                <!-- Value 2 -->
                <div class="value-group">
                    <h4 class="value-group-title">Core Value 2</h4>
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
                        <label class="form-label">Description</label>
                        <div id="editor-value2-container">
                            <div id="editor-value2"></div>
                        </div>
                    </div>
                </div>

                <!-- Value 3 -->
                <div class="value-group">
                    <h4 class="value-group-title">Core Value 3</h4>
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
                        <label class="form-label">Description</label>
                        <div id="editor-value3-container">
                            <div id="editor-value3"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="<?php echo get_app_base_url(); ?>/admin/dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">

<style>
/* Preview Links */
.legal-preview-links {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.legal-preview-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    color: #4b5563;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
}

.legal-preview-link:hover {
    background: #3b82f6;
    border-color: #3b82f6;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
}

.legal-preview-link svg {
    flex-shrink: 0;
}

/* Admin Card */
.admin-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

/* Form Sections */
.form-section {
    padding: 24px;
    margin-bottom: 24px;
}

.form-section:last-of-type {
    margin-bottom: 0;
}

.form-section-title {
    font-size: 18px;
    font-weight: 600;
    color: #1a202c;
    margin: 0 0 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-section-description {
    font-size: 14px;
    color: #6b7280;
    margin: 0 0 20px;
    line-height: 1.5;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-bottom: 16px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 8px;
}

.form-input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    font-family: inherit;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Editor Containers */
#editor-story-container,
#editor-vision-container,
#editor-mission-container,
#editor-value1-container,
#editor-value2-container,
#editor-value3-container {
    border: 1px solid #d1d5db;
    border-radius: 8px;
    overflow: hidden;
    background: white;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

#editor-story {
    min-height: 400px;
    font-size: 15px;
    line-height: 1.7;
}

#editor-vision,
#editor-mission {
    min-height: 200px;
    font-size: 15px;
    line-height: 1.7;
}

#editor-value1,
#editor-value2,
#editor-value3 {
    min-height: 150px;
    font-size: 15px;
    line-height: 1.7;
}

/* Quill Editor Styling */
.ql-toolbar {
    border: none !important;
    border-bottom: 1px solid #e5e7eb !important;
    background: #f9fafb;
    padding: 12px !important;
}

.ql-container {
    border: none !important;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

.ql-editor {
    padding: 20px;
}

.ql-editor p,
.ql-editor h1,
.ql-editor h2,
.ql-editor h3 {
    margin-bottom: 1em;
}

.ql-editor h2 {
    font-size: 1.5em;
    font-weight: 600;
    color: #1a202c;
}

.ql-editor h3 {
    font-size: 1.25em;
    font-weight: 600;
    color: #374151;
}

.ql-editor ul,
.ql-editor ol {
    margin-bottom: 1em;
    padding-left: 1.5em;
}

.ql-editor li {
    margin-bottom: 0.5em;
}

/* Value Groups */
.value-group {
    padding: 20px;
    background: #f9fafb;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 1px solid #e5e7eb;
}

.value-group:last-child {
    margin-bottom: 0;
}

.value-group-title {
    font-size: 16px;
    font-weight: 600;
    color: #374151;
    margin: 0 0 16px 0;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 12px;
    padding: 20px 24px;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
    border-radius: 0 0 8px 8px;
}

.btn {
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    border: 1px solid transparent;
    cursor: pointer;
}

.btn-primary {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.btn-primary:hover {
    background: #2563eb;
    border-color: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);
}

.btn-secondary {
    background: white;
    color: #6b7280;
    border-color: #d1d5db;
}

.btn-secondary:hover {
    background: #f9fafb;
    color: #374151;
}

/* Alert Messages */
.alert {
    padding: 16px 20px;
    border-radius: 8px;
    margin-bottom: 24px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    font-size: 14px;
}

.alert-success {
    background: #d1fae5;
    border: 1px solid #6ee7b7;
    color: #065f46;
}

.alert-error {
    background: #fee2e2;
    border: 1px solid #fca5a5;
    color: #991b1b;
}

/* Responsive */
@media (max-width: 768px) {
    .legal-preview-links {
        flex-direction: column;
    }
    
    .legal-preview-link {
        width: 100%;
        justify-content: center;
    }
    
    .form-section {
        padding: 16px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
        padding: 16px;
    }
    
    .btn {
        width: 100%;
    }
    
    #editor-story {
        min-height: 300px;
    }
    
    #editor-vision,
    #editor-mission {
        min-height: 150px;
    }
    
    #editor-value1,
    #editor-value2,
    #editor-value3 {
        min-height: 120px;
    }
}
</style>

<!-- Quill JS -->
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toolbar configuration
    var toolbarOptions = [
        [{ 'header': [1, 2, 3, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'color': [] }, { 'background': [] }],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        [{ 'indent': '-1'}, { 'indent': '+1' }],
        ['blockquote', 'code-block'],
        ['link'],
        [{ 'align': [] }],
        ['clean']
    ];
    
    // Initialize Story editor
    var quillStory = new Quill('#editor-story', {
        theme: 'snow',
        placeholder: 'Tell your company\'s story...',
        modules: {
            toolbar: toolbarOptions
        }
    });
    
    // Initialize Vision editor
    var quillVision = new Quill('#editor-vision', {
        theme: 'snow',
        placeholder: 'Describe your company\'s vision...',
        modules: {
            toolbar: toolbarOptions
        }
    });
    
    // Initialize Mission editor
    var quillMission = new Quill('#editor-mission', {
        theme: 'snow',
        placeholder: 'Describe your company\'s mission...',
        modules: {
            toolbar: toolbarOptions
        }
    });
    
    // Initialize Value editors
    var quillValue1 = new Quill('#editor-value1', {
        theme: 'snow',
        placeholder: 'Describe this core value...',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link'],
                ['clean']
            ]
        }
    });
    
    var quillValue2 = new Quill('#editor-value2', {
        theme: 'snow',
        placeholder: 'Describe this core value...',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link'],
                ['clean']
            ]
        }
    });
    
    var quillValue3 = new Quill('#editor-value3', {
        theme: 'snow',
        placeholder: 'Describe this core value...',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link'],
                ['clean']
            ]
        }
    });
    
    // Load existing content
    <?php if (!empty($formData['our_story'])): ?>
    quillStory.root.innerHTML = <?php echo json_encode($formData['our_story']); ?>;
    <?php endif; ?>
    
    <?php if (!empty($formData['our_vision'])): ?>
    quillVision.root.innerHTML = <?php echo json_encode($formData['our_vision']); ?>;
    <?php endif; ?>
    
    <?php if (!empty($formData['our_mission'])): ?>
    quillMission.root.innerHTML = <?php echo json_encode($formData['our_mission']); ?>;
    <?php endif; ?>
    
    <?php if (!empty($formData['value_1_desc'])): ?>
    quillValue1.root.innerHTML = <?php echo json_encode($formData['value_1_desc']); ?>;
    <?php endif; ?>
    
    <?php if (!empty($formData['value_2_desc'])): ?>
    quillValue2.root.innerHTML = <?php echo json_encode($formData['value_2_desc']); ?>;
    <?php endif; ?>
    
    <?php if (!empty($formData['value_3_desc'])): ?>
    quillValue3.root.innerHTML = <?php echo json_encode($formData['value_3_desc']); ?>;
    <?php endif; ?>
    
    // Sync editor content to hidden inputs on form submit
    document.getElementById('aboutForm').addEventListener('submit', function() {
        document.getElementById('storyInput').value = quillStory.root.innerHTML;
        document.getElementById('visionInput').value = quillVision.root.innerHTML;
        document.getElementById('missionInput').value = quillMission.root.innerHTML;
        document.getElementById('value1DescInput').value = quillValue1.root.innerHTML;
        document.getElementById('value2DescInput').value = quillValue2.root.innerHTML;
        document.getElementById('value3DescInput').value = quillValue3.root.innerHTML;
    });
});
</script>

<?php include __DIR__ . '/../templates/admin-footer.php'; ?>
