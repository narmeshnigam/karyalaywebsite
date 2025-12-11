<?php
/**
 * Admin Edit Blog Post Page
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Services\ContentService;

startSecureSession();
require_admin();
require_permission('blog.manage');

$contentService = new ContentService();

$post_id = $_GET['id'] ?? '';

if (empty($post_id)) {
    $_SESSION['admin_error'] = 'Blog post ID is required.';
    header('Location: ' . get_app_base_url() . '/admin/blog.php');
    exit;
}

$post = $contentService->read('blog_post', $post_id);

if (!$post) {
    $_SESSION['admin_error'] = 'Blog post not found.';
    header('Location: ' . get_app_base_url() . '/admin/blog.php');
    exit;
}

// Ensure tags is an array
$tags = $post['tags'] ?? [];
if (is_string($tags)) {
    $tags = json_decode($tags, true) ?? [];
}

$errors = [];
$form_data = [
    'title' => $post['title'],
    'slug' => $post['slug'],
    'content' => $post['content'] ?? '',
    'excerpt' => $post['excerpt'] ?? '',
    'featured_image' => $post['featured_image'] ?? '',
    'tags' => $tags,
    'status' => $post['status'] ?? 'DRAFT',
    'is_featured' => $post['is_featured'] ?? false,
    'published_at' => $post['published_at'] ?? null
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $previous_status = $form_data['status'];
        
        $form_data['title'] = sanitizeString($_POST['title'] ?? '');
        $form_data['slug'] = sanitizeString($_POST['slug'] ?? '');
        $form_data['content'] = $_POST['content'] ?? ''; // Allow HTML content
        $form_data['excerpt'] = sanitizeString($_POST['excerpt'] ?? '');
        $form_data['featured_image'] = sanitizeString($_POST['featured_image'] ?? '');
        $form_data['status'] = sanitizeString($_POST['status'] ?? 'DRAFT');
        $form_data['is_featured'] = isset($_POST['is_featured']) ? true : false;
        $form_data['author_id'] = $post['author_id'];
        
        if (empty($form_data['title'])) {
            $errors[] = 'Post title is required.';
        }
        
        if (empty($form_data['slug'])) {
            $errors[] = 'Post slug is required.';
        }
        
        if (empty($form_data['content'])) {
            $errors[] = 'Post content is required.';
        }
        
        if (!in_array($form_data['status'], ['DRAFT', 'PUBLISHED', 'ARCHIVED'])) {
            $errors[] = 'Invalid status value.';
        }
        
        if (!empty($_POST['tags'])) {
            $tags_raw = explode(',', $_POST['tags']);
            $form_data['tags'] = array_filter(array_map(function($tag) {
                return trim(sanitizeString($tag));
            }, $tags_raw));
        } else {
            $form_data['tags'] = [];
        }
        
        if ($form_data['status'] === 'PUBLISHED' && $previous_status !== 'PUBLISHED' && empty($form_data['published_at'])) {
            $form_data['published_at'] = date('Y-m-d H:i:s');
        }
        
        if (empty($errors)) {
            $result = $contentService->update('blog_post', $post_id, $form_data);
            
            if ($result) {
                $_SESSION['admin_success'] = 'Blog post updated successfully!';
                header('Location: ' . get_app_base_url() . '/admin/blog.php');
                exit;
            } else {
                $errors[] = 'Failed to update blog post. Please check if the slug is unique.';
            }
        }
    }
}

$csrf_token = getCsrfToken();
include_admin_header('Edit Blog Post');
?>

<!-- Quill CSS -->
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <nav class="admin-breadcrumb">
            <a href="<?php echo get_app_base_url(); ?>/admin/blog.php">Blog Posts</a>
            <span class="breadcrumb-separator">/</span>
            <span>Edit Post</span>
        </nav>
        <h1 class="admin-page-title">Edit Blog Post</h1>
        <p class="admin-page-description">Update blog post content and settings</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/blog.php" class="btn btn-secondary">← Back to Blog Posts</a>
        <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete Post</button>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <strong>Error:</strong>
        <ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="POST" action="<?php echo get_app_base_url(); ?>/admin/blog/edit.php?id=<?php echo urlencode($post_id); ?>" class="blog-form" id="blogForm">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
    <input type="hidden" name="content" id="contentInput">
    
    <div class="blog-form-layout">
        <div class="blog-form-main">
            <div class="admin-card">
                <div class="form-section">
                    <div class="form-group">
                        <label for="title" class="form-label required">Post Title</label>
                        <input type="text" id="title" name="title" class="form-input form-input-lg" 
                            value="<?php echo htmlspecialchars($form_data['title']); ?>"
                            required maxlength="255" placeholder="Enter your blog post title...">
                    </div>
                    
                    <div class="form-group">
                        <label for="slug" class="form-label required">Slug</label>
                        <div class="slug-preview">
                            <span class="slug-prefix"><?php echo get_app_base_url(); ?>/blog/</span>
                            <input type="text" id="slug" name="slug" class="form-input" 
                                value="<?php echo htmlspecialchars($form_data['slug']); ?>"
                                required pattern="[a-z0-9\-]+" maxlength="255" placeholder="post-url-slug">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="form-section">
                    <h2 class="form-section-title">Content</h2>
                    <div class="form-group">
                        <label class="form-label required">Post Content</label>
                        <div id="editor-container">
                            <div id="editor"><?php echo $form_data['content']; ?></div>
                        </div>
                        <p class="form-help">Use the toolbar to format text, add links, images, and more</p>
                    </div>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="form-section">
                    <h2 class="form-section-title">Excerpt</h2>
                    <div class="form-group">
                        <label for="excerpt" class="form-label">Summary</label>
                        <textarea id="excerpt" name="excerpt" class="form-textarea" rows="3"
                            placeholder="Brief summary of the post (displayed in listings)..."><?php echo htmlspecialchars($form_data['excerpt']); ?></textarea>
                        <p class="form-help">Short description shown in blog listings</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="blog-form-sidebar">
            <div class="admin-card">
                <div class="form-section">
                    <h2 class="form-section-title">Publish</h2>
                    <div class="form-group">
                        <label for="status" class="form-label required">Status</label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="DRAFT" <?php echo $form_data['status'] === 'DRAFT' ? 'selected' : ''; ?>>Draft</option>
                            <option value="PUBLISHED" <?php echo $form_data['status'] === 'PUBLISHED' ? 'selected' : ''; ?>>Published</option>
                            <option value="ARCHIVED" <?php echo $form_data['status'] === 'ARCHIVED' ? 'selected' : ''; ?>>Archived</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-checkbox-group">
                            <label class="form-checkbox-label">
                                <input type="checkbox" id="is_featured" name="is_featured" 
                                       class="form-checkbox" value="1"
                                       <?php echo !empty($form_data['is_featured']) ? 'checked' : ''; ?>>
                                <span class="form-checkbox-text">
                                    <strong>Feature on Homepage</strong>
                                    <span class="form-checkbox-help">Display this post in the featured blog section</span>
                                </span>
                            </label>
                        </div>
                    </div>
                    
                    <?php if ($form_data['published_at']): ?>
                    <div class="form-group">
                        <label class="form-label">Published</label>
                        <p class="form-static-text"><?php echo date('M j, Y \a\t g:i A', strtotime($form_data['published_at'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-actions-sidebar">
                        <button type="submit" class="btn btn-primary btn-block">Update Post</button>
                        <a href="<?php echo get_app_base_url(); ?>/admin/blog.php" class="btn btn-secondary btn-block">Cancel</a>
                    </div>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="form-section">
                    <h2 class="form-section-title">Featured Image</h2>
                    <div class="form-group">
                        <label for="featured_image" class="form-label">Image URL</label>
                        <input type="url" id="featured_image" name="featured_image" class="form-input" 
                            value="<?php echo htmlspecialchars($form_data['featured_image']); ?>"
                            placeholder="https://example.com/image.jpg">
                        <div id="image-preview" class="image-preview">
                            <?php if (!empty($form_data['featured_image'])): ?>
                                <img src="<?php echo htmlspecialchars($form_data['featured_image']); ?>" alt="Preview">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="form-section">
                    <h2 class="form-section-title">Tags</h2>
                    <div class="form-group">
                        <label for="tags" class="form-label">Post Tags</label>
                        <input type="text" id="tags" name="tags" class="form-input" 
                            value="<?php echo htmlspecialchars(is_array($form_data['tags']) ? implode(', ', $form_data['tags']) : ''); ?>"
                            placeholder="technology, tutorial, news">
                        <p class="form-help">Separate tags with commas</p>
                    </div>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="form-section">
                    <h2 class="form-section-title">Danger Zone</h2>
                    <button type="button" class="btn btn-danger btn-block" onclick="confirmDelete()">Delete This Post</button>
                </div>
            </div>
        </div>
    </div>
</form>

<form id="deleteForm" method="POST" action="<?php echo get_app_base_url(); ?>/admin/blog/delete.php" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($post_id); ?>">
</form>

<style>
.admin-breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 14px; margin-bottom: 8px; }
.admin-breadcrumb a { color: var(--color-primary); text-decoration: none; }
.admin-breadcrumb a:hover { text-decoration: underline; }
.breadcrumb-separator { color: var(--color-gray-400); }
.admin-page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; gap: 16px; }
.admin-page-header-content { flex: 1; }
.admin-page-title { font-size: 24px; font-weight: 700; color: var(--color-gray-900); margin: 0 0 8px 0; }
.admin-page-description { font-size: 14px; color: var(--color-gray-600); margin: 0; }
.admin-page-header-actions { display: flex; gap: 12px; }
.alert { padding: 16px; border-radius: 8px; margin-bottom: 24px; }
.alert-error { background-color: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }
.alert ul { margin: 8px 0 0 16px; padding: 0; }

.blog-form-layout { display: grid; grid-template-columns: 1fr 320px; gap: 24px; align-items: start; }
.blog-form-main { display: flex; flex-direction: column; gap: 24px; }
.blog-form-sidebar { display: flex; flex-direction: column; gap: 24px; position: sticky; top: 24px; }

.form-section { padding: 20px; }
.form-section-title { font-size: 16px; font-weight: 600; color: var(--color-gray-900); margin: 0 0 16px 0; padding-bottom: 12px; border-bottom: 1px solid var(--color-gray-200); }
.form-group { margin-bottom: 16px; }
.form-group:last-child { margin-bottom: 0; }
.form-label { display: block; font-size: 14px; font-weight: 600; color: var(--color-gray-700); margin-bottom: 8px; }
.form-label.required::after { content: ' *'; color: #dc2626; }
.form-input, .form-select, .form-textarea { width: 100%; padding: 10px 12px; border: 1px solid var(--color-gray-300); border-radius: 6px; font-size: 14px; color: var(--color-gray-900); font-family: inherit; box-sizing: border-box; }
.form-input-lg { font-size: 18px; padding: 12px 14px; }
.form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.form-textarea { resize: vertical; }
.form-help { font-size: 12px; color: var(--color-gray-500); margin: 4px 0 0 0; }
.form-static-text { font-size: 14px; color: var(--color-gray-700); margin: 0; }

.slug-preview { display: flex; align-items: center; gap: 0; }
.slug-prefix { padding: 10px 12px; background: var(--color-gray-100); border: 1px solid var(--color-gray-300); border-right: none; border-radius: 6px 0 0 6px; font-size: 13px; color: var(--color-gray-500); white-space: nowrap; }
.slug-preview .form-input { border-radius: 0 6px 6px 0; }

#editor-container { border: 1px solid var(--color-gray-300); border-radius: 6px; overflow: hidden; }
#editor { min-height: 400px; font-size: 16px; line-height: 1.7; }
.ql-toolbar { border: none !important; border-bottom: 1px solid var(--color-gray-300) !important; background: var(--color-gray-50); }
.ql-container { border: none !important; font-family: inherit; }
.ql-editor { padding: 20px; }
.ql-editor p, .ql-editor h1, .ql-editor h2, .ql-editor h3 { margin-bottom: 1em; }
.ql-editor img { max-width: 100%; height: auto; border-radius: 8px; }

.form-actions-sidebar { display: flex; flex-direction: column; gap: 8px; margin-top: 16px; }
.btn-block { width: 100%; text-align: center; }

.image-preview { margin-top: 12px; }
.image-preview img { max-width: 100%; height: auto; border-radius: 6px; border: 1px solid var(--color-gray-200); }

@media (max-width: 1024px) {
    .blog-form-layout { grid-template-columns: 1fr; }
    .blog-form-sidebar { position: static; }
}
@media (max-width: 768px) {
    .admin-page-header { flex-direction: column; }
    .slug-preview { flex-direction: column; }
    .slug-prefix { border-radius: 6px 6px 0 0; border-right: 1px solid var(--color-gray-300); border-bottom: none; width: 100%; }
    .slug-preview .form-input { border-radius: 0 0 6px 6px; }
}
</style>

<!-- Quill JS -->
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Quill editor
    var quill = new Quill('#editor', {
        theme: 'snow',
        placeholder: 'Write your blog post content here...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'indent': '-1'}, { 'indent': '+1' }],
                ['blockquote', 'code-block'],
                ['link', 'image', 'video'],
                [{ 'align': [] }],
                ['clean']
            ]
        }
    });
    
    // Sync editor content to hidden input on form submit
    document.getElementById('blogForm').addEventListener('submit', function() {
        document.getElementById('contentInput').value = quill.root.innerHTML;
    });
    
    // Featured image preview
    document.getElementById('featured_image').addEventListener('input', function() {
        var preview = document.getElementById('image-preview');
        if (this.value) {
            preview.innerHTML = '<img src="' + this.value + '" alt="Preview" onerror="this.style.display=\'none\'">';
        } else {
            preview.innerHTML = '';
        }
    });
});

function confirmDelete() {
    if (confirm('Are you sure you want to delete this blog post? This action cannot be undone.')) {
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include_admin_footer(); ?>
