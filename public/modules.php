<?php

/**
 * SellerPortal System
 * Modules Overview Page
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
use Karyalay\Database\Connection;
use Karyalay\Models\Module;

try {
    $moduleModel = new Module();
    
    // Fetch all active modules
    $modules = $moduleModel->findAll(['status' => 'PUBLISHED']);
    
} catch (Exception $e) {
    error_log('Error fetching modules: ' . $e->getMessage());
    $modules = [];
}

// Set page variables
$page_title = 'Modules';
$page_description = 'Explore our comprehensive suite of business management modules';

// Include header
include_header($page_title, $page_description);
?>

<!-- Page Header -->
<section class="section bg-gray-50">
    <div class="container">
        <h1 class="text-4xl font-bold mb-4">Our Modules</h1>
        <p class="text-xl text-gray-600">
            Discover powerful modules designed to streamline your business operations
        </p>
    </div>
</section>

<!-- Modules Grid -->
<section class="section">
    <div class="container">
        <?php if (empty($modules)): ?>
            <div class="card">
                <div class="card-body text-center">
                    <p class="text-gray-600">No modules available at the moment. Please check back later.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($modules as $module): ?>
                    <div class="card">
                        <div class="card-body">
                            <?php if (!empty($module['icon'])): ?>
                                <div class="mb-4">
                                    <img src="<?php echo htmlspecialchars($module['icon']); ?>" 
                                         alt="<?php echo htmlspecialchars($module['name']); ?>" 
                                         class="w-16 h-16 object-contain">
                                </div>
                            <?php endif; ?>
                            
                            <h3 class="text-xl font-semibold mb-3">
                                <?php echo htmlspecialchars($module['name']); ?>
                            </h3>
                            
                            <p class="text-gray-600 mb-4">
                                <?php echo htmlspecialchars($module['description']); ?>
                            </p>
                            
                            <a href="<?php echo get_base_url(); ?>/module.php?slug=<?php echo urlencode($module['slug']); ?>" 
                               class="btn btn-outline btn-sm">
                                Learn More
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- CTA Section -->
<section class="section bg-gray-50">
    <div class="container">
        <div class="card" style="max-width: 800px; margin: 0 auto; text-align: center;">
            <div class="card-body">
                <h2 class="text-3xl font-bold mb-4">Ready to Get Started?</h2>
                <p class="text-lg text-gray-600 mb-6">
                    Choose the modules that fit your business needs
                </p>
                <div class="flex gap-4 justify-center flex-wrap">
                    <a href="<?php echo get_base_url(); ?>/pricing.php" class="btn btn-primary btn-lg">View Pricing</a>
                    <a href="<?php echo get_base_url(); ?>/contact.php" class="btn btn-outline btn-lg">Contact Us</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include_footer();
?>
