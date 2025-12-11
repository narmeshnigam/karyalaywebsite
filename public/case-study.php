<?php

/**
 * SellerPortal System
 * Case Study Detail Page
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
use Karyalay\Models\CaseStudy;

// Get slug from query parameter
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: ' . get_base_url() . '/case-studies.php');
    exit;
}

try {
    $caseStudyModel = new CaseStudy();
    $caseStudy = $caseStudyModel->findBySlug($slug);
    
    if (!$caseStudy || $caseStudy['status'] !== 'PUBLISHED') {
        // Case study not found or not published
        header('HTTP/1.0 404 Not Found');
        $page_title = 'Case Study Not Found';
        $page_description = 'The requested case study could not be found';
        include_header($page_title, $page_description);
        ?>
        <section class="section">
            <div class="container">
                <div class="card">
                    <div class="card-body text-center">
                        <h1 class="text-3xl font-bold mb-4">Case Study Not Found</h1>
                        <p class="text-gray-600 mb-6">The case study you're looking for doesn't exist or is no longer available.</p>
                        <a href="<?php echo get_base_url(); ?>/case-studies.php" class="btn btn-primary">View All Case Studies</a>
                    </div>
                </div>
            </div>
        </section>
        <?php
        include_footer();
        exit;
    }
    
} catch (Exception $e) {
    error_log('Error fetching case study: ' . $e->getMessage());
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
                    <p class="text-gray-600 mb-6">An error occurred while loading the case study. Please try again later.</p>
                    <a href="<?php echo get_base_url(); ?>/case-studies.php" class="btn btn-primary">View All Case Studies</a>
                </div>
            </div>
        </div>
    </section>
    <?php
    include_footer();
    exit;
}

// Set page variables
$page_title = htmlspecialchars($caseStudy['title']);
$page_description = htmlspecialchars(substr($caseStudy['challenge'], 0, 160));

// Include header
include_header($page_title, $page_description);
?>

<!-- Case Study Header -->
<section class="section bg-gray-50">
    <div class="container">
        <div class="mb-4">
            <span class="inline-block px-3 py-1 text-sm font-semibold text-primary bg-primary-light rounded-full">
                <?php echo htmlspecialchars($caseStudy['industry']); ?>
            </span>
        </div>
        <h1 class="text-4xl font-bold mb-4"><?php echo htmlspecialchars($caseStudy['title']); ?></h1>
        <p class="text-xl text-gray-600">
            <strong>Client:</strong> <?php echo htmlspecialchars($caseStudy['client_name']); ?>
        </p>
    </div>
</section>

<!-- Challenge -->
<section class="section">
    <div class="container">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-3xl font-bold mb-4">The Challenge</h2>
            <div class="prose prose-lg">
                <p class="text-gray-700">
                    <?php echo nl2br(htmlspecialchars($caseStudy['challenge'])); ?>
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Solution -->
<section class="section bg-gray-50">
    <div class="container">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-3xl font-bold mb-4">The Solution</h2>
            <div class="prose prose-lg">
                <p class="text-gray-700">
                    <?php echo nl2br(htmlspecialchars($caseStudy['solution'])); ?>
                </p>
            </div>
            
            <?php if (!empty($caseStudy['modules_used']) && is_array($caseStudy['modules_used'])): ?>
                <div class="mt-6">
                    <h3 class="text-xl font-semibold mb-3">Modules Used:</h3>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($caseStudy['modules_used'] as $module): ?>
                            <span class="inline-block px-3 py-1 text-sm bg-gray-200 text-gray-700 rounded-full">
                                <?php echo htmlspecialchars($module); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Results -->
<section class="section">
    <div class="container">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-3xl font-bold mb-4">The Results</h2>
            <div class="prose prose-lg">
                <p class="text-gray-700">
                    <?php echo nl2br(htmlspecialchars($caseStudy['results'])); ?>
                </p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="section bg-gray-50">
    <div class="container">
        <div class="card" style="max-width: 800px; margin: 0 auto; text-align: center;">
            <div class="card-body">
                <h2 class="text-3xl font-bold mb-4">Ready to Achieve Similar Results?</h2>
                <p class="text-lg text-gray-600 mb-6">
                    Let Karyalay help transform your business operations
                </p>
                <div class="flex gap-4 justify-center flex-wrap">
                    <a href="<?php echo get_base_url(); ?>/pricing.php" class="btn btn-primary btn-lg">Get Started</a>
                    <a href="<?php echo get_base_url(); ?>/case-studies.php" class="btn btn-outline btn-lg">More Case Studies</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include_footer();
?>
