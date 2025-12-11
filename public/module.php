<?php

/**
 * SellerPortal System
 * Module Detail Page
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

// Database connection
use Karyalay\Models\Module;

// Get slug from query parameter
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: ' . get_base_url() . '/modules.php');
    exit;
}

try {
    $moduleModel = new Module();
    $module = $moduleModel->findBySlug($slug);
    
    if (!$module || $module['status'] !== 'PUBLISHED') {
        // Module not found or not published
        header('HTTP/1.0 404 Not Found');
        $page_title = 'Module Not Found';
        $page_description = 'The requested module could not be found';
        include_header($page_title, $page_description);
        ?>
        <section class="section">
            <div class="container">
                <div class="card">
                    <div class="card-body text-center">
                        <h1 class="text-3xl font-bold mb-4">Module Not Found</h1>
                        <p class="text-gray-600 mb-6">The module you're looking for doesn't exist or is no longer available.</p>
                        <a href="<?php echo get_base_url(); ?>/modules.php" class="btn btn-primary">View All Modules</a>
                    </div>
                </div>
            </div>
        </section>
        <?php
        include_footer();
        exit;
    }
    
} catch (Exception $e) {
    error_log('Error fetching module: ' . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    $page_title = 'Error';
    $page_description = 'An error occurred';
    include_header($page_title, $page_description);
    ?>
    <section class="section">
        <div class="container">
            <div class="card">
                <div class="card-body text-center">
                    <h1 class="text-3xl font-bold mb-4">Error</h1>
                    <p class="text-gray-600 mb-6">An error occurred while loading the module. Please try again later.</p>
                    <a href="<?php echo get_base_url(); ?>/modules.php" class="btn btn-primary">View All Modules</a>
                </div>
            </div>
        </div>
    </section>
    <?php
    include_footer();
    exit;
}

// Set page variables
$page_title = htmlspecialchars($module['name']);
$page_description = htmlspecialchars($module['description'] ?? '');

// Include header
include_header($page_title, $page_description);
?>

<!-- Module Header -->
<section class="section bg-gray-50">
    <div class="container">
        <div class="flex items-start gap-6">
            <?php if (!empty($module['icon'])): ?>
                <div class="flex-shrink-0">
                    <img src="<?php echo htmlspecialchars($module['icon']); ?>" 
                         alt="<?php echo htmlspecialchars($module['name']); ?>" 
                         class="w-24 h-24 object-contain">
                </div>
            <?php endif; ?>
            
            <div class="flex-1">
                <h1 class="text-4xl font-bold mb-4"><?php echo htmlspecialchars($module['name']); ?></h1>
                <p class="text-xl text-gray-600">
                    <?php echo htmlspecialchars($module['description'] ?? ''); ?>
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Key Features -->
<?php if (!empty($module['features']) && is_array($module['features'])): ?>
<section class="section">
    <div class="container">
        <h2 class="text-3xl font-bold mb-6">Key Features</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($module['features'] as $feature): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-primary flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <p class="text-gray-700"><?php echo htmlspecialchars($feature); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Screenshots -->
<?php if (!empty($module['screenshots']) && is_array($module['screenshots'])): ?>
<section class="section bg-gray-50">
    <div class="container">
        <h2 class="text-3xl font-bold mb-6">Screenshots</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($module['screenshots'] as $screenshot): ?>
                <div class="card">
                    <img src="<?php echo htmlspecialchars($screenshot); ?>" 
                         alt="Screenshot" 
                         class="w-full h-auto rounded-lg">
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- FAQs -->
<?php if (!empty($module['faqs']) && is_array($module['faqs'])): ?>
<section class="section">
    <div class="container">
        <h2 class="text-3xl font-bold mb-6">Frequently Asked Questions</h2>
        <div class="space-y-4">
            <?php foreach ($module['faqs'] as $faq): ?>
                <?php if (isset($faq['question']) && isset($faq['answer'])): ?>
                    <div class="card">
                        <div class="card-body">
                            <h3 class="text-xl font-semibold mb-3">
                                <?php echo htmlspecialchars($faq['question']); ?>
                            </h3>
                            <p class="text-gray-600">
                                <?php echo htmlspecialchars($faq['answer']); ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA Section -->
<section class="section bg-gray-50">
    <div class="container">
        <div class="card" style="max-width: 800px; margin: 0 auto; text-align: center;">
            <div class="card-body">
                <h2 class="text-3xl font-bold mb-4">Ready to Get Started?</h2>
                <p class="text-lg text-gray-600 mb-6">
                    Start using <?php echo htmlspecialchars($module['name']); ?> today
                </p>
                <div class="flex gap-4 justify-center flex-wrap">
                    <a href="<?php echo get_base_url(); ?>/pricing.php" class="btn btn-primary btn-lg">View Pricing</a>
                    <a href="<?php echo get_base_url(); ?>/modules.php" class="btn btn-outline btn-lg">View All Modules</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include_footer();
?>
