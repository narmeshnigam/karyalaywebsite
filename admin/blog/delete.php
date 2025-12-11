<?php
/**
 * Admin Delete Blog Post Handler
 * Handles deletion of blog posts
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../classes/Database/Connection.php';
require_once __DIR__ . '/../../classes/Services/ContentService.php';
require_once __DIR__ . '/../../classes/Middleware/CsrfMiddleware.php';

use Karyalay\Services\ContentService;
use Karyalay\Middleware\CsrfMiddleware;

// Start session
session_start();

// Require admin authentication and blog.manage permission
require_admin();
require_permission('blog.manage');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_message'] = 'Invalid request method.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /admin/blog.php');
    exit;
}

// Initialize services
$contentService = new ContentService();
$csrfMiddleware = new CsrfMiddleware();

// Validate CSRF token
if (!$csrfMiddleware->validate()) {
    $_SESSION['flash_message'] = 'Invalid security token. Please try again.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /admin/blog.php');
    exit;
}

// Get blog post ID
$post_id = $_POST['id'] ?? '';

if (empty($post_id)) {
    $_SESSION['flash_message'] = 'Blog post ID is required.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /admin/blog.php');
    exit;
}

// Attempt to delete the blog post
try {
    $result = $contentService->delete('blog_post', $post_id);
    
    if ($result) {
        $_SESSION['flash_message'] = 'Blog post deleted successfully.';
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash_message'] = 'Failed to delete blog post. It may not exist.';
        $_SESSION['flash_type'] = 'danger';
    }
} catch (Exception $e) {
    error_log("Blog post deletion error: " . $e->getMessage());
    $_SESSION['flash_message'] = 'An error occurred while deleting the blog post.';
    $_SESSION['flash_type'] = 'danger';
}

// Redirect back to blog posts list
header('Location: /admin/blog.php');
exit;
